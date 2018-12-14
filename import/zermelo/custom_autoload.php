<?php

function register_zermelo_api()
{
	if (file_exists(__DIR__ . '/Cache.php'))
	{
		include_once(__DIR__ . '/Cache.php');
	} else {
		throw new Exception(__DIR__ . "/Cache.php has not been found!");
	}
	
	if (file_exists(__DIR__ . '/Zermelo.php'))
	{
		include_once(__DIR__ . '/Zermelo.php');
	} else {
		throw new Exception(__DIR__ . "/Zermelo.php has not been found!");
	}	
}
