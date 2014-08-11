<?php namespace Volicon\Acl;

use Volicon\Acl\Models\GroupResources;
use Volicon\Acl\Models\Role;
use Volicon\Acl\Models\RolePermission;
use Volicon\Acl\Models\UserRole;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Description of Acl
 *
 * @author nadav.v
 */
class Acl implements AclResult {
	
	public $result						= self::DISALLOW;
	public $values_perms				= [];
	
	protected $_guard					= true;
	protected $group_resources			= [];
	protected $group_resources_ids		= [];
	protected $route_resources_in_group	= [];
	protected $allways_allow_resources	= [];


	public function __construct() {
		$this->group_resources	= Config::get('acl::config.group_resources');
		$this->group_resources_ids = $this->get_group_resources_ids();
		$this->route_resources_in_group = $this->get_route_resources_in_group($this->group_resources_ids);
		$this->allways_allow_resources = \Config::get('acl::allways_allow_resources', []);
	}

	/**
	 * 
	 * @param type $resource
	 * @param array $additional_values additional values from put/post...
	 * @return boolean
	 */
	public function check($resource = null, array $additional_values = [], $user_id = null) {
		
		$result			= new Acl();
		
		if(!$this->_guard) {
			$result->result = Acl::ALLOWED;
			return $result;
		}
		
		if(in_array($resource, $this->allways_allow_resources)) {
			$result->result = Acl::ALLOWED;
			return $result;
		}
		
		$default_permission = \Config::get("acl::config.default_permission");
		
		if(!$resource) {
			$id			= $this->searchId(\Request::path());
			if($id !== FALSE) {
				$additional_values[] = $id;
			}
			
			$resource	= \Route::currentRouteName();
		}
		
		if(!$user_id) {
			$user_id = \Auth::getUser()->user_id;
		}
		
		if(!$user_id) {
			$result->result = Acl::DISALLOW;
			return $result;
		}
		
		$admin_roles = Role::getAdminRoles();
		
		
		
		$user_roles_rows	= UserRole::where('user_id', '=', $user_id)->get(['role_id']);
		
		$user_roles		= [];
		foreach($user_roles_rows as $user_role) {
			if(in_array($user_role->role_id, $admin_roles)) {
				$result->result = Acl::ALLOWED;
				$result->values_perms = [];
				return $result;
			}
			$user_roles[]	= $user_role->role_id;
		}
		
		$permission_id = $this->getPermissionId($resource);
		
		if(!$permission_id) {
			$result->result = $default_permission;
			return $result;
		}
		
		if(!$permission_id || !$user_roles_rows->count()) {
			$result->result = $default_permission;
			return $result;
		}
		
		$permissions = RolePermission::select(['value', 'allowed'])->where('permission_id', '=', $permission_id)->whereIn('role_id', $user_roles)->get();
		
		if(!$permissions || !$permissions->count()) {
			$result->result = $default_permission;
			return $result;
		}
		
		$values_arr = [];
		
		foreach ($additional_values as $v) {
			$values_arr[$v] = null;
		}
		
		$have_permited_values		= false;
		$have_not_permited_values	= false;
		$have_no_values				= true;
		$have_disallow_all			= false;
		$not_have_allow_all			= true;
		$current_type				= AclCheck::ONLY_TYPE_UNSET;
		
		foreach($permissions as $perm) {			
			$current_type = AclCheck::checkForOnlyType($perm,$current_type);
			$perm = (object)$perm->toArray(); // debug
				
			if(!($perm->value === null || $perm->value === '')) {
				$have_no_values = false;
			} else if($perm->allowed) {
				$not_have_allow_all = false;
			}
			
			if($perm->allowed) {
				if(!count($values_arr)) {
					$result->result = Acl::ALLOWED;
					break;
				}
				
				if(array_key_exists($perm->value, $values_arr)) {
					$have_permited_values		= true;
					$values_arr[$perm->value]	= true;
				}
			} else {
				if(!($perm->value === null || $perm->value === '')) {
					if(array_key_exists($perm->value, $values_arr) && $values_arr[$perm->value] === NULL) {
						$values_arr[$perm->value]	= false;
						$have_not_permited_values	= true;
					}
				} else {
					$have_disallow_all = true;
				}
			}
		}
		
		if($values_arr) {
			if(!$not_have_allow_all) {
				$result->result	= Acl::ALLOWED;
			} else if($have_permited_values && $have_not_permited_values) {
				$result->result	= Acl::PARTLY_ALLOWED;
			} else if($have_permited_values && !$have_not_permited_values) {
				$result->result	= Acl::ALLOWED;
			} else if(!$have_permited_values && $have_not_permited_values) {
				$result->result	= Acl::DISALLOW;
			} else if($current_type == AclCheck::ONLY_TYPE_ALLOW && !$have_permited_values) {
				$result->result	= Acl::DISALLOW;
			} else if($current_type == AclCheck::ONLY_TYPE_FALSE && $have_disallow_all) {
				$result->result	= Acl::DISALLOW;
			} else {
				$result->result	= Acl::ALLOWED;
			}
			
			$result->values_perms = $values_arr;
		}
		
		
		return $result;
	}
	
