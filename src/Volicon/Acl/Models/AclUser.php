<?php namespace Volicon\Acl\Models;

use User;
use Auth;
use Illuminate\Support\Collection;
use Acl;

/**
 * Description of User
 *
 * @author nadav.v
 */
class AclUser {
	
	private static $authUser = null;

	public static function getAllUsers() {
		$users = User::all();
		$usersRoles = UserRole::all(['user_id', 'role_id'])->groupBy('user_id');
		foreach($users as &$user) {
			$user->roles = isset($usersRoles[$user->user_id]) ? array_pluck($usersRoles[$user->user_id], 'role_id') : [];
			$user->user_types = static::getUserTypes($user);
		}
		return $users;
	}

	// put your code here
	public static function getUser($user_id) {
		$user = User::find($user_id);
		
		if(!$user) {
			return;
		}
		
		$user->roles = UserRole::where('user_id', '=', $user_id)->get(['role_id'])->lists('role_id');
		$user->types = self::getUserTypes ( $user );
			
		return $user;
	}
	
	public static function getUserTypes($user) {
		
		$roles = [];
		
		if(is_int($user)) {
			$roles = UserRole::where('user_id', '=', $user)->get(['role_id'])->lists('role_id');
		} else {
			$roles = $user->roles;
		}
		
		if(!$roles) {
			return [0];
		}
		
		$roles_types = Role::whereIn ( 'role_id', $roles )->get ()->lists ( 'type' );
		
		return array_unique ( $roles_types, SORT_NUMERIC );
	}
	
	
	public static function getAuthUser() {
		
		if(self::$authUser) {
			return self::$authUser;
		}
		
		$user = Auth::getUser();
		
		if(!$user) {
			return NULL;
		}
		
		$user->roles = UserRole::where('user_id', '=', $user->user_id)->get(['role_id'])->lists('role_id');
		
		$user_types = [];
		$permissions = [];
		
		$roles = Acl::getRoles($user->roles);
		
		foreach($roles as $role) {
			if(!in_array($role->type, $user_types)) {
				$user_types[] = $role->type;
			}
			
			/* @var $perm \Volicon\Acl\Permission */
			foreach($role->permissions as $perm) {
				if(!isset($permissions[$perm->resource])) {
					$permissions[$perm->resource] = $perm;
				} else {
					$permissions[$perm->resource] = $permissions[$perm->resource]->mergePermission($perm);
				}
			}
		}
		
		$user->types		= $user_types;
		$user->permissions	= new Collection($permissions);
		
		self::$authUser = (object)$user->toArray();
		
		return self::$authUser;
	}
}
