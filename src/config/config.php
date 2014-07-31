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
	'allow_resources' => array(),
	
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
	'default_permission' => Acl::DISALLOW
	
);