	public function checkForWhere($resource = null, $user = null) {
		
		$default_permission = \Config::get("acl::config.default_permission");
		
		$result = (object)array(
			'result' => $default_permission,
			'values' => [],
			'include' => true
		);
		
		if(!$this->_guard) {
			$result->result = Acl::ALLOWED;
			return $result;
		}
		
		$resource = $this->_getResource($resource);
		$user_id = $this->_getUserId($user);
		
		$roles_ids = $this->getRolesBelongToUsers([$user_id], []);
		
		$is_admin = Role::whereIn('role_id', $roles_ids)->where('admin', '=', true)->get()->count() > 0;
		
		if($is_admin) {
			$result->result = Acl::ALLOWED;
			return $result;
		}
		
		$permission_id = $this->getPermissionId($resource);;
		
		if(!$permission_id) {
			return $result;
		}
		
		$permissions = RolePermission::where('permission_id', '=', $permission_id)->whereIn('role_id', $roles_ids)->get();
		
		return $this->checkPermissions($permissions);
		
	}
	
	

	protected function checkPermissions($permissions) {
		
		$default_permission = \Config::get("acl::config.default_permission");
		
		$result = (object)array(
			'result' => $default_permission,
			'values' => [],
			'include' => true
		);
		
		if(!$this->_guard) {
			$result->result = Acl::ALLOWED;
			return $result;
		}
		
		if(!$permissions|| !$permissions->count()) {
			return $result;
		}
		
		$resultNotSet = true;
		
		foreach($permissions as $perm) {
			
			$perm = (object)$perm->toArray(); // debug
			
			if($perm->allowed) {
				if($perm->value) {
					if($resultNotSet || $result->result = Acl::ALLOWED) {
						$result->result = Acl::PARTLY_ALLOWED;
						$result->values[] = $perm->value;
						$resultNotSet = false;
					} else if($result->result = Acl::PARTLY_ALLOWED) {
						if($result->include) {
							if(!in_array($perm->value, $result->values)) {
								$result->values[] = $perm->value;
							}
						} else {
							if(count($result->values)) {
								$pos = array_search($perm->value, $result->values);
								if($pos !== false) {
									unset($result->values[$pos]);
								}
							}
						}
					} else { //Acl::DISALLOW
						$result->result = Acl::PARTLY_ALLOWED;
						$result->values[] = $perm->value;
						$result->include = true;
					}
				} else {
					$result->result = Acl::ALLOWED;
					$result->values = [];
					$result->include = true;
					return $result;
				}
			} else { // not allowed
				if($perm->value) {
					if($resultNotSet) {
						$result->result = Acl::PARTLY_ALLOWED;
						$result->include = false;
						$result->values[] = $perm->value;
						$resultNotSet = false;
					} else if($result->result == Acl::PARTLY_ALLOWED) {
						if($result->include) {
							//do nothing
						} else {
							if(!in_array($perm->value, $result->values)) {
								$result->values[] = $perm->value;
							}
						}
					} else { //Acl::DISALLOW
						// do nothing
					}
				} else {
					if($result->result == Acl::ALLOWED) {
						$result->result = Acl::DISALLOW;
					}
				}
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

	/**
	 * 
	 * @param int $roleId
	 * @param array $permissions
	 */
	protected function addRolePermission($roleId, $permissions) {
		
		if(!$roleId) {
			return false;
		}
			
		
		/* delete all permissions for given role id*/
		RolePermission::where('role_id', '=', $roleId)->delete();
		
		
		foreach ($permissions as $permission) {
			/* get permission by resource */
			$permission_id = array_search($permission['resource'], $this->group_resources_ids);
			
			/* check if permission id is not null*/
			if($permission_id){
				
				/* set allowed -- default is 1 == allowed */
				$allowed = ( isset($permission['allowed']) ? $permission['allowed'] : 1);
				
				/* have values */
				if(isset($permission['values']) && !empty($permission['values'])){
					if(!is_array($permission['values'])){
						$permission['values'] = array($permission['values']);
					}
					
					foreach ($permission['values'] as $value) {
						$this->setRolePermission($roleId, $permission_id, $value, $allowed);
					}
				}else{ /* no values */
					$this->setRolePermission($roleId, $permission_id, '', $allowed);
				}
			}
		}
	}


	/**
	 * Remove RolePermission
	 * @param int/string $role role_id or role name
	 */
	public function removeRole($role) {
		$the_role = null;
		
		if(is_numeric($role)) {
			$role_id = $role;
			$the_role = Role::find($role_id);
		} else {
			$the_role = Role::where('name', '=', $role)->first();
		}
		
		if(!$the_role) {
			return;
		}
		
		$role_id = $the_role->role_id;
		
		$the_role->delete();
		
		RolePermission::where('role_id', '=', $role_id)->delete();
		
		UserRole::where('role_id', '=', $role_id)->delete();
		
		return $the_role;
		
	}
	
	public function getRole($role, $resources=[]) {
		$role_id = Role::getRoleId($role);
		
		return $this->getRoleById($role_id, $resources);
		
	}
	
	public function getRoleById($role_id, $resources=[]) {
		$roles = $this->getRoles($resources, [$role_id]);
		
		$result = count($roles) ? $roles[0] : null;
		
		return $result;
	}

	public function getRoles($resources = array(), $roleIds = array()) {
		$result = array();
		
		//TODO: need to filter resources
		
		$resourcesIds = [];
		foreach($resources as $resource) {
			$resourcesIds[] = array_search($resource, $this->group_resources_ids);
		}
		
		$rolesRes = Role::with(array('permissions' => function(HasMany $query) use ($resources, $resourcesIds) {
			if(count($resources)) {
				$query->whereIn('permission_id', $resourcesIds);
			}
		}, 'users'))->select();
		
		if(count($roleIds)) {
			$rolesRes->whereIn('role_id', $roleIds);
		}
		
		$roles = $rolesRes->get();
		
		foreach($roles as &$role) {
			$roleToAdd = $role->toArray();
			
			$permissionsRows = $role->permissions()->get(array('permission_id', 'value', 'allowed'));
			
			$permissions = $this->parseDBPermissions($permissionsRows);
			
			$roleToAdd['permissions'] = $permissions;
			
			$users = $role->users()->get(array('user_id'));
			
			$usersIds = array();
			foreach($users as &$user) {
				$usersIds[] = $user->user_id;
			}
			
			$roleToAdd['users'] = $usersIds;
			
			$result[] = $roleToAdd;
			
		}
		
		return $result;
	}
	
	public function addRole($role) {
		$role = (object)$role;
		$role_name = $role->name;
		
		$admin = false;
		
		if(isset($role->admin) && $role->admin) {
			if($this->isAdmin()) {
				$admin = true;
			}
		}
		
		if(Role::getRoleId($role_name)) {
			throw new \Exception('role already exist.');
		}
		
		$new_role = Role::create(array('name' => $role_name, 'admin' => $admin));
		
		$this->addRolePermission($new_role->role_id, $role->permissions);
		
		if(isset($role->users)) {
			$this->setUserRoles($new_role->role_id, $role->users);
		}
		
		return $new_role->role_id;
		
	}
	
	public function addRoles(array $roles) {
		foreach($roles as &$role) {
			$this->addRole($role);
		}
		
		return true;
	}
	
	public function updateRole($roleId, $role) {
		$role = (object)$role;
		$foundRole = Role::find($roleId);
		
		if(!$foundRole) {
			return null;
		}
		
		$admin = false;
		
		if(isset($role->admin) && $role->admin) {
			if($this->isAdmin()) {
				$admin = true;
			}
		}
		
		if($foundRole->name !== $role->name || $foundRole->admin != $admin) {
			$foundRole->name = $role->name;
			$foundRole->admin = $admin;
			$foundRole->save();
		}
		
		$this->addRolePermission($roleId, $role->permissions);
		$this->setUserRoles($roleId, $role->users);
		
		return $roleId;
		
	}
	
	public function updateUserRoles($user_id, array $roles_ids = []) {
		if(!$user_id) {
			return;
		}
		
		UserRole::where('user_id', '=', $user_id)->delete();
		
		foreach($roles_ids as $role_id) {
			UserRole::create(['role_id' => $role_id, 'user_id' => $user_id]);
		}
	}

	protected function setUserRoles($role_id, array $users) {

		if(!$role_id) {
			return;
		}
		
		UserRole::where('role_id', '=', $role_id)->delete();
		
		foreach($users as $user_id) {
			UserRole::create(['role_id' => $role_id, 'user_id' => $user_id]);
		}
	}


	/**
	 * 
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	protected function getUsersRoles(array $user_ids = array(),array $role_ids = array(), $search_users = true) {
		
		$result = array();

		$q = UserRole::select();
		if(count($user_ids)) {
			$q->whereIn('user_id', $user_ids);
		}
		
		if(count($role_ids)) {
			$q->whereIn('role_id', $role_ids);
		}
		
		$userRole = $q->get();
		foreach($userRole as $row) {
			if($search_users) {
				$result[] = $row->user_id;
			} else {
				$result[] = $row->role_id;
			}
		}
		return $result;
		
	}
	
	public function getRolesBelongToUsers(array $user_ids = array(),array $role_ids = array(), $only_ids=true) {
		$result = $this->getUsersRoles($user_ids, $role_ids, false);
		
		if(!$only_ids) {
			$result = Role::whereIn('role_id', $result)->get();
		}
		
		return $result;
	}
	
	public function getUsersBelongToRoles(array $user_ids = array(),array $role_ids = array()) {
		return $this->getUsersRoles($user_ids, $role_ids);
	}
	
	/**
	 * set Role Permission
	 * @param int $roleID
	 * @param int $permission_id
	 * @param mix $value
	 * @param int $allowed
	 */
	protected function setRolePermission($roleID, $permission_id, $value='', $allowed=1){
		$p = array(
			'role_id'=>$roleID,
			'permission_id'=>$permission_id,
			'value'=>$value,
			'allowed'=>$allowed
		);

		RolePermission::create($p);		
	}

	protected function searchId($path) {
		/**
		 * TODO: use regex...
		 */
		$path_parts	= explode('/', $path);
		foreach ($path_parts as $part) {
			if(is_numeric($part)) return $part;
		}
		
		return FALSE;
		
	}
	
	public function addWhere($resource, \Illuminate\Database\Eloquent\Builder $model, $db_field) {
		$check = $this->checkForWhere($resource);
		if($check->result == Acl::ALLOWED) {return true; }
		if($check->result == Acl::DISALLOW) {return false; }
 
		if($check->include) {
			$model->whereIn($db_field, $check->values);
		} else {
			$model->whereNotIn($db_field, $check->values);
		}
		
		return true;
	}
	
	public function addWhereForRole($role, $resource, \Illuminate\Database\Eloquent\Builder $model, $db_field) {
		
		if(is_numeric($role)) {
			$role = Role::find($role);
		}
		
		if(is_array($role)) {
			$role = (object)$role;
		}
		
		if($role->admin) {
			return true;
		}
		
		$role = $role->role_id;
		
		$permission_id = $this->route_resources_in_group[$resource];
		
		if(!$permission_id) {
			$default_permission = \Config::get("acl::config.default_permission");
			return $default_permission === Acl::ALLOWED;
		}
		
		$permissions = RolePermission::where('permission_id', '=', $permission_id)->where('role_id', '=', $role)->get();
		$check = $this->checkPermissions($permissions);
		
		if($check->result == Acl::ALLOWED) { return true; }
		if($check->result == Acl::DISALLOW) {return false; }
 
		if($check->include) {
			$model->whereIn($db_field, $check->values);
		} else {
			$model->whereNotIn($db_field, $check->values);
		}
		
		return true;
	}

	protected function _getResource($resource = null) {
		if(!$resource) {
			$resource	= \Route::currentRouteName();
		}
		
		return $resource;
	}

	/**
	 * 
	 * @param User|int|null $user
	 * @return int
	 */
	protected function _getUserId($user = null) {
		
		if(!$user) {
			$user = \Auth::getUser(); 
			return $user ? $user->user_id : false;
		}
		
		if(is_numeric($user)) {
			return $user;
		}
		
		return $user->user_id;
	}

	public function isAdmin($user = null) {
		
		if(!$this->_guard) {
			return true;
		}
		
		$user_id = $this->_getUserId($user);
		
		if(!$user_id) {
			return false;
		}
		
		$roles = UserRole::where('user_id', '=', $user_id)->get(['role_id']);
		$roles_ids = [];
		foreach($roles as $role) {
			$roles_ids[] = $role->role_id;
		}
		
		if(!$roles_ids) {
			return false;
		}
		
		return Role::where('admin', '=', 1)->whereIn('role_id', $roles_ids)->limit(1)->count() > 0;
	}

	protected function get_group_resources_ids() {
		$result = [];
		GroupResources::all()->each(function($row) use(&$result){
			$result[$row->permission_id] = $row->resource;
		});
		
		return $result;
	}

	public function get_route_resources_in_group($group_resources_ids) {
		
		$result = [];
		
		foreach($this->group_resources as $group_name=>$resources) {
			
			if(!isset($resources['resources'])) {
				$resources['resources'] = [];
			}
			
			if(!isset($resources['access_resources'])) {
				$resources['access_resources'] = [];
			}
			
			$routes = array_merge($resources['resources'], $resources['access_resources']);
			foreach($routes as $route) {
				$result[$route] = array_search($group_name, $group_resources_ids);
			}
		}
		return $result;
	}

	protected function getPermissionId($resource) {
		if(isset($this->route_resources_in_group[$resource])) {
			return $this->route_resources_in_group[$resource];
		}
		return null;
	}

	protected function parseDBPermissions($permissionsRows) {
		$permissions_by_allowed = array();
		foreach($permissionsRows as &$permission) {

			$resource = $this->group_resources_ids[$permission->permission_id];

			if(!isset($permissions_by_allowed[$resource])) {
				$permissions_by_allowed[$resource] = array();
			}

			$resourceInArr = &$permissions_by_allowed[$resource];

			if(!isset($resourceInArr[$permission->allowed])) {
				$resourceInArr[$permission->allowed] = array();
			}

			$values = &$resourceInArr[$permission->allowed];

			$values[] = $permission->value;

			$permission->resource		= $resource;
		}

		$permissions = array();

		foreach($permissions_by_allowed as $resource=>&$items) {
			//$permissionId = array_search($resource, $this->group_resources_ids);

			foreach ($items as $allowed=>&$values) {

				$permissions[] = array(
					'resource'	=> $resource,
					'values'		=> $values,
					'allowed'	=> $allowed
				);



			}
		}
		
		return $permissions;
	}

}
