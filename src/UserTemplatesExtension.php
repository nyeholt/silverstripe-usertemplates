<?php

namespace Symbiote\UserTemplates;

use Symbiote\UserTemplates\UserTemplate;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;

class UserTemplatesExtension extends DataExtension
{

    private static $db = array(
        'InheritTemplateSettings'   => DBBoolean::class,
        'NotInherited'              => DBBoolean::class,
    );

    private static $has_one = array(
        'MasterTemplate'            => UserTemplate::class,
        'LayoutTemplate'            => UserTemplate::class,
    );

    private static $defaults = array(
        'InheritTemplateSettings'       => 1
    );

    public function updateCMSFields(FieldList $fields)
    {
        $effectiveMaster = $this->effectiveTemplate();
        $effectiveLayout = $this->effectiveTemplate('Layout');

        if (($effectiveLayout && $effectiveLayout->ID) || ($effectiveMaster && $effectiveMaster->ID)) {
            $fields->addFieldsToTab(
                'Root.Main',
                LiteralField::create(
                    'TemplateWarning',
                    '<div class="message alert">This page has a custom template applied. This can be changed on the Settings tab</div>'
                ),
                'Title'
            );
        }
    }

    public function updateSettingsFields(FieldList $fields)
    {
        $layouts = DataList::create(UserTemplate::class)->filter(array('Use' => 'Layout'));
        $masters = DataList::create(UserTemplate::class)->filter(array('Use' => 'Master'));

        $fields->addFieldToTab('Root.Theme', DropdownField::create('MasterTemplateID', 'Master Template', $masters->map(), '', null)->setEmptyString(_t(__CLASS__ . '.NONE', 'None')));
        $fields->addFieldToTab('Root.Theme', DropdownField::create('LayoutTemplateID', 'Layout Template', $layouts->map(), '', null)->setEmptyString(_t(__CLASS__ . '.NONE', 'None')));
        $fields->addFieldToTab('Root.Theme', CheckboxField::create('InheritTemplateSettings', _t(__CLASS__ . '.InheritTemplateSettings', 'Inherit settings')));
        $fields->addFieldToTab('Root.Theme', CheckboxField::create('NotInherited', _t(__CLASS__ . '.NotInherited', "Don't cascade these templates to children")));

        $effectiveMaster = $this->effectiveTemplate();
        $effectiveLayout = $this->effectiveTemplate('Layout');

        if ($effectiveMaster) {
            $fields->addFieldToTab('Root.Theme', ReadonlyField::create('EffectiveMaster', _t(__CLASS__ . '.EffectiveMaster', 'Effective master template'), $effectiveMaster->Title));
        }

        if ($effectiveLayout) {
            $fields->addFieldToTab('Root.Theme', ReadonlyField::create('EffectiveLayout', _t(__CLASS__ . '.EffectiveLayout', 'Effective layout template'), $effectiveLayout->Title));
        }

        return $fields;
    }

    /**
     *
     * @param string $type
     *                  Whether to get a master or layout template
     * @param string $action
     *                  If there's a specific action involved for the template
     * @param int $forItem
     *                  The item we're getting the template for. Used to determine
     *                  whether the 'NotInherited' flag is checked
     * @return type
     */
    public function effectiveTemplate($type = 'Master', $action = null, $forItem = 0)
    {
        $name = $type . 'Template';
        $id = $name . 'ID';

        $myid = $this->owner->ID;
        $notIn = $this->owner->NotInherited;

        $skipInheritance = $this->owner->NotInherited && $forItem > 0 && $forItem != $this->owner->ID;

        if (!$skipInheritance && $this->owner->$id) {
            $template = $this->owner->getComponent($name);
            if ($action && $action != 'index') {
                // see if there's an override for this specific action
                $override = $template->getActionOverride($action);

                // if the template is strict, then we MUST have the action defined
                // otherwise we need to return null - so we set $template IF this is the case,
                // regardless of whether we found an override, OR if the override was set
                if ($template->StrictActions || $override) {
                    $template = $override;
                }
            }
            return $template;
        }

        if (!$forItem) {
            $forItem = $this->owner->ID;
        }

        if ($this->owner->InheritTemplateSettings && $this->owner->ParentID) {
            $parent = $this->owner->Parent();
            if ($parent && $parent->hasMethod('effectiveTemplate')) {
                return $parent->effectiveTemplate($type, $action, $forItem);
            }
        }
    }
}
