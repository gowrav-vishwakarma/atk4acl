<?php


namespace atk4\acl;


class CRUD extends \atk4\ui\CRUD {

    protected $actionDecorator = '\atk4\acl\TableColumn\AclEditDelete';
    protected $acl_controller = null;

	public function setModel(\atk4\data\Model $m, $defaultFields = null)
    {
        if ($defaultFields !== null) {
            $this->fieldsDefault = $defaultFields;
        }

        \atk4\ui\Grid::setModel($m, $this->fieldsRead ?: $this->fieldsDefault);
        $this->model->unload();

        $this->acl_controller = $this->add('\atk4\acl\Controller\Acl');

        if ($this->canCreate) {
            $this->initCreate();
        }

        if ($this->canUpdate) {
            $this->initUpdate();
        }

        if ($this->canDelete) {
            $this->initDelete();
        }

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