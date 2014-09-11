<?php namespace Volicon\Acl\Models;

use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Support\Collection;

//use Volicon\Acl\Facades\Acl;
use Acl;
use App;
use ErrorException;

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


	public function __construct($attributes = []) {
		
		if(App::runningInConsole()) {
			$this->use_acl = FALSE;
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
	
	public function getModel() {
		return $this;
	}
	
	public function remember($minutes, $key = null) {
		if(!$this->builder) {
			$this->builder = $this->newQuery();
		}
		$this->builder->remember($minutes, $key);
		return $this;
	}

	public function newQuery() {
		if(!$this->builder) {
			$this->builder = parent::newQuery();
		}
		if(get_class($this->builder) != 'Volicon\Acl\Models\AclBuilder') {
			//TODO find where builder get the real builder!!!
			$this->builder = AclBuilder::toAclBuilder($this->builder);
		}
		if($this->isMe()) {
			return $this->builder ? $this->builder->getRealBuilder() :  parent::newQuery()->getRealBuilder();
		} else {
		return $this->builder ? $this->builder : new AclBuilder;
		}
	}
	
	/**
	 * Create a new Eloquent query builder for the model.
	 *
	 * @param  \Illuminate\Database\Query\Builder $query
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function newEloquentBuilder($query)
	{
		return new AclBuilder($query);
	}
	
	public function newQueryWithoutScope($scope) {
		$builder = parent::newQueryWithoutScope($scope);
		if($this->isMe()) {
			return $builder;
		} else {
			$self = new static;
			$self->builder = $builder;
			return $self;
		}
	}
	
	public function newQueryWithoutScopes() {
		$self = new static;
		$result = parent::newQueryWithoutScopes();
		//doc said it return builder or model
		if($this->isMe() || !is_a($result, '\Illuminate\Database\Eloquent\Builder')) {
			return $result;
		} else {
			$self = new static;
			$self->builder = $result;
			return $self;
		}
	}
	
	public function applyGlobalScopes($builder) {
		$builder = parent::applyGlobalScopes($builder);
		if($this->isMe()) {
			return $builder;
		} else {
			$self = new static;
			$self->builder = $builder;
			return $self;
		}
	}
	
	public function removeGlobalScopes($builder) {
		$builder = parent::removeGlobalScopes($builder);
		if($this->isMe()) {
			return $builder;
		} else {
			$self = new static;
			$self->builder = $builder;
			return $self;
		}
	}
	
	//warning getConnection(),resolveConnection - must be public 
	public function getConnection() {
		if($this->isMe() || !$this->use_acl) {
			return parent::getConnection();
		}
		
		throw new \Exception('No allow getConnection on Acl enable');
	}
	
	public static function resolveConnection($connection = null) {
		$self = new static;
		if($self->isMe() || !$self->use_acl) {
			return parent::resolveConnection($connection);
		}
		
		throw new \Exception('No allow getConnection on Acl enable');
	}
	
	public static function getConnectionResolver() {
		$self = new static;
		if($self->isMe() || !$self->use_acl) {
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
		if(is_a($result, '\Illuminate\Database\Eloquent\Builder')) {
			return AclBuilder::toAclBuilder($result);
		}
		return $self;
	}

	public static function query() {
		$self = new static;
		$self->builder = $self->newQuery();
		return $self;
	}
	
	public static function on($connection = null) {
		$self = new static;
		$self->builder = parent::on($connection);
		return $self;
	}
	
	public static function all($columns = array('*'))
	{
		return static::query()->get($columns);
	}
	
	public static function find($id, $columns = array('*')) {
		if (is_array($id) && empty($id)) {
			return new Collection;
		}
		
		return static::query()->builder->find($id, $columns);
	}
	
	//findOrNew, findOrFail use find

	public function addWhere($model, $type) {
		return true;
	}
	
	public function checkParams(array $params) {
		return true;
	}

	public function isMe() {
		$bt = last(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));
		
		return in_array($bt['class'], ['Volicon\Acl\Models\AclModel', '\Illuminate\Database\Eloquent\Model', 'Illuminate\Database\Eloquent\Builder']);
	}

}
