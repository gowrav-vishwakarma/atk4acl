<?php

$config['acl']=[
	'model_role_field'=>'role_id',
	'role_class' =>'\atk4\acl\Model\Role',
	'acl_model_class'=>'\atk4\acl\Model\Acl',
	'UserModel' =>'\your\namespace\Model\Employee',
	'SuperUserRoles'=>['SuperUser'],
	'created_by_field'=>'created_by_id',
	'status_field'=>'status',
	'assigned_field'=>'assigned_to_id'
];