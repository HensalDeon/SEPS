<?php
/**
 * `wp seps seed` — loads the approved design content into a fresh install.
 *
 * @package SEPS\Core
 */

namespace SEPS\Core\CLI;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Seeds pages (sitemap), menus, Site Settings, home page sections, news posts,
 * testimonials and FAQs using the copy and imagery from the Figma design.
 *
 * Idempotent: every seeded item is tagged with a seed key and updated in place
 * on subsequent runs, so the command is safe to repeat.
 */
final class Seed_Command {

	private const SEED_META = '_seps_seed_key';

	/**
	 * Attachment IDs keyed by seed key ("image:file.jpg", "document:file.pdf").
	 *
	 * @var array<string,int>
	 */
	private array $attachments = array();

	/**
	 * Page IDs keyed by seed key.
	 *
	 * @var array<string,int>
	 */
	private array $pages = array();

	/**
	 * Team term IDs keyed by slug.
	 *
	 * @var array<string,int>
	 */
	private array $teams = array();

	/**
	 * Steps in run order: key => label.
	 */
	private const STEPS = array(
		'identity'     => 'Site identity',
		'pages'        => 'Pages',
		'reading'      => 'Reading & permalink settings',
		'menus'        => 'Menus',
		'settings'     => 'Site Settings',
		'testimonials' => 'Testimonials',
		'faqs'         => 'FAQs',
		'posts'        => 'News posts',
		'home'         => 'Home page sections',
		'staff'        => 'Staff',
		'about'        => 'About page',
		'academics'    => 'Academics page',
		'admissions'   => 'Admissions page',
		'student-life' => 'Student Life page',
		'news-events'  => 'News & Events page',
		'contact'      => 'Contact page',
		'careers'      => 'Careers page',
		'resources'    => 'Resources page',
		'registration' => 'Register Now page (registration form)',
		'careers-application' => 'Careers Application page (application form)',
		'legal'        => 'Privacy Policy and Terms & Conditions pages',
	);

	/**
	 * Seed demo content for local development and design review.
	 *
	 * Seeded items are updated in place, so re-running a step overwrites any
	 * edits made to those items. Use --only to run just the steps you need.
	 *
	 * ## OPTIONS
	 *
	 * [--only=<steps>]
	 * : Comma-separated steps to run (default: all). Available: identity,
	 * pages, reading, menus, settings, testimonials, faqs, posts, home, staff,
	 * about, academics, admissions, student-life,
	 * news-events (run after posts), contact, careers, resources, registration,
	 * careers-application, legal.
	 *
	 * ## EXAMPLES
	 *
	 *     # Fresh site: everything.
	 *     wp seps seed
	 *
	 *     # Existing site: add the About page content only.
	 *     wp seps seed --only=staff,about
	 *
	 * @param array<int,string>    $args       Positional arguments (unused).
	 * @param array<string,string> $assoc_args Associative arguments.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		if ( ! function_exists( 'update_field' ) ) {
			WP_CLI::error( 'Secure Custom Fields must be installed and active: wp plugin install secure-custom-fields --activate' );
		}

		if ( 'seps' !== get_template() ) {
			WP_CLI::warning( 'The SEPS theme is not active. Activate it first (wp theme activate seps) so the home page fields exist.' );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$only = isset( $assoc_args['only'] ) ? array_filter( array_map( 'trim', explode( ',', $assoc_args['only'] ) ) ) : array_keys( self::STEPS );

		$unknown = array_diff( $only, array_keys( self::STEPS ) );
		if ( $unknown ) {
			WP_CLI::error( sprintf( 'Unknown step(s): %s. Available: %s', implode( ', ', $unknown ), implode( ', ', array_keys( self::STEPS ) ) ) );
		}

		// Later steps need the seeded page IDs even when pages aren't re-seeded.
		if ( ! in_array( 'pages', $only, true ) ) {
			$this->index_pages();
		}

		$handlers = array(
			'identity'     => fn() => $this->seed_identity(),
			'pages'        => fn() => $this->seed_pages(),
			'reading'      => fn() => $this->seed_reading_settings(),
			'menus'        => fn() => $this->seed_menus(),
			'settings'     => fn() => $this->seed_site_settings(),
			'testimonials' => fn() => $this->seed_testimonials(),
			'faqs'         => fn() => $this->seed_faqs(),
			'posts'        => fn() => $this->seed_posts(),
			'home'         => fn() => $this->seed_front_page(),
			'staff'        => fn() => $this->seed_staff(),
			'about'        => fn() => $this->seed_about(),
			'academics'    => fn() => $this->seed_academics(),
			'admissions'   => fn() => $this->seed_admissions(),
			'student-life' => fn() => $this->seed_student_life(),
			'news-events'  => fn() => $this->seed_news_events(),
			'contact'      => fn() => $this->seed_contact(),
			'careers'      => fn() => $this->seed_careers(),
			'resources'    => fn() => $this->seed_resources(),
			'registration' => fn() => $this->seed_registration(),
			'careers-application' => fn() => $this->seed_careers_application(),
			'legal'        => fn() => $this->seed_legal(),
		);

		foreach ( self::STEPS as $key => $label ) {
			if ( in_array( $key, $only, true ) ) {
				$this->step( $label, $handlers[ $key ] );
			}
		}

		WP_CLI::success( sprintf( 'Done. Visit %s', home_url( '/' ) ) );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Steps
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Site title, tagline and logo.
	 */
	private function seed_identity(): void {
		update_option( 'blogname', 'Sunrise English Private School' );
		update_option( 'blogdescription', 'Abu Dhabi' );
		set_theme_mod( 'custom_logo', $this->image( 'logo.png', 'Sunrise English Private School emblem' ) );
	}

