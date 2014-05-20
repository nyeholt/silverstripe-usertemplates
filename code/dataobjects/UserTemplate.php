<?php

/**
 * A template that a user can create and apply within the system
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class UserTemplate extends DataObject {
	public static $db = array(
		'Title'				=> 'Varchar',
		'Description'		=> 'Varchar',
		'Use'				=> "Enum('Layout,Master')",
		'Content'			=> 'Text',
		'ContentFile'		=> 'Varchar(132)',
		'StrictActions'		=> 'Boolean',
		'ActionTemplates'	=> 'MultiValueField',
	);

	public static $many_many = array(
		'CustomCSSFiles' => 'File',
		'CustomJSFiles' => 'File'
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

	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$cssFiles = new UploadField('CustomCSSFiles',_t('UserTemplatesExtension.CustomCSSFiles',"Custom CSS Files"));
		$jsFiles = new UploadField('CustomJSFiles',_t('UserTemplatesExtension.CustomJSFiles',"Custom JS Files"));
		$cssFiles->setFolderName(self::$css_folder);
		$jsFiles->setFolderName(self::$js_folder);

		$fields->removeByName('CustomCSSFiles');
		$fields->removeByName('CustomJSFiles');

		$templates = $this->fileBasedTemplates();
		if (count($templates)) {
			$fields->addFieldToTab('Root.Main', $dd = new DropdownField('ContentFile', _t('UserTemplatesExtension.CONTENT_FILE', 'File containing template'), $templates));
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
		
		$templates = DataList::create('UserTemplate')->filter(array('ID:not' => $this->ID));
		if ($templates->count()) {
			$templates = $templates->map();
			$fields->addFieldToTab('Root.Main', $kv = new KeyValueField('ActionTemplates', _t('UserTemplates.ACTION_TEMPLATES', 'Action specific templates'), array(), $templates));
			$kv->setRightTitle(_t('UserTemplates.ACTION_TEMPLATES_HELP', 'Specify an action name and select another user defined template to handle a specific action. Only used for Layout templates'));
		}
		
		
		$fields->addFieldToTab('Root.Main', $cssFiles);
		$fields->addFieldToTab('Root.Main', $jsFiles);

		return $fields;
	}

	protected function fileBasedTemplates() {
		$templates = array('' => 'None');
		foreach (glob(Director::baseFolder() . '/' . THEMES_DIR .'/*', GLOB_ONLYDIR) as $theme) {
			$themeName = ucfirst(basename($theme));
			if (is_dir($theme .'/user-templates')) {
				foreach (glob($theme.'/user-templates/*.ss') as $templateFile) {
					$templateFile = str_replace(Director::baseFolder() . '/', '', $templateFile);
					$templates[$templateFile] = $themeName . ' - ' . basename($templateFile);
				}
			}
		}
		
		return $templates;
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Title = FileNameFilter::create()->filter($this->Title);
		
		if (strlen($this->ContentFile)) {
			$templates = $this->fileBasedTemplates();
			if (!isset($templates[$this->ContentFile])) {
				$this->ContentFile = '';
			}
		}
	}

	public function onAfterWrite() {
		if (strlen($this->Content)) {
			$this->generateCacheFile();
		}
	}

	protected function generateCacheFile() {
		$file = $this->getCacheFilename();
		file_put_contents($file, $this->Content);
	}
	
	/**
	 * Return an override template for a specific action if given
	 * 
	 * @param string $action
	 */
	public function getActionOverride($action) {
		if ($this->ActionTemplates) {
			$actions = $this->ActionTemplates->getValues();
			if ($actions && isset($actions[$action])) {
				return DataList::create('UserTemplate')->byID($actions[$action]);
			}
		}
	}
	
	/**
	 * Get a filename that represents the 
	 * 
	 * @return string
	 */
	public function getTemplateFile() {
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
	protected function getCacheFilename() {
		$dir = BASE_PATH . '/usertemplates/template-cache/' . $this->Use . '/';
		Filesystem::makeFolder($dir);
		$file = $dir . '/' . $this->Title . '.ss';
		if (!file_exists($file)) {
			touch($file);
			chmod($file, 0664);
		}
		return $file;
	}
	
	public function includeRequirements() {
		$obj = $this->CustomCSSFiles();
		if ($obj) {
			foreach ($obj as $file) {
				Requirements::css($file->Filename);
			}
		}
		
		$obj = $this->CustomJSFiles();
		if ($obj) {
			foreach ($obj as $file) {
				Requirements::javascript($file->Filename);
			}
		}
		
	}
}
