<?php


namespace atk4\acl;


class Grid extends \atk4\ui\Grid {

	public function setModel(\atk4\data\Model $model, $columns = null)
    {
        parent::setModel($model, $columns);
        $this->add('\atk4\acl\Controller\Acl');
        return $this->model;
    }
}