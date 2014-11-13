<?php namespace Volicon\Acl\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * Description of GroupResources
 *
 * @author nadav.v
 * @property int $permission_id
 * @property string $resource
 */
class GroupResources extends \Eloquent {
	protected $table = 'group_resources';
	protected $primaryKey = 'permission_id';
	public $timestamps = false;
	
	private static $group_resources = [];
	private static $resources_in_groups = [];

	public static function getGroupResources() {
		
		if(!self::$group_resources) {
			foreach(static::all() as $row) {
				self::$group_resources[$row->permission_id] = $row->resource;
			}
		}
		
		return new Collection(self::$group_resources);
	}
	
	public static function getDependentGroupsResources() {
		$config_group_resources = Config::get('acl::config.group_resources');
		$result = [];
		
		$func_data = function(&$data) {
			if(!isset ( $data ['@options'] )) {
				$data ['@options'] = [];
			}
			
			if(!isset ( $data ['@options']['depend'] )) {
				$data ['@options']['depend'] = [];
			}
			
			if(!isset ( $data ['@options']['sub_resource'] )) {
				$data ['@options']['sub_resource'] = false;
			}
		};
		
		foreach($config_group_resources as $group=>$data) {
			$func_data($data);
			if(!$data['@options']['sub_resource']) {
				$result[$group] = [];
				self::_build_dependent_group_resources($result[$group], $group);
			}
		}
		
		return $result;
	}

	public static function getResourceGroup($resource) {
		if(in_array($resource, self::$group_resources)) {
			return $resource;
		}
		
		self::_fillResourcesInGroups();
		return isset(self::$resources_in_groups[$resource]) ? self::$resources_in_groups[$resource] : FALSE;
	}
	
	
	private static function _fillResourcesInGroups() {
		
		if(self::$resources_in_groups) {
			return;
		}
		
		$config_group_resources = Config::get('acl::config.group_resources');
		
		foreach($config_group_resources as $group=>&$resources) {
			foreach($resources as $resource) {
				if(is_array($resource)) {
					continue;
				}
				self::$resources_in_groups[$resource] = $group;
			}
		}
	}
	
	private static function _get_config_group ($resource) {
		$group_resources = Config::get('acl::config.group_resources');
		$data = $group_resources[$resource];
		
		if(!is_array($data)) {
			return NULL;
		}
		
		if(!isset ( $data ['@options'] )) {
			$data ['@options'] = [];
		}

		if(!isset ( $data ['@options']['depend'] )) {
			$data ['@options']['depend'] = [];
		}

		if(!isset ( $data ['@options']['sub_resource'] )) {
			$data ['@options']['sub_resource'] = false;
		}
		
		return $data;
	}
	
	private static function _build_dependent_group_resources(&$result, $resource) {
		$config_group = self::_get_config_group($resource);
		$sub_resources = $config_group['@options']['depend'];
		foreach($sub_resources as $sub_resource) {
			$result[] = $sub_resource;
			self::_build_dependent_group_resources($result, $sub_resource);
		}
	}
	
}
