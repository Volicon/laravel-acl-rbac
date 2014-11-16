<?php namespace Volicon\Acl;

use Volicon\Acl\Facades\Acl as AclFacade;
use Volicon\Acl\Exceptions\NoPermissionsException;
use Illuminate\Support\Facades\Route;

/**
 * Description of AclRoute
 *
 * @author nadav.v
 */
class AclRoute {
	
	public static function check() {
		$route = Route::current();
		
		$route_name = $route->getName();
		if(!$route_name) {
			$route_name = $route->getActionName();
		}
		
		$params = $route->parametersWithoutNulls();
		
		$ids = [];
		
		if($params) {
			$param = current($params);
			if(is_numeric($param)) {
				$ids[] = $param;
			}
		}
		
		$result = AclFacade::check($route_name, $ids);
		
		if(!$result) {
			$error_message = "No Permission for $route_name";
			
			if($ids) {
				$error_message .= " for id: {$ids[0]}";
			}
			
			throw new NoPermissionsException($error_message);
		}
		
		return $result;
	}
}
