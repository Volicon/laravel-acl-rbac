<?php namespace Volicon\Acl;

use Volicon\Acl\Support\AclInterface;
use Volicon\Acl\Support\AclTrait;
use Volicon\Acl\RoleProviders\AclRoleProvider;
use Volicon\Acl\Models\GroupResources;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Auth;

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
	
	public function __construct() {
		
		$this->group_resources = Config::get ( 'acl::config.group_resources' );
		$this->allways_allow_resources = \Config::get ( 'acl::allways_allow_resources', [ ] );
		
		$roleProviders = Config::get('acl::config.roleProviders');
		
		foreach($roleProviders as $role_type => $roleProvider) {
			if(is_subclass_of($roleProvider, 'Volicon\Acl\RoleProviders\AclRoleProvider')) {
				$this->registerRoleProvider($role_type, new $roleProvider);
			}
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
		if(!isset($this->registersHooks[$resource])) {
			$this->registersHooks[$resource] = [];
		}
		
		$this->registersHooks[$resource][] = $callback;
	}

	public function getPermission($resource) {
		
		if(! $this->_guard) {
			return new AclPermission($resource, [], true);
		}
		
		if(in_array($resource, Config::get('acl::allways_allow_resources'))) {
			return new AclPermission($resource, [], true);
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
			$permission = $authUser->getPermission($resource);
			return $this->applyHook($permission);
		}
		
		$result = new AclPermission ( $resource );
		foreach ( $authUser->user_types as $type ) {
			if (isset ( $this->registersRoleProviders [$type] )) {
				$permission = $this->registersRoleProviders [$type]->getPermission ( $resource );
				$result = $result->mergePermission ( $permission );
			}
			
			if($result->isAllowAll()) {
				break;
			}
		}
		
		return $this->applyHook($result);
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
		if(!Auth::check()) {
			return NULL;
		}
		
		return AclUser::findWithPermissions(Auth::id());
	}

	public function applyHook(AclPermission $permission) {
		
		if(!isset($this->registersHooks[$permission->resource])) {
			return $permission;
		}
		
		foreach ($this->registersHooks[$permission->resource] as $callback) {
			$handler_result = $callback($permission);
			if($handler_result instanceof AclPermission) {
				$permission = $permission->mergePermission($handler_result);
			}
		}
		
		return $permission;
	}

}
