<?php	

	/*
	
	DVS Daemon-omgeving
	Handige functies voor de verwerking van DVS-berichten
	
	De copyright ligt bij de auteurs van onderstaande functies/methoden
	
	*/


	function xmlstr_to_array($xmlstr) {
		$doc = new DOMDocument();
		$doc->loadXML($xmlstr);
		return domnode_to_array($doc->documentElement);
	}
	
	function domnode_to_array($node) {
		$output = array();
		
		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$output = trim($node->textContent);
			break;
			case XML_ELEMENT_NODE:
				for ($i=0, $m=$node->childNodes->length; $i<$m; $i++) { 
					$child = $node->childNodes->item($i);
					$v = domnode_to_array($child);
					if(isset($child->tagName)) {
						$t = $child->tagName;
						if(!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					}
					elseif($v) {
						$output = (string) $v;
					}
				}
				
				if(is_array($output)) {
					if($node->attributes->length) {
						$a = array();
						foreach($node->attributes as $attrName => $attrNode) {
							$a[$attrName] = (string) $attrNode->value;
						}
						$output['@overig'] = $a;
					}
					foreach ($output as $t => $v) {
						if(is_array($v) && count($v)==1 && $t!='@overig') {
							$output[$t] = $v[0];
						}
					}
				}
			break;
		}
		return $output;
	}
	
	function output($bericht, $onderwerp) {
		echo date("d-m-Y H:i:s")." [".$onderwerp."] ".$bericht."\r\n";
	}

?>