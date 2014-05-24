<?php

namespace keeko\application\api;

use keeko\core\application\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiApplication extends AbstractApplication {
	
	
	/* (non-PHPdoc)
	 * @see \keeko\core\application\AbstractApplication::run()
	 */
	public function run(Request $request) {
		return new JsonResponse();
	}

}