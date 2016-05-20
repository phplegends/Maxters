<?php

namespace Maxters\Controllers;

class Home extends Controller
{
	public function index()
	{	
		$data['framework'] = 'Maxters';

		$query = $this->app['request']->getQueryParams();

		if (isset($query['framework']))
		{
			$data['framework'] = $query['framework'];
		}

		return $this->render('home/index', $data);
	}	

	public function jsonExample()
	{
		return $this->json(['name' => 'Maxters Framework']);
	}
}