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
	 * array(
	 *		['name' => 'Role1', 'permissions' => [
	 *					["resource" => "product.create", "values" => [3,2] ,"allowed" => 1],
	 *					["resource" => "product.edit", "values" => [1,4,5] ,"allowed" => 1],
	 *					["resource" => "product.delete", "values" => [1,"test"] ,"allowed" => 1]
	 *				]
	 *		]
	 * )
	 */
	'roles' => array(
		array(
			'name' => 'allowAll',
			'permissions' => array(
				array("resource" => "product.index", "values" => [] ,"allowed" => 1),
			)
		),
		array(
			'name' => 'allowValue1',
			'permissions' => array(
				array("resource" => "product.index", "values" => [1] ,"allowed" => 1),
			)
		),
		array(
			'name' => 'disallowAll',
			'permissions' => array(
				array("resource" => "product.index", "values" => [] ,"allowed" => 0),
			)
		),
		array(
			'name' => 'disallowValue1',
			'permissions' => array(
				array("resource" => "product.index", "values" => [1] ,"allowed" => 0),
			)
		),
		array(
			'name' => 'allowValue1_2_3',
			'permissions' => array(
				array("resource" => "product.index", "values" => [1, 2, 3] ,"allowed" => 1),
			)
		),
		array(
			'name' => 'disallowValue2_3_4',
			'permissions' => array(
				array("resource" => "product.index", "values" => [2, 3, 4] ,"allowed" => 0),
			)
		)
	),
	
	/**
	 * what is the default permission
	 */
	'default_permission' =>  false,
	
	
	'roleProviders' => [
		0 => '\Volicon\Acl\RoleProviders\UsersRoleProvider',
		1 => '\Volicon\Acl\RoleProviders\AdminRoleProvider'
	],
	
	'using_cache' => false,
	
	'cache_key' => '_volicon_acl_'
	
);

