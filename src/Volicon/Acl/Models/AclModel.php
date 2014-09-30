<?php namespace Volicon\Acl\Models;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Collection;

//use Volicon\Acl\Facades\Acl;
use Acl;
use App;

/**
 * Description of Model
 *
 * @author nadav.v
 */
class AclModel extends Model {
	
	private $use_acl = true;
	protected $acl_field_key = '';
	private static $enable_acl = true;


	public function __construct($attributes = []) {
		
		if(App::runningInConsole()) {
			$this->use_acl = FALSE;
			self::$enable_acl = FALSE;
		}
		
		parent::__construct($attributes);
		
		if(!$this->acl_field_key && $this->getKeyName()) {
			$this->acl_field_key = $this->getKeyName();
		}
	}

	public function addWhere($model) {
		return true;
	}
	
	public static function disableACL() {
		$self = new static;
		$self->use_acl = false;
		return $self;
	}
	
	public static function unguardAcl() {
		self::$enable_acl = FALSE;
	}
	
	public static function reguardAcl() {
		self::$enable_acl = TRUE;
	}

	public function newQuery() {
		$builder = parent::newQuery();
		
		if(!$this->use_acl || !self::$enable_acl) {
			return $builder;
		}
		
		$this->applyPermissionsRules($builder);
		
		$ok = $this->addWhere($builder);
		
		return $ok !== false ? $builder : $builder->whereRaw('1 = 0');
	}
	
	public static function boot() {
		parent::boot();
		
		self::_registerCreatingPermissions();
		self::_registerUpdatingPermissions();
		self::_registerDeletingPermissions();
		
	}
	
	protected function applyPermissionsRules($builder) {
		$class = get_class($this);
		
		if($class == 'User') {//bug to fix, use to login
			return;
		}
		
		$result = Acl::addWhere($class.'.select', $builder, $this->getAclKey());
		
		if(!$result) {
			$builder->whereRaw('1 = 0');
		}
		
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
	
	private function _useAcl() {
		return $this->use_acl && !self::$enable_acl;
	}

	private static function _registerCreatingPermissions() {
		static::creating(function($model) {
			if($model->_useAcl()) {
				$class = get_class($model);
				$id = $model[$this->getAclKey()];
				return Acl::check($class.'.insert', [$id]);
			}
		});
		static::creating(function($model) {
			if($model->_useAcl()) {
				return $this->checkCreatingPermissions($model);
			}
		});
	}

	private static function _registerUpdatingPermissions() {
		static::updating(function($model) {
			if($model->_useAcl()) {
				$class = get_class($model);
				$id = $model[$this->getAclKey()];
				return Acl::check($class.'.update', [$id]);
			}
		});
		static::updating(function($model) {
			if($model->_useAcl()) {
				return $this->checkCreatingPermissions($model);
			}
		});
	}

	private static function _registerDeletingPermissions() {
		static::deleting(function($model) {
			if($model->_useAcl()) {
				$class = get_class($model);
				$id = $model[$this->getAclKey()];
				return Acl::check($class.'.delete', [$id]);
			}
		});
		static::deleting(function($model) {
			if($model->_useAcl()) {
				return $this->checkDeletingPermissions($model);
			}
		});
	}

}
