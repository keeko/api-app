<?php

namespace keeko\application\api;

use keeko\core\routing\AbstractRouter;
use keeko\core\model\ApiQuery;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class ApiRouter extends AbstractRouter {
	
	public function __construct(array $options) {
		parent::__construct($options);
	
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
					[$api->getMethod()] // methods
			);
			
			$routes->add($name, $route);
			
			// with params
			$paramRoute = clone $route;
			$paramRoute->setPath(sprintf('%s%s{params}', $path, $this->options['param-separator']));
			$paramName = $name . 'WithParam';
			
			$routes->add($paramName, $paramRoute);
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
}