<?php

	/*
	
	DVS API-omgeving
	Scraper voor het ophalen van voorzieningen per DVS-station
	
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
	
	require("../Helpers/constants.php");
	
	//connect to our datasource on specific port
	$memoryConn = new Memcache();
	$memoryConn->pconnect(CONST_MEM_HOST, CONST_MEM_PORT);	
	
	$result = json_decode($memoryConn->get('stations'));
	foreach($result as $code => $trein) {
		//get specific station info from the official NS-site	
		$stations_info = file_get_contents(CONST_NS_SITE.$code); 
	
		//instantiate new DOMDocument
	    $dom = new DOMDocument;
		$dom->loadHTML($stations_info);
		
		//search for the div with id 'voorzieningen'
		$main2 = $dom->getElementById('voorzieningen');
		
		$content = '';
		foreach($main2->childNodes as $node) {
		    $content .= $dom->saveXML($node, LIBXML_NOEMPTYTAG);
		}
		
		//search for UL
		$dom = new DOMDocument;
		$dom->loadHTML($content);
		$items = $dom->getElementsByTagName('ul');
		
		//each UL
		foreach($items as $node) {
			//each LI in the list
			foreach($node->getElementsByTagName('li') as $voorz) {
				//add voorziening
				$voorzieningen[$code][] = trim($voorz->nodeValue);
			}
		}
	}
	
	//write all voorzieningen to JSON-file
	file_put_contents(CONST_FILE_VOORZIENINGEN, json_encode($voorzieningen));
	
?>