	/**
	 * Top-level pages from the Figma sitemap.
	 *
	 * Every page is a single page: the sitemap's sub-items (e.g. About →
	 * Vision & Mission, Leadership) are sections on that page, not separate
	 * pages. Sub-pages seeded by earlier versions are moved to the Trash.
	 */
	private function seed_pages(): void {
		$pages = array(
			'home'           => 'Home',
			'about'          => 'About',
			'academics'      => 'Academics',
			'admissions'     => 'Admissions',
			'student-life'   => 'Student Life',
			'news-events'    => 'News & Events',
			'contact'        => 'Contact',
			'register-now'   => 'Register Now',
			'resources'      => 'Resources',
			'careers'        => 'Careers',
			'careers-application' => 'Careers Application',
			'privacy-policy' => 'Privacy Policy',
			'terms'          => 'Terms & Conditions',
		);

		$this->adopt_privacy_page();

		$order = 0;
		foreach ( $pages as $key => $title ) {
			$this->page( $key, $title, ++$order );
		}

		// WordPress' privacy tools (Settings → Privacy) use the seeded page.
		update_option( 'wp_page_for_privacy_policy', $this->pages['privacy-policy'] );

		// Retire pages from earlier seeds that are no longer in the sitemap.
		$seeded = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => self::SEED_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- CLI only.
				'suppress_filters' => true,
			)
		);

		foreach ( $seeded as $id ) {
			if ( ! isset( $pages[ (string) get_post_meta( $id, self::SEED_META, true ) ] ) ) {
				wp_trash_post( $id );
				WP_CLI::log( sprintf( '  Moved obsolete page to Trash: %s', get_the_title( $id ) ) );
			}
		}

		// Remove WordPress' default sample content (moved to Trash, recoverable).
		foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $type ) {
			$default = get_page_by_path( $slug, OBJECT, $type );
			if ( $default && ! get_post_meta( $default->ID, self::SEED_META, true ) ) {
				wp_trash_post( $default->ID );
			}
		}
	}

	/**
	 * Use one Privacy Policy page.
	 *
	 * WordPress creates a draft "Privacy Policy" page on install that holds the
	 * `privacy-policy` slug, so a second seeded page would end up at
	 * /privacy-policy-2/. Adopt WordPress' page instead; on sites seeded before
	 * this fix (both pages exist), trash WordPress' untouched draft so the
	 * seeded page gets the clean slug (trashed posts release their slug).
	 */
	private function adopt_privacy_page(): void {
		$core   = (int) get_option( 'wp_page_for_privacy_policy' );
		$seeded = $this->find( 'page', 'privacy-policy' );

		if ( ! $core || $core === $seeded || ! get_post( $core ) || 'trash' === get_post_status( $core ) ) {
			return;
		}

		if ( ! $seeded ) {
			update_post_meta( $core, self::SEED_META, 'privacy-policy' );
			return;
		}

		if ( 'draft' === get_post_status( $core ) ) {
			wp_trash_post( $core );
			WP_CLI::log( '  Moved WordPress’ default Privacy Policy draft to Trash (it held the privacy-policy address).' );
		} else {
			// WordPress' page is published (possibly edited): keep it, retire the duplicate.
			wp_trash_post( $seeded );
			delete_post_meta( $seeded, self::SEED_META );
			update_post_meta( $core, self::SEED_META, 'privacy-policy' );
			WP_CLI::log( '  Using the existing published Privacy Policy page; moved the duplicate to Trash. Re-run the menus step: wp seps seed --only=menus' );
		}
	}

	/**
	 * Static front page, posts page and pretty permalinks.
	 */
	private function seed_reading_settings(): void {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $this->pages['home'] );
		update_option( 'page_for_posts', $this->pages['news-events'] );

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}

		flush_rewrite_rules( false );
	}

	/**
	 * All theme menu locations. Footer column headings come from menu names.
	 */
	private function seed_menus(): void {
		$page = fn( string $key ): array => array( 'page' => $key );
		$link = fn( string $title, string $url = '#' ): array => array(
			'title' => $title,
			'url'   => $url,
		);
		// ETH Digital Campus (DC Netra): one sign-in for parents, students
		// and staff. The current school website links its logins here too.
		$portal = 'https://seps.ethdigitalcampus.com/SEPS/';

		$locations = array(
			'primary'        => array(
				'Primary Navigation',
				array(
					$page( 'about' ),
					$page( 'academics' ),
					$page( 'admissions' ),
					$page( 'student-life' ),
					$page( 'news-events' ),
					$page( 'contact' ),
				),
			),
			'utility'        => array(
				'Top Bar',
				array(
					// The "CBSE Mandatory Public Disclosure" section on Academics.
					$link( 'Mandatory Disclosure', get_permalink( $this->pages['academics'] ) . '#mandatory-disclosure' ),
					$page( 'resources' ),
					$page( 'careers' ),
				),
			),
			'footer_campus'  => array(
				'Explore Our Campus',
				array(
					$link( 'Facilities & Infrastructure', get_permalink( $this->pages['resources'] ) ),
					$link( 'Sports & Activities', get_permalink( $this->pages['student-life'] ) ),
					$link( 'Arts & Student Life', get_permalink( $this->pages['student-life'] ) ),
					$link( 'Photo Gallery', get_permalink( $this->pages['student-life'] ) ),
				),
			),
			'footer_quick'   => array(
				'Quick Links',
				array(
					$page( 'home' ),
					$page( 'about' ),
					$page( 'academics' ),
					$link( 'CBSE Portal', 'https://www.cbse.gov.in/' ),
					$page( 'admissions' ),
					$page( 'student-life' ),
					$page( 'news-events' ),
					$page( 'careers' ),
					$page( 'contact' ),
					$page( 'register-now' ),
				),
			),
			'footer_portals' => array(
				'SEPS Portals',
				array(
					$link( 'Parent Portal', $portal ),
					$link( 'Student Portal', $portal ),
					$link( 'Staff Portal', $portal ),
					$link( 'Fee Payment', $portal ), // Paid after signing in (to confirm: open question #55).
					$link( 'School Calendar', get_permalink( $this->pages['news-events'] ) ),
				),
			),
			'legal'          => array(
				'Legal',
				array( $page( 'privacy-policy' ), $page( 'terms' ) ),
			),
		);

		$assigned = (array) get_theme_mod( 'nav_menu_locations', array() );

		foreach ( $locations as $location => [ $name, $items ] ) {
			$existing = wp_get_nav_menu_object( $name );
			if ( $existing ) {
				wp_delete_nav_menu( $existing->term_id );
			}

			$menu_id = wp_create_nav_menu( $name );
			if ( is_wp_error( $menu_id ) ) {
				WP_CLI::error( $menu_id );
			}

			$this->add_menu_items( (int) $menu_id, $items );
			$assigned[ $location ] = (int) $menu_id;
		}

		set_theme_mod( 'nav_menu_locations', $assigned );
	}

	/**
	 * Global Site Settings (options page).
	 */
	private function seed_site_settings(): void {
		$settings = array(
			'email'             => 'school@seps-auh.com',
			'phone'             => '+971 2 552 9989',
			'address'           => "P.O. Box 71356, Shabiya, ME-09, Near Safeer Mall,\nAbu Dhabi, United Arab Emirates",
			'social_links'      => array(
				array(
					'network' => 'facebook',
					'url'     => 'https://www.facebook.com/sepsauh/',
				),
				array(
					'network' => 'x',
					'url'     => 'https://x.com/PrivateSunrise',
				),
				array(
					'network' => 'instagram',
					'url'     => 'https://www.instagram.com/sepsauh/',
				),
			),
			'header_button'     => $this->link( 'register-now', 'Register Now' ),
			'footer_heading'    => 'Sunrise English Private School, Abu Dhabi',
			'newsletter_text'   => 'Stay connected with SEPS. Subscribe for school updates, news and important announcements.',
			'newsletter_button' => $this->link( 'contact', 'Subscribe Now' ),
			'footer_image'      => $this->image( 'footer.jpg', 'Smiling young student drawing in class' ),
			'copyright'         => '© {year} Sunrise English Private School. All rights reserved.',
		);

		foreach ( $settings as $name => $value ) {
			update_field( "field_seps_settings_{$name}", $value, 'option' );
		}
	}

	/**
	 * Parent testimonials (newest first in the carousel).
	 */
	private function seed_testimonials(): void {
		$items = array(
			array( 'Aisha Khan', 'Parent of Grade 5 & Grade 2 students', 'Choosing SEPS has been a wonderful experience for our family. The teachers are supportive, the school community is welcoming, and we’ve seen our child grow in confidence, independence and love for learning.' ),
			array( 'Fatima Al Hosani', 'Parent of Grade 4 Student', 'SEPS has been a wonderful place for our child to grow. We appreciate the caring teachers, supportive environment and the way the school encourages children to take part, express themselves and become more confident.' ),
			array( 'Rahul Menon', 'Parent of Grade 8 Student', 'The balance between academics and activities is excellent. Our son looks forward to school every day and the regular updates from teachers keep us closely involved in his progress.' ),
			array( 'Sara Ahmed', 'Parent of KG II Student', 'From the very first week, the kindergarten team made our daughter feel safe and happy. She has learned so much through play and is always excited to share what she did in class.' ),
		);

		foreach ( $items as $index => [ $name, $role, $quote ] ) {
			$id = $this->post(
				'seps_testimonial',
				'testimonial-' . ( $index + 1 ),
				array(
					'post_title' => $name,
					'post_date'  => wp_date( 'Y-m-d H:i:s', time() - ( $index * HOUR_IN_SECONDS ) ),
				)
			);

			update_field( 'field_seps_testimonial_quote', $quote, $id );
			update_field( 'field_seps_testimonial_author_role', $role, $id );
		}
	}

	/**
	 * FAQs (ordered by menu_order).
	 */
	private function seed_faqs(): void {
		$items = array(
			array( 'What curriculum does SEPS follow?', 'Sunrise English Private School follows the CBSE curriculum from KG I to Grade XII, with Arabic-medium subjects following the UAE Ministry of Education curriculum. The curriculum balances knowledge, skills and attitudes with practical and cross-curricular learning.' ),
			array( 'What is the admission process at SEPS?', 'Admission steps, eligibility criteria and required documents are explained on our Admissions page. Our admissions team is happy to guide you through each stage of the application.' ),
			array( 'What are the school timings?', 'School timings vary by grade and are published on the School Timings page under Academics, along with any seasonal changes announced during the year.' ),
			array( 'Does SEPS offer extracurricular activities?', 'Yes. Students can take part in sports, arts, music, clubs and student council initiatives that complement classroom learning and help them discover new interests.' ),
			array( 'Is school transportation available?', 'Yes. School transport is available on selected routes. Please visit the Transport page under Admissions for route details and registration.' ),
		);

		$topic = term_exists( 'General', 'seps_faq_topic' ) ?: wp_insert_term( 'General', 'seps_faq_topic' );
		$term  = is_array( $topic ) ? (int) $topic['term_id'] : 0;

		foreach ( $items as $index => [ $question, $answer ] ) {
			$id = $this->post(
				'seps_faq',
				'faq-' . ( $index + 1 ),
				array(
					'post_title'   => $question,
					'post_content' => $answer,
					'menu_order'   => $index + 1,
				)
			);

			if ( $term ) {
				wp_set_object_terms( $id, $term, 'seps_faq_topic' );
			}
		}
	}

	/**
	 * News posts with featured images (newest = featured on the home page).
	 */
	private function seed_posts(): void {
		$category = term_exists( 'News', 'category' ) ?: wp_insert_term( 'News', 'category' );
		$cat_id   = is_array( $category ) ? (int) $category['term_id'] : 0;

		$posts = array(
			array(
				'news-1.jpg',
				'Green Week: Promoting Sustainability',
				'Students participated in activities focused on environmental awareness, including planting drives and eco-friendly initiatives across the campus.',
				'Teacher high-fiving a student in a bright classroom',
			),
			array(
				'news-2.jpg',
				'Building Strong Foundations in Early Education',
				'Early years play a crucial role in shaping a child’s confidence and curiosity. At Sunrise, our approach focuses on interactive, play-based learning that builds early literacy, numeracy and social skills.',
				'Kindergarten children building with blocks',
			),
			array(
				'news-3.jpg',
				'Students Excel in CBSE Board Examinations',
				'We are proud to celebrate the outstanding performance of our Grade X and XII students, achieving excellent results across subjects in the CBSE Board Examinations.',
				'Teacher guiding students during a lesson',
			),
			array(
				'news-4.jpg',
				'Sports Day Celebration 2025',
				'Our Sports Day was a vibrant showcase of talent, creativity, and teamwork, with students delivering outstanding performances across track, field and team events.',
				'Students playing football on the school field',
			),
		);

		foreach ( $posts as $index => [ $image, $title, $excerpt, $alt ] ) {
			$id = $this->post(
				'post',
				'news-' . ( $index + 1 ),
				array(
					'post_title'    => $title,
					'post_excerpt'  => $excerpt,
					'post_content'  => "<!-- wp:paragraph -->\n<p>{$excerpt}</p>\n<!-- /wp:paragraph -->",
					'post_date'     => wp_date( 'Y-m-d H:i:s', time() - ( $index * DAY_IN_SECONDS ) ),
					'post_category' => $cat_id ? array( $cat_id ) : array(),
				)
			);

			set_post_thumbnail( $id, $this->image( $image, $alt ) );
		}
	}

	/**
	 * Home page section groups.
	 */
	private function seed_front_page(): void {
		$front = $this->pages['home'];
		$apply = $this->link( 'register-now', 'Apply Now' );

		$sections = array(
			'hero'         => array(
				'enabled' => 1,
				'title'   => "Where Young Minds Grow,\nDream and Discover",
				'text'    => 'We believe education is about more than what happens in a classroom. At SEPS, students are encouraged to explore their potential, think independently and grow into confident, responsible individuals.',
				'button'  => $this->link( 'about', 'Discover SEPS' ),
				'image'   => $this->image( 'hero.jpg', 'Young student concentrating on a drawing in class' ),
			),
			'about'        => array(
				'enabled'         => 1,
				'heading'         => 'A Legacy Built on Education, Values and Excellence',
				'image'           => $this->image( 'about-primary.jpg', 'Smiling student holding books with classmates' ),
				'features'        => array(
					array(
						'icon'  => 'certificate',
						'title' => 'A Strong Academic Foundation',
						'text'  => 'Following the CBSE curriculum from KG to Grade XII, SEPS builds knowledge, skills and confidence through structured, progressive learning.',
					),
					array(
						'icon'  => 'student',
						'title' => 'Learning Across Subjects',
						'text'  => 'Cross-disciplinary and practical learning helps students connect ideas, apply knowledge and develop skills beyond the classroom.',
					),
				),
				'intro'           => 'Established in 1988, Sunrise English Private School has grown into one of Abu Dhabi’s pioneering Indian schools, with a longstanding commitment to academic excellence, holistic development and strong values. Our CBSE-based education combines structured learning with opportunities for students to explore, think critically and develop as confident individuals.',
				'button'          => $this->link( 'about', 'Learn More' ),
				'image_secondary' => $this->image( 'about-secondary.jpg', 'Student shooting a basketball outdoors' ),
			),
			'programs'     => array(
				'enabled' => 1,
				'heading' => "Explore Our\nPrograms",
				'intro'   => 'From the early years through Grade XII, SEPS provides a structured CBSE education that grows with each student, building strong foundations, broad knowledge and pathways for further study.',
				'items'   => array(
					array(
						'image'  => $this->image( 'program-kindergarten.jpg', 'Kindergarten children playing with building blocks' ),
						'title'  => 'Kindergarten',
						'text'   => 'A nurturing start to learning through English, Mathematics, Understanding the World, creative activities, music and games.',
						'button' => $apply,
						'label'  => 'KG I–II',
					),
					array(
						'image'  => $this->image( 'program-primary.jpg', 'Teacher high-fiving a primary student' ),
						'title'  => 'Primary Education',
						'text'   => 'Students build strong foundations across languages, mathematics, science, computing, social studies, arts, physical education and more.',
						'button' => $apply,
						'label'  => 'Grade I–V',
					),
					array(
						'image'  => $this->image( 'program-kindergarten.jpg', 'Kindergarten children playing with building blocks' ),
						'title'  => 'Secondary Education',
						'text'   => 'Students progress through a broad range of subjects, with specialised Science and Commerce streams available in Grades XI and XII.',
						'button' => $apply,
						'label'  => 'Grade VI–XII',
					),
				),
			),
			'student_life' => array(
				'enabled'    => 1,
				'heading'    => "Discover Life\nat SEPS",
				'text'       => 'From the early years to Grade XII, SEPS offers a supportive environment where students learn, explore their interests and grow with confidence.',
				'button'     => $this->link( 'student-life', 'Explore Student Life' ),
				'image'      => $this->image( 'student-life.jpg', 'Student smiling at his desk during class' ),
				'stat_value' => '75%+',
				'stat_text'  => '127 Grade 12 students and 167 Grade 10 students achieved aggregate scores of 75% or above in the 2024 CBSE Board Examinations.',
			),
			'principal'    => array(
				'enabled' => 1,
				'heading' => 'A Message from the Principal',
				'message' => "At Sunrise English Private School, we believe every student has the potential to learn, grow and make a difference. Our role is to create an environment where young minds are encouraged to think independently, discover their strengths and grow with confidence.\n\nAs we prepare our students to meet the challenges of a changing world, we remain committed to academic excellence, strong values and the holistic development of every learner.",
				'image'   => $this->image( 'principal.jpg', 'Dr. Thakur S. Mulchandani at his desk' ),
				'name'    => 'Dr. Thakur S. Mulchandani',
				'role'    => 'School Director & Principal, Sunrise English Private School',
			),
			'facilities'   => array(
				'enabled' => 1,
				'heading' => 'Spaces That Inspire Learning',
				'intro'   => 'Our learning environment brings together dedicated academic facilities, creative spaces and opportunities for students to explore, experiment and stay active.',
				'button'  => $this->link( 'student-life', 'View Gallery' ),
				'items'   => $this->facility_items(),
			),
			'highlights'   => array(
				'enabled' => 1,
				'items'   => array(
					array( 'label' => '🏆 Academic Excellence' ),
					array( 'label' => '🎓 College-Ready Education' ),
					array( 'label' => '🌍 Recognized & Affiliated' ),
					array( 'label' => '🥇 Holistic Development' ),
					array( 'label' => '👩‍🏫 Strong Community' ),
					array( 'label' => '🏫 Lifelong Learning' ),
				),
			),
			'testimonials' => array(
				'enabled' => 1,
				'heading' => 'Hear from our Parents',
				'intro'   => 'Have questions about admissions, eligibility or the application process? Our team is here to help you take the next step.',
				'button'  => $this->link( 'contact', 'Enquire Now' ),
				'image'   => $this->image( 'testimonials.jpg', 'Mother hugging her two children outside school' ),
				'items'   => array(),
			),
			'faq'          => array(
				'enabled' => 1,
				'heading' => "Frequently Asked\nQuestions",
				'image'   => $this->image( 'faq.jpg', 'Two students listening attentively in class' ),
				'items'   => array(),
			),
			'news'         => array(
				'enabled'  => 1,
				'heading'  => 'Latest News & Media Corner',
				'category' => '',
			),
		);

		foreach ( $sections as $name => $value ) {
			update_field( "field_seps_front_{$name}", $value, $front );
		}
	}

	/**
	 * Staff members and their teams (Principals, Leadership Team).
	 *
	 * Only people with their own photo in the design get one; the design
	 * reuses other staff photos as placeholders, which we don't replicate.
	 */
	private function seed_staff(): void {
		foreach ( array( 'principals' => 'Principals', 'leadership-team' => 'Leadership Team' ) as $slug => $name ) {
			$term = term_exists( $slug, 'seps_team' ) ?: wp_insert_term( $name, 'seps_team', array( 'slug' => $slug ) );

			if ( is_wp_error( $term ) ) {
				WP_CLI::error( $term );
			}

			$this->teams[ $slug ] = (int) $term['term_id'];
		}

		$people = array(
			array( 'principals', 'Dr. Thakur S. Mulchandani', "Principal,\nSunrise English Private School", 'staff-principal.jpg' ),
			array( 'principals', 'Mr. Thomas Francis', "Vice Principal,\nSunrise English Private School", 'staff-vice-principal.jpg' ),
			array( 'leadership-team', 'Mrs. Cheryl Ann Felix', 'KG & Grade 1 Lead Teacher', 'staff-cheryl-ann-felix.jpg' ),
			array( 'leadership-team', 'Mrs. Deepa Binu', 'Grade 2–3 Primary Section Lead Teacher', 'staff-deepa-binu.jpg' ),
			array( 'leadership-team', 'Mrs. Sumitha Anilkumar', 'Grade 4–5 Primary Section Lead Teacher', 'staff-sumitha-anilkumar.jpg' ),
			array( 'leadership-team', 'Mrs. Nausheen Ikhlaq', 'Grade 1, 2 & 3 Supervisor, Primary Section', '' ),
			array( 'leadership-team', 'Mrs. Shajila Saithoon Beevi Majeed', 'Grade 4–5 Supervisor, Primary Section', '' ),
			array( 'leadership-team', 'Mrs. Indu Suresh', 'Grade 6–12 Boys Section Lead Teacher', '' ),
		);

		foreach ( $people as $index => [ $team, $name, $position, $photo ] ) {
			$id = $this->post(
				'seps_person',
				'person-' . sanitize_title( $name ),
				array(
					'post_title' => $name,
					'menu_order' => $index + 1,
				)
			);

			update_field( 'field_seps_person_position', $position, $id );
			wp_set_object_terms( $id, $this->teams[ $team ], 'seps_team' );

			if ( $photo ) {
				set_post_thumbnail( $id, $this->image( $photo, $name ) );
			} else {
				delete_post_thumbnail( $id );
			}
		}
	}

	/**
	 * About page, built with the Page Sections builder. Vision & Mission,
	 * Core Values, the Chairman's message and Leadership are sections of it.
	 */
	private function seed_about(): void {
		foreach ( array( 'principals', 'leadership-team' ) as $slug ) {
			if ( ! isset( $this->teams[ $slug ] ) ) {
				$term = get_term_by( 'slug', $slug, 'seps_team' );
				if ( ! $term ) {
					WP_CLI::error( 'Staff teams are missing. Run: wp seps seed --only=staff,about' );
				}
				$this->teams[ $slug ] = (int) $term->term_id;
			}
		}

		$hero = fn( string $title, string $text ): array => array(
			'acf_fc_layout' => 'page_hero',
			'title'         => $title,
			'text'          => $text,
			'button'        => $this->link( 'register-now', 'Apply Now' ),
			'image'         => $this->image( 'about-hero.jpg', 'Young students drawing at their desks' ),
		);

		$mission = array(
			array( 'Academic Excellence', 'We pursue academic excellence while meeting the diverse academic and non-academic needs of every student.' ),
			array( 'Holistic Development', 'We nurture the whole child academically, socially and spiritually, helping students grow into responsible citizens.' ),
			array( 'Community & Collaboration', 'We engage parents, staff and stakeholders in continuous improvement and in shaping the school\'s direction and future.' ),
			array( 'UAE Culture & Values', 'We cultivate respect for the UAE\'s culture, religion and heritage while upholding the vision and values of Abu Dhabi 2030.' ),
			array( 'Relevant & Lifelong Learning', 'We provide value-based education relevant to life, fostering critical thinking, creativity, individuality and a lifelong passion for learning.' ),
			array( 'Excellence & Wellbeing', 'We uphold high standards, adapt to change and create a healthy, safe environment where every student can thrive.' ),
		);

		$vision = array(
			'acf_fc_layout'  => 'media_text',
			'image'          => $this->image( 'about-vision.jpg', 'Students walking together along a school corridor' ),
			'image_position' => 'left',
			'blocks'         => array(
				array(
					'heading' => 'Our Vision',
					'text'    => 'To foster excellence in educating young people through collaboration, informed decision-making and continuous improvement, preparing responsible citizens to meet future global challenges.',
					'cards'   => array(),
				),
				array(
					'heading' => 'Our Mission',
					'text'    => '',
					'cards'   => array_map(
						static fn( array $card ): array => array(
							'title' => $card[0],
							'text'  => $card[1],
						),
						$mission
					),
				),
			),
		);

		$values = array(
			'acf_fc_layout' => 'values',
			'heading'       => 'Core Values & Code of Conduct',
			'intro'         => 'Sunrise English Private School nurtures future citizens through a safe environment built on discipline, respect, perseverance and responsibility.',
			'panel_title'   => 'What Students Are Expected to Follow',
			'panel_items'   => array(
				array(
					'label' => 'Attendance & Punctuality',
					'text'  => 'Maintain 90% attendance, arrive on time and follow the school\'s leave procedures.',
				),
				array(
					'label' => 'Uniform & Personal Conduct',
					'text'  => 'Wear the proper school uniform, maintain personal cleanliness and follow classroom and behavioural expectations.',
				),
				array(
					'label' => 'School Rules & Safety',
					'text'  => 'Respect school property, follow rules in common areas and do not bring prohibited or sharp objects to school.',
				),
				array(
					'label' => 'Examinations',
					'text'  => 'Attend all school examinations and follow examination rules, including regulations against unfair means.',
				),
			),
			'panel_button'  => $this->link( 'resources', 'Learn More' ),
			'accordion'     => array(
				array(
					'title'    => 'Discipline',
					'subtitle' => 'Respecting rules and maintaining order',
					'points'   => "Follow school rules and regulations\nMaintain personal hygiene and cleanliness\nCare for school facilities and property\nAim towards 100% attendance\nMaintain discipline in common areas",
				),
				array(
					'title'    => 'Respect',
					'subtitle' => 'Honouring beliefs, culture and dignity',
					'points'   => "Respect the beliefs and cultures of others\nTreat classmates, staff and visitors with courtesy\nUse polite and considerate language",
				),
				array(
					'title'    => 'Perseverance',
					'subtitle' => 'Working hard and striving for improvement',
					'points'   => "Complete work carefully and on time\nLearn from mistakes and keep trying\nSet goals and work towards them",
				),
				array(
					'title'    => 'Responsibility',
					'subtitle' => 'Making responsible and informed choices',
					'points'   => "Take ownership of your actions\nMake safe and informed decisions\nLook after your belongings and the environment",
				),
			),
		);

		$message = array(
			'acf_fc_layout' => 'message',
			'heading'       => 'A Message of Triumph',
			'message'       => "It gives me immense pleasure to celebrate the achievement of the coveted Band-A rating. This recognition is a testament to the hard work, dedication and honest efforts of the entire Sunrise family.\n\nSunrise English Private School has built a strong legacy in education, providing our students with opportunities to move beyond rote learning, discover their hidden potential and develop their individual talents and skills.\n\nWe believe in nurturing every child with care while allowing them to develop their own identity. I encourage our parents to join hands with us in giving our children the tools, opportunities and support they need to grow in body, mind and soul.\n\nI remain committed to extending my continued and unwavering support to the Sunrise community as we move forward with the same indomitable spirit.",
			'name'          => 'Mr. Saeed Omeir Yousef Ahmed Almheiri',
			'role'          => 'Chairman, Sunrise English Private School',
			'image'         => $this->image( 'about-chairman.jpg', 'Young graduate in cap and gown smiling at a desk' ),
			'quote'         => 'Children are the finest flowers, given to us to nurture with tender care.',
		);

		$team = array(
			'acf_fc_layout' => 'team',
			'heading'       => 'Senior Leadership Team',
			'intro'         => 'Meet the leaders and educators who guide Sunrise English Private School and support the academic, personal and holistic development of our students.',
			'groups'        => array(
				array(
					'heading' => 'Principals',
					'team'    => $this->teams['principals'],
					'style'   => 'featured',
				),
				array(
					'heading' => 'Leadership Team',
					'team'    => $this->teams['leadership-team'],
					'style'   => 'grid',
				),
			),
		);

		update_field(
			'field_seps_page_sections',
			array(
				$hero( 'Discover Our School', 'Learn more about Sunrise English Private School, our educational approach, core values and commitment to helping every student learn, grow and reach their potential.' ),
				$vision,
				$values,
				$message,
				$team,
			),
			$this->pages['about']
		);
	}

	/**
	 * Academics page: curriculum highlights, courses, timetable and the CBSE
	 * mandatory disclosure documents (Section ID "mandatory-disclosure", linked
	 * from the top bar).
	 */
	private function seed_academics(): void {
		$url   = (string) get_permalink( $this->pages['academics'] );
		$tiles = array(
			array( 'book', 'Structured Syllabus' ),
			array( 'tick', 'No-Fail Policy Till Grade 8' ),
			array( 'graduation', 'College Exam Aligned' ),
			array( 'globe', 'Relocation Friendly' ),
			array( 'bulb', 'Engineering & Medical Ready' ),
		);
		$cards = array(
			array( 'raindrop', 'Humanities', 'Geography, History, Economics and more.' ),
			array( 'sun', 'Science & Technology', 'Biology, Chemistry, Physics and related subjects.' ),
			array( 'cross-circle', 'Visual & Performing Arts', 'Dance, Music, Painting and more subjects.' ),
			array( 'home', 'Commerce', 'Business Studies, Accountancy, Entrepreneurship and more.' ),
			array( 'pin', 'Health & PE', 'Supporting students\' physical development, wellbeing and healthy lifestyles.' ),
		);
		$item  = static fn( string $badge, string $title, string $content ): array => compact( 'badge', 'title', 'content' );

		$sections = array(
			array(
				'acf_fc_layout' => 'page_hero',
				'title'         => 'A Curriculum Designed to Help Students Grow',
				'text'          => 'At SEPS, our CBSE curriculum combines structured learning with practical experiences, critical thinking and opportunities for students to explore their interests from KG to Grade XII.',
				'button'        => array(
					'url'    => $url . '#curriculum',
					'title'  => 'Explore Curriculum',
					'target' => '',
				),
				'image'         => $this->image( 'academics-hero.jpg', 'Smiling student holding a laptop against a brick wall' ),
				'decor'         => 'sun',
			),
			array(
				'acf_fc_layout' => 'features',
				'anchor'        => 'curriculum',
				'heading'       => 'CBSE Curriculum',
				'intro'         => 'The CBSE curriculum combines disciplinary and interdisciplinary learning with academic excellence, independent learning, inquiry and intercultural understanding, preparing students to grow as confident, capable learners.',
				'tiles'         => array_map(
					static fn( array $t ): array => array(
						'icon'  => $t[0],
						'label' => $t[1],
					),
					$tiles
				),
				'cards_heading' => 'Curriculum Focus Areas',
				'cards'         => array_map(
					static fn( array $c ): array => array(
						'icon'  => $c[0],
						'title' => $c[1],
						'text'  => $c[2],
					),
					$cards
				),
				'callout_icon'  => 'people',
				'callout_title' => 'Special Education Support',
				'callout_text'  => 'Students with learning difficulties may be referred to a Special Education teacher for remedial support, with examination papers adapted to meet their needs.',
				'callout_image' => $this->image( 'academics-special-education.jpg', 'Empty classroom with rows of desks' ),
			),
			array(
				'acf_fc_layout' => 'badge_accordion',
				'heading'       => 'Courses Offered',
				'intro'         => 'Our academic program progresses from KG I through Grade XII, with students building their knowledge and skills across each stage before choosing specialized pathways in senior secondary.',
				'image'         => $this->image( 'academics-courses.jpg', 'Students studying at desks in a bright classroom' ),
				'groups'        => array(
					array(
						'heading' => '',
						'items'   => array(
							$item( 'KG I–II', 'Foundation Years', 'English, Mathematics, UTW, Art & Crafts, Music and Games.' ),
							$item( 'GRADE I–V', 'Primary', 'Languages, Mathematics, Science, Computing, Social Studies, Arts, Physical Education and more.' ),
							$item( 'GRADE VI–VIII', 'Middle School', 'Languages, Mathematics, Science, Social Science, Computer Science, Arts and Physical Education.' ),
							$item( 'GRADE IX–X', 'Secondary', 'Languages, Mathematics, Science, Social Science, Computer Science and Physical Education.' ),
						),
					),
					array(
						'heading' => 'Senior Secondary Streams (Grade XI & XII)',
						'items'   => array(
							$item( 'Group 1', 'Science Stream | Computer Science', 'English, Physics, Chemistry, Mathematics, Computer Science, Arabic, Islamic Studies / Moral Science, GST, Art, PHE.' ),
							$item( 'Group 2', 'Science Stream | Biology & Mathematics', 'English, Physics, Chemistry, Biology, Mathematics.' ),
							$item( 'Group 3', 'Science Stream | Biology & Psychology', 'English, Physics, Chemistry, Biology, Psychology.' ),
							$item( 'Group 4', 'Commerce Stream | Marketing', 'English, Accountancy, Business Studies, Economics, Marketing.' ),
							$item( 'Group 5', 'Commerce Stream | Informatics Practices', 'English, Accountancy, Business Studies, Economics, Informatics Practices.' ),
						),
					),
				),
			),
			array(
				'acf_fc_layout' => 'timetable',
				'heading'       => 'A Day at Sunrise',
				'intro'         => 'Our school day follows a structured timetable that balances classroom learning, breaks and time for students to recharge.',
				'days'          => array(
					array(
						'label' => 'Monday – Thursday (Full School Day)',
						'rows'  => array(
							array( 'slots' => "Daily Assembly | 7:25 - 7:40 AM\nPeriod 1 | 7:40 - 8:25 AM\nPeriod 2 | 8:25 - 9:05 AM\nPeriod 3 | 9:05 - 9:45 AM" ),
							array( 'slots' => "Recess | 9:45 - 10:00 AM\nPeriod 4 | 10:00 - 10:40 AM\nPeriod 5 | 10:40 - 11:20 AM\nPeriod 6 | 11:20 - 12:00 PM\nPeriod 7 | 12:00 - 12:35 PM" ),
							array( 'slots' => "Recess | 12:35 - 1:00 PM\nPeriod 8 | 1:00 - 1:35 PM\nPeriod 9 | 1:35 - 2:10 PM\nStudent Dispersal | 2:10 PM" ),
						),
					),
					array(
						'label' => 'Friday (Half Day)',
						'rows'  => array(
							array( 'slots' => "Daily Assembly | 7:25 - 7:40 AM\nPeriod 1 | 7:40 - 8:15 AM\nPeriod 2 | 8:15 - 8:50 AM\nPeriod 3 | 8:50 - 9:25 AM" ),
							array( 'slots' => "Recess | 9:25 - 9:40 AM\nPeriod 4 | 9:40 - 10:15 AM\nPeriod 5 | 10:15 - 10:50 AM\nPeriod 6 | 10:50 - 11:25 AM\nStudent Dispersal | 2:10 PM" ),
						),
					),
				),
			),
			array(
				'acf_fc_layout' => 'documents',
				'anchor'        => 'mandatory-disclosure',
				'heading'       => 'CBSE Mandatory Public Disclosure',
				'intro'         => 'Access official school documents and information published in accordance with CBSE mandatory disclosure requirements.',
				'items'         => $this->disclosure_items(),
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['academics'] );
	}

	/**
	 * CBSE Mandatory Public Disclosure documents (Academics), as published on
	 * the school's current website. PDFs are in demo/documents; the photos are
	 * placeholders (demo/CREDITS.md).
	 *
	 * @return array<int,array{title:string,image:int,file:int}>
	 */
	private function disclosure_items(): array {
		$docs = array(
			// Title, PDF, photo, photo alt text.
			array( 'Mandatory Public Disclosure', 'mandatory-public-disclosure.pdf', 'doc-disclosure.jpg', 'Documents on a clipboard on a desk' ),
			array( 'Fee Structure', 'fee-structure-2026-27.pdf', 'program-kindergarten.jpg', 'Kindergarten children playing with building blocks' ),
			array( 'Annual Academic Calendar', 'annual-academic-calendar.pdf', 'doc-calendar.jpg', 'Open planner with handwritten notes' ),
			array( 'Affidavit', 'affidavit.pdf', 'doc-affidavit.jpg', 'Hand signing a document with a fountain pen' ),
			array( 'Parent Council Members (PTA)', 'parent-council-members.pdf', 'doc-parent-council.jpg', 'Two people talking at a meeting table' ),
			array( 'Teachers Details 2026', 'teachers-details-2026.pdf', 'doc-teachers.jpg', 'Teacher with students in a classroom' ),
			array( 'Drinking Water Certificate', 'drinking-water-certificate.pdf', 'doc-drinking-water.jpg', 'Water poured into a glass' ),
			array( 'Water Test Report', 'water-test-report.pdf', 'doc-water-test.jpg', 'Hands under running tap water' ),
			array( 'Affiliation', 'affiliation-approval.pdf', 'doc-affiliation.jpg', 'Signing paperwork at a desk' ),
			array( 'Bus License', 'bus-license.pdf', 'doc-bus-license.jpg', 'Yellow school bus on the road' ),
			array( 'Result and Academics', 'result-and-academics.pdf', 'doc-results.jpg', 'Exam hall with rows of desks' ),
			array( 'Civil Defence Certificate 2026–27', 'civil-defence-certificate-2026-27.pdf', 'doc-civil-defence.jpg', 'Fire extinguisher on a wall' ),
			array( 'Commercial License', 'commercial-license.pdf', 'doc-commercial-license.jpg', 'Glass office building against the sky' ),
			array( 'Clinic License 2026–27', 'clinic-license-2026-27.pdf', 'doc-clinic.jpg', 'Stethoscope on a white desk' ),
			array( 'School Managing Committee', 'school-management-committee.pdf', 'doc-smc.jpg', 'Empty boardroom with a long table' ),
			array( 'NOC', 'noc.pdf', 'doc-noc.jpg', 'Writing a checklist in a notebook' ),
			array( 'Self Certification', 'self-certification.pdf', 'doc-self-certification.jpg', 'Clipboard on a wooden desk' ),
			array( 'ADEK License', 'adek-license.pdf', 'doc-adek.jpg', 'Sheikh Zayed Grand Mosque, Abu Dhabi' ),
			// The current website links the commercial license PDF here too (open question #59).
			array( 'School Trade License', 'commercial-license.pdf', 'doc-trade-license.jpg', 'Abu Dhabi skyline seen from the water' ),
		);

		return array_map(
			fn( array $doc ): array => array(
				'title' => $doc[0],
				'file'  => $this->document( $doc[1], $doc[0] ),
				'image' => $this->image( $doc[2], $doc[3] ),
			),
			$docs
		);
	}

	/**
	 * Admissions page: process, fees, transport, uniform, registrations,
	 * withdrawals and admissions FAQs (FAQ posts in the "Admissions" topic).
	 */
	private function seed_admissions(): void {
		$register = $this->link( 'register-now', 'Apply Now' );
		$link     = static fn( string $title, string $url = '#' ): array => array(
			'url'    => $url,
			'title'  => $title,
			'target' => '',
		);

		// Admissions FAQs. Only the first answer is in the design; the others
		// summarise content published elsewhere on the site.
		$topic = term_exists( 'Admissions', 'seps_faq_topic' ) ?: wp_insert_term( 'Admissions', 'seps_faq_topic' );
		$faqs  = array(
			array( 'What curriculum does the school follow?', 'We follow the CBSE (Central Board of Secondary Education) curriculum, offering a structured and well-balanced academic framework from KG I to Grade XII.' ),
			array( 'What is the admission process?', 'Complete the online registration form, then submit the printed form together with the documents listed under Admission Process on this page. Our admissions team will guide you through the next steps.' ),
			array( 'What are the school timings?', 'From Monday to Thursday the school day runs from the 7:25 AM assembly to 2:10 PM. Friday is a half day. The full timetable is on the Academics page.' ),
			array( 'Does the school offer extracurricular activities?', 'Yes. Students can take part in sports, arts, music, clubs and student council initiatives that complement classroom learning.' ),
			array( 'Is transportation available?', 'Yes. School transport is provided through Fast Rent a Car, our authorized transport provider. See Transport on this page for details and fees.' ),
		);
		$faq_ids = array();
		foreach ( $faqs as $index => [ $question, $answer ] ) {
			$id = $this->post(
				'seps_faq',
				'faq-admissions-' . ( $index + 1 ),
				array(
					'post_title'   => $question,
					'post_content' => $answer,
					'menu_order'   => 100 + $index,
				)
			);
			if ( is_array( $topic ) ) {
				wp_set_object_terms( $id, (int) $topic['term_id'], 'seps_faq_topic' );
			}
			$faq_ids[] = $id;
		}

		$list = static fn( array $items ): string => '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';

		$sections = array(
			array(
				'acf_fc_layout' => 'page_hero',
				'title'         => 'Begin Your Child’s Journey at SEPS',
				'text'          => 'Give your child a strong start with quality CBSE education in a supportive learning environment, where academic growth, values and holistic development go hand in hand.',
				'note'          => 'Admissions 2026–2027',
				'button'        => $register,
				'image'         => $this->image( 'admissions-hero.jpg', 'Graduate in cap and gown holding books' ),
				'decor'         => 'none',
			),
			array(
				'acf_fc_layout' => 'requirements',
				'heading'       => 'Admission Process',
				'intro'         => 'Dear Parent, welcome to Sunrise English Private School. We are pleased that you are considering SEPS for your child’s education.',
				'button'        => $register,
				'left_title'    => 'Age Requirements',
				'left_rows'     => array(
					array(
						'badge' => 'KG 1',
						'text'  => '4 years by 31st March 2026',
					),
					array(
						'badge' => 'KG 2',
						'text'  => '5 years by 31 March 2026, or passed KG 1 within the UAE under MOE recognition',
					),
					array(
						'badge' => 'Grade 1',
						'text'  => '6 years by 31 March 2026, or passed KG 2 within the UAE under MOE recognition',
					),
					array(
						'badge' => 'Grade 2+',
						'text'  => 'Based on the Transfer Certificate and Final Report Card of the previous year, duly attested and stamped by the Ministry of Foreign Affairs and the Embassy in the country of origin.',
					),
				),
				'right_title'   => 'Documents Required',
				'right_items'   => implode(
					"\n",
					array(
						'Printed online registration form',
						'2 photographs',
						'Valid Emirates ID copies of the student and parents',
						'Attested Transfer Certificate, for Grade 2 and above',
						'Final report card of the last grade attended',
						'Vaccination record',
						'Electricity/water bill or tenancy contract mentioning the premises ID',
						'Passport and residence visa copies of the student/parent',
						'Attested birth certificate',
					)
				),
			),
			array(
				'acf_fc_layout' => 'info_cards',
				'background'    => 'navy',
				'corner'        => 1,
				'heading'       => 'Fee Structure',
				'intro'         => '<p>We keep our fee information clear and accessible for families. Find details on fee terms, payment timings, accepted payment methods and bank transfer information below.</p>',
				'button'        => array(
					'url'    => (string) wp_get_attachment_url( $this->document( 'fee-structure-2026-27.pdf', 'Fee Structure' ) ),
					'title'  => 'View Fee Structure 2026–27',
					'target' => '_blank',
				),
				'icon_style'    => 'above',
				'cards'         => array(
					array(
						'icon'  => 'payment',
						'title' => 'Modes of Payment',
						'width' => 'full',
						'text'  => '<p>Payment can be made by <strong>cash at the school counter, current-dated cheque or bank transfer.</strong> For bank transfers, please mention the student\'s name, grade and section as the reference.</p>',
						'rows'  => array(),
					),
					array(
						'icon'  => 'calendar',
						'title' => 'Three Terms a Year',
						'width' => 'half',
						'text'  => '<p>School fees are collected in three terms: <strong>April, October and January</strong>. Part-payment of fees is not permitted.</p>',
						'rows'  => array(),
					),
					array(
						'icon'  => 'clock',
						'title' => 'Collection Hours',
						'width' => 'half',
						'text'  => '<p><strong>Sunday to Thursday</strong>: 8:00 AM – 2:00 PM<br><strong>Friday &amp; Saturday</strong>: 8:00 AM – 12:00 PM</p>',
						'rows'  => array(),
					),
					array(
						'icon'  => '',
						'title' => 'Bank Transfer Details',
						'width' => 'full',
						'text'  => '',
						'rows'  => array(
							array(
								'label' => 'Account Name',
								'value' => 'Sunrise English Private School',
							),
							array(
								'label' => 'Bank Name',
								'value' => 'Abu Dhabi Commercial Bank',
							),
							array(
								'label' => 'Account No.',
								'value' => '252369920001',
							),
							array(
								'label' => 'IBAN',
								'value' => 'AE980030000252369920001',
							),
						),
					),
				),
				'image'         => $this->image( 'admissions-fee.jpg', 'Students studying together in the library' ),
				'image_style'   => 'right',
			),
			array(
				'acf_fc_layout' => 'text_image',
				'background'    => 'white',
				'heading'       => 'Transport',
				'content'       => '<p>Sunrise English Private School provides safe and reliable transportation through <strong>Fast Rent a Car,</strong> our authorized transport provider. Students should arrive at their pick-up point at least 5 minutes before the scheduled bus time.</p><p>For <strong>KG1 to Grade 6</strong>, an authorized adult with a Guardian Card must be present at drop-off. Alternative drop-off and later return requests require prior written approval and are subject to route and seat availability.</p>',
				'buttons'       => array(
					array(
						'link'  => $link( 'View Transport Fee' ),
						'style' => 'accent',
					),
					array(
						'link'  => $link( 'View Transport Fee Policy' ),
						'style' => 'primary',
					),
				),
				'image'         => $this->image( 'admissions-transport.jpg', 'Yellow school bus on the road' ),
				'image_size'    => 'narrow',
				'decor'         => 'flower',
			),
			array(
				'acf_fc_layout' => 'info_cards',
				'background'    => 'lilac',
				'corner'        => 0,
				'heading'       => 'Uniform',
				'intro'         => '<p>All students at Sunrise English Private School must wear a <strong>clean, neatly ironed and complete school uniform</strong>, maintaining a smart and presentable appearance.</p>',
				'icon_style'    => 'inline',
				'cards'         => array(
					array(
						'icon'  => 'boy',
						'title' => 'Boys',
						'width' => 'half',
						'text'  => $list( array( 'Fancy shoes are not allowed', 'Hair must be short and neatly trimmed', 'Full-sleeve shirts and round-neck inner T-shirts are not allowed', 'Shirts must be neatly tucked in' ) ),
						'rows'  => array(),
					),
					array(
						'icon'  => 'girl',
						'title' => 'Girls',
						'width' => 'half',
						'text'  => $list( array( 'Short hair must not fall on the brow or face', 'Long hair must be worn in double plaits with black ribbons', 'Flowers or decorative ornaments are not allowed' ) ),
						'rows'  => array(),
					),
					array(
						'icon'  => '',
						'title' => 'Regular Uniform',
						'width' => 'half',
						'text'  => '<p><strong>For Boys</strong>: Dark grey classic trousers, sky blue shirt with school logo, school tie, black belt, navy blue socks and black shoes.</p><p><strong>For Girls</strong>: Sky blue shirt, blue and white checked pinafore with school logo, navy blue socks and black ribbon.</p>',
						'rows'  => array(),
					),
					array(
						'icon'  => '',
						'title' => 'Same for Girls & Boys',
						'width' => 'half',
						'text'  => '<p><strong>Sports Uniform</strong>: White polo shirt with house colour and school logo, navy blue track pants with house colour lining, navy blue socks and white canvas shoes.</p><p><strong>Winter Jacket</strong>: Navy blue fleece jacket.</p>',
						'rows'  => array(),
					),
				),
				'image'         => $this->image( 'admissions-uniform.jpg', 'Teacher writing on a chalkboard' ),
				'image_style'   => 'circle',
			),
			array(
				'acf_fc_layout' => 'text_image',
				'background'    => 'navy',
				'heading'       => 'Registrations',
				'content'       => '<p>Stay informed about the registration and admission timelines for the <strong>2026–2027 academic year</strong>, including registration dates, interviews, document submission, entrance tests and fee payment.</p>',
				'buttons'       => array(
					array(
						'link'  => $this->link( 'register-now', 'View Registration' ),
						'style' => 'accent',
					),
				),
				'image'         => $this->image( 'admissions-registration.jpg', 'Parent reading with a child in a library' ),
				'image_size'    => 'wide',
				'decor'         => 'halves',
			),
			array(
				'acf_fc_layout' => 'checklist',
				'heading'       => 'Withdrawals',
				'notice_icon'   => 'alert',
				'notice_title'  => 'Withdrawal Notice',
				'notice_text'   => '<p>Parents who wish to withdraw their ward from the school at any time must submit a <strong>TC application in the prescribed format</strong> available on the school website.</p>',
				'notice_image'  => $this->image( 'admissions-withdrawal-notice.jpg', 'Three students studying outdoors with a laptop' ),
				'list_title'    => 'Application Checklist',
				'list_intro'    => '<p>Please ensure all required documents are ready before submitting the application. <strong>Incomplete applications cannot be processed.</strong></p>',
				'list'          => $list(
					array(
						'Clear all pending dues before submitting the form.',
						'<strong>N.O.C. from the new school</strong> is required for transfers within Abu Dhabi, Al Ain or the Western Region.',
						'TCs are issued within <strong>10 working days</strong> during the academic year.',
						'Post-academic-year applications must be submitted by the <strong>second week of January.</strong>',
						'Early TC requests require <strong>ADEK approval.</strong>',
						'Mid-session discontinuation requires <strong>Ministry approval.</strong>',
						'The student\'s attendance must continue until the last date stated on the TC form.',
						'TCs for use outside Abu Dhabi require <strong>Abu Dhabi Education Council attestation.</strong>',
						'TC issuance is subject to <strong>seat availability.</strong>',
						'TC requests are suspended during summer vacation; submit requests <strong>15 days before school closure</strong> if discontinuing after the break.',
						'For <strong>Grades X &amp; XII</strong>, TCs are issued after results are declared and ADEK approval is received.',
					)
				),
				'button'        => $link( 'Download TC Application Form' ),
				'image'         => $this->image( 'admissions-withdrawal.jpg', 'Student listening to a teacher in class' ),
			),
			array(
				'acf_fc_layout' => 'faq',
				'background'    => 'white',
				'heading'       => "Frequently Asked\nQuestions",
				'image'         => $this->image( 'admissions-faq.jpg', 'Three students studying on a bench outdoors' ),
				'items'         => $faq_ids,
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['admissions'] );
	}

	/**
	 * Student Life page: hero, student council, activities, achievements,
	 * media gallery and campus tour.
	 */
	private function seed_student_life(): void {
		$link = static fn( string $title, string $url = '#' ): array => array(
			'url'    => $url,
			'title'  => $title,
			'target' => '',
		);
		$card = fn( string $image, string $alt, string $title, string $text = '', ?array $url = null ): array => array(
			'image' => $this->image( $image, $alt ),
			'title' => $title,
			'text'  => $text,
			'link'  => $url,
		);

		$years = array(
			array( 'student-life-year-1.jpg', 'Curved library bookshelves', '2023–24' ),
			array( 'student-life-year-2.jpg', 'Students working in a classroom', '2024–25' ),
			array( 'student-life-year-3.jpg', 'Graduates throwing their caps in the air', '2025–26' ),
		);

		$achievements = array(
			'Celebrating the achievements and milestones of our students from the 2023–24 academic year.',
			'Discover the academic, creative, sporting and extracurricular achievements from 2024–25.',
			'Explore the latest achievements, milestones and accomplishments from the 2025–26 academic year.',
		);

		// As in the design (the year labels and descriptions do not match; see OPEN-QUESTIONS.md).
		$gallery = array(
			'Memorable moments from the 2022–23 academic year.',
			'A glimpse into school life and memorable moments from 2023–24.',
			'Explore moments from classrooms, activities, events and celebrations from 2024–25.',
		);

		// Achievements and gallery: one card per school year, each with a "View" button.
		$year_cards = static fn( array $texts ): array => array_map(
			static fn( array $year, string $text ): array => $card( $year[0], $year[1], $year[2], $text, $link( 'View' ) ),
			$years,
			$texts
		);

		$sections = array(
			array(
				'acf_fc_layout' => 'page_hero',
				'title'         => 'More Than a School Day',
				'text'          => 'From learning and leadership to sports, activities and friendships, SEPS gives students the space to discover their strengths and make the most of their school years.',
				'note'          => '',
				'button'        => $link( 'Learn More', '#student-council' ),
				'image'         => $this->image( 'student-life-hero.jpg', 'Young graduate in cap and gown making a heart with her hands' ),
				'image_focus'   => 'top',
				'decor'         => 'flower',
			),
			array(
				'acf_fc_layout' => 'card_list',
				'background'    => 'lilac',
				'heading'       => 'Student Council 2025–2026',
				'intro'         => '<p>We warmly congratulate the elected members of the School Cabinet and Council of Ministers for the 2025–2026 academic year.</p><p>Your election reflects the leadership qualities you have demonstrated and the trust your peers have placed in you. As you take on these responsibilities, lead with integrity, serve by example and uphold the values and standards of our school community.</p><p>We look forward to the positive changes, ideas and initiatives you will bring to SEPS during your tenure.</p>',
				'signature'     => "With appreciation,\nFaculty & Staff, Sunrise English Private School",
				'button'        => null,
				'display'       => 'carousel',
				'columns'       => '4',
				'image_height'  => 'medium',
				'panel'         => 1,
				'panel_title'   => 'Elected Cabinet & Council of Ministers',
				'cards'         => array(
					$card( 'student-life-council-1.jpg', 'Utsukta Gurung', 'Utsukta Gurung', 'Home Minister' ),
					$card( 'student-life-council-2.jpg', 'Annmiya Aby', 'Annmiya Aby', 'Health Minister' ),
					$card( 'student-life-council-3.jpg', 'Samit TV', 'Samit TV', 'Culture Minister' ),
					$card( 'student-life-council-4.jpg', 'Shreyas Sanil', 'Shreyas Sanil', 'Sports Minister' ),
				),
				'image'         => 0,
				'decor'         => 'none',
				'anchor'        => 'student-council',
			),
			array(
				'acf_fc_layout' => 'card_list',
				'background'    => 'white',
				'heading'       => 'Education, National Identity & Activities',
				'intro'         => '<p>Learning at SEPS extends beyond the classroom through cultural celebrations, national occasions, creative activities and community initiatives that help students explore their talents and build a strong sense of identity.</p>',
				'signature'     => '',
				'button'        => $link( 'Read More', get_permalink( $this->pages['news-events'] ) ),
				'display'       => 'carousel',
				'columns'       => '4',
				'image_height'  => 'small',
				'panel'         => 1,
				'panel_title'   => '',
				'cards'         => array(
					$card( 'student-life-activity-1.jpg', 'Students pointing at a world map with flags', 'GCC Exhibition Report 2024' ),
					$card( 'student-life-activity-2.jpg', 'Students visiting an art exhibition', 'National Day Report 2023' ),
					$card( 'student-life-activity-3.jpg', 'Flag flying against the sky', 'Flag Day Assembly 2023' ),
					$card( 'student-life-activity-4.jpg', 'Children working together on a flags project', 'Emirates Red Crescent' ),
				),
				'image'         => $this->image( 'student-life-activities.jpg', 'Young student drawing with a pencil' ),
				'decor'         => 'none',
			),
			array(
				'acf_fc_layout' => 'card_list',
				'background'    => 'navy',
				'heading'       => "Celebrating Our Achievements\nThrough the Years",
				'intro'         => '<p>Every achievement tells a story of effort, talent and determination. Explore the milestones and accomplishments that have shaped the Sunrise English Private School community through the years.</p>',
				'signature'     => '',
				'button'        => $link( 'View Teachers’ Achievements 2025–26' ),
				'display'       => 'carousel',
				'columns'       => '3',
				'image_height'  => 'large',
				'panel'         => 0,
				'panel_title'   => '',
				'cards'         => $year_cards( $achievements ),
				'image'         => 0,
				'decor'         => 'sun',
			),
			array(
				'acf_fc_layout' => 'card_list',
				'background'    => 'lilac',
				'heading'       => 'Media Gallery',
				'intro'         => '<p>Every school year brings moments worth remembering. Explore our gallery of classrooms, celebrations, activities and everyday experiences that make life at SEPS memorable.</p>',
				'signature'     => '',
				'button'        => null,
				'display'       => 'grid',
				'columns'       => '3',
				'image_height'  => 'large',
				'panel'         => 0,
				'panel_title'   => '',
				'cards'         => $year_cards( $gallery ),
				'image'         => 0,
				'decor'         => 'none',
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'white',
				'heading'        => 'Discover Our Campus',
				'content'        => '<p>Step inside a learning environment built for more than the classroom, with science labs, robotics, art and music rooms, a library and spaces for learning, activities and student life.</p><p>Come see the SEPS learning environment for yourself and discover the spaces where students learn, create and grow.</p>',
				'buttons'        => array(
					array(
						'link'  => $this->link( 'contact', 'Contact Us' ),
						'style' => 'primary',
					),
				),
				'image'          => $this->image( 'student-life-campus.jpg', 'The Sunrise English Private School building' ),
				'image_size'     => 'large',
				'image_position' => 'left',
				'decor'          => 'none',
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['student-life'] );
	}

	/**
	 * News & Events page (the posts page): hero, SEPS Chronicle, ADEK
	 * calendar, media gallery and "Love for the UAE" posts. The list of all
	 * posts follows the sections automatically (home.php).
	 *
	 * Adds the "Love for the UAE" category to three of the seeded news posts.
	 */
	private function seed_news_events(): void {
		$link = static fn( string $title, string $url = '#' ): array => array(
			'url'    => $url,
			'title'  => $title,
			'target' => '',
		);

		$term = term_exists( 'Love for the UAE', 'category' ) ?: wp_insert_term( 'Love for the UAE', 'category' );
		$uae  = is_array( $term ) ? (int) $term['term_id'] : 0;

		if ( $uae ) {
			foreach ( array( 'news-2', 'news-3', 'news-4' ) as $key ) {
				$id = $this->find( 'post', $key );
				if ( $id ) {
					wp_set_post_categories( $id, array( $uae ), true );
				}
			}
		}

		$news     = term_exists( 'News', 'category' );
		$news_url = is_array( $news ) ? get_category_link( (int) $news['term_id'] ) : '#';
		$gallery  = array(
			array( 'news-events-gallery-1.jpg', 'Empty classroom with desks and a chalkboard', '2022–23', 'Memorable moments from the 2022–23 academic year.' ),
			array( 'news-events-gallery-2.jpg', 'Students raising their hands in class', '2023–24', 'A glimpse into school life and memorable moments from 2023–24.' ),
			array( 'news-events-gallery-3.jpg', 'Students listening in a lecture hall', '2024–25', 'Explore moments from classrooms, activities, events and celebrations from 2024–25.' ),
		);

		$sections = array(
			array(
				'acf_fc_layout' => 'page_hero',
				'title'         => 'Where SEPS Comes to Life',
				'text'          => 'Stay connected with the latest news, events and stories from Sunrise English Private School, from academic activities and student achievements to celebrations, competitions and community initiatives.',
				'note'          => '',
				'button'        => $link( 'View News', '#all-news' ),
				'button_2'      => $link( 'Explore News', (string) $news_url ),
				'image'         => $this->image( 'news-events-hero.jpg', 'Young children raising their hands in class' ),
				'image_focus'   => 'center',
				'decor'         => 'none',
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'lilac',
				'heading'        => 'SEPS Chronicle',
				'content'        => '<p>A collection of the moments, milestones and stories that make life at SEPS memorable.</p><p>Explore student achievements, school events, activities and celebrations that showcase the many ways our community learns, participates and grows together.</p>',
				'buttons'        => array(
					array(
						'link'  => $link( 'View SEPS Chronicle' ),
						'style' => 'primary',
					),
				),
				'image'          => $this->image( 'news-events-chronicle.jpg', 'Students talking with a teacher in the library' ),
				'image_size'     => 'circle',
				'image_position' => 'left',
				'decor'          => 'flower_photo',
				'corner'         => 1,
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'white',
				'heading'        => 'ADEK School Calendar',
				'content'        => '<p>Stay up to date with important dates across the school year, including academic terms, holidays and key school milestones.</p><p>Keep the calendar handy to plan ahead and stay informed throughout the year.</p>',
				'buttons'        => array(
					array(
						'link'  => $link( 'View Calendar' ),
						'style' => 'primary',
					),
				),
				'image'          => $this->image( 'news-events-calendar.jpg', 'Close-up of a printed calendar' ),
				'image_size'     => 'wide',
				'image_position' => 'right',
				'decor'          => 'none',
				'corner'         => 0,
			),
			array(
				'acf_fc_layout' => 'card_list',
				'background'    => 'navy',
				'heading'       => 'Media Gallery',
				'intro'         => '<p>Every school year brings moments worth remembering. Explore our gallery of classrooms, celebrations, activities and everyday experiences that make life at SEPS memorable.</p>',
				'signature'     => '',
				'button'        => null,
				'display'       => 'grid',
				'columns'       => '3',
				'image_height'  => 'large',
				'panel'         => 0,
				'panel_title'   => '',
				'cards'         => array_map(
					fn( array $row ): array => array(
						'image' => $this->image( $row[0], $row[1] ),
						'title' => $row[2],
						'text'  => $row[3],
						'link'  => $link( 'View' ),
					),
					$gallery
				),
				'image'         => 0,
				'decor'         => 'halves',
			),
			array(
				'acf_fc_layout' => 'posts',
				'heading'       => 'Love for the UAE',
				'intro'         => '<p>Explore how our students express their appreciation for the UAE through stories, poems, creative work and reflections on its culture, heritage and national identity.</p><p>Discover student voices that celebrate the country they call home and the values that connect our school community to the UAE.</p>',
				'button'        => $link( 'View Blogs', $uae ? (string) get_category_link( $uae ) : '#' ),
				'category'      => $uae,
				'count'         => 3,
				'image'         => $this->image( 'news-events-uae.jpg', 'Student presenting her work while a classmate raises a hand' ),
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['news-events'] );
	}

	/**
	 * Contact page: hero, enquiry form, contact details and map.
	 */
	private function seed_contact(): void {
		$lines = static fn( array $items ): string => '<p>' . implode( '<br>', $items ) . '</p>';
		$query = rawurlencode( 'Sunrise English Private School, Mussafah, Abu Dhabi' );

		$sections = array(
			array(
				'acf_fc_layout' => 'page_hero',
				'title'         => 'We’re Here to Connect, Support & Stay in Touch',
				'text'          => 'Have a question about admissions, academics, school life or anything else at SEPS? Get in touch with our team and we’ll be happy to help.',
				'note'          => '',
				'button'        => array(
					'url'    => '#enquiry-form',
					'title'  => 'Send an Enquiry',
					'target' => '',
				),
				'button_2'      => null,
				'image'         => $this->image( 'contact-hero.jpg', 'Child writing in a workbook at a wooden desk' ),
				'image_focus'   => 'center',
				'layout'        => 'wide',
				'decor'         => 'flower',
			),
			array(
				'acf_fc_layout' => 'contact_form',
				'heading'       => 'We’d Love to Hear From You',
				'intro'         => 'Have a question or need more information about SEPS? Send us your enquiry, and our team will get back to you as soon as possible.',
				'form'          => $this->enquiry_form(),
				'image'         => $this->image( 'contact-enquiry.jpg', 'Grandfather and grandson reading together' ),
			),
			array(
				'acf_fc_layout' => 'info_cards',
				'background'    => 'white',
				'corner'        => 0,
				'compact'       => 1,
				'heading'       => 'Reach Us Directly',
				'intro'         => '<p>Find the essential contact details for Sunrise English Private School, including our phone, email, address and office and school hours.</p>',
				'button'        => null,
				'icon_style'    => 'inline',
				'cards'         => array(
					array(
						'icon'  => 'call',
						'title' => 'Phone & Email',
						'width' => 'half',
						'text'  => '<p><a href="tel:+97125529989">+971 2 552 9989</a><br><a href="mailto:school@seps-auh.com">school@seps-auh.com</a></p>',
						'rows'  => array(),
					),
					array(
						'icon'  => 'clock',
						'title' => 'Office Hours - Accounts only',
						'width' => 'half',
						'text'  => $lines( array( 'Monday–Thursday: 7:00 AM–3:00 PM', 'Friday: 8:00 AM–1:00 PM', 'Saturday: 8:00 AM–11:00 AM' ) ),
						'rows'  => array(),
					),
					array(
						'icon'  => 'heart',
						'title' => 'Postal Address',
						'width' => 'half',
						'text'  => $lines( array( 'Shabiya, ME-09,', 'Near Safeer Mall, Mussafah', 'P.O. Box 71356, Abu Dhabi,', 'United Arab Emirates' ) ),
						'rows'  => array(),
					),
					array(
						'icon'  => 'payment',
						'title' => 'School Hours',
						'width' => 'half',
						'text'  => $lines( array( 'KG: Monday–Friday, 7:30 AM–11:30 AM', 'Grades 1–12: Monday–Thursday, 7:30 AM–2:00 PM', 'Friday: 7:30 AM–11:30 AM' ) ),
						'rows'  => array(),
					),
				),
				'image'         => 0,
				'image_style'   => 'right',
			),
			array(
				'acf_fc_layout' => 'map',
				'heading'       => 'Our Location',
				'intro'         => 'Find Sunrise English Private School on the map and get directions to our campus.',
				'button'        => array(
					'url'    => 'https://www.google.com/maps/search/?api=1&query=' . $query,
					'title'  => 'View Location',
					'target' => '_blank',
				),
				'embed_url'     => 'https://www.google.com/maps?q=' . $query . '&output=embed',
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['contact'] );
	}

	/**
	 * Careers page: hero and teaching / non-teaching roles.
	 */
	private function seed_careers(): void {
		$link = static fn( string $title, string $url = '#' ): array => array(
			'url'    => $url,
			'title'  => $title,
			'target' => '',
		);

		$sections = array(
			array(
				'acf_fc_layout'  => 'page_hero',
				'title'          => "Build Your Career\nat SEPS",
				'text'           => 'Join a school community committed to quality education, where educators and staff can contribute their expertise, grow professionally and make a meaningful difference in students’ lives.',
				'note'           => '',
				'button'         => $link( 'Learn More', '#roles' ),
				'button_2'       => null,
				'image'          => $this->image( 'careers-hero.jpg', 'Graduates in caps and gowns on campus' ),
				'image_focus'    => 'center',
				'layout'         => 'standard',
				'decor'          => 'sun',
				'decor_position' => 'top',
			),
			array(
				'acf_fc_layout' => 'stacked_cards',
				'background'    => 'white',
				'heading'       => 'Teaching & Academic Roles',
				'intro'         => 'Explore opportunities to join SEPS’s teaching and academic teams and contribute to students’ learning and development.',
				'image'         => $this->image( 'careers-roles.jpg', 'Smiling graduate in a cap and gown' ),
				'cards'         => array(
					array(
						'image' => $this->image( 'careers-academic.jpg', 'Teacher at the board with students raising hands' ),
						'title' => 'Academic Careers',
						'text'  => 'Bring your subject expertise and passion for education to the SEPS teaching community.',
						'link'  => null,
					),
					array(
						'image' => $this->image( 'careers-non-academic.jpg', 'Staff member helping a student with her work' ),
						'title' => 'Non-Academic Careers',
						'text'  => 'Explore roles that support the school’s wider operations and help our community thrive.',
						'link'  => null,
					),
				),
				'button'        => $this->link( 'careers-application', 'View Application Form' ),
				'anchor'        => 'roles',
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['careers'] );
	}

	/**
	 * Register Now page: hero and the registration form (Contact Form 7).
	 */
	private function seed_registration(): void {
		$sections = array(
			array(
				'acf_fc_layout'  => 'page_hero',
				'title'          => 'Register Your Child at SEPS',
				'text'           => 'Begin your child’s journey with Sunrise English Private School. Complete the registration form below and our admissions team will contact you with the next steps.',
				'note'           => '',
				'button'         => null,
				'button_2'       => null,
				'image'          => $this->image( 'contact-hero.jpg', 'Child writing in a workbook at a wooden desk' ),
				'image_focus'    => 'center',
				'layout'         => 'wide',
				'decor'          => 'flower',
				'decor_position' => 'auto',
			),
			array(
				'acf_fc_layout' => 'contact_form',
				'layout'        => 'full',
				'heading'       => 'Registration Form',
				'intro'         => '<p>Fields marked <span class="enquiry__req">*</span> are required. Already have an account? <a href="#"><strong>Sign In</strong></a></p>',
				'form'          => $this->registration_form(),
				'image'         => 0,
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['register-now'] );
	}

	/**
	 * Careers Application page: hero and the job application form (Contact
	 * Form 7, with CV and cover letter uploads).
	 */
	private function seed_careers_application(): void {
		$sections = array(
			array(
				'acf_fc_layout'  => 'page_hero',
				'title'          => "Apply to Join\nOur Team",
				'text'           => 'Whether you teach or support our school’s operations, we’d love to hear from you. Complete the application below and our HR team will be in touch if your profile matches a current opening.',
				'note'           => '',
				'button'         => null,
				'button_2'       => null,
				'image'          => $this->image( 'careers-hero.jpg', 'Graduates in caps and gowns on campus' ),
				'image_focus'    => 'center',
				'layout'         => 'standard',
				'decor'          => 'sun',
				'decor_position' => 'top',
			),
			array(
				'acf_fc_layout' => 'contact_form',
				'layout'        => 'full',
				'heading'       => 'Application Form',
				'intro'         => '<p>Fields marked <span class="enquiry__req">*</span> are required. Fields tagged <strong>Academic roles</strong> only apply to teaching positions.</p>',
				'form'          => $this->application_form(),
				'image'         => 0,
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['careers-application'] );
	}

	/**
	 * Privacy Policy and Terms & Conditions: a text-only title band and the
	 * policy text.
	 *
	 * A starting draft written for this website (UAE / Abu Dhabi, the
	 * site's forms and services). The school must have it reviewed before
	 * launch; see OPEN-QUESTIONS.md.
	 */
	private function seed_legal(): void {
		$updated = 'Last updated: 24 November 2026';
		$policy  = esc_url( (string) get_permalink( $this->pages['privacy-policy'] ) );
		$contact = '<p><strong>Sunrise English Private School</strong><br>P.O. Box 71356, Shabiya, ME-09, Near Safeer Mall, Mussafah, Abu Dhabi, United Arab Emirates<br>Email: <a href="mailto:school@seps-auh.com">school@seps-auh.com</a><br>Phone: <a href="tel:+97125529989">+971 2 552 9989</a></p>';
		$hero    = static fn( string $title, string $text ) => array(
			'acf_fc_layout'  => 'page_hero',
			'title'          => $title,
			'text'           => $text,
			'note'           => $updated,
			'button'         => null,
			'button_2'       => null,
			'image'          => 0,
			'image_focus'    => 'upper',
			'layout'         => 'standard',
			'decor'          => 'none',
			'decor_position' => 'auto',
		);
		$text    = static fn( string $html ) => array(
			'acf_fc_layout' => 'text',
			'heading'       => '',
			'content'       => $html,
		);

		$privacy = <<<HTML
<p>Sunrise English Private School (“SEPS”, “the school”, “we”, “us”) respects your privacy. This policy explains what personal information we collect through this website, how we use and protect it, and the rights you have. It applies to information collected through this website, including the contact, registration and job application forms.</p>
<p>We handle personal information in line with the UAE Personal Data Protection Law (Federal Decree-Law No. 45 of 2021) and the requirements of the Abu Dhabi Department of Education and Knowledge (ADEK).</p>

<h2>1. Who we are</h2>
<p>Sunrise English Private School is a private CBSE school in Abu Dhabi, United Arab Emirates. The school is responsible for the personal information collected through this website. You can contact us using the details in section 12.</p>

<h2>2. Information we collect</h2>
<h3>Information you give us</h3>
<ul>
<li><strong>Enquiry form (Contact page):</strong> your name, phone number, email address, the subject of your enquiry, your preferred contact time and your message.</li>
<li><strong>Registration form (Register Now):</strong> details about the student, including name, gender, date and place of birth, nationality, religion, mother tongue, blood group, area of residence, eSIS number, previous school, passport, visa and Emirates ID details; details of any siblings at SEPS; and details of the parents or guardians, including names, nationality, occupation, qualification, organisation, Emirates ID details, email addresses and mobile numbers.</li>
<li><strong>Job application form (Careers):</strong> the position you are applying for, your name, email address, mobile number and date of birth, details of your most recent role (including remuneration), your answers to our additional questions (including vaccination status and ADEK approval), and your CV and cover letter.</li>
<li><strong>Emails and calls:</strong> anything you choose to tell us when you contact us directly.</li>
</ul>
<p>Some of this information is sensitive, such as a student’s religion and blood group, an applicant’s vaccination status, and passport and Emirates ID details. We ask for it only because it is needed for admission, for the student’s welfare, for recruitment, or because the school is required to collect it, and we restrict who can see it.</p>
<h3>Information collected automatically</h3>
<p>Like most websites, our web server records technical information when you visit, such as your IP address, browser type and the pages you request. We use it to keep the website secure and working properly.</p>

<h2>3. How we use your information</h2>
<ul>
<li>To answer your enquiries and contact you at your preferred time.</li>
<li>To process registrations and admissions, including assessing applications, communicating with parents and meeting ADEK and Ministry of Education requirements.</li>
<li>To consider job applications and contact applicants.</li>
<li>To keep the website and our systems secure, prevent spam and misuse, and fix problems.</li>
<li>To meet our legal and regulatory obligations.</li>
</ul>
<p>We use personal information because it is necessary to respond to your request or to take steps you have asked us to take (such as a registration or application), because the law requires it, or with your consent. Where we rely on your consent, you can withdraw it at any time.</p>
<p>We do not sell your personal information, and we do not use it for advertising.</p>

<h2>4. Who we share it with</h2>
<p>We share personal information only when we need to:</p>
<ul>
<li><strong>School staff</strong> who need it to handle your enquiry, registration or application.</li>
<li><strong>Government and education authorities</strong>, such as ADEK and the Ministry of Education, where the law or our licence requires it.</li>
<li><strong>Service providers</strong> who help us run the website and our communications, such as our website hosting provider and email services. They may only use the information to provide their services to us.</li>
<li><strong>Others where the law requires it</strong>, for example to comply with a court order or to protect the safety of students and staff.</li>
</ul>

<h2>5. Cookies and third-party content</h2>
<p>We do not use advertising or analytics cookies. Our website uses only the cookies it needs to work and to keep it secure.</p>
<p>The Contact page shows an embedded Google Map. When it loads, Google may set its own cookies and collect information about your visit, as described in <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google’s Privacy Policy</a>. Links to other websites, such as our parent and student portals, are covered by those websites’ own privacy policies.</p>

<h2>6. Where your information is stored</h2>
<p>Our website and email services are provided by third parties, and some of their servers may be located outside the United Arab Emirates. Where personal information is transferred outside the UAE, we take steps to make sure it is protected to the standard required by UAE law.</p>

<h2>7. How long we keep it</h2>
<p>We keep personal information only for as long as we need it for the purpose it was collected for, or as long as the law requires. For example, records of enrolled students form part of the student record that the school must keep, while enquiries, and registrations and job applications that do not go ahead, are deleted once they are no longer needed.</p>

<h2>8. How we protect it</h2>
<p>Our website uses an encrypted (HTTPS) connection, access to submitted information is limited to authorised staff, and we use technical and organisational measures to protect personal information against loss, misuse and unauthorised access. No method of transmission over the internet is completely secure, but we work to protect the information you share with us.</p>

<h2>9. Children’s information</h2>
<p>Information about students is provided by their parents or legal guardians. Our forms are intended to be completed by adults, and we do not knowingly collect personal information directly from children through this website.</p>

<h2>10. Your rights</h2>
<p>Subject to UAE law, you have the right to:</p>
<ul>
<li>ask what personal information we hold about you and receive a copy of it;</li>
<li>ask us to correct information that is inaccurate or incomplete;</li>
<li>ask us to delete your information, or to restrict how we use it;</li>
<li>object to our use of your information;</li>
<li>withdraw your consent where we rely on it.</li>
</ul>
<p>To make a request, contact us using the details below. We may need to confirm your identity before we respond. Some requests may be limited where we are required by law to keep information, for example as part of a student’s school record. If you are not satisfied with our response, you may complain to the UAE Data Office.</p>

<h2>11. Changes to this policy</h2>
<p>We may update this policy from time to time. The date at the top of this page shows when it was last updated.</p>

<h2>12. Contact us</h2>
<p>For any question about this policy or your personal information, please contact:</p>
{$contact}
HTML;

		$terms = <<<HTML
<p>These terms apply to your use of the Sunrise English Private School (“SEPS”, “the school”, “we”, “us”) website. By using the website, you agree to them. If you do not agree, please do not use the website.</p>

<h2>1. About the website</h2>
<p>This website provides information about the school, its curriculum, admissions, school life and news, and lets you contact us, register your child and apply for jobs online. The school may change, suspend or withdraw any part of the website at any time.</p>

<h2>2. Accuracy of information</h2>
<p>We work to keep the information on this website accurate and up to date, but it is provided for general information only. Details such as fees, dates, timings, transport, policies and admission requirements may change and are confirmed only by the school directly. If anything is unclear, please contact us before relying on it.</p>

<h2>3. Registrations, enquiries and applications</h2>
<ul>
<li>When you submit a form, you confirm that the information you provide is accurate and complete and that you are entitled to provide it, including information about your child or other family members.</li>
<li>Submitting a registration does not guarantee a place at the school. Admission is subject to the school’s admission process, seat availability and ADEK requirements.</li>
<li>Submitting a job application does not guarantee an interview or employment.</li>
<li>Our <a href="{$policy}">Privacy Policy</a> explains how we handle the information you submit.</li>
</ul>

<h2>4. Intellectual property</h2>
<p>The content of this website, including text, photographs, graphics, the school name, logo and crest, belongs to the school or is used with permission. You may view and print pages for your personal, non-commercial use. You may not copy, reproduce, modify or publish any content for any other purpose without the school’s written permission.</p>

<h2>5. Acceptable use</h2>
<p>You must not use the website in any way that is unlawful or that could damage it or interfere with others’ use of it. In particular, you must not:</p>
<ul>
<li>submit false, misleading or someone else’s information;</li>
<li>send spam or upload files that contain viruses or other harmful code;</li>
<li>try to gain unauthorised access to the website, its servers or the information it holds;</li>
<li>use automated tools to collect information from the website.</li>
</ul>

<h2>6. Links to other websites</h2>
<p>The website links to other websites and services, such as our parent and student portals, fee payment, Google Maps and social media. We are not responsible for the content, availability or privacy practices of those websites, which have their own terms.</p>

<h2>7. Availability and liability</h2>
<p>We aim to keep the website available and secure, but we do not guarantee that it will always be available or free from errors or viruses. To the extent permitted by law, the school is not liable for any loss or damage arising from your use of the website or your reliance on its content. Nothing in these terms limits any liability that cannot be limited under UAE law.</p>

<h2>8. Governing law</h2>
<p>These terms are governed by the laws of the United Arab Emirates as applied in the Emirate of Abu Dhabi. Any dispute relating to them is subject to the jurisdiction of the courts of Abu Dhabi.</p>

<h2>9. Changes to these terms</h2>
<p>We may update these terms from time to time. The date at the top of this page shows when they were last updated. Continuing to use the website after a change means you accept the updated terms.</p>

<h2>10. Contact us</h2>
<p>For any question about these terms, please contact:</p>
{$contact}
HTML;

		update_field(
			'field_seps_page_sections',
			array(
				$hero( 'Privacy Policy', 'How Sunrise English Private School collects, uses and protects personal information shared through this website.' ),
				$text( $privacy ),
			),
			$this->pages['privacy-policy']
		);

		update_field(
			'field_seps_page_sections',
			array(
				$hero( 'Terms & Conditions', 'The terms that apply when you use the Sunrise English Private School website.' ),
				$text( $terms ),
			),
			$this->pages['terms']
		);
	}

	/**
	 * Create or update the Contact page enquiry form (Contact Form 7).
	 *
	 * @return int Form ID, or 0 when Contact Form 7 is not active.
	 */
	private function enquiry_form(): int {
		$subjects = array( 'Admissions', 'Academics', 'Fees & Payments', 'Transport', 'Student Life & Activities', 'General Enquiry' );
		$times    = array( 'Morning', 'Afternoon', 'Evening' );

		$template = implode(
			"\n\n",
			array(
				$this->form_field( 'Parent’s Name', '[text* your-name id:enquiry-name class:enquiry__control autocomplete:name maxlength:100 placeholder "Enter full name here"]', 'enquiry-name' ),
				$this->form_field( 'Phone Number', $this->form_phone( 'phone-code', 'your-phone', 'enquiry-phone', true, 'Enter phone number here' ), 'enquiry-phone' ),
				$this->form_field( 'Email Address', '[email* your-email id:enquiry-email class:enquiry__control autocomplete:email placeholder "Enter email address here"]', 'enquiry-email', 'enquiry__field--full' ),
				$this->form_field( 'Subject', '[select* your-subject id:enquiry-subject class:enquiry__control class:enquiry__select first_as_label "What is this regarding" ' . $this->form_options( $subjects ) . ']', 'enquiry-subject' ),
				$this->form_field( 'Preferred Contact Time', '[select contact-time id:enquiry-time class:enquiry__control class:enquiry__select ' . $this->form_options( $times ) . ']', 'enquiry-time' ),
				$this->form_field( 'Message', '[textarea* your-message id:enquiry-message class:enquiry__control class:enquiry__textarea maxlength:5000 placeholder "Tell us a bit about your enquiry..."]', 'enquiry-message', 'enquiry__field--full' ),
				'<div class="enquiry__actions">[submit class:btn class:btn--primary class:enquiry__submit "Send Enquiry"]</div>',
			)
		);

		return $this->cf7_form(
			'contact-enquiry',
			'Contact page enquiry',
			'contact',
			$template,
			array(
				'subject'            => 'New enquiry: [your-subject] ([your-name])',
				'additional_headers' => 'Reply-To: [your-name] <[your-email]>',
				'body'               => $this->mail_body(
					array(
						'Parent’s name'          => '[your-name]',
						'Phone'                  => '[phone-code] [your-phone]',
						'Email'                  => '[your-email]',
						'Subject'                => '[your-subject]',
						'Preferred contact time' => '[contact-time]',
						'Message'                => "\n[your-message]",
					),
					'the Contact page'
				),
			),
			array(
				'mail_sent_ok' => 'Thank you! Your enquiry has been sent. Our team will get back to you as soon as possible.',
				'mail_sent_ng' => 'Sorry, something went wrong and your enquiry was not sent. Please try again, or email us directly.',
				'spam'         => 'Sorry, something went wrong and your enquiry was not sent. Please try again, or email us directly.',
			)
		);
	}

	/**
	 * Create or update the registration form (Register Now page).
	 *
	 * @return int Form ID, or 0 when Contact Form 7 is not active.
	 */
	private function registration_form(): int {
		$req    = '<span class="enquiry__req" aria-hidden="true">*</span>';
		$text   = static fn( string $name, string $placeholder, bool $required = false, string $type = 'text', string $extra = '' ): string => sprintf(
			'[%1$s%2$s %3$s id:reg-%3$s class:enquiry__control%4$s placeholder "%5$s"]',
			$type,
			$required ? '*' : '',
			$name,
			$extra ? ' ' . $extra : '',
			$placeholder
		);
		$date   = static fn( string $name, bool $required = false ): string => sprintf( '[date%1$s %2$s id:reg-%2$s class:enquiry__control]', $required ? '*' : '', $name );
		$select = fn( string $name, string $placeholder, array $options, bool $required = false ): string => sprintf(
			'[select%1$s %2$s id:reg-%2$s class:enquiry__control class:enquiry__select first_as_label "%3$s" %4$s]',
			$required ? '*' : '',
			$name,
			$placeholder,
			$this->form_options( $options )
		);
		$radio  = fn( string $name, array $options ): string => sprintf( '[radio %1$s use_label_element %2$s]', $name, $this->form_options( $options ) );
		$f      = fn( string $label, string $tag, string $name, bool $required = false, string $class = '' ): string => $this->form_field( $label . ( $required ? ' ' . $req : '' ), $tag, 'reg-' . $name, $class );
		$choice = fn( string $label, string $tag ): string => $this->form_choice( $label . ' ' . $req, $tag );

		$grades   = array_merge( array( 'KG 1', 'KG 2' ), array_map( static fn( int $n ): string => 'Grade ' . $n, range( 1, 12 ) ) );
		$genders  = array( 'Male', 'Female' );
		$yes_no   = array( 'Yes', 'No' );
		$religion = array( 'Islam', 'Christianity', 'Hinduism', 'Buddhism', 'Sikhism', 'Jainism', 'Judaism', 'Other' );
		$blood    = array( 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-' );
		$areas    = array( 'Mussafah', 'Shabiya', 'Mohammed Bin Zayed City', 'Khalifa City', 'Baniyas', 'Al Shamkha', 'Al Reef', 'Al Reem Island', 'Abu Dhabi Island', 'Al Ain', 'Other' );

		// Up to three siblings; the second and third appear on "+ Add Another Sibling".
		$siblings = '';
		foreach ( range( 1, 3 ) as $n ) {
			$fields = implode(
				"\n",
				array(
					$f( 'Sibling Name', $text( "sibling-{$n}-name", 'Enter sibling’s name' ), "sibling-{$n}-name" ),
					$f( 'Grade', $select( "sibling-{$n}-grade", 'Select grade', $grades ), "sibling-{$n}-grade" ),
					$f( 'Gender', $select( "sibling-{$n}-gender", 'Select gender', $genders ), "sibling-{$n}-gender" ),
					$f( 'Enrolment Number', $text( "sibling-{$n}-enrolment", 'Enter enrolment number' ), "sibling-{$n}-enrolment" ),
					$f( 'School', $select( "sibling-{$n}-school", 'Select school', array( 'Sunrise English Private School, Abu Dhabi' ) ), "sibling-{$n}-school" ),
				)
			);
			$siblings .= 1 === $n
				? $fields . "\n"
				: sprintf( "<div class=\"enquiry__repeat\" data-repeat=\"siblings\" hidden>\n<h4 class=\"enquiry__subhead\">Sibling %d</h4>\n%s\n</div>\n", $n, $fields );
		}
		$siblings .= '<button type="button" class="enquiry__add" data-repeat-add="siblings" hidden>+ Add Another Sibling</button>';

		$parent = function ( string $who, string $title, bool $main ) use ( $f, $text, $date, $select, $choice, $radio, $yes_no ): string {
			return implode(
				"\n",
				array(
					"<h4 class=\"enquiry__subhead\">{$title}</h4>",
					$f( 'First Name', $text( "{$who}-first-name", 'Enter first name', true, 'text', 'autocomplete:given-name' ), "{$who}-first-name", true ),
					$f( 'Middle Name', $text( "{$who}-middle-name", 'Enter middle name', false, 'text', 'autocomplete:additional-name' ), "{$who}-middle-name" ),
					$f( 'Last Name', $text( "{$who}-last-name", 'Enter last name', true, 'text', 'autocomplete:family-name' ), "{$who}-last-name", true ),
					$choice( 'School Staff', $radio( "{$who}-staff", $yes_no ) ),
					$f( 'Occupation', $text( "{$who}-occupation", 'Enter occupation', $main ), "{$who}-occupation", $main ),
					$f( 'Qualification', $text( "{$who}-qualification", 'Enter qualification' ), "{$who}-qualification" ),
					$f( 'Nationality', $select( "{$who}-nationality", 'Select nationality', $this->countries(), true ), "{$who}-nationality", true ),
					$f( 'Organisation', $text( "{$who}-organisation", 'Enter organisation', $main ), "{$who}-organisation", $main ),
					$f( 'Emirates ID Number', $text( "{$who}-emirates-id", '784-XXXX-XXXXXXX-X', false, 'text', 'maxlength:18' ), "{$who}-emirates-id" ),
					$f( 'Emirates ID Expiry Date', $date( "{$who}-emirates-id-expiry" ), "{$who}-emirates-id-expiry" ),
					$f( 'Email Address', $text( "{$who}-email", 'Enter email address', true, 'email', 'autocomplete:email' ), "{$who}-email", true ),
					$f( 'Mobile Number', $this->form_phone( "{$who}-mobile-code", "{$who}-mobile", "reg-{$who}-mobile", true, 'Enter mobile number' ), "{$who}-mobile", true ),
				)
			);
		};

		$template = implode(
			"\n\n",
			array(
				$this->form_step(
					'01',
					'Academic Details',
					implode(
						"\n",
						array(
							$f( 'Academic Year', $select( 'academic-year', 'Select academic year', array( '2026–2027', '2027–2028' ), true ), 'academic-year', true ),
							$f( 'Grade', $select( 'grade', 'Select grade', $grades, true ), 'grade', true ),
						)
					)
				),
				$this->form_step(
					'02',
					'Student Details',
					implode(
						"\n",
						array(
							$f( 'First Name', $text( 'student-first-name', 'Enter first name', true ), 'student-first-name', true ),
							$f( 'Middle Name', $text( 'student-middle-name', 'Enter middle name' ), 'student-middle-name' ),
							$f( 'Last Name', $text( 'student-last-name', 'Enter last name', true ), 'student-last-name', true ),
							$choice( 'Gender', $radio( 'student-gender', $genders ) ),
							$f( 'Date of Birth', $date( 'student-dob', true ), 'student-dob', true ),
							$f( 'Place of Birth', $text( 'student-birthplace', 'Enter place of birth', true ), 'student-birthplace', true ),
							$f( 'Religion', $select( 'student-religion', 'Select religion', $religion, true ), 'student-religion', true ),
							$f( 'Mother Tongue', $text( 'student-mother-tongue', 'Enter mother tongue', true ), 'student-mother-tongue', true ),
							$f( 'Blood Group', $select( 'student-blood-group', 'Select blood group', $blood, true ), 'student-blood-group', true ),
							$f( 'Nationality', $select( 'student-nationality', 'Select nationality', $this->countries(), true ), 'student-nationality', true ),
							$f( 'Area of Residence (UAE)', $select( 'residence-area', 'Select area', $areas ), 'residence-area' ),
							$f( 'eSIS Number', $text( 'esis-number', 'Enter eSIS number' ), 'esis-number' ),
							$f( 'Previous School Name', $text( 'previous-school', 'Enter school name', true ), 'previous-school', true ),
							$f( 'Previous School Country', $text( 'previous-school-country', 'Enter country', true ), 'previous-school-country', true ),
							$f( 'Previous School Grade', $text( 'previous-school-grade', 'Enter grade' ), 'previous-school-grade' ),
						)
					)
				),
				$this->form_step(
					'03',
					'Passport &amp; Visa Details',
					implode(
						"\n",
						array(
							$f( 'Passport Number', $text( 'passport-number', 'Enter passport number', true ), 'passport-number', true ),
							$f( 'Passport Issue Date', $date( 'passport-issue-date', true ), 'passport-issue-date', true ),
							$f( 'Passport Expiry Date', $date( 'passport-expiry-date', true ), 'passport-expiry-date', true ),
							$f( 'Passport Place of Issue', $text( 'passport-place', 'Enter place of issue' ), 'passport-place' ),
							$f( 'Visa Number', $text( 'visa-number', 'Enter visa number' ), 'visa-number' ),
							$f( 'Visa Valid Until', $date( 'visa-valid-until' ), 'visa-valid-until' ),
							$f( 'Emirates ID Number', $text( 'student-emirates-id', '784-XXXX-XXXXXXX-X', false, 'text', 'maxlength:18' ), 'student-emirates-id' ),
						)
					)
				),
				$this->form_step( '04', 'Sibling Information', $siblings, '(if any sibling studies at SEPS)' ),
				$this->form_step(
					'05',
					'Parent Details',
					implode(
						"\n",
						array(
							$choice( 'Primary Contact', $radio( 'primary-contact', array( 'Father', 'Mother' ) ) ),
							$parent( 'father', 'Father’s Details', true ),
							$parent( 'mother', 'Mother’s Details', false ),
						)
					)
				),
				'<p class="enquiry__consent">By submitting this form, you confirm that the information provided is accurate and complete.</p>',
				'<div class="enquiry__actions">[submit class:btn class:btn--primary class:enquiry__submit "Submit Registration"]</div>',
			)
		);

		// One field per line: with "exclude blank" on, empty fields drop out.
		$sibling_lines = array();
		foreach ( range( 1, 3 ) as $n ) {
			$sibling_lines += array(
				"\nSibling {$n}"                => '',
				"Sibling {$n} name"             => "[sibling-{$n}-name]",
				"Sibling {$n} grade"            => "[sibling-{$n}-grade]",
				"Sibling {$n} gender"           => "[sibling-{$n}-gender]",
				"Sibling {$n} enrolment number" => "[sibling-{$n}-enrolment]",
				"Sibling {$n} school"           => "[sibling-{$n}-school]",
			);
		}
		$parent_lines = static function ( string $who, string $title ): array {
			return array(
				"\n{$title}"                      => '',
				"{$title}’s first name"           => "[{$who}-first-name]",
				"{$title}’s middle name"          => "[{$who}-middle-name]",
				"{$title}’s last name"            => "[{$who}-last-name]",
				"{$title} is school staff"        => "[{$who}-staff]",
				"{$title}’s occupation"           => "[{$who}-occupation]",
				"{$title}’s qualification"        => "[{$who}-qualification]",
				"{$title}’s nationality"          => "[{$who}-nationality]",
				"{$title}’s organisation"         => "[{$who}-organisation]",
				"{$title}’s Emirates ID"          => "[{$who}-emirates-id]",
				"{$title}’s Emirates ID expiry"   => "[{$who}-emirates-id-expiry]",
				"{$title}’s email"                => "[{$who}-email]",
				"{$title}’s mobile"               => "[{$who}-mobile-code] [{$who}-mobile]",
			);
		};
		$student_lines = array(
			'Academic year'            => '[academic-year]',
			'Grade'                    => '[grade]',
			"\nStudent"                => '',
			'First name'               => '[student-first-name]',
			'Middle name'              => '[student-middle-name]',
			'Last name'                => '[student-last-name]',
			'Gender'                   => '[student-gender]',
			'Date of birth'            => '[student-dob]',
			'Place of birth'           => '[student-birthplace]',
			'Religion'                 => '[student-religion]',
			'Mother tongue'            => '[student-mother-tongue]',
			'Blood group'              => '[student-blood-group]',
			'Nationality'              => '[student-nationality]',
			'Area of residence'        => '[residence-area]',
			'eSIS number'              => '[esis-number]',
			'Previous school'          => '[previous-school]',
			'Previous school country'  => '[previous-school-country]',
			'Previous school grade'    => '[previous-school-grade]',
			"\nPassport & visa"        => '',
			'Passport number'          => '[passport-number]',
			'Passport issue date'      => '[passport-issue-date]',
			'Passport expiry date'     => '[passport-expiry-date]',
			'Passport place of issue'  => '[passport-place]',
			'Visa number'              => '[visa-number]',
			'Visa valid until'         => '[visa-valid-until]',
			'Student’s Emirates ID'    => '[student-emirates-id]',
		);

		return $this->cf7_form(
			'registration',
			'Registration form',
			'registration',
			$template,
			array(
				'subject'            => 'New registration: [student-first-name] [student-last-name] – [grade], [academic-year]',
				'additional_headers' => 'Reply-To: [father-email], [mother-email]',
				'body'               => $this->mail_body(
					array_merge(
						$student_lines,
						$sibling_lines,
						array( "\nPrimary contact" => '[primary-contact]' ),
						$parent_lines( 'father', 'Father' ),
						$parent_lines( 'mother', 'Mother' )
					),
					'the Register Now page'
				),
			),
			array(
				'mail_sent_ok' => 'Thank you! Your registration has been received. Our admissions team will contact you with the next steps.',
				'mail_sent_ng' => 'Sorry, something went wrong and your registration was not sent. Please try again, or contact the school.',
				'spam'         => 'Sorry, something went wrong and your registration was not sent. Please try again, or contact the school.',
			)
		);
	}

	/**
	 * Create or update the job application form (Careers Application page).
	 *
	 * Subject, Classes Taught and Subjects Taught are only required for
	 * academic roles (`required-if:job-type:Academic`, see the theme's CF7
	 * integration).
	 *
	 * @return int Form ID, or 0 when Contact Form 7 is not active.
	 */
	private function application_form(): int {
		$req     = '<span class="enquiry__req" aria-hidden="true">*</span>';
		$tag     = '<span class="enquiry__tag">Academic roles</span>';
		$text    = static fn( string $name, string $placeholder, string $flags = '', string $extra = '' ): string => sprintf(
			'[text%1$s %2$s id:job-%2$s class:enquiry__control%3$s placeholder "%4$s"]',
			$flags,
			$name,
			$extra ? ' ' . $extra : '',
			$placeholder
		);
		$f       = fn( string $label, string $control, string $name, string $class = '' ): string => $this->form_field( $label, $control, 'job-' . $name, $class );
		$choice  = fn( string $label, string $control ): string => $this->form_choice( $label . ' ' . $req, $control );
		$upload  = static fn( string $name, string $label, bool $required ): string => sprintf(
			"<div class=\"enquiry__field\">\n<span class=\"enquiry__label\">%1\$s</span>\n<label class=\"enquiry__drop\">\n<span class=\"enquiry__drop-icon\" aria-hidden=\"true\"></span>\n<span>Drag &amp; drop your file here or <u>Browse</u></span>\n<span class=\"enquiry__drop-hint\" data-file-hint>PDF, DOC or DOCX · Max 5 MB</span>\n[file%2\$s %3\$s limit:5mb filetypes:pdf|doc|docx]\n</label>\n</div>",
			$label,
			$required ? '*' : '',
			$name
		);
		$academic = 'required-if:job-type:Academic';
		$depts    = array( 'Kindergarten', 'Primary', 'Middle School', 'Secondary', 'Senior Secondary', 'Special Education', 'Administration', 'Accounts & Finance', 'Admissions & Front Office', 'IT', 'Transport', 'Facilities & Maintenance', 'Other' );

		$template = implode(
			"\n\n",
			array(
				$this->form_step(
					'01',
					'Position',
					implode(
						"\n",
						array(
							$this->form_choice( 'Job Type ' . $req, '[radio job-type class:enquiry__cards use_label_element "Academic" "Non-Academic"]', 'enquiry__field--wide' ),
							$f( 'Position Applied For ' . $req, $text( 'position', 'e.g. Grade 5 Science Teacher, Accountant', '*' ), 'position', 'enquiry__field--first' ),
							$f( 'Division / Department ' . $req, '[select* department id:job-department class:enquiry__control class:enquiry__select first_as_label "Select division or department" ' . $this->form_options( $depts ) . ']', 'department' ),
							$f( "Subject {$req} {$tag}", $text( 'subject', 'Enter subject', '', $academic ), 'subject' ),
						)
					)
				),
				$this->form_step(
					'02',
					'Personal Information',
					implode(
						"\n",
						array(
							$f( 'Full Name ' . $req, $text( 'full-name', 'Enter your full name', '*', 'autocomplete:name' ), 'full-name' ),
							$f( 'Email Address ' . $req, '[email* email id:job-email class:enquiry__control autocomplete:email placeholder "Enter your email address"]', 'email' ),
							$f( 'Mobile Number', $this->form_phone( 'mobile-code', 'mobile', 'job-mobile', false, 'Enter mobile number' ), 'mobile' ),
							$f( 'Date of Birth ' . $req, '[date* date-of-birth id:job-date-of-birth class:enquiry__control]', 'date-of-birth' ),
						)
					)
				),
				$this->form_step(
					'03',
					'Most Recent Experience',
					implode(
						"\n",
						array(
							$f( 'Name of Institution ' . $req, $text( 'institution', 'Enter institution name', '*' ), 'institution' ),
							$f( 'Duration of Work ' . $req, $text( 'duration', 'e.g. Aug 2021 – Jun 2024', '*' ), 'duration' ),
							$f( 'Remuneration ' . $req, $text( 'remuneration', 'Enter monthly salary (AED)', '*' ), 'remuneration' ),
							$f( "Classes Taught {$req} {$tag}", $text( 'classes-taught', 'e.g. Grades 6–8', '', $academic ), 'classes-taught' ),
							$f( "Subjects Taught {$req} {$tag}", $text( 'subjects-taught', 'e.g. Mathematics, Physics', '', $academic ), 'subjects-taught' ),
							$f( 'Reason for Leaving', $text( 'leaving-reason', 'Enter reason' ), 'leaving-reason' ),
							$f( 'Remarks', '[textarea remarks id:job-remarks class:enquiry__control class:enquiry__textarea class:enquiry__textarea--short maxlength:3000 placeholder "Anything else you’d like us to know"]', 'remarks', 'enquiry__field--full' ),
						)
					)
				),
				$this->form_step(
					'04',
					'Additional Information',
					implode(
						"\n",
						array(
							$choice( 'Are you fully vaccinated?', '[radio vaccinated use_label_element "Yes" "No"]' ),
							$choice( 'Have you ever received ADEK approval?', '[radio adek-approval use_label_element "Yes" "No"]' ),
						)
					)
				),
				$this->form_step(
					'05',
					'Documents',
					$upload( 'cv', 'CV / Resume ' . $req, true ) . "\n" . $upload( 'cover-letter', 'Cover Letter', false ),
					'',
					'enquiry__grid--2'
				),
				'<p class="enquiry__consent">By submitting this application, you confirm that the information provided is accurate and complete.</p>',
				'<div class="enquiry__actions">[submit class:btn class:btn--primary class:enquiry__submit "Submit Application"]</div>',
			)
		);

		return $this->cf7_form(
			'careers-application',
			'Careers application',
			'careers-application',
			$template,
			array(
				'subject'            => 'Job application: [position] ([job-type]) – [full-name]',
				'additional_headers' => 'Reply-To: [full-name] <[email]>',
				'attachments'        => "[cv]\n[cover-letter]",
				'body'               => $this->mail_body(
					array(
						'Job type'             => '[job-type]',
						'Position'             => '[position]',
						'Division/department'  => '[department]',
						'Subject'              => '[subject]',
						"\nApplicant"          => '',
						'Name'                 => '[full-name]',
						'Email'                => '[email]',
						'Mobile'               => '[mobile-code] [mobile]',
						'Date of birth'        => '[date-of-birth]',
						"\nMost recent role"   => '',
						'Institution'          => '[institution]',
						'Duration'             => '[duration]',
						'Remuneration'         => '[remuneration]',
						'Classes taught'       => '[classes-taught]',
						'Subjects taught'      => '[subjects-taught]',
						'Reason for leaving'   => '[leaving-reason]',
						'Remarks'              => '[remarks]',
						"\nAdditional"         => '',
						'Fully vaccinated'     => '[vaccinated]',
						'ADEK approval'        => '[adek-approval]',
						"\nThe CV and cover letter are attached." => '',
					),
					'the Careers Application page'
				),
			),
			array(
				'mail_sent_ok' => 'Thank you for applying! Our HR team will be in touch if your profile matches a current opening.',
				'mail_sent_ng' => 'Sorry, something went wrong and your application was not sent. Please try again, or email us directly.',
				'spam'         => 'Sorry, something went wrong and your application was not sent. Please try again, or email us directly.',
			)
		);
	}

	/**
	 * Create or update a seeded Contact Form 7 form.
	 *
	 * Mail goes to the school email from Site Settings, from an address on the
	 * site's own domain (CF7 rejects mail-tags inside the address; update it
	 * per environment, see README "Email delivery"). Recipients, wording and
	 * messages are then edited in Contact → Contact Forms.
	 *
	 * @param string               $key      Seed key.
	 * @param string               $title    Form title in the admin.
	 * @param string               $step     Seeder step (for the "not active" warning).
	 * @param string               $template Form template (theme markup + form-tags).
	 * @param array<string,mixed>  $mail     Mail properties (subject, body, headers…).
	 * @param array<string,string> $messages Messages to override.
	 * @return int Form ID, or 0 when Contact Form 7 is not active.
	 */
	private function cf7_form( string $key, string $title, string $step, string $template, array $mail, array $messages ): int {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			WP_CLI::warning( "Contact Form 7 is not active: the \"{$title}\" form was skipped. Install and activate it (wp plugin install contact-form-7 flamingo --activate), then run: wp seps seed --only={$step}" );
			return 0;
		}

		$school = sanitize_email( (string) get_field( 'email', 'option' ) );
		$domain = (string) preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		$id   = $this->find( 'wpcf7_contact_form', $key );
		$form = $id ? \WPCF7_ContactForm::get_instance( $id ) : null;

		if ( ! $form ) {
			$form = \WPCF7_ContactForm::get_template( array( 'title' => $title ) );
		}

		$form->set_title( $title );
		$form->set_properties(
			array(
				'form'     => $template,
				'mail'     => array_merge(
					array(
						'active'             => true,
						'recipient'          => is_email( $school ) ? $school : '[_site_admin_email]',
						'sender'             => '[_site_title] <wordpress@' . $domain . '>',
						'additional_headers' => '',
						'attachments'        => '',
						'use_html'           => false,
						'exclude_blank'      => true,
					),
					$mail
				),
				'messages' => array_merge(
					(array) $form->prop( 'messages' ),
					array( 'validation_error' => 'Please check the highlighted fields and try again.' ),
					$messages
				),
			)
		);
		$id = (int) $form->save();

		update_post_meta( $id, self::SEED_META, $key );

		if ( ! class_exists( 'Flamingo_Inbound_Message' ) ) {
			WP_CLI::log( '  Tip: activate the Flamingo plugin to keep a copy of every submission in the admin.' );
		}

		return $id;
	}

	/**
	 * Plain-text mail body: "Label: value" lines; a label starting with a
	 * line break and an empty value is a heading.
	 *
	 * @param array<string,string> $lines Label => mail-tag text.
	 * @param string               $where Where the form is, e.g. "the Contact page".
	 */
	private function mail_body( array $lines, string $where ): string {
		$body = array();
		foreach ( $lines as $label => $value ) {
			$body[] = '' === $value ? $label : "{$label}: {$value}";
		}
		$body[] = "\n-- \nSent from {$where} of [_site_title] ([_site_url]).";

		return implode( "\n", $body );
	}

	/**
	 * Form field: label + control (theme markup used by the form templates).
	 *
	 * @param string $label Label (may contain the required marker / tags).
	 * @param string $tag   Control (form-tag or HTML).
	 * @param string $for   ID of the control the label points to.
	 * @param string $class Extra field class.
	 */
	private function form_field( string $label, string $tag, string $for, string $class = '' ): string {
		return sprintf(
			"<div class=\"enquiry__field%s\">\n\t<label class=\"enquiry__label\" for=\"%s\">%s</label>\n\t%s\n</div>",
			$class ? ' ' . $class : '',
			$for,
			$label,
			$tag
		);
	}

	/**
	 * Radio group: a fieldset whose legend is the label.
	 *
	 * @param string $label Legend.
	 * @param string $tag   Radio form-tag.
	 * @param string $class Extra field class.
	 */
	private function form_choice( string $label, string $tag, string $class = '' ): string {
		return sprintf(
			"<fieldset class=\"enquiry__field enquiry__field--choice%s\">\n\t<legend class=\"enquiry__label\">%s</legend>\n\t%s\n</fieldset>",
			$class ? ' ' . $class : '',
			$label,
			$tag
		);
	}

	/**
	 * Country code + phone number pair.
	 *
	 * @param string $code_name   Country code field name.
	 * @param string $name        Number field name.
	 * @param string $id          Number field ID.
	 * @param bool   $required    Number required.
	 * @param string $placeholder Number placeholder.
	 */
	private function form_phone( string $code_name, string $name, string $id, bool $required, string $placeholder ): string {
		return sprintf(
			'<div class="enquiry__phone">[text %1$s class:enquiry__control class:enquiry__code maxlength:5 autocomplete:tel-country-code "+971"][tel%2$s %3$s id:%4$s class:enquiry__control maxlength:20 autocomplete:tel-national placeholder "%5$s"]</div>',
			$code_name,
			$required ? '*' : '',
			$name,
			$id,
			$placeholder
		);
	}

	/**
	 * Numbered step: "01 Title" and a grid of fields.
	 *
	 * @param string $number Step number, e.g. "01".
	 * @param string $title  Step title.
	 * @param string $fields Fields markup.
	 * @param string $note   Optional note after the title.
	 * @param string $grid   Extra grid class.
	 */
	private function form_step( string $number, string $title, string $fields, string $note = '', string $grid = '' ): string {
		return sprintf(
			"<div class=\"enquiry__step\">\n<h3 class=\"enquiry__step-title\"><span class=\"enquiry__step-number\">%1\$s</span> %2\$s%3\$s</h3>\n<div class=\"enquiry__grid%4\$s\">\n%5\$s\n</div>\n</div>",
			$number,
			$title,
			$note ? " <span class=\"enquiry__step-note\">{$note}</span>" : '',
			$grid ? ' ' . $grid : '',
			$fields
		);
	}

	/**
	 * Quoted option list for a form-tag.
	 *
	 * @param string[] $options Options.
	 */
	private function form_options( array $options ): string {
		return '"' . implode( '" "', $options ) . '"';
	}

	/**
	 * Countries for the nationality dropdowns (UAE first).
	 *
	 * @return string[]
	 */
	private function countries(): array {
		$others = array( 'Afghanistan', 'Albania', 'Algeria', 'Andorra', 'Angola', 'Antigua and Barbuda', 'Argentina', 'Armenia', 'Australia', 'Austria', 'Azerbaijan', 'Bahamas', 'Bahrain', 'Bangladesh', 'Barbados', 'Belarus', 'Belgium', 'Belize', 'Benin', 'Bhutan', 'Bolivia', 'Bosnia and Herzegovina', 'Botswana', 'Brazil', 'Brunei', 'Bulgaria', 'Burkina Faso', 'Burundi', 'Cabo Verde', 'Cambodia', 'Cameroon', 'Canada', 'Central African Republic', 'Chad', 'Chile', 'China', 'Colombia', 'Comoros', 'Congo', 'Costa Rica', 'Côte d’Ivoire', 'Croatia', 'Cuba', 'Cyprus', 'Czechia', 'Democratic Republic of the Congo', 'Denmark', 'Djibouti', 'Dominica', 'Dominican Republic', 'Ecuador', 'Egypt', 'El Salvador', 'Equatorial Guinea', 'Eritrea', 'Estonia', 'Eswatini', 'Ethiopia', 'Fiji', 'Finland', 'France', 'Gabon', 'Gambia', 'Georgia', 'Germany', 'Ghana', 'Greece', 'Grenada', 'Guatemala', 'Guinea', 'Guinea-Bissau', 'Guyana', 'Haiti', 'Honduras', 'Hungary', 'Iceland', 'India', 'Indonesia', 'Iran', 'Iraq', 'Ireland', 'Italy', 'Jamaica', 'Japan', 'Jordan', 'Kazakhstan', 'Kenya', 'Kiribati', 'Kuwait', 'Kyrgyzstan', 'Laos', 'Latvia', 'Lebanon', 'Lesotho', 'Liberia', 'Libya', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Madagascar', 'Malawi', 'Malaysia', 'Maldives', 'Mali', 'Malta', 'Marshall Islands', 'Mauritania', 'Mauritius', 'Mexico', 'Micronesia', 'Moldova', 'Monaco', 'Mongolia', 'Montenegro', 'Morocco', 'Mozambique', 'Myanmar', 'Namibia', 'Nauru', 'Nepal', 'Netherlands', 'New Zealand', 'Nicaragua', 'Niger', 'Nigeria', 'North Korea', 'North Macedonia', 'Norway', 'Oman', 'Pakistan', 'Palau', 'Palestine', 'Panama', 'Papua New Guinea', 'Paraguay', 'Peru', 'Philippines', 'Poland', 'Portugal', 'Qatar', 'Romania', 'Russia', 'Rwanda', 'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'Samoa', 'San Marino', 'São Tomé and Príncipe', 'Saudi Arabia', 'Senegal', 'Serbia', 'Seychelles', 'Sierra Leone', 'Singapore', 'Slovakia', 'Slovenia', 'Solomon Islands', 'Somalia', 'South Africa', 'South Korea', 'South Sudan', 'Spain', 'Sri Lanka', 'Sudan', 'Suriname', 'Sweden', 'Switzerland', 'Syria', 'Tajikistan', 'Tanzania', 'Thailand', 'Timor-Leste', 'Togo', 'Tonga', 'Trinidad and Tobago', 'Tunisia', 'Türkiye', 'Turkmenistan', 'Tuvalu', 'Uganda', 'Ukraine', 'United Kingdom', 'United States', 'Uruguay', 'Uzbekistan', 'Vanuatu', 'Vatican City', 'Venezuela', 'Vietnam', 'Yemen', 'Zambia', 'Zimbabwe' );

		return array_merge( array( 'United Arab Emirates' ), $others );
	}

	/**
	 * Resources page: annual report, library, policies, school rules,
	 * inspection report, parent handbook and facilities.
	 */
	private function seed_resources(): void {
		$link   = static fn( string $title, string $url = '#' ): array => array(
			'url'    => $url,
			'title'  => $title,
			'target' => '',
		);
		$button = static fn( string $title ): array => array(
			array(
				'link'  => $link( $title ),
				'style' => 'primary',
			),
		);
		$policy = fn( string $image, string $alt, string $title ): array => array(
			'image' => $this->image( $image, $alt ),
			'title' => $title,
			'text'  => '',
			'link'  => null,
		);
		$list   = static fn( array $items ): string => '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';

		// Only "General Rules" is written out in the design; the other items
		// repeat what the site already says and point to the Parent Handbook.
		$rules = array(
			array(
				'title'    => 'General Rules',
				'subtitle' => 'Safe and responsible school life.',
				'content'  => $list(
					array(
						'Be at school by <strong>7:20 a.m.</strong> and follow entry rules.',
						'Inform the school by <strong>7:45 a.m.</strong> if a student is on leave.',
						'Own Transport pickup is at <strong>2:20 p.m.</strong> and students must be picked up by <strong>2:45 p.m.</strong>, or by <strong>4:00 p.m.</strong> if staying for an after-school activity.',
						'Wear the school ID card and proper school uniform every day.',
						'Maintain personal hygiene, cleanliness and tidiness.',
						'Take care of school facilities and property.',
						'Maintain discipline in corridors, bathrooms, buses, playgrounds and other shared areas.',
					)
				),
			),
			array(
				'title'    => 'Attendance & Leave',
				'subtitle' => 'Guidelines for regular attendance and approved leave.',
				'content'  => $list(
					array(
						'Regular attendance is expected throughout the school year.',
						'Inform the school by <strong>7:45 a.m.</strong> if a student is on leave.',
						'The full attendance and leave guidelines are in the Parent Handbook.',
					)
				),
			),
			array(
				'title'    => 'Special Care / Special Needs',
				'subtitle' => 'Supporting students who require special care.',
				'content'  => '<p>Students who need special care are supported by our Special Education team. Please speak to the school about your child’s needs; the full guidelines are in the Parent Handbook.</p>',
			),
			array(
				'title'    => 'Disciplinary Behavior & Classroom Conduct',
				'subtitle' => 'Guidelines for respectful and responsible behaviour.',
				'content'  => '<p>Students are expected to be respectful and responsible in class and around the school. The full code of conduct is in the Parent Handbook.</p>',
			),
		);

		$sections = array(
			array(
				'acf_fc_layout'  => 'page_hero',
				'title'          => "Explore Our School\nResources",
				'text'           => 'Find the information and resources you need, from academic guidance and school policies to parent handbooks, calendars and essential documents.',
				'note'           => '',
				'button'         => $link( 'Learn More', '#explore' ),
				'button_2'       => null,
				'image'          => $this->image( 'resources-hero.jpg', 'Students talking with a teacher in the library' ),
				'image_focus'    => 'center',
				'layout'         => 'standard',
				'decor'          => 'none',
				'decor_position' => 'auto',
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'white',
				'heading'        => 'Progress & Achievements',
				'content'        => '<p>Go behind the highlights of the year and discover the achievements, activities, academic progress and milestones that shaped life at SEPS in 2024–25.</p>',
				'buttons'        => $button( 'View Annual Report 2024–25' ),
				'image'          => $this->image( 'resources-progress.jpg', 'Young children raising their hands in class' ),
				'image_size'     => 'circle_small',
				'image_position' => 'left',
				'decor'          => 'halves_brand',
				'corner'         => 0,
				'anchor'         => 'explore',
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'lilac',
				'heading'        => 'Explore Our Library',
				'content'        => '<p>A space for reading, research and discovery, our library brings together books, reference materials and digital resources to support learning across grades and subjects.</p><p>From guided reading and book reviews to reading activities that encourage students to discover new books and ideas, the library helps make reading a part of everyday school life.</p>',
				'buttons'        => $button( 'Explore Library' ),
				'image'          => $this->image( 'resources-library.jpg', 'Children reading books in the library' ),
				'image_size'     => 'fill',
				'image_position' => 'right',
				'decor'          => 'none',
				'corner'         => 0,
			),
			array(
				'acf_fc_layout' => 'card_list',
				'background'    => 'white',
				'heading'       => 'Policies',
				'intro'         => '<p>Our policies provide clear guidance for learning, student wellbeing, safety and the wider school community.</p><p>Explore the policies that guide everyday life at SEPS, covering academics, attendance, assessment, student support, safeguarding, transport, staff responsibilities and more.</p>',
				'signature'     => '',
				'button'        => null,
				'display'       => 'carousel',
				'columns'       => '4',
				'image_height'  => 'small',
				'panel'         => 0,
				'panel_title'   => '',
				'cards'         => array(
					$policy( 'resources-policy-1.jpg', 'Teacher presenting to a class', 'Attendance Policy' ),
					$policy( 'resources-policy-2.jpg', 'Two staff members meeting at a desk', 'Curriculum Policy' ),
					$policy( 'resources-policy-3.jpg', 'Teacher with young students in a classroom', 'Assessment Policy' ),
					$policy( 'resources-policy-4.jpg', 'Student writing in a notebook', 'Student Protection Policy' ),
				),
				'image'         => 0,
				'decor'         => 'none',
			),
			array(
				'acf_fc_layout' => 'accordion_panel',
				'background'    => 'navy',
				'heading'       => 'School Rules & Regulations',
				'intro'         => 'Essential guidelines for students and parents covering daily conduct, attendance, special care and classroom behaviour at SEPS.',
				'image'         => $this->image( 'resources-rules.jpg', 'Young students writing at their desks' ),
				'items'         => $rules,
				'decor'         => 'halves',
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'white',
				'heading'        => 'School Inspection Report',
				'content'        => '<p>Take a closer look at SEPS through the 2024/25 ADEK Irtiqa’a School Inspection Report, covering teaching and learning, academic performance, student development and school leadership.</p><p>Discover the report’s findings, strengths and areas identified for continued improvement.</p>',
				'buttons'        => $button( 'View Report' ),
				'image'          => $this->image( 'resources-inspection.jpg', 'Student writing in class' ),
				'image_size'     => 'circle_large',
				'image_position' => 'right',
				'decor'          => 'sun_photo',
				'corner'         => 0,
			),
			array(
				'acf_fc_layout'  => 'text_image',
				'background'     => 'lilac',
				'heading'        => 'Parent Handbook',
				'content'        => '<p>Everything parents need to know about life at SEPS, from school routines and expectations to important guidelines and everyday information.</p><p>Keep the handbook handy for useful information throughout the school year.</p>',
				'buttons'        => $button( 'View Handbook' ),
				'image'          => $this->image( 'resources-handbook.jpg', 'Cover of the SEPS school diary' ),
				'image_size'     => 'document',
				'image_position' => 'left',
				'decor'          => 'none',
				'corner'         => 0,
			),
			array(
				'acf_fc_layout' => 'facilities',
				'heading'       => 'Spaces That Inspire Learning',
				'intro'         => 'Our learning environment brings together dedicated academic facilities, creative spaces and opportunities for students to explore, experiment and stay active.',
				'button'        => $this->link( 'student-life', 'View Gallery' ),
				'items'         => $this->facility_items(),
			),
		);

		update_field( 'field_seps_page_sections', $sections, $this->pages['resources'] );
	}

	/**
	 * Facilities ("Spaces That Inspire Learning"), shared by the home page and
	 * Resources.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function facility_items(): array {
		return array(
			array(
				'image' => $this->image( 'facility-1.jpg', 'Science laboratory glassware' ),
				'title' => 'Science Laboratories',
				'tags'  => "Physics\nChemistry\nBiology",
			),
			array(
				'image' => $this->image( 'facility-2.jpg', 'Robotic arm in the computer science lab' ),
				'title' => 'Computer Science & Robotics',
				'tags'  => "Technology\nInnovation",
			),
			array(
				'image' => $this->image( 'facility-3.jpg', 'Indoor sports hall' ),
				'title' => 'Sports Facilities',
				'tags'  => "Football\nPhysical Education",
			),
			array(
				'image' => $this->image( 'facility-4.jpg', 'Library bookshelves and reading tables' ),
				'title' => 'Libraries',
				'tags'  => "Reading\nResearch",
			),
			array(
				'image' => $this->image( 'facility-5.jpg', 'Students raising hands in a science class' ),
				'title' => 'Art & Music',
				'tags'  => 'Creative Expression',
			),
			array(
				'image' => $this->image( 'facility-6.jpg', 'Student gardening in the organic farm' ),
				'title' => 'Organic Farm',
				'tags'  => "Sustainability\nAwareness",
			),
		);
	}

	/*
	 * -------------------------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------------------------
	 */

	/**
	 * Run and log a step.
	 *
	 * @param string   $label    Step label.
	 * @param callable $callback Step body.
	 */
	private function step( string $label, callable $callback ): void {
		WP_CLI::log( "• {$label}…" );
		$callback();
	}

	/**
	 * Map seed keys to existing page IDs (when the pages step is skipped).
	 */
	private function index_pages(): void {
		$ids = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'meta_key'         => self::SEED_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- CLI only.
				'suppress_filters' => true,
			)
		);

		foreach ( $ids as $id ) {
			$this->pages[ (string) get_post_meta( $id, self::SEED_META, true ) ] = (int) $id;
		}

		if ( ! isset( $this->pages['home'] ) ) {
			WP_CLI::error( 'Seeded pages not found. Run the full seed first: wp seps seed' );
		}
	}

	/**
	 * Find a seeded post by key.
	 *
	 * @param string $post_type Post type.
	 * @param string $key       Seed key.
	 */
	private function find( string $post_type, string $key ): int {
		$ids = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'meta_key'         => self::SEED_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- CLI only.
				'meta_value'       => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- CLI only.
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'suppress_filters' => true,
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Create or update a seeded post.
	 *
	 * @param string              $post_type Post type.
	 * @param string              $key       Seed key.
	 * @param array<string,mixed> $data      wp_insert_post() data.
	 */
	private function post( string $post_type, string $key, array $data ): int {
		$data = array_merge(
			array(
				'post_type'   => $post_type,
				'post_status' => 'publish',
			),
			$data
		);

		$existing = $this->find( $post_type, $key );
		if ( $existing ) {
			$data['ID'] = $existing;
		}

		$id = wp_insert_post( wp_slash( $data ), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id );
		}

		update_post_meta( $id, self::SEED_META, $key );

		return (int) $id;
	}

	/**
	 * Create or update a seeded page.
	 *
	 * @param string $key   Seed key (also the slug).
	 * @param string $title Title.
	 * @param int    $order Menu order.
	 */
	private function page( string $key, string $title, int $order ): int {
		$this->pages[ $key ] = $this->post(
			'page',
			$key,
			array(
				'post_title'  => $title,
				'post_name'   => $key,
				'post_parent' => 0,
				'menu_order'  => $order,
			)
		);

		return $this->pages[ $key ];
	}

	/**
	 * SCF link value pointing at a seeded page.
	 *
	 * @param string $page_key Seed key.
	 * @param string $title    Link text.
	 * @return array{url:string,title:string,target:string}
	 */
	private function link( string $page_key, string $title ): array {
		return array(
			'url'    => (string) get_permalink( $this->pages[ $page_key ] ),
			'title'  => $title,
			'target' => '',
		);
	}

	/**
	 * Import a demo image once and return its attachment ID.
	 *
	 * @param string $file File name in demo/images.
	 * @param string $alt  Alternative text.
	 */
	private function image( string $file, string $alt ): int {
		$id = $this->attachment( 'images', $file, $alt );

		update_post_meta( $id, '_wp_attachment_image_alt', $alt );

		return $id;
	}

	/**
	 * Import a demo document (PDF) once and return its attachment ID.
	 *
	 * @param string $file  File name in demo/documents.
	 * @param string $title Media Library title.
	 */
	private function document( string $file, string $title ): int {
		return $this->attachment( 'documents', $file, $title );
	}

	/**
	 * Import a file from the plugin's demo folder into the Media Library once
	 * (found again by its seed key on later runs).
	 *
	 * @param string $folder Folder in demo/: 'images' or 'documents'.
	 * @param string $file   File name.
	 * @param string $title  Attachment title.
	 */
	private function attachment( string $folder, string $file, string $title ): int {
		$key = ( 'images' === $folder ? 'image' : 'document' ) . ":{$file}";

		if ( isset( $this->attachments[ $key ] ) ) {
			return $this->attachments[ $key ];
		}

		$id = $this->find( 'attachment', $key );

		if ( ! $id ) {
			$source = SEPS_CORE_DIR . "demo/{$folder}/{$file}";
			$tmp    = wp_tempnam( $file );

			if ( ! is_readable( $source ) || ! copy( $source, $tmp ) ) {
				WP_CLI::error( "Cannot read demo file {$folder}/{$file}." );
			}

			$id = media_handle_sideload(
				array(
					'name'     => $file,
					'tmp_name' => $tmp,
				),
				0,
				$title
			);

			if ( is_wp_error( $id ) ) {
				wp_delete_file( $tmp );
				WP_CLI::error( $id );
			}

			update_post_meta( $id, self::SEED_META, $key );
		}

		$this->attachments[ $key ] = (int) $id;

		return (int) $id;
	}

	/**
	 * Add items to a nav menu. Menus are single-level by design (no dropdowns).
	 *
	 * @param int                            $menu_id Menu term ID.
	 * @param array<int,array<string,mixed>> $items   Items: page key, or title + url.
	 */
	private function add_menu_items( int $menu_id, array $items ): void {
		foreach ( $items as $position => $item ) {
			$args = array(
				'menu-item-status'   => 'publish',
				'menu-item-position' => $position + 1,
			);

			if ( isset( $item['page'] ) ) {
				$args += array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $this->pages[ $item['page'] ],
				);
			} else {
				$args += array(
					'menu-item-type'  => 'custom',
					'menu-item-title' => $item['title'],
					'menu-item-url'   => $item['url'],
				);
			}

			$item_id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( is_wp_error( $item_id ) ) {
				WP_CLI::error( $item_id );
			}
		}
	}
}
