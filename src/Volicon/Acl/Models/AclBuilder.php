<?php namespace Volicon\Acl\Models;

use Illuminate\Database\Eloquent\Builder;
use \Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Description of AclBuilder
 *
 * @author nadav.v
 */
class AclBuilder {
	/* @var $builder \Illuminate\Database\Eloquent\Builder */
	private $builder = null;
	private $use_acl = true;
	protected $acl_field_key = '';
	
	private $allow_model_operations = [
		'where', 'whereIn', 'withTrashed','whereHas', 'onlyTrashed', 'observe', 'saving',
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
		'query', 'setModel'];
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
		'generateCacheKey', 'getModel'
	];
	private $allow_select_operetions = ['find', 'findOrFail', 'first', 'firstOrFail', 'get', 'pluck', 'lists', 'paginate', 'getFresh', 'getCached', 'implode',
	'exists', 'count', 'min', 'max', 'sum', 'avg', 'aggregate'];
	private $allow_insert_operations = [];
	private $allow_update_operations = ['update', 'increment', 'decrement', 'restore', 'push', 'touch'];
	private $allow_delete_operations = ['delete', 'forceDelete', 'destroy'];
	private $allow_fill_operations = ['fill', 'newInstance', 'newFromBuilder',
		'setCreatedAt', 'setUpdatedAt', 'setAttribute', 'setRawAttributes'];
	private $allow_relationships_operations = ['with','hasMany'];
	private $allow_table_manipulation_oprations = ['truncate'];
	
	public function __call($name, $arguments) {
		
		/*if(!$this->builder && in_array($name, $this->allow_init_builder_operations)) {
			$this->builder = call_user_func_array([$this->model, $name], $arguments);
			return $this;
		}*/
		if(in_array($name, $this->allow_model_operations)
		|| in_array($name, $this->allow_relationships_operations)) {
			if(!$this->builder) {
				$this->builder = $this->newQuery();
			}
			
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
			return $this->do_delete_operation($name, $arguments);
		}
		
		if(in_array($name, $this->allow_table_manipulation_oprations)) {
			if(!$this->use_acl) {
				return call_user_func_array([$this->builder, $name], $arguments);
			}
		}
		
	}
	
	
	public function getQuery() {
		
		$result = $this->builder->getQuery();
		
		$bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$file_parts = explode(DIRECTORY_SEPARATOR, $bt[0]['file']);
		$class_file = implode('\\', array_slice($file_parts, -4));
		$class = explode('.', $class_file)[0];
		if($class == 'Illuminate\Database\Eloquent\Builder')
		{
			return $result;
		}
		
		throw new Exception('prevent external use of getQuery');
	}

	

	protected function do_select_operation($name, $arguments) {
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
	
	public static function toAclBuilder(Builder $builder) {
		$instance = new static($builder);
		$instance->builder = $builder;
		return $instance;
	}
	
	public function getRealBuilder() {
		return $this->builder;
	}
	
	public function __construct($query)
	{
		if(get_class($query) == 'Illuminate\Database\Eloquent\Builder') {
			$this->builder = $query;
		} else if(get_class($query) == 'Illuminate\Database\Query\Builder') {
			$this->builder = new Builder($query);
		} else {
			throw new \Exception("Wrong argument for AclBuilder::__construct ".get_class($query));
		}
	}
	
	public function __clone()
	{
		$this->builder = clone $this->builder;
	}
	
}
