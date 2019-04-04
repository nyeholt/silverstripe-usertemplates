<?php

namespace Symbiote\UserTemplates;

use Symbiote\UserTemplates\UserTemplate;
use SilverStripe\Admin\ModelAdmin;

/**
 * Description of UserTemplateAdmin
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class UserTemplateAdmin extends ModelAdmin {
	private static $menu_title = 'Templates';
	private static $url_segment = 'templates';
	private static $managed_models = array(UserTemplate::class);
	private static $menu_icon_class = 'font-icon-p-code';
}
