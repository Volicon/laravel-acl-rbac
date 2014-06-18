<?php

return array(
	
	/**
	 * List of models you can use in ACL.
	 * It will filter models only from this list.
	 * 
	 */
	'allow_models' => array(
	),
	
	/**
	 * 
	 * example:
	 *  you have resource "product"
	 *	Route::resource('product', 'productController');
	 *  Route::resource('catalog', 'catalogController');
	 */
	
	/**
	 * List of resources you can use in ACL.
	 * It will check resources only from this list.
	 * 
	 * For example:
	 * 'allow_resources' => array(
	 *		'product',
	 *		'catalog'
	 * ),
	 */
	'allow_resources' => array('product', 'catalog'),
	
	/**
	 * List of actions handle by ACL
	 */
	'resourceActions' => array('index', 'create', 'store', 'show', 'edit', 'update', 'destroy'),
	
	/** 
	 * List of special routes which not resource routing.
	 * Example: array('home.helloWorld', ROUTING_NAME.ACTION_NAME);
	 * 
	 */
	'special_resources' => array(),
	
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
	'default_permission' => Acl::DISALLOW
	
);

