<?php

namespace Maxters;

use PHPLegends\Routes\Dispatchable;
use PHPLegends\Routes\Router;
use PHPLegends\Http\ServerRequest;
use PHPLegends\Routes\Collection;
use PHPLegends\Http\Exceptions\HttpException;

/**
 * Dispatcher for Maxters Framework application
 * This dispatcher is costume of this framework and implement Dispatchable for PHPLegends\Route packages
 * @author Wallace de Souza Vizerra <wallacemaxters@gmail.com>
 * 
 * */
class Dispatcher implements Dispatchable
{
	
	/**
	 * @var \Maxters\Container
	 * */
	protected $app;

	/**
	 * 
	 * @param \Maxters\Container $app
	 * */
	public function __construct(\Maxters\Container $app)
	{
		$this->app = $app;
	}

	public function dispatch(Router $router)
	{
		$request = $this->app['request'];

		$uri = $request->getUri()->getPath();

		$routes = $this->filterRoutesByRequest($request, $router->getCollection());

		$method = $request->getMethod();

		$route = $routes->findByVerb($method);

		if ($route === null) {

			$message = sprintf(
				'Method "%s" is not allowed for "%s" route', $method, $uri
			);

			throw $this->getHttpException($message, 405);
		}

		$resultFilter = $router->getFilters()
								->processRouteFilters($route, $this->app);

		if ($resultFilter !== null) {

			return $this->processFilterResult($resultFilter);
		}

		$action = $this->resolveRouteAction($route);

		$response = call_user_func_array($action, $route->match($uri));

		return $this->processRouteResponse($response);

	}

	protected function resolverControllerInstance($class, $method)
	{
		$controller = new $class;

		$controller->setApp($this->app);

		return [$controller, $method];
	}

	protected function processFilterResult($resultFilter)
	{
		if ($resultFilter instanceof \PHPLegends\Http\Response)
		{
			return $resultFilter->send();
		}

		if (is_string($resultFilter))
		{
			return new \PHPLegends\Http\Response($resultFilter);
		}

		//throw new \Exception('Unprocessable filter value');
	}

	protected function processRouteResponse($response)
	{

		if ($this->shouldBeResponse($response)) {

			$response = new \PHPLegends\Http\Response($response, 200, [
				'Content-Type' => 'text/html; charset=utf8;'
			]);

			$response;

		} elseif ($this->shouldBeJsonResponse($response)) {

			$response = new \PHPLegends\Http\JsonResponse($response, 200, JSON_PRETTY_PRINT);

		} elseif (! $response instanceof \PHPLegends\Http\Response) {

			throw new \RunTimeException(
				sprintf(
					'Unprocessable response of type "%s"',
					is_object($response) ? get_class($response) : gettype($response)
				)
			);
		}

		$response->send();
	}

	protected function shouldBeJsonResponse($candidate)
	{
		return is_array($candidate) 
				|| $candidate instanceof \JsonSerializable 
				|| $candidate instanceof \ArrayObject 
				|| $candidate instanceof \stdClass;
	}

	protected function shouldBeResponse($candidate)
	{
		return is_scalar($candidate) || $candidate instanceof \PHPLegends\View\View;
	}

	protected function filterRoutesByRequest(ServerRequest $request, Collection $routes)
	{

		$uri = $request->getUri()->getPath();

		$routes = $routes->filterByUri($uri);

		if ($routes->isEmpty()) {

			throw new HttpException("Route '{$uri}' not found", 404);
		}

		return $routes;
	}

	protected function resolveRouteAction($route)
	{
		$action = $route->getAction();

		if (is_array($action)) {
			
			$action = $this->resolverControllerInstance($action[0], $action[1]);

		} else {

			$action = $action->bindTo($this->app);
		}

		return $action;
	}

	protected function getHttpException($message, $statusCode = 500)
	{		
		return new HttpException($message, $statusCode);
	}
	
}