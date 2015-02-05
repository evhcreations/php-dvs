<?php

	/*
	
	DVS API-omgeving
	Daemon voor het verwerken van de te ontvangen DVS-berichten
	van een specifieke pubsub-server en poort.
	
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

	class Daemon {
		
		//define private attributes
		private $subscriber;
		private $station_store = array();
		private $trein_store = array();
		private $geheugen;
		private $temp_file = "data/temp.gz";
		
		//start DVS-daemon
		public function __construct() {
		
			//inform the CLI
			output("Start DVS-daemon", "init");
			output("Trying to connect pubsub-server ".CONST_PUBSUB_TCP, "init");
			
			//start new instance of ZMQContext
			$context = new ZMQContext();
			$this->subscriber = new ZMQSocket($context, ZMQ::SOCKET_SUB);
			
			//connect to pubsub server defined in constants.php
			$this->subscriber->connect(CONST_PUBSUB_TCP);
			$this->subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "/");
			output("Connected to pubsub-server ".CONST_PUBSUB_TCP, "init");
			
			//connect to memcache for communication between PHP-files
			output("Trying to connect memcache ".CONST_MEM_HOST.":".CONST_MEM_PORT, "init");
			$this->geheugen = new Memcache;
			$this->geheugen->pconnect(CONST_MEM_HOST, CONST_MEM_PORT);
			output("Connected to memcache ".CONST_MEM_HOST.":".CONST_MEM_PORT, "init");
			
			output("Start processing DVS-messages", "init");
			//ready for processing actual DVS-messages
			$this->checkDVS();
		}
		
		//method for processing stations
		public function processStations() {
			//write to memcache all stationinformation
			$this->geheugen->set('stations', json_encode($this->station_store), MEMCACHE_COMPRESSED);
		}
		
		//method for processing trains
		public function processTrains() {
			//write to memcache all traininformation
			$this->geheugen->set('trains', json_encode($this->trein_store), MEMCACHE_COMPRESSED);
		}
		
		//method for showing statistics
		public function showStats() {
			//show statistics about the total items in station_store and trein_store
			output("Processed ".count($this->station_store)." stations, ".count($this->trein_store)." trains", "statistics");
		}
		
		
		//method for processing incoming DVS-messages
		public function checkDVS() {
			
			$timer = 0;
			while (true) {
			
				$timer++;
				
				//if this method has processed more than 100 DVS-messages, show statistics
				if($timer > 100) {
					$timer = 0;
					$this->showStats();
				}
			
				//process stations and trains
				$this->processStations();
				$this->processTrains();
				
				//get DVS-content from ZeroMQ
			    $address = $this->subscriber->recv();
			    $contents = $this->subscriber->recv();
			    
			    //write the DVS-content to a temp-file
				file_put_contents($this->temp_file, $contents);				
				$out_file_name = str_replace('.gz', '', $this->temp_file);				
				$file = gzopen($this->temp_file, 'rb');
				$out_file = fopen($out_file_name, 'wb');
				
				//processing content of the DVS-message
				while(!gzeof($file)) {
				    $content = gzread($file, 100096);
				}
				
				//convert XML-message to a JSON-message
				$arr = xmlstr_to_array($content);
				
				
				//create new Trein-object
				$Trein = new Trein();
				
				//set all attributes of Trein
				$Trein->rit_id = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitId"];
				$Trein->status = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinStatus"];
				$Trein->rit_station_code = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitStation"]["ns1:StationCode"];
				$Trein->treinnr = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinNummer"];
				$Trein->rit_datum = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitDatum"];
				$Trein->rit_station = array(
					"StationCode" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitStation"]["ns1:StationCode"],
					"Type" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitStation"]["ns1:Type"],
					"KorteNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitStation"]["ns1:KorteNaam"],
					"MiddelNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitStation"]["ns1:MiddelNaam"],
					"LangeNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:RitStation"]["ns1:LangeNaam"]
				);
				
				$Trein->rit_timestamp = $arr["ns1:ReisInformatieProductDVS"]["@overig"]["TimeStamp"];
				$Trein->soort = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinSoort"];
				$Trein->vervoerder = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:Vervoerder"];
				$Trein->vertrekrichting = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:VertrekRichting"];
				$Trein->vertrek = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:VertrekTijd"][0];
				$Trein->vertrek_actueel = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:VertrekTijd"][1];
				$Trein->vertrekspoor = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinVertrekSpoor"][0]["ns1:SpoorNummer"];
				$Trein->vertrekspoor_actueel = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinVertrekSpoor"][1]["ns1:SpoorNummer"];
				$Trein->eindbestemming = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][0] = array(
					
					"StationCode" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][0]["ns1:StationCode"],
					"Type" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][0]["ns1:Type"],
					"KorteNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][0]["ns1:KorteNaam"],
					"MiddelNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][0]["ns1:MiddelNaam"],
					"LangeNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][0]["ns1:LangeNaam"]
				
				);
				$Trein->eindbestemming_actueel = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][1] = array(
					
					"StationCode" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][1]["ns1:StationCode"],
					"Type" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][1]["ns1:Type"],
					"KorteNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][1]["ns1:KorteNaam"],
					"MiddelNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][1]["ns1:MiddelNaam"],
					"LangeNaam" => $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinEindBestemming"][1]["ns1:LangeNaam"]
				
				);
				
				$Trein->reserveren = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:Reserveren"];
				$Trein->toeslag = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:Toeslag"];
				$Trein->niet_instappen = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:NietInstappen"];
				$Trein->rangeerbeweging = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:RangeerBeweging"];
				$Trein->speciaal_kaartje = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:SpeciaalKaartje"];
				$Trein->achterblijven = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:AchterBlijvenAchtersteTreinDeel"];
				$Trein->exacte_vertrekvertraging = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:ExacteVertrekVertraging"];
				$Trein->gedempte_vertrekvertraging = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:GedempteVertrekVertraging"];
				
				if(isset($arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:VerkorteRoute"][0]["ns1:Station"])) {
					$Trein->verkorte_route = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:VerkorteRoute"][0]["ns1:Station"];
					$Trein->verkorte_route_actueel = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:VerkorteRoute"][1]["ns1:Station"];
				}
				
				if(isset($arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:ReisTip"])) {
					$Trein->reistips = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:ReisTip"];
				}
				
				$Trein->TreinVleugel = $arr["ns1:ReisInformatieProductDVS"]["ns1:DynamischeVertrekStaat"]["ns1:Trein"]["ns1:TreinVleugel"];
				
				
				//if trainstatus is equal to 5 (if train is leaved at the station)
				if($Trein->status == "5" or empty($Trein->status) or is_array($Trein->status)) {
					//unset station_store -> station_code
					if(!empty($this->station_store[$Trein->rit_station_code][$Trein->treinnr])) {
						unset($this->station_store[$Trein->rit_station_code][$Trein->treinnr]);
					}
					
					//unset trein_store -> train number
					if(!empty($this->trein_store[$Trein->treinnr][$Trein->rit_station_code])) {
						unset($this->trein_store[$Trein->treinnr][$Trein->rit_station_code]);
					}
				} else {
					//if the status is planned or driving
					
					//create new train if not exists
					if(empty($this->trein_store[$Trein->treinnr])) {
						$this->trein_store[$Trein->treinnr] = array();
					}
					
					//create new station if not exists
					if(empty($this->station_store[$Trein->rit_station_code])) {
						$this->station_store[$Trein->rit_station_code] = array();
					}
					
					//Opmerking: nog aanpassen aan timestamp!
					if(!empty($this->station_store[$Trein->rit_station_code])) {
						//if nieuwer bericht (timestamp)
						$this->station_store[$Trein->rit_station_code][$Trein->treinnr] = $Trein;
						//else warning
					} else {
						$this->station_store[$Trein->rit_station_code][$Trein->treinnr] = $Trein;
					}
					
					if(!empty($this->trein_store[$Trein->treinnr])) {
						//if nieuwe bericht (timestamp)
						$this->trein_store[$Trein->treinnr][$Trein->rit_station_code] = $Trein;
						//else warning
					} else {
						$this->trein_store[$Trein->treinnr][$Trein->rit_station_code] = $Trein;
					}
				}
				
				//close all IO-operations
				fclose($out_file);
				gzclose($file);
				
				output("Message for train #".$Trein->treinnr." on StationCode ".$Trein->rit_station_code, "message");
			}
		}
	}

?>
