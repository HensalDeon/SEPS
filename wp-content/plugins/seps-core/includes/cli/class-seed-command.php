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
	 * Attachment IDs keyed by demo file name.
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
	 * about.
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
			'privacy-policy' => 'Privacy Policy',
			'terms'          => 'Terms & Conditions',
		);

		$order = 0;
		foreach ( $pages as $key => $title ) {
			$this->page( $key, $title, ++$order );
		}

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
					// A section of the Resources page (added when that page is built).
					$link( 'Mandatory Disclosure', get_permalink( $this->pages['resources'] ) . '#mandatory-disclosure' ),
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
					$link( 'CBSE Portal' ),
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
					$link( 'Parent Portal' ),
					$link( 'Student Portal' ),
					$link( 'Staff Portal' ),
					$link( 'Fee Payment' ),
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
					'url'     => 'https://www.facebook.com/',
				),
				array(
					'network' => 'x',
					'url'     => 'https://x.com/',
				),
				array(
					'network' => 'instagram',
					'url'     => 'https://www.instagram.com/',
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
				'items'   => array(
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
				),
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
		if ( isset( $this->attachments[ $file ] ) ) {
			return $this->attachments[ $file ];
		}

		$id = $this->find( 'attachment', "image:{$file}" );

		if ( ! $id ) {
			$source = SEPS_CORE_DIR . 'demo/images/' . $file;
			$tmp    = wp_tempnam( $file );

			if ( ! is_readable( $source ) || ! copy( $source, $tmp ) ) {
				WP_CLI::error( "Cannot read demo image {$file}." );
			}

			$id = media_handle_sideload(
				array(
					'name'     => $file,
					'tmp_name' => $tmp,
				),
				0,
				$alt
			);

			if ( is_wp_error( $id ) ) {
				wp_delete_file( $tmp );
				WP_CLI::error( $id );
			}

			update_post_meta( $id, self::SEED_META, "image:{$file}" );
		}

		update_post_meta( $id, '_wp_attachment_image_alt', $alt );

		$this->attachments[ $file ] = (int) $id;

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
