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
	
}
