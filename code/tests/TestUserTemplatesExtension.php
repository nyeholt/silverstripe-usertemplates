<?php

class TestUserTemplatesExtension extends SapphireTest {
	protected $requiredExtensions = array(
		"SiteTree" => array("UserTemplatesExtension"),
		'Page_Controller' => 'UserTemplatesControllerExtension'
	);

	public function testCreateUserDefinedTemplate() {
		$this->logInWithPermission();

		$ut = new UserTemplate();
		$ut->Title = 'Template 1';
		$ut->Use = 'Layout';
		$ut->Content = 'UserTemplate 1 $Content';

		$ut->write();

		$page = Page::create();
		$page->Title = 'My page';
		$page->Content = 'PageContent';

		$page->write();

		$out = $page->renderWith(array('Page', 'Page'));

		$this->assertTrue(strpos($out, 'PageContent') > 0);
		$this->assertTrue(strpos($out, 'UserTemplate 1') === false);

		// bind the user template
		$page->LayoutTemplateID = $ut->ID;
		$page->write();

		$ctrl = Page_Controller::create($page);
		$viewer = $ctrl->getViewer('index');

		$out = $viewer->process($ctrl);
		$this->assertTrue(strpos($out, 'UserTemplate 1 PageContent') > 0);
	}

	public function testRegenerateOnDelete() {
		$this->logInWithPermission();

		$ut = new UserTemplate();
		$ut->Title = 'Template 1';
		$ut->Use = 'Layout';
		$ut->Content = 'UserTemplate 1 $Content';

		$ut->write();

		$file = $ut->getTemplateFile();

		clearstatcache();
		$size = filesize($file);
		$this->assertTrue($size > 0);

		unlink($file);

		$file = $ut->getTemplateFile();

		clearstatcache();
		$size = filesize($file);
		$this->assertTrue($size > 0);
	}
}