<?php namespace Volicon\Acl\Models;

use Volicon\Acl\AclRole;
use Illuminate\Support\Facades\Config;

class UserRole extends \Eloquent {
	protected $table = 'user_role';
	protected $fillable = [ 
			'user_id',
			'role_id' 
	];
	protected $primaryKey = 'role_id';
	public $timestamps = FALSE;
	
	public function role() {
		return $this->hasOne ( 'Volicon\Acl\Models\Role', 'role_id' );
	}

	public static function updateRoleUsers(AclRole $role) {
		
		$current_users = UserRole::where('role_id', '=', $role->role_id)->get();
		$defaults_users = $current_users->where('default')->lists('user_id');
		
		$users_to_delete = array_diff($current_users->lists('user_id'), $role->users->toArray(), $defaults_users);
		if($users_to_delete) {
			static::where ( 'role_id', '=', $role->role_id )->whereIn('user_id', $users_to_delete)->delete();
			
			$default_role_id = static::getDefaultRoleId();
			if($default_role_id) {
				$users_with_roles = static::whereIn('user_id', $users_to_delete)->get()->lists('user_id');
				$user_without_roles = array_diff($users_to_delete, $users_with_roles);
				foreach($user_without_roles as $user_id) {
					UserRole::create ( [ 
						'role_id' => $default_role_id,
						'user_id' => $user_id 
				] );
				}
			}
		}
		
		$users_to_add = array_diff($role->users->toArray(), $current_users);
		
		if($users_to_add) {
			foreach ( $users_to_add as $user_id ) {
				UserRole::create ( [ 
						'role_id' => $role->role_id,
						'user_id' => $user_id 
				] );
			}
		}
		
	}
	
	/*
	 * 
	 */
	public static function getDefaultRoleId() {
		$default_role = Config::get('acl::default_role', false);
		if($default_role) {
			$role = Role::where('name', '=', $default_role)->where('default', '=', 1)->get();
			return $role->count() ? $role[0]->role_id : false;
		}
		
		return false;
	}

}
