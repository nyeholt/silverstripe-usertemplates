<?php

class UserTemplatesSSViewer extends SSViewer
{

    public function __construct($templateList)
    {
        // flush template manifest cache if requested
        if (isset($_GET['flush']) && $_GET['flush'] == 'all') {
            if (Director::isDev() || Director::is_cli() || Permission::check('ADMIN')) {
                self::flush_template_cache();
            } else {
                return Security::permissionFailure(null, 'Please log in as an administrator to flush the template cache.');
            }
        }
        
        if (!is_array($templateList) && substr((string) $templateList, -3) == '.ss') {
            $this->chosenTemplates['main'] = $templateList;
        } else {
            $this->chosenTemplates = SS_TemplateLoader::instance()->findTemplates(
                $templateList, self::current_theme()
            );
            //Debug::show($this->chosenTemplates);
        }



        if (!$this->chosenTemplates) {
            $templateList = (is_array($templateList)) ? $templateList : array($templateList);
          
            user_error("None of these templates can be found in theme '"
            . self::current_theme() . "': ". implode(".ss, ", $templateList) . ".ss", E_USER_WARNING);
        }
    }
}
