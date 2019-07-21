<?php

namespace Symbiote\UserTemplates;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Extension;

/**
 * @author marcus
 */
class TemplatedElementControllerExtension extends Extension
{
    public function isTemplated()
    {
        $widget = $this->owner->getElement();
        return $widget && ($widget->LayoutTemplateID || strlen($widget->RenderWithTemplate));
    }

    public function TemplatedContent()
    {
        $out = '';

        $widget = $this->owner->getElement();
        if (!$widget) {
            return;
        }

        if ($widget->hasMethod('includeRequirements')) {
            $widget->includeRequirements();
        }

        $template = $widget->LayoutTemplate();

        if ($widget->LayoutTemplateID > 0 && $template) {
            $template->includeRequirements();
            $out = $this->owner->renderWith($template->getTemplateFile());
        } elseif (strlen($widget->RenderWithTemplate) && intval($widget->RenderWithTemplate) == 0) {
            $out = $this->owner->renderWith($widget->RenderWithTemplate);
        } else {
            $out = $this->owner->renderWith(array_reverse(ClassInfo::ancestry($widget->ClassName)));
        }

        return $out;
    }
}
