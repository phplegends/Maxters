<?php

$router = include __DIR__ . '/../boot/start.php';


header('content-type: text/html; charset=utf-8;');

try {

	echo $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

} catch (PHPLegends\Routes\Exceptions\HttpException $e) {

	http_response_code($e->getStatusCode());

	throw $e;
}
