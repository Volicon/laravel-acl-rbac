<?php namespace Volicon\Acl;

use Volicon\Acl\Support\AclInterface;
use Volicon\Acl\Support\AclTrait;
use Volicon\Acl\Support\MicrotimeDate;
use Volicon\Acl\RoleProviders\AclRoleProvider;
use Volicon\Acl\Models\GroupResources;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Closure;

/**
 * Description of Acl
 *
 * @author nadav.v
 */
class Acl implements AclInterface {
	use AclTrait;
	
	public $result = false;
	public $values_perms = [ ];
	protected $_guard = true;
	protected $group_resources = [ ];
	protected $route_resources_in_group = [ ];
	protected $allways_allow_resources = [ ];
	protected $registersRoleProviders = [ ];
	protected $registersHooks = [ ];
	private $use_cache = false;
	private $cache_prefix = '_volicon_acl_';
	
	public function __construct() {
		
		$this->group_resources = Config::get ( 'acl::config.group_resources' );
		$this->allways_allow_resources = \Config::get ( 'acl::allways_allow_resources', [ ] );
		
		$roleProviders = Config::get('acl::config.roleProviders');
		
		foreach($roleProviders as $role_type => $roleProvider) {
			if(is_subclass_of($roleProvider, 'Volicon\Acl\RoleProviders\AclRoleProvider')) {
				$this->registerRoleProvider($role_type, new $roleProvider);
			}
		}
		
		if(Config::get('acl::using_cache', false)) {
			$this->use_cache = true;
			$this->cache_prefix = Config::get('acl::cache_key', $this->cache_prefix);
		}
		
	}
	
	public function registerRoleProvider($role_type, AclRoleProvider $roleProvider) {
		if (! is_int ( $role_type )) {
			throw new \Exception ( "role_type should be number $role_type given" );
		}
		
		if (isset ( $this->registersRoleProviders [$role_type] )) {
			throw new \Exception ( "role_type $role_type already register" );
		}
		
		$this->registersRoleProviders [$role_type] = $roleProvider;
		$roleProvider->setRoleType($role_type);
		$this->registersHooks[$role_type] = [];
		
	}
	
	public function registerHook($resource, $callback) {
		
		if(!is_array($resource)) {
			$resource = [$resource];
		}
		
		foreach($resource as $res) {
		
			if(!isset($this->registersHooks[$res])) {
				$this->registersHooks[$res] = [];
			}

			$this->registersHooks[$res][] = $callback;
		}
	}

	public function getPermission($resource, array $ids = []) {
		
		if(! $this->_guard) {
			return new AclPermission($resource, $ids, true);
		}
		
		if(in_array($resource, Config::get('acl::allways_allow_resources'))) {
			return new AclPermission($resource, $ids, true);
		}
		
		$authUser = $this->getAuthUser();
		if(!$authUser) {
			return new AclPermission($resource, [], false);
		}
		
		$groupResource = GroupResources::getResourceGroup($resource);
		if($groupResource) {
			$resource = $groupResource;
		}
		
		if(isset($authUser->permissions[$resource])) {
			$permission = $authUser->getPermission($resource, $ids);
			return $this->applyHook($permission, $ids);
		}
		
		$result = new AclPermission ( $resource );
		foreach ( $authUser->user_types as $type ) {
			if (isset ( $this->registersRoleProviders [$type] )) {
				$permission = $this->registersRoleProviders [$type]->getPermission ( $resource, $ids );
				$result = $result->mergePermission ( $permission );
			}
			
			if($result->isAllowAll()) {
				break;
			}
		}
		
		return $this->applyHook($result, $ids);
	}
	
	public function reguard() {
		$this->_guard = true;
	}
	public function unguard() {
		$this->_guard = false;
	}
	
	public function isGuard() {
		return $this->_guard;
	}
	
	/**
	 * run function without ACL restictions.	 
	 * @param Closure $closure
	 * @return mix closure result
	 */
	public function runUnguardCallback(Closure $closure) {
		$isGuard = $this->isGuard();
		$this->unguard();
		$result = $closure();
		if($isGuard) {
			$this->reguard();
		}
		return $result;
	}

	public function getRoleProvider($role_type) {
		return $this->registersRoleProviders[$role_type];
	}
	
	public function getRoleProvidersTypes() {
		return array_keys($this->registersRoleProviders);
	}

	public function getRoles($roleIds = []) {
		$result = new Collection();
		
		/* @var $role_provider \Volicon\Acl\RoleProviders\AclRoleProvider */
		foreach($this->registersRoleProviders as $role_provider) {
			$roles = $role_provider->getRoles($roleIds);
			$result = $result->merge($roles);
		}
		
		return $result;
	}
	
	public function getAuthUser() {
		$auth_user = NULL;
		if(!Auth::id()) {
			return $auth_user;
		}
		
		if($this->use_cache) {
			$auth_user_cached_time = Cache::get($this->cache_prefix.'_mt_authUser_'.Auth::id(), null);
			$role_mt = Cache::get($this->cache_prefix.'_last_role_update', null);
			if($auth_user_cached_time && $role_mt && $role_mt->compare($auth_user_cached_time)) {
				$auth_user = Cache::get($this->cache_prefix.'_authUser_'.Auth::id());
			}
		}
		
		if(!$auth_user) {
			$auth_user = AclUser::findWithPermissions(Auth::id());
			if($this->use_cache) {
				Cache::forever($this->cache_prefix.'_authUser_'.Auth::id(), $auth_user);
				Cache::forever($this->cache_prefix.'_mt_authUser_'.Auth::id(), new MicrotimeDate());
			}
		}
		
		return $auth_user;
	}

	public function applyHook(AclPermission $permission, $ids=[]) {
		
		if(!isset($this->registersHooks[$permission->resource])) {
			return $permission;
		}
		
		foreach ($this->registersHooks[$permission->resource] as $callback) {
			$handler_result = $callback($permission, $ids);
			if($handler_result instanceof AclPermission) {
				$permission = $permission->mergePermission($handler_result);
			}
		}
		
		return $permission;
	}

}
