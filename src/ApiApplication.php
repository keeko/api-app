<?php
namespace keeko\application\api;

use keeko\framework\foundation\AbstractApplication;
use keeko\framework\exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class ApiApplication extends AbstractApplication {

	/** @var ApiRouter */
	private $router;

	public function run(Request $request) {
		$response = new JsonResponse();
		$this->router = new ApiRouter($request, ['basepath' => $this->getAppPath()]);
		$path = str_replace('//', '/', '/' . $this->getDestinationPath());

		try {
			$match = $this->router->match($path);

			// set headers for CORS
			if ($request->isMethod('options')) {
				return $this->setCorsHeaders($response, $match['path']);
			}

			$action = $match['action'];
			$module = $this->service->getModuleManager()->load($action->getModule()->getName());
			$action = $module->loadAction($action, 'json');

			$params = $match['params'];
			unset($match['_route']);
			unset($match['action']);
			unset($match['path']);
			unset($match['params']);

			$action->setParams(array_merge($params, $match));
			$kernel = $this->getServiceContainer()->getKernel();
			$response = $kernel->handle($action, $request);
		}

		// 404 - Resource not found
		catch (ResourceNotFoundException $e) {
			$response->setStatusCode(Response::HTTP_NOT_FOUND);
			$response->setData($this->exceptionToJson($e));
		}

		// 403 - Permission denied
		catch (PermissionDeniedException $e) {
			$response->setStatusCode(Response::HTTP_FORBIDDEN);
			$response->setData($this->exceptionToJson($e));
		}

		// 405 - Method not allowed
		catch (MethodNotAllowedException $e) {
			$response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
			$response->setData($this->exceptionToJson($e));
		}

		// 500 - Internal Server error
		catch (\Exception $e) {
			$response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
			$response->setData($this->exceptionToJson($e));
		}

		$response = $this->setCorsHeaders($response, $path);

		return $this->postProcessing($response);
	}

	private function exceptionToJson(\Exception $e) {
		return [
			'errors' => [[
				'code' => $e->getCode(),
				'title' => $e->getMessage(),
				'meta' => [
					'exception' => get_class($e),
					'trace' => $e->getTrace()
				]
			]]
		];
	}

	/**
	 * Some post processing on the generated result. Replacing some variables.
	 *
	 * @param JsonResponse $response
	 * @return JsonResponse
	 */
	private function postProcessing(JsonResponse $response) {
		$apiUrl = $this->getServiceContainer()->getPreferenceLoader()->getSystemPreferences()->getApiUrl();
		$response->setContent(str_replace('%apiurl%', $apiUrl, $response->getContent()));
		return $response;
	}

	private function setCorsHeaders(Response $response, $path) {
		$response->headers->set('Access-Control-Allow-Origin', '*');
		$response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->router->getMethods($path)));
		$response->headers->set('Access-Control-Allow-Headers', 'origin, content-type, accept, authorization');
		$response->headers->set('Access-Control-Allow-Credentials', true);
		$response->headers->set('Access-Control-Max-Age', '1000');

		return $response;
	}

}