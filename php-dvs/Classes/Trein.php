<?php	

	/*
	
	DVS Daemon-omgeving
	Definitie van een Trein-object
	
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


	class Trein {
		
		//attributen
		public $rit_id;
		public $rit_datum;
		public $rit_station;
		public $rit_timestamp;
		
		public $treinnr;
		public $soort;
		public $soort_code;
		public $vervoerder;
		
		public $treinnaam;
		
		public $status;
		
		public $vertrek;
		public $vertrek_actueel;
		public $vertraging;
		public $vertraging_gedempt;
		public $vertrekspoor;
		public $vertrekspoor_actueel;
		public $vertrekrichting;
		
		public $exacte_vertrekvertraging;
		public $gedempte_vertrekvertraging;
		
		public $eindbestemming;
		public $eindbestemming_actueel;
		
		//diversen
		public $reserveren;
		public $toeslag;
		public $niet_instappen;
		public $rangeerbeweging;
		public $speciaal_kaartje;
		public $achterblijven;
		
		//wijzigingsberichten
		public $wijzigingen;
		public $reistips;
		public $instaptips; //objecten van InstapTip()
		public $overstaptips; //objecten van OverstapTip()
		
		//verkorte route
		public $verkorte_route;
		public $verkorte_route_actueel;
		
		public $TreinVleugel;
		
	}


?>