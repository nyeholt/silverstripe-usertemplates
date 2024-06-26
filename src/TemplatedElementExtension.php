<?php

namespace Symbiote\UserTemplates;

use Exception;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\SSViewer;
use SilverStripe\Control\Director;
use DNADesign\ElementalList\Model\ElementList;
use SilverStripe\ORM\DataExtension;

/**
 * Allows an element to have a custom rendering template assigned to it
 * from within the CMS.
 *
 * Second, the element may be templated from a 'creation' standpoint; that is,
 * if it has a particular "child elements" property set, during its onaftercreate
 * it will create any indicated templated elements.
 *
 * @author Stephen McMahon <stephen@symbiote.com.au>
 */
class TemplatedElementExtension extends DataExtension
{
    private static $db = [
        'RenderWithTemplate' => 'Varchar(128)', // allows developers access to specify a different template at create
    ];

    private static $has_one = [
        'LayoutTemplate' => UserTemplate::class
    ];

    public function onBeforeWrite()
    {
        if (intval($this->owner->RenderWithTemplate) > 0) {
            $this->owner->LayoutTemplateID = $this->owner->RenderWithTemplate;
        } else {
            $this->owner->LayoutTemplateID = 0;
        }
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('RenderWithTemplate');
        $fields->removeByName('LayoutTemplateID');

        if (Permission::check('ADMIN')) {
            // get the list of templates
            $fields->addFieldToTab(
                'Root.Settings',
                DropdownField::create(
                    'RenderWithTemplate',
                    'Display template',
                    $this->getElementTemplateList()
                )->setEmptyString('-- default --')
            );
        }
    }

    public function getElementTemplateList()
    {
        $layouts = DataList::create(UserTemplate::class)->filter(['Use' => 'Layout']);

        $templates = $this->fileBasedTemplates();

        if ($layouts) {
            foreach ($layouts->map() as $ID => $title) {
                $templates[$ID] = $title;
            }
        }

        return $templates;
    }

    protected function fileBasedTemplates()
    {
        $templates = ['' => 'None'];
        foreach (glob(Director::baseFolder() . '/' . THEMES_DIR . '/*', GLOB_ONLYDIR) as $theme) {
            $themeName = ucfirst(basename($theme));
            if (is_dir($theme . '/element-templates')) {
                foreach (glob($theme . '/element-templates/*.ss') as $templateFile) {
                    $templateFile = str_replace(Director::baseFolder() . '/', '', $templateFile);
                    $templates[$templateFile] = $themeName . ' - ' . basename($templateFile);
                }
            }
        }

        return $templates;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (is_array($this->owner->sub_elements) && count($this->owner->sub_elements)) {
            // we _may_ need to create an element list to store these things.
            $elementList = $list = null;
            if ($this->owner->ID) {
                $list = $this->owner instanceof ElementList ? $this->owner->Elements() : null;

                // okay, need to create as child items of 'this' element, so do it against a newly created
                // List attached to this element
                if (!$list) {
                    $elementList = $this->owner->List();
                    if (!$this->owner->ListID) {
                        $elementList = ElementList::create();
                        $elementList->Title = _t('TemplatedElement.LIST_TITLE', 'Elements');
                        $elementList->write();
                    }
                    $list = $elementList->Elements();
                }
            }
            if (!$list) {
                return;
            }
            $this->createTemplatedElements($this->owner->sub_elements, $list);
            $this->owner->sub_elements = null;

            // and make sure to set the correct list ID if we created a sublist
            if (!$this->owner->ListID && $elementList && $elementList->ID) {
                $this->owner->ListID = $elementList->ID;
                $this->owner->write();
            }
        }
    }

    /**
     * Creates a set of elements as items in a given list
     *
     * The passed in configuration array has a mapping of property => value
     * for the to-be created elements
     *
     * @param array $config
     */
    public function createTemplatedElements($config, $addToList = null)
    {
        foreach ($config as $elementConfig) {
            $element = $this->createElement($elementConfig);

            if ($addToList) {
                $addToList->add($element);
            }
        }

        return $addToList;
    }

    /**
     * Creates a single element from the given configuration data, basically a key => value
     * map of properties.
     *
     * @param type $config
     * @return type
     * @throws Exception
     */
    protected function createElement($config)
    {
        if (!isset($config['ClassName'])) {
            throw new Exception('ClassName not found for new defined element');
        }

        $elClass = $config['ClassName'];
        $element = $elClass::create();
        $element->update($config);
        $element->write();

        return $element;
    }
}
