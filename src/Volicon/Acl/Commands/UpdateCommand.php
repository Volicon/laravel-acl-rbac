<?php namespace Volicon\Acl\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Console\Input\InputOption;
use Volicon\Acl\Role;
use Volicon\Acl\Models\UserRole;
use Volicon\Acl\Models\RolePermission;
use Volicon\Acl\Models\GroupResources;
use Acl;

class UpdateCommand extends Command {
	protected $name = 'acl:update';
	protected $description = "Update acl rules from config";
	public function fire() {
		$updateRolesOpt = $this->option ( 'update-roles' );
		
		$role_permissions = Config::get ( "acl::config.roles" );
		$group_resources = Config::get ( "acl::config.group_resources" );
		
		$db_group_resources = GroupResources::all ();
		$db_role_permissions = RolePermission::all ();
		
		if (! $db_role_permissions->count ()) {
			$updateRolesOpt = true;
		}
		
		$group_resources_map = array ();
		foreach ( $db_group_resources as $row ) {
			$group_resources_map [$row->resource] = $row->permission_id;
		}
		
		$this->updateResorces ( $group_resources_map, $group_resources );
		
		$this->updateRolesResources ();
		
		if ($updateRolesOpt && count ( $role_permissions )) {
			
			// if there new roles add them, don't delete not list role
			$roles = Acl::getRoles();
			$rolesMap = array ();
			foreach ( $roles as $row ) {
				$rolesMap [$row->name] = $row->role_id;
			}
			
			\Acl::unguard ();
			
			foreach ( $role_permissions as $role ) {
				
				$aclRole = new Role($role);
				$role_name = $role ['name'];
				
				if (isset ( $rolesMap [$role_name] )) {
					$role['role_id'] = $rolesMap [$role_name];
					$aclRole = new Role($role);
					$aclRole->update();
				} else {
					$aclRole = new Role($role);
					$aclRole->add();
				}
			}
			
			\Acl::reguard ();
			
			$roles_ids = array_values ( $rolesMap );
			
			if (count ( $roles_ids )) {
				UserRole::whereNotIn ( 'role_id', $roles_ids )->delete ();
			}
		}
	}
	
	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions() {
		return array (
				array (
						'update-roles',
						null,
						InputOption::VALUE_NONE,
						"Update roles permissions." 
				) 
		);
	}
	protected function updateResorces(&$db_resources_map, &$config_resources) {
		$db_resources = array_keys ( $db_resources_map );
		$config_resources = array_keys ( $config_resources );
		
		// delete group resources that are not in config
		$not_in_config_resources = array_diff ( $db_resources, $config_resources );
		if (count ( $not_in_config_resources )) {
			GroupResources::whereIn ( 'resource', $not_in_config_resources )->delete ();
		}
		
		// delete role permissions then are not in config
		$deleted_permission_ids = array ();
		foreach ( $not_in_config_resources as $deleted_resource ) {
			$deleted_permission_ids [] = $db_resources_map [$deleted_resource];
		}
		
		if (count ( $deleted_permission_ids )) {
			RolePermission::whereIn ( 'permission_id', $deleted_permission_ids )->delete ();
		}
		
		// add the new resources
		$new_resources = array_diff ( $config_resources, $db_resources );
		\Eloquent::unguard ();
		foreach ( $new_resources as $resource ) {
			GroupResources::create ( array (
					'resource' => $resource 
			) );
		}
		\Eloquent::reguard ();
	}
	public function updateRolesResources() {
		$roles = Acl::getRoles ();
		\Eloquent::unguard ();
		Acl::unguard ();
		/* @var $role \Volicon\Acl\Role */
		foreach ( $roles as $role ) {
			$role->update();
		}
		Acl::reguard ();
		\Eloquent::reguard ();
	}
}
