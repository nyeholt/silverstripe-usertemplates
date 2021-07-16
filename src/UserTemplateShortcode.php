<?php

namespace Symbiote\UserTemplates;

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;

class UserTemplateShortcode
{
    public static function render_template($arguments, $content = null, $parser = null)
    {
        $templateId = isset($arguments['id']) ? $arguments['id'] : null;
        $name = isset($arguments['name']) ? $arguments['name'] : null;
        $contextId = isset($arguments['context_id']) ? $arguments['context_id'] : null;

        if (!$templateId && !$name) {
            return "Please specify a template id or name";
        }

        $curr = Controller::has_curr() ? Controller::curr() : null;

        // allow manual setting of the controller if not found
        if ($contextId && !($curr instanceof ContentController)) {
            $page = SiteTree::get()->byID($contextId);
            if ($page && $page->canView()) {
                $curr = ModelAsController::controller_for($page);
            }
        }

        $filter = $templateId ? ['ID' => $templateId] : ['Title' => $name];

        $template = UserTemplate::get()->filter($filter)->first();

        if ($template && $template->canView()) {
            $context = $curr ? $curr : $template;
            return $context->renderWith($template->getTemplateFile());
        }

        return "Please specify a template id or name";
    }
}
