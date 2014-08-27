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
	
	private $builder = null;
	private $use_acl = true;
	protected $acl_field_key = '';


	private $allow_model_operations = [
		'where', 'whereIn', 'withTrashed', 'onlyTrashed', 'observe', 'saving',
		'saved', 'updating', 'updated',
		'creating', 'created', 'deleting', 'deleted', 'flushEventListeners',
		'setSoftDeleting', 'fillable', 'guard', 'setPerPage' ,'setHidden',
		'setVisible', 'unguard', 'reguard',	'setUnguardState',
		'setIncrementing', 'syncOriginal', 'setEventDispatcher',
		'setEventDispatcher', 'offsetUnset', 'orderBy','skip', 'take',
		'forPage', 'union', 'unionAll', 'groupBy', 'having',
		'addSelect', 'distinct', 'from', 'join', 'leftJoin', 'orWhere',
		'whereRaw', 'orWhereRaw', 'whereBetween', 'orWhereBetween',
		'whereNested', 'whereSub', 'whereExists', 'orWhereExists',
		'whereNotExists', 'orWhereNotExists', 'orWhereIn', 'whereNotIn',
		'orWhereNotIn', 'whereNull', 'orWhereNull', 'whereNotNull',
		'orWhereNotNull', 'dynamicWhere', 'havingRaw', 'orHavingRaw', 'select',
		'query'];
	private $allow_info_operations = [
		'getMutatedAttributes', 'offsetExists', 'offsetGet',
		'getObservableEvents','getCreatedAtColumn', 'getUpdatedAtColumn',
		'getDeletedAtColumn', 'getQualifiedDeletedAtColumn', 'freshTimestamp',
		'trashed', 'getTable', 'getKey', 'getKeyName', 'getQualifiedKeyName',
		'usesTimestamps', 'isSoftDeleting', 'getPerPage', 'isDirty',
		'getForeignKey', 'getHidden', 'getFillable', 'toJson', 'toArray',
		'isFillable', 'isGuarded', 'totallyGuarded', 'getIncrementing',
		'attributesToArray', 'getAttribute', 'hasGetMutator', 'hasSetMutator',
		'getDates', 'getAttributes', 'getOriginal',
		'getDirty', 'getTablePrefix', 'toSql', 'getCacheKey',
		'generateCacheKey',
	];
	private $allow_select_operetions = ['find', 'findOrFail', 'first', 'firstOrFail', 'get', 'pluck', 'lists', 'paginate', 'getFresh', 'getCached', 'implode',
	'exists', 'count', 'min', 'max', 'sum', 'avg', 'aggregate'];
	private $allow_insert_operations = [];
	private $allow_update_operations = ['update', 'increment', 'decrement', 'restore', 'push', 'touch'];
	private $allow_delete_operations = ['delete', 'forceDelete', 'destroy'];
	private $allow_fill_operations = ['fill', 'newInstance', 'newFromBuilder',
		'setCreatedAt', 'setUpdatedAt', 'setAttribute', 'setRawAttributes'];
	private $allow_relationships_operations = ['with','hasMany'];


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
		if(in_array($name, $self->allow_select_operetions)) {
			
			return $self->do_select_operation($name, $arguments);
			
		}
		
		if(in_array($name, $self->allow_relationships_operations)) {
			//$self->builder = $self->model->newQuery();
			$self->builder = $self->newQuery();
			return $self->do_relationships_operation($name, $arguments);
		}
		
		return call_user_func_array([$self, $name], $arguments);
	}
	
	public function __call($name, $arguments) {
		
		/*if(!$this->builder && in_array($name, $this->allow_init_builder_operations)) {
			$this->builder = call_user_func_array([$this->model, $name], $arguments);
			return $this;
		}*/
		
		if(in_array($name, $this->allow_model_operations)) {
			call_user_func_array([$this->builder, $name], $arguments);
			return $this;
		}
		
		if(in_array($name, $this->allow_info_operations)) {
			return call_user_func_array([$this->builder, $name], $arguments);
		}
		
		if(in_array($name, $this->allow_select_operetions)) {
			return $this->do_select_operation($name, $arguments);
		}
		
		if(in_array($name, $this->allow_insert_operations)) {
			return $this->do_insert_operation($name, $arguments);
		}
		
		if(in_array($name, $this->allow_update_operations)) {
			return $this->do_update_operation($name, $arguments);
		}
		
		if(in_array($name, $this->allow_delete_operations)) {
			return $this->do_update_operation($name, $arguments);
		}
		
		if(in_array($name, $this->allow_fill_operations)) {
			return $this->do_fill_operation($name, $arguments);
		}
	}
	
	//TODO: Not Implemented
	protected function do_fill_operation($name, $arguments) {
		throw new Exception('TODO: Not Implemented');
	}
	
	protected function do_select_operation($name, $arguments) {
		
		if(!$this->builder) {
			$this->builder = $this->newQuery();
		}
		if($this->use_acl) {
			if($this->acl_field_key) {
				Acl::addWhere(get_class().'.select', $this->builder, $this->acl_field_key);
			}
			$this->addWhere($this, 'select');
		}
		
		return call_user_func_array([$this->builder, $name], $arguments);
	}
	
	protected function do_insert_operation($name, $arguments) {
		if($this->use_acl) {
			if($this->acl_field_key) {
				Acl::addWhere(get_class().'.insert', $this->builder, $this->acl_field_key);
			}
			$this->addWhere($this, 'insert');
		}
		call_user_func_array([$this->builder, $name], $arguments);
		return $this;
	}
	
	protected function do_update_operation($name, $arguments) {
		if($this->use_acl) {
			if($this->acl_field_key) {
				Acl::addWhere(get_class().'.update', $this->builder, $this->acl_field_key);
			}
			$this->addWhere($this, 'update');
		}
		call_user_func_array([$this->builder, $name], $arguments);
		return $this;
	}
	
	protected function do_delete_operation($name, $arguments = []) {
		if($this->use_acl) {
			Acl::addWhere(get_class().'.delete', $this->builder, $this->acl_field_key);
		}
		return call_user_func_array([$this->builder, $name], $arguments);
	}
	
	protected function do_relationships_operation($name, $arguments) {
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
		if($this->isMe()) {
			return $this->builder ? $this->builder :  parent::newQuery();
		} else {
			if($this->builder) {
				return $this;
			} else {
				$self = new static;
				$self->builder = parent::newQuery();
				return $self;
			}
		}
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
	
	public function newEloquentBuilder($query) {
		$builder = parent::newEloquentBuilder($query);
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
		return $this->do_delete_operation('delete');
	}
	
	public static function with($relations) {
		$self = new static;
		$result = call_user_func_array('parent::with', func_get_args());
		//doc said it return builder or model
		if(is_a($result, '\Illuminate\Database\Eloquent\Builder')) {
			$self->builder = $result;
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
		$instance = new static;
		$instance->builder = $instance->newQuery();
		return $instance->do_select_operation('get', func_get_args());
	}
	
	public static function find($id, $columns = array('*')) {
		if (is_array($id) && empty($id)) {
			return new Collection;
		}
		
		$instance = new static;
		$instance->builder = $instance->newQuery();
		return $instance->do_select_operation('find', func_get_args());
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
