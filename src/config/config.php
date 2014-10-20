<?php

return array(
	
	/**
	 * Group resources, resources can be routes or any custom resources like models
	 * 
	 * resources field - resources that need check also the values 
	 * access_resources field - resources that check regardless the values, general aprove
	 * 
	 * for example:
	 * array(
	 *		'products.view' => [
	 *			'resources' => [
	 *				'products.show'
	 *			],
	 *			'access_resources' => [
	 *				'products.index'
	 *			]
	 *		]
	 * 
	 */
	'group_resources' => array(),
	
	/**
	 * routes that allow for any user
	 */
	'allways_allow_resources' => [],
	
	/** 
	 * Initial roles and permissions, you can add manually with Acl::addRole, AddRolePermission
	 * 
	 * if the "values" are empy so the role relted to all values.
	 * if allowed=false, it meant that all values excpet for the "values" defined are allow
	 * 
	 * array(
	 *		['name' => 'Role1', 'permissions' => [
	 *					["resource" => "product.create", "values" => [3,2] ,"allowed" => 1],
	 *					["resource" => "product.edit", "values" => [1,4,5] ,"allowed" => 1],
	 *					["resource" => "product.delete", "values" => [1,"test"] ,"allowed" => 1]
	 *				]
	 *		]
	 * )
	 */
	'roles' => array(),
	
	/**
	 * what is the default permission
	 */
	'default_permission' => false,
	
	
	'roleProviders' => [
		0 => '\Volicon\Acl\RoleProviders\UsersRoleProvider',
		1 => '\Volicon\Acl\RoleProviders\AdminRoleProvider'
	]
	
);

