<?php

namespace Symbiote\UserTemplates;

use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;

class UserTemplateShortcode
{
    public static function render_template($arguments, $content = null, $parser = null)
    {
        $templateId = isset($arguments['id']) ? $arguments['id'] : null;

        $contextId = isset($arguments['context_id']) ? $arguments['context_id'] : null;

        if (!$templateId) {
            return "Please specify a template id";
        }

        $curr = null;
        if ($contextId) {
            $page = SiteTree::get()->byID($contextId);
            if ($page && $page->canView()) {
                $curr = ModelAsController::controller_for($page);
            }
        }

        $template = UserTemplate::get()->byID($templateId);

        if ($template && $template->canView()) {
            if (!$curr) {
                $curr = Controller::has_curr() ? Controller::curr() : null;    
            }
            
            $context = $curr ? $curr : $template;
            return $context->renderWith($template->getTemplateFile());
        }

        return "Please specify a template id";
    }
}
