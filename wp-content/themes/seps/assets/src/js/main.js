/**
 * SEPS theme scripts. Loaded with `defer`, so the DOM is ready on execution.
 *
 * Every module is progressive enhancement: the page is fully usable without JS.
 */

import initNavigation from './modules/navigation';
import initCarousels from './modules/carousel';
import initEnquiryForms from './modules/enquiry-form';

initNavigation();
initCarousels();
initEnquiryForms();
