<?php

	/*
	
	DVS Daemon-omgeving
	Definiering van constanten
	
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

	//configuration file
	define("CONST_MEM_HOST", "localhost");
	define("CONST_MEM_PORT", 11211);
	
	define("CONST_FILE_VOORZIENINGEN", "/var/www/php-dvs/data/voorzieningen.json");
	define("CONST_NS_SITE", 'http://www.ns.nl/reizigers/reisinformatie/stationsvoorzieningen/?r220_r1_r1_r1:station=');
	
	define("CONST_PUBSUB_TCP", "tcp://pubsub.ndovloket.nl:7660");
	
?>