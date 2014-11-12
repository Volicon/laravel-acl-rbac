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
		$group_resources = GroupResources::getGroupResources();
		
		$perm_ids = [];
		
		/* @var $perm \Volicon\Acl\Permission */
		foreach($role->permissions as $key=>$perm) {
			$permission_id = $group_resources->search($perm->resource);
			$perm_ids[] = $permission_id;
			$role->permissions[$key]->permission_id = $permission_id;
			
			if($permission_id === FALSE) {
				throw new \Exception('Resource not exists: '.$perm->resource);
			}
		}
		
		$db_role_perm = RolePermission::where ( 'role_id', '=', $role->role_id )->get()->keyBy('permission_id');
		$db_perm_ids = $db_role_perm->lists('permission_id');
		$perm_to_delete = array_diff($db_perm_ids, $perm_ids);
		$perm_to_add = array_diff($perm_ids, $db_perm_ids);
		
		if($perm_to_delete) {
			RolePermission::where ( 'role_id', '=', $role->role_id )->whereIn('permission_id', $perm_to_delete)->delete ();
		}
		
		foreach($role->permissions as $perm) {
			if(in_array($perm->permission_id, $perm_to_add)) {
				RolePermission::create([
					'role_id'		=> $role->role_id,
					'permission_id'	=> $perm->permission_id,
					'values'		=> json_encode($perm->values),
					'allowed'		=> $perm->allowed
				]);
			} else {
				RolePermission::where('role_id', '=', $role->role_id)
						->where('permission_id', '=', $perm->permission_id)
						->update([
							'values'	=> json_encode($perm->values),
							'allowed'	=> $perm->allowed
						]);
			}
		}
		
	}

}
