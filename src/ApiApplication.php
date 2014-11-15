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
use keeko\core\exceptions\PermissionDeniedException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class ApiApplication extends AbstractApplication {
	
	private $prefixes;
	
	/* (non-PHPdoc)
	 * @see \keeko\core\application\AbstractApplication::run()
	 */
	public function run(Request $request, $path) {
	
		$response = new JsonResponse();
		$router = new ApiRouter($request, ['basepath' => $this->getAppPath()]);

		try {
			$path = str_replace('//', '/', '/' . $path);
			$match = $router->match($path);

			$action = $match['action'];
			$module = $this->service->getModuleManager()->load($action->getModule()->getName());
			$action = $module->loadAction($action, 'json');

			$params = $match['params'];
			unset($match['_route']);
			unset($match['action']);
			unset($match['path']);
			unset($match['params']);
			
			$body = [];
			$contents = $request->getContent();
			if (!empty($contents)) {
				$json = json_decode($contents, true);
				if ($json !== null) {
					$body = $json;
				}
			}

			$action->setParams(array_merge($params, $match, $body));
			
			return $action->run($request);
		} catch (ResourceNotFoundException $e) {
			$response->setStatusCode(Response::HTTP_NOT_FOUND);
			$response->setData($this->exceptionToJson($e));
		} catch (PermissionDeniedException $e) {
			$response->setStatusCode($e->getCode());
			$response->setData($this->exceptionToJson($e));
		} catch (MethodNotAllowedException $e) {
			$response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
			$response->setData($this->exceptionToJson($e));
		} catch (\Exception $e) {
			$response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
			$response->setData($this->exceptionToJson($e));
		}
		return $response;
	}
	
	private function exceptionToJson(\Exception $e) {
		return [
				'error' => [
					'code' => $e->getCode(),
					'message' => $e->getMessage(),
					'type' => get_class($e)
				]
			];
	}

}