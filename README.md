# UserTemplates

TEST

Allows cms users to apply templates that are created and defined within the
CMS directly, including CSS and JavaScript files, to trees of content. 

## Getting started

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

## Usage

Create a new template from the admin/templates section.

The **Use** of the template indicates whether it will be applied as a 'main'
template or just the 'Layout' of the page.

The **Content** field should be defined as per a normal SilverStripe template

Any CSS or JS files uploaded will be included using Requirements:: calls


On a page, navigate to the Settings -> Theme tab. Select the relevant template
to use.

If "Inherit" is set, then any parent page with a custom template defined will
be used.


## Templated elements

You'll need to add a custom ElementArea.ss template to your theme (on the path
`themes/your-theme/templates/DNADesign/Elemental/Models/ElementalArea.ss`) 
that contains something similar to the following

```
<% if $ElementControllers.count > 0 %>
    <% loop $ElementControllers %>
        <div class="ElementAreaElement <% if Last %>ElementAreaElement--Last<% end_if %>">
            <% if $isTemplated %>
            $TemplatedContent
            <% else %>
            $Me
            <% end_if %>
        </div>
    <% end_loop %>
<% end_if %>


```

to make use of it. 

Then, bind the extensions

```
---
Name: element_config
---
DNADesign\Elemental\Models\BaseElement:
  extensions:
    - Symbiote\UserTemplates\TemplatedElementExtension

DNADesign\Elemental\Controllers\ElementController:
  extensions:
    - Symbiote\UserTemplates\TemplatedElementControllerExtension
```

## Credits

Icon via https://icons8.com/license/
