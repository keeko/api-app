<?php
namespace keeko\application\api;

use keeko\core\model\ApiQuery;
use keeko\framework\routing\AbstractRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use phootwork\collection\Map;
use phootwork\collection\ArrayList;

class ApiRouter extends AbstractRouter {

	private $methods;

	public function __construct(Request $request, array $options) {
		parent::__construct($request, $options);
		$this->methods = new Map();

		// create routes from db
		$apis = ApiQuery::create()->joinAction()->useActionQuery()->joinModule()->endUse()->find();
		$routes = new RouteCollection();

		/* @var $api Api */
		foreach ($apis as $api) {
			$action = $api->getAction();
			$module = $action->getModule();
			$path = str_replace('//', '/', '/' . $api->getRoute());
			$required = $api->getRequiredParams();
			$required = is_array($required) ? explode(',', $required) : [];

			$name = $module->getName() . ':' . $action->getName() . '@' . $api->getMethod();
			$route = new Route(
				$path, // path
				['path' => $path, 'action' => $action], // default values
				$required, // required
				[], // options
				null, // host
				[], // schemes
				[$api->getMethod(), 'options'] // methods
			);

			// debug: print routes
// 			printf('%s: %s -> %s<br>', $api->getMethod(), $path, $module->getName() . ':' . $action->getName());

			$routes->add($name, $route);

			// with params
			$paramRoute = clone $route;
			$paramRoute->setPath(sprintf('%s%s{params}', $path, $this->options['param-separator']));
			$paramName = $name . 'WithParam';

			$routes->add($paramName, $paramRoute);

			if (!$this->methods->has($path)) {
				$this->methods->set($path, new ArrayList());
			}
			$this->methods->get($path)->add(strtoupper($api->getMethod()));
		}

		$this->init($routes);
	}

	public function match($destination) {
		if ($destination == '') {
			$destination = '/';
		}

		$data = $this->matcher->match($destination);

		// unserialize params
		if (isset($data['params'])) {
			$data['params'] = $this->unserializeParams($data['params']);
		} else {
			$data['params'] = [];
		}

		return $data;
	}

	/**
	 * Returns methods for a given path
	 *
	 * @param string $path
	 * @return array
	 */
	public function getMethods($path) {
		if ($this->methods->has($path)) {
			return $this->methods->get($path)->toArray();
		}

		return [];
	}
}