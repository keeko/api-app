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
		$router = new ApiRouter($request, ['basepath' => $this->prefix]);

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
			
			$body = [];
			$contents = $request->getContent();
			if (!empty($contents)) {
				$body = json_decode($contents, true);
			}
			
			$action->setParams(array_merge($params, $match, $body));
			
			return $action->run($request);
		} catch (ResourceNotFoundException $e) {
			$response->setStatusCode(Response::HTTP_NOT_FOUND);
		} catch (\Exception $e) {
			$response->setData([
				'error' => [
					'code' => $e->getCode(),
					'message' => $e->getMessage(),
					'type' => get_class($e)
				]
			]);
		}
		return $response;
	}

}