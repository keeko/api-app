<?php

namespace keeko\application\api;

use keeko\core\application\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use keeko\core\model\Api;
use keeko\core\model\ApiQuery;
use keeko\core\model\Module;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ApiApplication extends AbstractApplication {
	
	private $prefixes;
	
	/* (non-PHPdoc)
	 * @see \keeko\core\application\AbstractApplication::run()
	*/
	public function run(Request $request, $path) {
	
		$response = new JsonResponse();
		$router = new ApiRouter(['basepath' => $this->prefix]);

		try {
			$path = str_replace('//', '/', '/' . $path);
			$match = $router->match($path);

			$action = $match['action'];
			$module = $this->moduleManager->load($action->getModule()->getName());
			$action = $module->loadAction($action, 'json');

			$params = $match['params'];
			unset($match['_route']);
			unset($match['action']);
			unset($match['path']);
			unset($match['params']);
			
			$action->setParams(array_merge($params, $match));
			
			return $action->run($request);
		} catch (ResourceNotFoundException $e) {
			$response->setStatusCode(Response::HTTP_NOT_FOUND);
		}
		return $response;
	}
	
// 	private function loadRoutes() {
// 		// create routes from db
// 		$apis = ApiQuery::create()->joinAction()->useActionQuery()->joinModule()->endUse()->find();
// 		$routes = new RouteCollection();
	
// 		/* @var $api Api */
// 		foreach ($apis as $api) {
// 			$action = $api->getAction();
// 			$module = $action->getModule();
// 			$path = str_replace('//', '/', '/' . $api->getRoute());
// 			$required = $api->getRequiredParams();
// 			$required = is_array($required) ? explode(',', $required) : [];
	
// 			$name = $module->getName() . ':' . $action->getName() . '@' . $api->getMethod();
// 			$route = new Route(
// 					$path, // path
// 					['path' => $path, 'action' => $action], // default values
// 					$required, // required
// 					[], // options
// 					null, // host
// 					[], // schemes
// 					[$api->getMethod()] // methods
// 			);
	
// 			$routes->add($name, $route);
// 		}
// 		return $routes;
// 	}
	
// 	private function getModuleNamespace(Module $module) {
// 		$name = $module->getName();
// 		if (isset($this->prefixes[$name])) {
// 			return $this->prefixes[$name];
// 		}
	
// 		$packageManager = $this->moduleManager->getPackageManager();
// 		$package = $packageManager->getModulePackage($name);
// 		$extra = $package->getExtra();
	
// 		if (isset($extra['keeko']) && isset($extra['keeko']['app'])
// 				&& isset($extra['keeko']['app']['api'])
// 				&& isset($extra['keeko']['app']['api']['namespace'])) {
// 					return $extra['keeko']['app']['api']['namespace'];
// 				}
	
// 				return explode('/', $name)[1];
// 	}
	

}