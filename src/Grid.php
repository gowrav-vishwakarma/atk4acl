<?php


namespace atk4\acl;


class Grid extends \atk4\ui\Grid {
    
    public $actionDecorator = '\atk4\acl\TableColumn\AclEditDelete';
    public $acl_controller = null;

	public function setModel(\atk4\data\Model $model, $columns = null)
    {
        parent::setModel($model, $columns);
        
        $this->acl_controller = $this->add('\atk4\acl\Controller\Acl');
        
        return $this->model;
    }

    public function addAction($button, $action, $confirm = false)
    {
        if (!$this->actions) {
            $this->actions = $this->table->addColumn('actions', new $this->actionDecorator($this->acl_controller->status_actions, $this->acl_controller));
        }
        return $this->actions->addAction($button, $action, $confirm);
    }
}