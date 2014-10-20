<?php namespace Volicon\Acl\Models;

use Volicon\Acl\Role as AclRole;

class RolePermission extends \Eloquent {
	protected $table = 'role_permission';
	protected $fillable = [ 
			'role_id',
			'permission_id',
			'values',
			'allowed' 
	];
	public $timestamps = false;
	protected $primaryKey = 'role_id';
	public function role() {
		return $this->HasOne ( 'Volicon\Acl\Models\Role', 'role_id' );
	}

	public static function updateRolePermissions(AclRole $role) {
		RolePermission::where ( 'role_id', '=', $role->role_id )->delete ();
		
		$group_resources = GroupResources::getGroupResources();
		
		/* @var $perm \Volicon\Acl\Permission */
		foreach($role->permissions as $perm) {
			$permission_id = $group_resources->search($perm->resource);
			
			if($permission_id === FALSE) {
				throw new \Exception('Resource not exists: '.$perm->resource);
			}
			
			RolePermission::create([
				'role_id'		=> $role->role_id,
				'permission_id'	=> $permission_id,
				'values'		=> json_encode($perm->values),
				'allowed'		=> $perm->allowed
			]);
			
		}
		
	}

}
