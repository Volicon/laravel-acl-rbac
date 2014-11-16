<?php namespace Volicon\Acl\Models;

use Volicon\Acl\AclRole;

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
		
		$current_users = UserRole::where('role_id', '=', $role->role_id)->lists('user_id');
		
		$users_to_delete = array_diff($current_users, $role->users->toArray());
		if($users_to_delete) {
			static::where ( 'role_id', '=', $role->role_id )->whereIn('user_id', $users_to_delete)->delete();
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

}
