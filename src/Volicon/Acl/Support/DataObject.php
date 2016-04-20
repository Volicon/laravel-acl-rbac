<?php namespace Volicon\Acl\Support;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable as ArrayableInterface;
use Illuminate\Contracts\Support\Jsonable as JsonableInterface;

/**
 * Description of DataObject
 *
 * @author nadav.v
 */
abstract class DataObject  implements ArrayAccess, ArrayableInterface, JsonableInterface, JsonSerializable {
	
	protected $attributes = [];
	
	public function __construct(array $data) {
		$this->attributes = $data;
	}

	public function jsonSerialize() {
		return $this->toArray();
	}

	public function offsetExists($offset) {
		return isset($this->attributes[$offset]);
	}

	public function offsetGet($offset) {
		return $this->attributes[$offset];
	}
	
	public function offsetSet($offset, $value) {
		$this->attributes[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->attributes[$offset]);
	}
	
	public function toArray() {
		return $this->attributes;
	}

	public function toJson($options = 0) {
		return json_encode($this, $options);
	}
	
	public function __get($name) {
		return $this->offsetGet($name);
	}
	
	public function __set($name, $value) {
		$this->offsetSet($name, $value);
	}
	
	public function __isset($name) {
		return isset($this->attributes[$name]);
	}
	
	public function __unset($name) {
		unset($this->attributes[$name]);
	}
	
}
