<?php

namespace Symbiote\UserTemplates;


use SilverStripe\View\SSViewer;



class UserTemplatesSSViewer extends SSViewer {

	public function __construct($templateList) {
		if(!is_array($templateList) && substr((string) $templateList,-3) == '.ss') {
			$this->setTemplate($templateList);
		} else {
            $this->setTemplate($templateList);
//            $this->chosenTemplates = ThemeResourceLoader::inst()->findTemplate(
//                $templateList, SSViewer::get_themes()
//            );
			//Debug::show($this->chosenTemplates);
		}



		if(!$this->chosen) {
		  $templateList = (is_array($templateList)) ? $templateList : array($templateList);

		  user_error("None of these templates can be found in theme '"
			. self::current_theme() . "': ". implode(".ss, ", $templateList) . ".ss", E_USER_WARNING);
		}
	}


}
