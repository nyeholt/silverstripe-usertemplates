<?php

/**
 * Description of UserTemplateAdmin
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class UserTemplateAdmin extends ModelAdmin
{
    public static $menu_title = 'Templates';
    public static $url_segment = 'templates';
    public static $managed_models = array('UserTemplate');
}
