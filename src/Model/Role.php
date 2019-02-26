<?php


namespace atk4\acl\Model;


class Role extends \atk4\data\Model {
	
	public $table='role';
	public $caption ="Rost";


	public function init(){
        parent::init();
        

        $this->addFields([
            ['name']
        ]);

        // $this->hasMany('Employee',new \nebula\we\Model\Employee);

        // (new \atk4\schema\Migration\MySQL($this))->migrate();
    }
}