<?php

namespace atk4\acl\TableColumn;



class AclEditDelete extends \atk4\ui\TableColumn\Actions {

	// ['Active'=>['de_activate','send_email'],'InActive'=>['activate','raise_issue']];
	private $status_actions=[];
	
	public function __construct($status_actions, $acl_controller){
		$this->acl_controller = $acl_controller;
		$this->status_actions = $status_actions;
	}

	public function addAction($button, $callback, $confirm = false)
    {

    	$action_params=[];
    	if(isset($_REQUEST[$this->acl_controller->name])){
	    	$model_id = $_REQUEST[$this->acl_controller->name];
	    	$action = $_REQUEST[$this->acl_controller->name.'_act'];
	    	$action_params[$this->acl_controller->name] = $model_id;
	    	$action_params[$this->acl_controller->name.'_act'] = $action;
    	}

        $name = $this->name.'_action_'.(count($this->actions) + 1);

        if (!is_object($button)) {
            $button = new \atk4\ui\Button($button);
        }
        $button->app = $this->table->app;

        $this->actions[$name] = $button;
        $button->addClass('b_'.$name);
        $button->addClass('compact');
        $this->table->on('click', '.b_'.$name, $callback, array_merge($action_params,[$this->table->jsRow()->data('id'), 'confirm' => $confirm]));

        return $button;
    }

	public function getDataCellTemplate(\atk4\data\Field $f = null)
    {
        return '{$actions}';
    }

	public function getHtmlTags($row, $field)
    {
    	$status=$row['status']?:0;
    	$status_actions = $this->status_actions[$status];

        $output = '';
        foreach ($this->actions as $action) {
        	$disabled = false;
        	$to_check = is_object($action->icon)?$action->icon->content : $action->icon;
        	$to_check = ($to_check=='edit')?'edit':'delete';
        	if($status_actions[$to_check][0] == 'None') $disabled = true;
        	if(($status_actions[$to_check][0] == 'SelfOnly' || $this->acl_controller->isAssignField($status,$to_check)) && $row[$this->acl_controller->getConditionalField($status,$to_check)] != $this->acl_controller->app->auth->model->id) $disabled = true; 
        	if($this->status_actions[$row['status']][$to_check]){
        		if($disabled){
        			$button = new \atk4\ui\Button(['icon'=>($to_check=='edit')?'edit':'red trash','disabled'=>true]);
        			$output .= $button->getHTML();
        		}else{
		            $output .= $action->getHTML();
        		}
        	}
        }

        return ['actions'=>$output];
    }
}