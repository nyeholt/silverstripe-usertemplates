<?php

class UserTemplatesExtension extends DataExtension {
	
	public static $db = array(
		'InheritTemplateSettings'	=> 'Boolean',
	);
	
	public static $has_one = array(
		'MasterTemplate'			=> 'UserTemplate',
		'LayoutTemplate'			=> 'UserTemplate',
	);
	
	public static $defaults = array(
		'InheritTemplateSettings'		=> 1
	);

	public function updateSettingsFields(FieldList $fields) {
		$layouts = DataList::create('UserTemplate')->filter(array('Use' => 'Layout'));
		$masters = DataList::create('UserTemplate')->filter(array('Use' => 'Master'));

		$fields->addFieldToTab('Root.Theme', DropdownField::create('MasterTemplateID', 'Master Template', $masters->map(), '', null)->setEmptyString('None'));
		$fields->addFieldToTab('Root.Theme', DropdownField::create('LayoutTemplateID', 'Layout Template', $layouts->map(), '', null)->setEmptyString('None'));
		$fields->addFieldToTab('Root.Theme', CheckboxField::create('InheritTemplateSettings', 'Inherit Settings'));
		
		$effectiveMaster = $this->effectiveTemplate();
		$effectiveLayout = $this->effectiveTemplate('Layout');
		
		if($effectiveMaster){
			$fields->addFieldToTab('Root.Theme', ReadonlyField::create('EffectiveMaster', 'Effective master template', $effectiveMaster->Title));	
		}
		
		if($effectiveLayout){
			$fields->addFieldToTab('Root.Theme', ReadonlyField::create('EffectiveLayout', 'Effective layout template', $effectiveLayout->Title));	
		}
		
		return $fields;
	}
	
	/**
	 * 
	 * @param string $type
	 *					Whether to get a master or layout template
	 * @param string $action
	 *					If there's a specific action involved for the template
	 * @return type
	 */
	public function effectiveTemplate($type = 'Master', $action = null) {
		$name = $type . 'Template';
		$id = $name . 'ID';
		if ($this->owner->$id) {
			$template = $this->owner->getComponent($name);
			if ($action) {
				// see if there's an override for this specific action
				$template = $template->getActionOverride($action);
			}
			return $template;
		}

		if ($this->owner->InheritTemplateSettings && $this->owner->ParentID) {
			return $this->owner->Parent()->effectiveTemplate($type, $action);
		}
	}

}

class UserTemplatesControllerExtension extends Extension {
	
	public function updateViewer($action, $viewer) {
		$master = $this->owner->data()->effectiveTemplate('Master');
		if ($master && $master->ID) {
			// set the main template
			$master->includeRequirements();
			$viewer->setTemplateFile('main', $master->getTemplateFile());
		}

		$layout = $this->owner->data()->effectiveTemplate('Layout', $action);

		if ($layout && $layout->ID) {
			$layout->includeRequirements();
			$viewer->setTemplateFile('Layout', $layout->getTemplateFile());
		}
	}
}