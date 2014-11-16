<?php namespace Volicon\Acl;

use Volicon\Acl\Models\VirtualModel;
use Volicon\Acl\Models\UserRole;
use Volicon\Acl\Facades\Acl as AclFacade;
use User;
use InvalidArgumentException;
use Illuminate\Support\Collection;

/**
 * Description of User
 *
 * @author nadav.v
 * @property int user_id
 * @property int role_id
 * @property array roles
 * @property array user_types
 */
class AclUser extends VirtualModel {
    
    public function __construct($data) {
        
        if(is_array($data)) {
            parent::__construct($data);
        } else if($data instanceof User) {
            parent::__construct($data->toArray());
        } else {
            throw new InvalidArgumentException("argument should be array or User");
        }
        
        if(!isset($this->roles)) {
            $this->roles = UserRole::where('user_id', '=', $this->user_id)->get(['role_id'])->lists('role_id');
            $this->user_types = AclFacade::getRoles($this->roles)->lists('type');
        }
        
        if(!isset($this->user_types)) {
            $this->user_types = AclFacade::getRoles($this->roles)->lists('type');
        }
    }
    
    public static function find($user_id) {
        $user = User::find($user_id);
		
		if(!$user) {
            return NULL;
		}
        
        return new static($user);
    }

	public static function findWithPermissions($user_id) {
        $user = static::find($user_id);
        
        if(!$user) {
            return $user;
        }
        
        $user_types = [];
		$permissions = [];
        
        $roles = AclFacade::getRoles($user->roles);
		
		foreach($roles as $role) {
			if(!in_array($role->type, $user_types)) {
				$user_types[] = $role->type;
			}
			
			/* @var $perm \Volicon\Acl\AclPermission */
			foreach($role->permissions as $perm) {
				if(!isset($permissions[$perm->resource])) {
					$permissions[$perm->resource] = $perm;
				} else {
					$permissions[$perm->resource] = $permissions[$perm->resource]->mergePermission($perm);
				}
			}

		}
		
		$user->user_types		= $user_types;
		$user->permissions	= new Collection($permissions);
        
        return $user;
        
    }
    
    /**
     * search and paginate users
     * 
     */
    public static function search() {
        $result = new Collection();
        $users = User::all()->toArray();
		$usersRoles = UserRole::all(['user_id', 'role_id'])->groupBy('user_id');
		foreach($users as &$user) {
			$user['roles'] = isset($usersRoles[$user['user_id']]) ? array_pluck($usersRoles[$user['user_id']], 'role_id') : [];
            $result[] = new static($user);
		}
        
        return $result;
    }
	
	public function setRoles(array $roleIds) {
		if($roleIds) {
			UserRole::where('user_id', '=', $this->user_id)->whereNotIn('role_id', $roleIds)->delete();
		} else {
			UserRole::where('user_id', '=', $this->user_id)->delete();
			return;
		}
		
		$roles = AclFacade::getRoles($roleIds);
		/* @var $role \Volicon\Acl\AclRole */
		foreach($roles as $role) {
			$role->users[] = $this->user_id;
			$role->update();
		}
	}
    
}
