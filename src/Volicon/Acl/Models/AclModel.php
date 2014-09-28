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
	
	/* @var $builder \Illuminate\Database\Eloquent\Builder */
	private $builder = null;
	private $use_acl = true;
	protected $acl_field_key = '';
	private static $enable_acl = true;


	public function __construct($attributes = []) {
		
		if(App::runningInConsole()) {
			$this->use_acl = FALSE;
			self::$enable_acl = FALSE;
		}
		
		$this->check_parms($attributes);
		
		parent::__construct($attributes);
		
		if(!$this->acl_field_key && $this->getKeyName()) {
			$this->acl_field_key = $this->getKeyName();
		}
	}
	
	public static function disableACL() {
		$self = new static;
		$self->use_acl = false;
		return $self;
	}

	public static function __callStatic($name, $arguments) {
		$self = new static;
		$self->builder = $self->newQuery();
		
		return call_user_func_array([$self->builder, $name], $arguments);
	}
	
	public function __call($name, $arguments) {
		if(!$this->builder) {
			$this->builder = $this->newQuery();
		}
		
		return call_user_func_array([$this->builder, $name], $arguments);
		
	}
	
	protected function check_parms($attributes = []) {
		if(count($attributes) && !$this->checkParams($attributes)) {
			throw New Exception("Wrong attrinutes for ".get_class($this)." args:".print_r($attributes, 1));
		}
		
		return true;
	}
	
	public function remember($minutes, $key = null) {
		if(!$this->builder) {
			$this->builder = $this->newQuery();
		}
		$this->builder->remember($minutes, $key);
		return $this;
	}

	public function newQuery() {
		$builder = parent::newQuery();
		
		if($this->isCalledFromBuilder()) {
			return $builder;
		}
		
		return AclBuilder::toAclBuilder($builder);
	}
	
	/**
	 * Create a new Eloquent query builder for the model.
	 *
	 * @param  \Illuminate\Database\Query\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function newEloquentBuilder($query)
	{
		$builder = parent::newEloquentBuilder($query);
		if($this->isCalledFromBuilder()) {
			return $builder;
		}
		
		return new AclBuilder($builder);
	}
	
	public function newQueryWithoutScope($scope) {
		$builder = parent::newQueryWithoutScope($scope);
		if($this->isCalledFromBuilder()) {
			return $builder;
		}
		
		return AclBuilder::toAclBuilder($builder);
	}
	
	public function newQueryWithoutScopes() {//debug problem not a new
		$builder = parent::newQueryWithoutScopes();
		if($this->isCalledFromBuilder()) {
			return $builder;
		}
		
		return AclBuilder::toAclBuilder($builder);
	}
	
	public function applyGlobalScopes($builder) {
		$builder = parent::applyGlobalScopes($builder);
		
		if($this->isCalledFromBuilder()) {
			return $builder;
		}
		
		if($builder instanceof \Illuminate\Database\Eloquent\Builder) {
			$builder = AclBuilder::toAclBuilder($builder);
		}
		return $builder;
	}
	
	public function removeGlobalScopes($builder) {
		$builder = parent::removeGlobalScopes($builder);
		if($this->isCalledFromBuilder()) {
			return $builder;
		}
		
		if($builder instanceof \Illuminate\Database\Eloquent\Builder) {
			$builder = AclBuilder::toAclBuilder($builder);
		}
		
		return $builder;
	}
	
	public function getConnection() {
		if($this->isCalledFromBuilder() || !$this->use_acl || !self::$enable_acl) {
			return parent::getConnection();
		}
		
		throw new \Exception('No allow getConnection on Acl enable');
	}
	
	public static function resolveConnection($connection = null) {
		$self = new static;
		if($self->isCalledFromBuilder() || !$self->use_acl || !self::$enable_acl) {
			return parent::resolveConnection($connection);
		}
		
		throw new \Exception('No allow getConnection on Acl enable');
	}
	
	public static function getConnectionResolver() {
		$self = new static;
		if($self->isCalledFromBuilder() || !$self->use_acl || self::$enable_acl) {
			return parent::getConnectionResolver();
		}
		
		throw \Exception('No allow getConnectionResolver on Acl enable');
	}

	public static function create(array $attributes) {
		 $model = new static($attributes);
  
          $model->save();
  
         return $model;
	}
	
	public function save(array $options = array()) {
		$acl_check = true;
		if(isset($options[$this->acl_field_key])) {
			$acl_check_result = Acl::check(get_class().'.update', $options[$this->acl_field_key]);
			$acl_check = ($acl_check_result === Acl::ALLOWED);
		}
		
		if($this->check_parms($options) && $acl_check) {
			return parent::save($options);
		} else {
			throw new \Exception("faild save ".get_class(new static).' fields:'.print_r($options, 1));
		}
	}
	
	/*public static function select($fields = []) {
		$self = new static();
		$self->builder = $self->newQuery()->select($fields);
		return $self;
	}*/
	
	/*public function update() {
		Acl::addWhere(get_class().'.update', $this->model);
		$this->addWhere($this->model);
		return $this->model->update();
	}*/
	
	public function delete() {
		/*if($this->use_acl) {
			Acl::addWhere(get_class().'.delete', $this->builder, $this->acl_field_key);
		}*/
		//TODO: need to use model delete for check primaryKey, fireModelEvent and so on
		if($this->builder) {
			return $this->do_delete_operation('delete');
		} else {
			//TODO: need to implement permission changes
			return parent::delete();
		}
		
	}
	
	public static function with($relations) {
		
		$self = new static;
		$result = call_user_func_array('parent::with', func_get_args());
		//doc said it return builder or model
		if(is_a($result, '\Illuminate\Database\Eloquent\Builder') || 
			is_a($result, 'Volicon\Acl\Models\AclBuilder')) {
			return AclBuilder::toAclBuilder($result);
		}
		return $self;
	}

	public static function query() {
		$self = new static;
		$builder = $self->newQuery();
		if($self->isCalledFromBuilder()) {
			return $builder;
		}
		
		return AclBuilder::toAclBuilder($builder);
	}
	
	public static function on($connection = null) {
		$builder = parent::on($connection);
		if(static::isCalledFromBuilder()) {
			return $builder;
		}
		
		return AclBuilder::toAclBuilder($builder);
	}
	
	public static function all($columns = array('*'))
	{
		return static::query()->get($columns);
	}
	
	public static function find($id, $columns = array('*')) {
		if (is_array($id) && empty($id)) {
			return new Collection;
		}
		
		return static::query()->find($id, $columns);
	}

	public function addWhere($model, $type) {
		return true;
	}
	
	public function checkParams(array $params) {
		return true;
	}

	private static function isCalledFromBuilder() {
		//$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 7);
		//$bt = last($bt);
		
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$file_parts = explode(DIRECTORY_SEPARATOR, $bt[1]['file']);
		$class_file = implode('\\', array_slice($file_parts, -4));
		$class = explode('.', $class_file)[0];
		$result = ($class == 'Illuminate\Database\Eloquent\Builder' ||
				  $class == 'Volicon\Acl\Models\AclModel' ||
				 $class == 'Illuminate\Database\Eloquent\Model');
		
		return $result;
	}
	
	public static function unguardAcl() {
		self::$enable_acl = FALSE;
	}
	
	public static function reguardAcl() {
		self::$enable_acl = TRUE;
	}
	
}
