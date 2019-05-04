<?php

namespace atk4\acl\TableColumn;
/**
 * Action Decorator class
 * Creates DropDown Menu in status column and on click calls action with PK of row
 */

class AclActionDecorator extends \atk4\ui\TableColumn\Generic {
	private $acl_controller=null;

	// ['Active'=>['de_activate','send_email'],'InActive'=>['activate','raise_issue']];
	private $status_actions=[];
	
	public function __construct($status_actions, $acl_controller){
		$this->acl_controller = $acl_controller;
		$this->status_actions = $status_actions;
	}

	public function init(){
		parent::init();
		$thisname = $this->name;
		$this->table=$this->acl_controller->view;


		$this->method_callback = $this->table->_add(new \atk4\ui\CallbackLater());
        $this->method_callback->set(function ()use($thisname) {
        	$model_id = $_REQUEST[$thisname];
        	$action = $_REQUEST[$thisname.'_act'];
            if(!$action) return;
        	if($this->acl_controller->model->hasMethod($action)){
        		$this->acl_controller->model->load($model_id);
        		$this->acl_controller->model->{$action}();
        	}else{
        		throw new \atk4\ui\Exception(['Method not deifined '.$action.' in '.get_class($this->acl_controller->model),'class'=>get_class($this->acl_controller->model), 'method'=>$action ]);
        	}

            $reload = $this->table->reload ?: $this->table;

            $this->table->app->terminate($reload->renderJSON());
        });

        $this->page_callback = $this->acl_controller->view->add('VirtualPage');
        $this->page_callback->set(function($page)use($thisname){
        	$model_id = $_REQUEST[$thisname];
        	$action = $_REQUEST[$thisname.'_act'];

            if(!$action) return;

        	$this->acl_controller->app->addURLArgs($thisname, $model_id);
        	$this->acl_controller->app->addURLArgs($thisname.'_act', $action);



        	if($this->acl_controller->model->hasMethod('page_'.$action)){
	        	$this->acl_controller->model->load($model_id);
	        	$page_return = $this->acl_controller->model->{'page_'.$action}($page);
	        	// TODO manage Page Return automatically
	        }else{
        		throw new \atk4\ui\Exception(['Method '.$action.' not deifined in '.get_class($this->acl_controller->model),'class'=>get_class($this->acl_controller->model), 'method'=>$action ]);
	        }
        });

		$this->table->on('click', '.acl-action.method')->atkAjaxec([
            'uri'         => $this->method_callback->getJSURL(),
            'uri_options' => [$thisname => (new \atk4\ui\jQuery(new \atk4\ui\jsExpression('this')))->data('id'), $thisname.'_act'=>(new \atk4\ui\jQuery(new \atk4\ui\jsExpression('this')))->data('action')],
        ]);

		// < =========  1. COMES FROM HERE
        $this->table->on('click', '.acl-action.page')->atkAjaxec([
            'uri'         => $this->page_callback->getJSURL('cut'),
            'method'	  => 'GET',
            'uri_options' => [$thisname => (new \atk4\ui\jQuery(new \atk4\ui\jsExpression('this')))->data('id'), $thisname.'_act'=>(new \atk4\ui\jQuery(new \atk4\ui\jsExpression('this')))->data('action')],
        ]);
	}

	public function getHtmlTags	($row, $field)
    {
        $status=$field->get()?:0;
    	$status_actions = $this->status_actions[$status];
    	$dropdown_string =	'<div class="ui compact menu">
    							<div class="ui simple dropdown item">'.$field->get().'<i class="dropdown icon"></i>
    								<div class="menu">';
		
		foreach ($status_actions as $act => $detail) {
			if($detail[0] == 'None' || ($detail['display'] !=='page' && $detail['display'] !== 'method')) continue;
            if(($detail[0] == 'SelfOnly' || $this->acl_controller->isAssignField($status,$act)) && $row[$this->acl_controller->getConditionalField($status,$act)] != $this->acl_controller->app->auth->model->id) continue; 

			$act_title = ucwords(str_replace('_', ' ', $act));
			$dropdown_string .= 		'<div class="item acl-action '. $detail['display'] .'" data-id="'.$row['id'].'" data-action="'.$act.'">'.$act_title.'</div>';
		}

		$dropdown_string .='		</div>
								</div>
							</div>';

        
        return [$field->short_name => $dropdown_string];
    }
}