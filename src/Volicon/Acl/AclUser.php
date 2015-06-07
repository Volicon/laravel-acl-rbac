<?php namespace Volicon\Acl;

use Volicon\Acl\Support\DataObject;
use Volicon\Acl\Models\UserRole;
use Volicon\Acl\Facades\Acl as AclFacade;
use Volicon\Acl\Support\AclInterface;
use Volicon\Acl\Support\AclTrait;
use Illuminate\Support\Facades\Config;
use Volicon\Acl\Models\GroupResources;
use User;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Volicon\Acl\Support\MicrotimeDate;
use DB;

/**
 * Description of User
 *
 * @author nadav.v
 * @property int role_id
 * @property array roles
 * @property array user_types
 */
class AclUser extends DataObject implements AclInterface {
	use AclTrait;
	
	private static $__user_key;

	public function __construct($data) {
        
        if(is_array($data)) {
            parent::__construct($data);
        } else if($data instanceof User) {
            parent::__construct($data->toArray());
        } else {
            throw new InvalidArgumentException("argument should be array or User");
        }

        if(!isset($this->roles)) {
            $this->roles = UserRole::where('user_id', '=', $this->getKey())->get(['role_id'])->lists('role_id');
            $this->user_types = AclFacade::getRoles($this->roles)->lists('type');
        }
        
        if(!isset($this->user_types)) {
            $this->user_types = count($this->roles) ? AclFacade::getRoles($this->roles)->lists('type') : [];
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
        
        $roles = $user->roles ? AclFacade::getRoles($user->roles) : [];
		
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
		$key_name = static::getKeyName();
        $users = User::all()->toArray();
		$usersRoles = UserRole::all(['user_id', 'role_id'])->groupBy('user_id');
		foreach($users as &$user) {
			$user['roles'] = isset($usersRoles[$user[$key_name]]) ? array_pluck($usersRoles[$user[$key_name]], 'role_id') : [];
            $result[] = new static($user);
		}
        
        return $result;
    }
	
	public function setRoles(array $roleIds) {
        DB::beginTransaction();
		
		$new_roles = array_diff($roleIds, $this->roles);
		$exist_roles = array_intersect($roleIds, $this->roles);
        $deleted_roles = array_diff($this->roles, $roleIds);
		$new_role_saved = [];
		$roleProviders = AclFacade::getRoleProvidersTypes();
		foreach($roleProviders as $rp_type) {
			$rp = AclFacade::getRoleProvider($rp_type);
			if($rp->allowUpdateRole()) {
				$roles_to_delete = $deleted_roles ? $rp->getRoles($deleted_roles)->lists('role_id') : [];
				UserRole::where('user_id', '=', $this->getKey())->whereIn('role_id', $roles_to_delete)->delete();
				$roles = $new_roles ? $rp->getRoles($new_roles) : [];
				foreach($roles as $role) {
					$role_id = $role->role_id;
					UserRole::create([
						'user_id' => $this->user_id,
						'role_id' => $role_id
					]);
					$new_role_saved[] = $role_id;
				}
			}
		}
		
        DB::commit();
		$this->roles = array_merge($exist_roles, $new_role_saved);
		Event::fire('acl_role_updated', $roleIds);
		$cache_prefix = Config::get('acl::cache_key', '_volicon_acl_');
		Cache::forever($cache_prefix.'_last_role_update', new MicrotimeDate());
	}

	public function getPermission($resource, array $ids = []) {
		
		if(in_array($resource, Config::get('acl::allways_allow_resources'))) {
			return new AclPermission($resource, $ids, true);
		}
		
		$groupResource = GroupResources::getResourceGroup($resource);
		if($groupResource) {
			$resource = $groupResource;
		}
		
		$result = new AclPermission($resource);
		
		if($ids) {
			$result = $result->newSubPermission($ids);
		}
        
        $result = AclFacade::applyHook($result, $ids, $this);
		
		$aclUser = $this;
		if(!isset($this->permissions)) {
			$aclUser = self::findWithPermissions($this->getKey());
		}
		
		if(isset($aclUser->permissions[$resource])) {
			$result = $aclUser->permissions[$resource];
			if($ids) {
				$result = $result->newSubPermission($ids);
			}
		}
		return $result;
	}
	
	public function getKey() {
		return isset($this[$this->getKeyName()]) ? $this[$this->getKeyName()] : FALSE;
	}
	
	public static function getKeyName() {
		if(!self::$__user_key) {
			self::$__user_key = (new User)->getKeyName();
		}
		
		return self::$__user_key;
	}

}
