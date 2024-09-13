<?php

namespace Symbiote\UserTemplates\Tests;

use Page;
use PageController;
use SilverStripe\CMS\Model\SiteTree;
use Symbiote\UserTemplates\UserTemplate;
use Symbiote\UserTemplates\UserTemplatesExtension;
use Symbiote\UserTemplates\UserTemplatesControllerExtension;
use SilverStripe\Dev\SapphireTest;

class UserTemplatesExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        SiteTree::class => [UserTemplatesExtension::class],
        PageController::class => [UserTemplatesControllerExtension::class]
    ];

    public function testCreateUserDefinedTemplate()
    {
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

        $out = $page->renderWith(['Page', 'Page']);

        $this->assertTrue(strpos($out, 'PageContent') > 0);
        $this->assertTrue(!str_contains($out, 'UserTemplate 1'));

        // bind the user template
        $page->LayoutTemplateID = $ut->ID;
        $page->write();

        $action = 'index';
        $ctrl = PageController::create($page);
        $viewer = $ctrl->getViewer($action);

        // to mimic the patch needed for this module
        $ctrl->extend('updateViewer', $action, $viewer);

        $out = $viewer->process($ctrl);
        $this->assertTrue(strpos((string) $out, 'UserTemplate 1 PageContent') > 0);
    }

    public function testRegenerateOnDelete()
    {
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

    public function testRegenerateOnChange()
    {
        $this->logInWithPermission();

        $ut = new UserTemplate();
        $ut->Title = 'Template 1';
        $ut->Use = 'Layout';
        $ut->Content = 'UserTemplate 1 $Content';

        $ut->write();

        $file = $ut->getTemplateFile();

        $ut->Content = "New template";

        sleep(2);
        $ut->write();

        $nextFile = $ut->getTemplateFile();

        $basename = basename($nextFile);

        $this->assertNotEquals($file, $nextFile);
        $this->assertEquals($ut->Title . '-' . strtotime($ut->LastEdited) . '.ss', $basename);
    }
}
