<?php namespace Volicon\Acl;

use Volicon\Acl\Interfaces\AclInterface;
use Volicon\Acl\RoleProviders\AclRoleProvider;
use Volicon\Acl\Models\AclUser;
use Volicon\Acl\Models\GroupResources;
use Volicon\Acl\Models\UserRole;
use Illuminate\Support\Facades\Config;
use \Illuminate\Support\Collection;

/**
 * Description of Acl
 *
 * @author nadav.v
 */
class Acl implements AclInterface {
	public $result = false;
	public $values_perms = [ ];
	protected $_guard = true;
	protected $group_resources = [ ];
	protected $route_resources_in_group = [ ];
	protected $allways_allow_resources = [ ];
	protected $registersRoleProviders = [ ];
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
	
	/**
	 *
	 * @param type $resource        	
	 * @param array $additional_values
	 *        	additional values from put/post...
	 * @return boolean
	 */
	public function check($resource = null, array $additional_values = []) {
		if (! $this->_guard) {
			return true;
		}
		
		if (in_array ( $resource, $this->allways_allow_resources )) {
			return true;
		}
		
		$filter_result = $this->filter ( $resource, $additional_values );
		
		return $filter_result !== FALSE;
	}
	
	public function filter($resource, array $ids = []) {
		
		$perm = $this->getPermission ( $resource );
		
		if ($perm->allowed && ! $perm->values) {
			return $ids;
		}
		
		if ($perm->allowed) {
			return array_intersect ( $ids, $perm->values );
		}
		
		if (!$perm->values) {
			return FALSE;
		}
		
		return array_diff ( $ids, $perm->values );
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
		
	}
	public function getPermission($resource) {
		
		if(! $this->_guard) {
			return new Permission($resource, [], true);
		}
		
		if(in_array($resource, Config::get('acl::allways_allow_resources'))) {
			return new Permission($resource, [], true);
		}
		
		$authUser = AclUser::getAuthUser();
		if(!$authUser) {
			return new Permission($resource, [], false);
		}
		
		$groupResource = GroupResources::getResourceGroup($resource);
		if($groupResource) {
			$resource = $groupResource;
		}
		
		if(isset($authUser->permissions[$resource])) {
			return $authUser->permissions[$resource];
		}
		
		$result = new Permission ( $resource );
		$user_id = $authUser->user_id;
		$aclUser = AclUser::getUser ( $user_id );
		foreach ( $aclUser->types as $type ) {
			if (isset ( $this->registersRoleProviders [$type] )) {
				$permission = $this->registersRoleProviders [$type]->getPermission ( $resource );
				$result = $result->mergePermission ( $permission );
			}
			
			if($result->isAllowAll()) {
				return $permission;
			}
		}
		
		return $result;
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
	
	public function getRoles($roleIds = []) {
		$result = new Collection();
		
		/* @var $role_provider \Volicon\Acl\RoleProviders\AclRoleProvider */
		foreach($this->registersRoleProviders as $role_provider) {
			$roles = $role_provider->getRoles($roleIds);
			$result = $result->merge($roles);
		}
		
		return $result;
	}
	
	public function updateUserRoles($user_id, $roleIds = []) {
		UserRole::where('user_id', '=', $user_id)->whereNotIn('role_id', $roleIds)->delete();
		
		$roles = $this->getRoles($roleIds);
		/* @var $role \Volicon\Acl\Role */
		foreach($roles as $role) {
			$role->users[] = $user_id;
			$role->update();
		}
	}
	
}
