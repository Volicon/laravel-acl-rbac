<?php namespace Volicon\Acl\Models;

use \Illuminate\Database\Eloquent\Model;
use Volicon\Acl\Exceptions\NoPermissionsException;
use Volicon\Acl\AclPermission;
use Volicon\Acl\Facades\Acl;

/**
 * Description of Model
 *
 * @author nadav.v
 */
class AclModel extends Model {
	protected $acl_field_key = '';
	
	public function __construct(array $attributes = array() ) {
		
		parent::__construct ( $attributes );
		
		if (! $this->acl_field_key && $this->getKeyName ()) {
			$this->acl_field_key = $this->getKeyName ();
		}
	}
	
	public function newQuery() {
		$builder = parent::newQuery ();
		
		if (!Acl::isGuard()) {
			return $builder;
		}
		
		$ok = $this->applyPermissionsRules ( $builder );
		
		return $ok !== false ? $builder : $builder->whereRaw ( '1 = 0' );
	}
	protected static function boot() {
		parent::boot ();
		
		self::_registerCreatingPermissions ();
		self::_registerUpdatingPermissions ();
		self::_registerDeletingPermissions ();
	}
	protected function applyPermissionsRules($builder) {
		$class = get_class ( $this );
		
		$user_class = \Config::get('auth.model', 'App\Http\User');
		if ($class == $user_class) { // bug to fix, use to login
			return;
		}
		
		$permission = Acl::getPermission ( $class . '.select' );
		
		return $this->wherePermission ( $builder, $permission );
	}
	
	/**
	 *
	 * @param \Illuminate\Database\Eloquent\Builder $builder        	
	 * @param AclPermission $permission        	
	 * @param string $field        	
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	public function wherePermission($builder, AclPermission $permission, $field = null) {
		if (! $field) {
			$field = $this->getAclKey ();
		}
		
		if ($permission->allowed && ! $permission->values) {
			return $builder;
		}
		
		if ($permission->allowed) {
			$builder->whereIn ( $this->getTable() . '.' . $field, $permission->values );
			return $builder;
		}
		
		if ($permission->values) {
			$builder->whereNotIn ( $this->getTable() . '.' . $field, $permission->values );
			return $builder;
		}
		
		throw new NoPermissionsException ( "No perrmision to " . $permission->resource );
		
		// return $builder->whereRaw('1 = 0');
	}
	protected function getAclKey() {
		return $this->acl_field_key;
	}
	protected static function checkCreatingPermissions($model) {
	}
	protected static function checkUpdatingPermissions($model) {
	}
	protected static function checkDeletingPermissions($model) {
	}
	
	private static function _registerCreatingPermissions() {
		static::creating ( function ($model) {
			if (Acl::isGuard()) {
				$class = get_class ( $model );
				$result = Acl::check ( $class . '.insert' );
				if (! $result) {
					throw new NoPermissionsException ( "No Permission to create $class" );
				}
				return $result;
			}
		} );
		static::creating ( function ($model) {
			if (Acl::isGuard()) {
				$result = $this->checkCreatingPermissions ( $model );
				if (! $result) {
					throw new NoPermissionsException ( "No Permission to create $class" );
				}
				return $result;
			}
		} );
	}
	private static function _registerUpdatingPermissions() {
		static::updating ( function ($model) {
			if (Acl::isGuard()) {
				$class = get_class ( $model );
				$id = $model [$model->getAclKey ()];
				$result = Acl::check ( $class . '.update', [ 
						$id 
				] );
				if (! $result) {
					throw new NoPermissionsException ( "No Permission to update $class id:" . $id );
				}
				return $result;
			}
		} );
		static::updating ( function ($model) {
			if (Acl::isGuard()) {
				$result = $this->checkCreatingPermissions ( $model );
				if (! $result) {
					throw new NoPermissionsException ( "No Permission to update $class id:" . $id );
				}
				return $result;
			}
		} );
	}
	private static function _registerDeletingPermissions() {
		static::deleting ( function ($model) {
			if (Acl::isGuard()) {
				$class = get_class ( $model );
				$id = $model [$model->getAclKey ()];
				$result = Acl::check ( $class . '.delete', [ 
						$id 
				] );
				if (! $result) {
					throw new NoPermissionsException ( "No Permission to delete $class id:" . $id );
				}
				return $result;
			}
		} );
		static::deleting ( function ($model) {
			if (Acl::isGuard()) {
				$result = $this->checkDeletingPermissions ( $model );
				if (! $result) {
					$id = $model [$model->getAclKey ()];
					throw new NoPermissionsException ( "No Permission to delete $class id:" . $id );
				}
				return $result;
			}
		} );
	}
}
