<?php

namespace Symbiote\UserTemplates;

use SilverStripe\Core\Extension;

/**
 * 
 *
 * @author marcus
 */

class UserTemplatesControllerExtension extends Extension {

	public function updateViewer($action, $viewer) {
		$master = $this->owner->data()->effectiveTemplate('Master');
		if ($master && $master->ID) {
			// set the main template
			$master->includeRequirements();
			$viewer->setTemplateFile('main', $master->getTemplateFile());
		}

		$layout = $this->owner->data()->effectiveTemplate('Layout', $action);

		if ($layout && $layout->ID) {
			$layout->includeRequirements();
			$viewer->setTemplateFile('Layout', $layout->getTemplateFile());
		}
	}

    /**
     * Update the list of templates used by mediawesome
     *
     * @param array $templates
     */
    public function updateTemplates(&$templates) {
//        $templates = $this->owner->getViewer('index');
        if ($this->owner instanceof \nglasl\extensible\ExtensibleSearchPageController) {
            $layout = $this->owner->data()->effectiveTemplate('Layout', 'getSearchResults');

            if ($layout && $layout->ID) {
                $layout->includeRequirements();
                array_unshift($templates, $layout->getTemplateFile());
//                $viewer->setTemplateFile('Layout', $layout->getTemplateFile());
            }
        }
        $finder = $this->owner->getViewer('index');
        $o = 1;
    }
}