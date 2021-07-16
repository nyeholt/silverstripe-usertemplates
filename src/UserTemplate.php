<?php

namespace Symbiote\UserTemplates;

use Symbiote\MultiValueField\Fields\KeyValueField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Control\Director;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\View\Requirements;

/**
 * A template that a user can create and apply within the system
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class UserTemplate extends DataObject implements PermissionProvider
{

    private static $table_name = 'UserTemplate';

    private static $db = array(
        'Title'             => 'Varchar',
        'Description'       => 'Varchar',
        'Use'               => "Enum('Layout,Master')",
        'Content'           => 'Text',
        'ContentFile'       => 'Varchar(132)',
        'StrictActions'     => DBBoolean::class,
        'ActionTemplates'   => 'MultiValueField',
    );

    private static $many_many = array(
        'CustomCSSFiles' => File::class,
        'CustomJSFiles' => File::class
    );


    /**
     * folder for custom javascript files
     * @var string
     **/
    protected static $js_folder = 'custom-theme/javascript';

    /**
     * folder for custom css files
     * @var string
     **/
    protected static $css_folder = 'custom-theme/css';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $cssFiles = new UploadField('CustomCSSFiles', _t('UserTemplatesExtension.CustomCSSFiles', "Custom CSS Files"));
        $jsFiles = new UploadField('CustomJSFiles', _t('UserTemplatesExtension.CustomJSFiles', "Custom JS Files"));
        $cssFiles->setFolderName(self::$css_folder);
        $jsFiles->setFolderName(self::$js_folder);

        $fields->removeByName('CustomCSSFiles');
        $fields->removeByName('CustomJSFiles');

        $templates = $this->fileBasedTemplates();
        if (count($templates)) {
            $fields->addFieldToTab('Root.Main', $dd = DropdownField::create('ContentFile', _t('UserTemplatesExtension.CONTENT_FILE', 'File containing template'), $templates)->setEmptyString('-- none --'));
            $dd->setRightTitle('If selected, any Content set above will be ignored');
        } else {
            $fields->removeByName('ContentFile');
        }

        $fields->push($strict = CheckboxField::create('StrictActions', _t('UserTemplates.STRICT_ACTIONS', 'Require actions to be explicitly overridden')));
        $text = <<<DOC
   When applied to a page type that has sub-actions, an action template will be used ONLY if the action is listed below, and this main
	   template will only be used for the 'index' action. If this is not checked, then this template will be used for ALL actions
	   in the page it is applied to.
DOC;
        $strict->setRightTitle(_t('UserTemplates.STRICT_HELP', $text));

        $templates = DataList::create(UserTemplate::class)->filter(array('ID:not' => $this->ID));
        if ($templates->count()) {
            $templates = $templates->map();
            $fields->addFieldToTab('Root.Main', $kv = new KeyValueField('ActionTemplates', _t('UserTemplates.ACTION_TEMPLATES', 'Action specific templates'), array(), $templates));
            $kv->setRightTitle(_t('UserTemplates.ACTION_TEMPLATES_HELP', 'Specify an action name and select another user defined template to handle a specific action. Only used for Layout templates'));
        } else {
            $fields->removeByName('ActionTemplates');
        }


        $fields->addFieldToTab('Root.Main', $cssFiles);
        $fields->addFieldToTab('Root.Main', $jsFiles);

        return $fields;
    }

    protected function fileBasedTemplates()
    {
        $templates = array('' => 'None');
        foreach (glob(Director::baseFolder() . '/' . THEMES_DIR . '/*', GLOB_ONLYDIR) as $theme) {
            $themeName = ucfirst(basename($theme));
            if (is_dir($theme . '/user-templates')) {
                foreach (glob($theme . '/user-templates/*.ss') as $templateFile) {
                    $templateFile = str_replace(Director::baseFolder() . '/', '', $templateFile);
                    $templates[$templateFile] = $themeName . ' - ' . basename($templateFile);
                }
            }
        }

        return $templates;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->Title = FileNameFilter::create()->filter($this->Title);

        if (strlen($this->ContentFile)) {
            $templates = $this->fileBasedTemplates();
            if (!isset($templates[$this->ContentFile])) {
                $this->ContentFile = '';
            }
        }
    }

    public function onAfterWrite()
    {
        if (strlen($this->Content)) {
            $this->generateCacheFile();
        }
    }

    protected function generateCacheFile()
    {
        $file = $this->getCacheFilename();
        file_put_contents($file, $this->Content);
    }

    /**
     * Return an override template for a specific action if given
     *
     * @param string $action
     */
    public function getActionOverride($action)
    {
        if ($this->ActionTemplates) {
            $actions = $this->ActionTemplates->getValues();
            if ($actions && isset($actions[$action])) {
                return DataList::create(UserTemplate::class)->byID($actions[$action]);
            }
        }
    }

    /**
     * Get a filename that represents the
     *
     * @return string
     */
    public function getTemplateFile()
    {
        if (strlen($this->ContentFile)) {
            $templateFile = Director::baseFolder() . '/' . $this->ContentFile;
            if (file_exists($templateFile)) {
                return $templateFile;
            }
        }

        $file = $this->getCacheFilename();
        if (!@filesize($file)) {
            clearstatcache();
            $this->generateCacheFile();
            return $file;
        }

        return $file;
    }

    /**
     * Get the name of the cache file
     *
     * @return string
     */
    protected function getCacheFilename()
    {
        $dir = TEMP_PATH . '/usertemplates/template-cache/' . $this->Use . '/';
        Filesystem::makeFolder($dir);
        $dateStamp = strtotime($this->LastEdited);
        $file = $dir . '/' . $this->Title . '-' . $dateStamp . '.ss';
        if (!file_exists($file)) {
            touch($file);
            chmod($file, 0664);
        }
        return $file;
    }

    public function includeRequirements()
    {
        $obj = $this->CustomCSSFiles();
        if ($obj) {
            foreach ($obj as $file) {
                Requirements::css(Director::absoluteURL($file->getURL()));
            }
        }

        $obj = $this->CustomJSFiles();
        if ($obj) {
            foreach ($obj as $file) {
                Requirements::javascript(Director::absoluteURL($file->getURL()));
            }
        }
    }

    public function canView($member = null, $context = array())
    {
        return true;
    }

    public function canEdit($member = null, $context = array())
    {
        return Permission::check('ADMIN') || Permission::check('TEMPLATE_EDIT');
    }

    public function canDelete($member = null, $context = array())
    {
        return Permission::check('ADMIN') || Permission::check('TEMPLATE_DELETE');
    }

    public function canCreate($member = null, $context = array())
    {
        return Permission::check('ADMIN') || Permission::check('TEMPLATE_CREATE');
    }

    public function canPublish($member = null, $context = array())
    {
        return Permission::check('ADMIN') || Permission::check('TEMPLATE_PUBLISH');
    }

    public function providePermissions()
    {
        return array(
            'TEMPLATE_EDIT' => array(
                'name' => 'Edit a Template',
                'category' => 'Template',
            ),
            'TEMPLATE_DELETE' => array(
                'name' => 'Delete a Template',
                'category' => 'Template',
            ),
            'TEMPLATE_CREATE' => array(
                'name' => 'Create a Template',
                'category' => 'Template'
            )
        );
    }
}
