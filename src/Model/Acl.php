<?php


namespace atk4\acl\Model;


class Acl extends \atk4\data\Model {
	
	public $table='acl_permission';
    public $acl_type='Acl_Permission';
    public $caption='ACL';

    public $actions = [
        'Active'=>['view','edit','delete'],
        'InActive'=>['view','edit','delete'],
    ];

	public function init(){
        parent::init();

        // You can override and change class but field name should be role_id
        $this->hasOne('role_id',new \atk4\acl\Model\Role)
        ->withTitle();

        $this->addFields([
        	['acl_type'],
        	['can_add','type'=>'boolean'],
            ['acl','type'=>'text']
        ]);

        // (new \atk4\schema\Migration\MySQL($this))->migrate();

    }
}