<?php namespace Volicon\Acl;

use Volicon\Acl\Models\VirtualModel;
use Config;
use InvalidArgumentException;
use Volicon\Acl\Models\GroupResources;

/**
 * Description of Permission
 *
 * @author nadav.v
 */
class Permission extends VirtualModel {
	
	public function __construct($resource, $values = [], $allowed = null) {
		
		$data = [];
		
		$default_permission = Config::get ( "acl::config.default_permission" );
		
		if(is_array($resource)) {
			$resource = (object)$resource;
		}
		
		if(is_object($resource)) {
			/* @var $resource Permission */
			if(!(isset($resource->resource) || isset($resource->permission_id))) {
				throw new InvalidArgumentException('permission must include resource');
			}
			
			if(!isset($resource->resource)) {
				$group_resources = GroupResources::getGroupResources();
				
				if(!isset($group_resources[$resource->permission_id])) {
					throw new InvalidArgumentException('permission id do not have resource: '.$resource->permission_id);
				}
				
				$data['resource'] = $group_resources[$resource->permission_id];
			} else {
				$data['resource'] = $resource->resource;
			}
			
			if(isset($resource->values)) {
				if(is_array($resource->values)) {
					$data['values'] = $resource->values;
				} else if(is_string($resource->values)) {
					$data['values'] = json_decode($resource->values);
				}
			}
			
			$data['allowed'] = !isset($resource->allowed) || is_null ( $resource->allowed ) ? $default_permission : (bool)$resource->allowed;
		} else {
		
			$data['resource'] = $resource;
			$data['values'] = $values;
		
		
			$data['allowed'] = is_null ( $allowed ) || ! is_bool ( $allowed ) ? $default_permission : $allowed;
		}
		
		parent::__construct($data);
	}
	public function mergePermission(Permission $permission) {
		
		if($permission->resource !== $this->resource) {
			throw new \Exception('Resouce not match');
		}
		
		if (($this->allowed && $permission->allowed) || ($this->allowed && ! $this->values) || ($permission->allowed && ! $permission->values)) {
			if (! $this->values || ! $permission->values) {
				return new self($this->resource, [], true);
			} else {
				$ids = array_merge ( $this->values, $permission->values);
				return new self($this->resource, $ids, true);
			}
		}
		
		$p1 = $permission->allowed ? $permission : $this;
		$p2 = $permission->allowed ? $this : $permission;
		
		if ($p1->allowed) {
			if (! $p2->values) {
				return clone $p1;
			}
			
			$values = array_diff ( $p2->values, $p1->values );
			return new self($p1->resource, $values, false);
		}
		
		// both not allow
		if (! $p1->values && ! $p2->values) {
			return clone $p1;
		}
		
		if ($p1->values && $p2->values) {
			$values = array_intersect ( $p1->values, $p2->values );
			return new self($p1->resource, $values, false);
		}
		
		if ($p1->values) {
			return clone $p1;
		}
		
		if ($p2->values) {
			return clone $p2;
		}
		
		return new self($this->resource);
		
	}
	
	public function isAllowAll() {
		return $this->allowed && !$this->values;
	}

	public function offsetUnset($offset) {
		throw new \Exception();
	}

}
