<?php namespace Volicon\Acl\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;

use Volicon\Acl\Models\Role;
use Volicon\Acl\Models\UserRole;
use Volicon\Acl\Models\RolePermission;
use Volicon\Acl\Models\PermissionAttr;

class UpdateCommand extends Command {
	
	protected $name = 'acl:update';
	protected $description = "Update acl rules from config";

	public function fire() {
		
		$updateRolesOpt = $this->option('update-roles');
		
		$role_permissions	= Config::get("acl::config.roles");
		
		$permissionAttr		= PermissionAttr::all();
		$rolePermission	= RolePermission::all();
		
		if(!$rolePermission->count()) {
			$updateRolesOpt = true;
		}
		
		$permissionAttrMap = array();
		foreach ($permissionAttr as $row) {
			$permissionAttrMap[$row->resource] = $row->permission_id;
		}
		
		$config_resources	= $this->getConfigResources();
		
		$this->updateResorces($permissionAttrMap, $config_resources);
		
		if($updateRolesOpt && count($role_permissions)) {
			
			//if there new roles add them, don't delete not list role
			$roles		= Role::all();
			$rolesMap	= array();
			foreach ($roles as $row) {
				$rolesMap[$row->name] = $row->role_id;
			}

			foreach ($role_permissions as $role) {
				$role_name = $role['name'];
				
				if(isset($rolesMap[$role_name])) {					
					$role_id = $rolesMap[$role_name];
					\Acl::updateRole($role_id, $role);
				} else {
					\Acl::addRole($role);
				}
			}
			
			$roles_ids = array_values($rolesMap);

			if(count($roles_ids)) {
				UserRole::whereNotIn('role_id', $roles_ids)->delete();
			}
			
		}
		
	}
	
	/**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('update-roles', null, InputOption::VALUE_NONE, "Update roles permissions.")
        );
    }

	protected function getConfigResources() {
		
		$result = array();
		
		$allow_models		= Config::get("acl::config.allow_models", array());
		$resource_actions	= Config::get("acl::config.resourceActions", array());
		$allow_resources	= Config::get("acl::config.allow_resources", array());
		$special_resources	= Config::get("acl::config.special_resources", array());
		
		$models_actions		= array('select', 'update', 'delete');
		foreach($allow_models as $model) {
			foreach($models_actions as $action) {
				$result[] = $model.'.'.$action;
			}
		}
		
		foreach($allow_resources as $resource) {
			foreach($resource_actions as $action) {
				$result[] = snake_case($resource, '-').'.'.$action;
			}
		}
		
		foreach ($special_resources as $resource) {
			$result[] = $resource;
		}
		
		return $result;
		
	}

	protected function updateResorces(&$db_resources_map, &$config_resources) {
		$db_resources		= array_keys($db_resources_map);
		
		//delete permissions attr that are not in config
		$not_in_config_resources = array_diff($db_resources, $config_resources);
		if(count($not_in_config_resources)) {
			PermissionAttr::whereIn('resource', $not_in_config_resources)->delete();
		}
		
		//delete role permissions then are not in config
		$deleted_permission_ids = array();
		foreach($not_in_config_resources as $deleted_resource) {
			$deleted_permission_ids[] = $db_resources_map[$deleted_resource];
		}
		
		if(count($deleted_permission_ids)) {
			RolePermission::whereIn('permission_id', $deleted_permission_ids)->delete();
		}
		
		//add the new resources
		$new_resources = array_diff($config_resources, $db_resources);
		\Eloquent::unguard();
		foreach($new_resources as $resource) {
			PermissionAttr::create(array(
				'resource'	=> $resource,
				'name'		=> $resource
			));
		}
		\Eloquent::reguard();
	}

}
