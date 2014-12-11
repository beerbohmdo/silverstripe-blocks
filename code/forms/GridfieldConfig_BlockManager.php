<?php

class GridFieldConfig_BlockManager extends GridFieldConfig{

	public $blockManager;

	public function __construct($canAdd = true, $canEdit = true, $canDelete = true) {
		parent::__construct();

		$this->blockManager = Injector::inst()->get('BlockManager');
		
		// Get available Areas (for page) or all in case of SiteConfig/ModelAdmin
		if(Controller::curr()->class == 'CMSPageEditController'){
			$currentPage = Controller::curr()->currentPage();
			$areasFieldSource = $this->blockManager->getAreasForPageType($currentPage->ClassName);
		} else {
			$areasFieldSource = $this->blockManager->getAreasForTheme();
		}
		
		// EditableColumns only makes sense on Saveable parenst (eg Page), or inline changes won't be saved
		if(Controller::curr()->class != 'BlockAdmin'){
			$this->addComponent($editable = new GridFieldEditableColumns());
			$displayfields = array(
				'singular_name' => array('title' => 'Block Type', 'field' => 'ReadonlyField'),
				'Name'        	=> array('title' => 'Name', 'field' => 'ReadonlyField'),
				'Area'	=> array(	
					'title' => 'Block Area
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
						// &nbsp;s prevent wrapping of dropdowns
					'callback' => function() use ($areasFieldSource){
							return new DropdownField('Area', 'Block Area', $areasFieldSource);
						} 
				),
				'Published'		=> array('title' => 'Published (global)', 'field' => 'CheckboxField'),
				'PageListAsString' => array('title' => 'Used on pages', 'field' => 'ReadonlyField'),
				//'InheritedFrom' => array('title' => 'Inherited From', 'field' => 'ReadonlyField'),
				//'Weight'    	=> array('title' => 'Weight', 'field' => 'NumericField'),
				//'PagesCount'  	=> array('title' => 'Number of Pages', 'field' => 'ReadonlyField'),
			);
			$editable->setDisplayFields($displayfields);
		} else {
			$this->addComponent($dcols = new GridFieldDataColumns());
			$displayfields = array(
				'singular_name' => array('title' => 'Block Type', 'field' => 'ReadonlyField'),
				'Name'        	=> array('title' => 'Name', 'field' => 'ReadonlyField'),
				'Area'			=> array('title' => 'Block area', 'field' => 'ReadonlyField'),
				'PublishedString' => array('title' => 'Published (global)', 'field' => 'ReadonlyField'),
				'PageListAsString' => array('title' => 'Used on pages', 'field' => 'ReadonlyField'),
			);
			$dcols->setDisplayFields($displayfields);
		}

		$this->addComponent(new GridFieldButtonRow('before'));
		$this->addComponent(new GridFieldToolbarHeader());
		$this->addComponent(new GridFieldDetailForm());
		$this->addComponent($sort = new GridFieldSortableHeader());
		$this->addComponent($filter = new GridFieldFilterHeader());

		$filter->setThrowExceptionOnBadDataType(false);
		$sort->setThrowExceptionOnBadDataType(false);

		if($canAdd){
			$this->addComponent(new GridFieldAddNewButton('buttons-before-left'));	
		}
		
		if($canEdit){
			$this->addComponent(new GridFieldEditButton());	
		}

		if($canDelete){
			$this->addComponent(new GridFieldDeleteAction(true));
		}

		return $this;		
		
	}

	public function addExisting($pageClass = null){
		if($pageClass){
			$areas = $this->blockManager->getAreasForPageType($pageClass);	
		}else{
			$areas = $this->blockManager->getAreasForTheme();	
		}

		if(!is_array($areas)){
			return $this;
		}

		$this->addComponent($add = new GridFieldAddExistingSearchButton());
		
		$list = Block::get()->filter('Area', array_keys($areas));

		// TODO find a more appropriate way of doing this
		if(Block::has_extension('MultisitesAware')){
			$list = $list->filter('SiteID', Multisites::inst()->getActiveSite()->ID);
		}

		$add->setSearchList($list);

		return $this;
	}


	public function addBulkEditing(){
		if(class_exists('GridFieldBulkManager')){
			$this->addComponent(new GridFieldBulkManager());
		}
		return $this;
	}

}
