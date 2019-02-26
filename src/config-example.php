<?php

$config['acl']=[
	'AclModelClass'=>'\atk4\acl\Model\Acl',
	'UserModel' =>'\your\namespace\Model\Employee',
	'SuperUserRoles'=>['SuperUser'],
	'created_by_field'=>'created_by_id',
	'status_field'=>'status',
	'assigned_field'=>'assigned_to_id'
];