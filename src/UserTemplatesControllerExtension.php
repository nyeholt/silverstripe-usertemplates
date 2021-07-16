<?php

namespace Symbiote\UserTemplates;

use SilverStripe\Core\Extension;
use SilverStripe\Control\Controller;

/**
 *
 *
 * @author marcus
 */

class UserTemplatesControllerExtension extends Extension
{

    private static $use_custom_view = null;

    public function updateViewer($action, $viewer)
    {
        $master = $this->owner->data()->effectiveTemplate('Master');
        if ($master && $master->ID) {
            // set the main template
            $master->includeRequirements();
            $viewer->setTemplateFile('main', $master->getTemplateFile());
            self::$use_custom_view = true;
        }

        $layout = $this->owner->data()->effectiveTemplate('Layout', $action);

        if ($layout && $layout->ID) {
            $layout->includeRequirements();
            $viewer->setTemplateFile('Layout', $layout->getTemplateFile());
            self::$use_custom_view = true;
        }
    }

    /**
     * Update the list of templates used by mediawesome and extensible search.
     *
     * @param array $templates
     */
    public function updateTemplates(&$templates)
    {
        $req = $this->owner instanceof Controller ? $this->owner->getRequest() : null;
        $action = null;
        if ($req) {
            $action = $req->param('Action');
        }

        $viewer = $this->owner->getViewer($action);
        if (self::$use_custom_view) {
            $templates = $viewer;
            self::$use_custom_view = false;
        }
    }
}
