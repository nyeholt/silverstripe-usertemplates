<?php

/**
 * A template that a user can create and apply within the system
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 */
class UserTemplate extends DataObject {
	public static $db = array(
		'Title'			=> 'Varchar',
		'Description'	=> 'Varchar',
		'Use'			=> "Enum('Layout,Master')",
		'Content'		=> 'Text',
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
		
		$fields->push($cssFiles);
		$fields->push($jsFiles);
		
		
		
		return $fields;
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->Title = FileNameFilter::create()->filter($this->Title);
	}
	
	public function onAfterWrite() {
		if (strlen($this->Content)) {
			$this->generateCacheFile();
		}
	}
	
	protected function generateCacheFile() {
		$file = $this->getTemplateFile();
		file_put_contents($file, $this->Content);
	}
	
	public function getTemplateFile() {
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
