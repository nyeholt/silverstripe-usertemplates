# Getting started

Add the following to your Page\_Controller class:

    public function getViewer($action) {
        $viewer = parent::getViewer($action);

		$this->extend('updateViewer', $action, $viewer);

        return $viewer;
    }


Add configuration to bind the extension into place

```
SilverStripe\CMS\Model\SiteTree:
  extensions: 
    - Symbiote\UserTemplates\UserTemplatesExtension
PageController:
  extensions:
    - Symbiote\UserTemplates\UserTemplatesControllerExtension
```
