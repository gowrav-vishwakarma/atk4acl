<?php


namespace atk4\acl;


class CRUD extends \atk4\ui\CRUD {

	public function setModel(\atk4\data\Model $m, $defaultFields = null)
    {
        if ($defaultFields !== null) {
            $this->fieldsDefault = $defaultFields;
        }

        \atk4\ui\Grid::setModel($m, $this->fieldsRead ?: $this->fieldsDefault);
        $this->model->unload();

        $this->add('\atk4\acl\Controller\Acl');

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
}