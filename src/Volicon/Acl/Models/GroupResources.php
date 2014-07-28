<?php namespace Volicon\Acl\Models;

/**
 * Description of GroupResources
 *
 * @author nadav.v
 * @property int $permission_id
 * @property string $resource
 */
class GroupResources  extends \Eloquent {
	protected $table = 'group_resources';
	protected $primaryKey = 'permission_id';
	public $timestamps = false;
}
