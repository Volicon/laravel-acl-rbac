<?php namespace Volicon\Acl;

/**
 * Description of AclRoute
 *
 * @author nadav.v
 */
class AclRoute extends Acl {
	public function check($resource = null, array $additional_values = [], $user_id = null) {
		if (! $resource) {
			$id = $this->searchId ( \Request::path () );
			if ($id !== FALSE) {
				$additional_values [] = $id;
			}
			
			$resource = \Route::currentRouteName ();
		}
		
		return parent::check ( $resource, $additional_values, $user_id );
	}
}
