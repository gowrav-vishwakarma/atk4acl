<?php

namespace \atk4\acl\TableColumn;



class EditDecorator extends \atk4\ui\TableColumn\Generic {
	private $acl_controller=null;

	// ['Active'=>['de_activate','send_email'],'InActive'=>['activate','raise_issue']];
	private $status_actions=[];
	
	public function __construct($status_actions, $acl_controller){
		$this->acl_controller = $acl_controller;
		$this->status_actions = $status_actions;
	}

	public function getHtmlTags	($row, $field)
    {
    	$status_actions = $this->status_actions[$field->get()];

    	return;
        
        return [$field->short_name => $dropdown_string];
    }
}