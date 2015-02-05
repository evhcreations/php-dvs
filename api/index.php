<?php

	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	/*
	
	DVS API-omgeving
	Opvragen van InfoPlus-data via een geheugensocket
	
	Copyright 2015 Erik van Heck - info@evhcreations.nl
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	*/
	ini_set('memory_limit','500M');
	require('vendor/constants.php');
	require('vendor/autoload.php');
	
	//instantiate variables
	$app = new \Slim\Slim();
	
	//connect to our datasource on port CONST_MEM_PORT
	$memoryConn = new Memcache();
	$memoryConn->pconnect(CONST_MEM_HOST, CONST_MEM_PORT);	
	
	
	//method for looking up all station codes
	$app->get('/station/', function () use ($memoryConn) {
	    $result = json_decode($memoryConn->get('stations'));
	    foreach($result as $station => $trein) {
	    	$return[] = $station;
	    }
	    
	    echo json_encode($return);
	});
	
	
	//method for looking up a specific station for trains
	$app->get('/station/:code/', function ($code) use ($memoryConn) {
	    $result = json_decode($memoryConn->get('stations'), true);
	    echo json_encode($result[$code]);
	});
	
	//method for looking up a specific station for trains
	$app->get('/station/:code/voorzieningen/', function ($code) use ($memoryConn) {
	    $bestand = json_decode(file_get_contents(CONST_FILE_VOORZIENINGEN), true);
	    echo json_encode($bestand[$code]);
	});
	
	
	//method for looking up all trains
	$app->get('/train/', function () use ($memoryConn) {
	    $result = json_decode($memoryConn->get('trains'), true);
	    echo json_encode($result);
	});
	
	
	//method for looking up all trains (with limit)
	$app->get('/limit/train/:aantal/', function ($aantal) use ($memoryConn) {
		$return = array();
	    $result = json_decode($memoryConn->get('stations'), true);
	    $teller = 0;
	    foreach($result as $trein => $var) {
	    
	    	if($teller < $aantal && !empty($var)) {
		    	$return[] = $var;
		    	$teller++;
	    	}
	    	
	    	
	    }
	    echo json_encode($return);
	});
	
	
	//method for looking up a specific train
	$app->get('/train/:code/', function ($code) use ($memoryConn) {
	    $result = json_decode($memoryConn->get('trains'), true);
	    echo json_encode($result[$code]);
	});
	
	$app->run();

?>