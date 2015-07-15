<?php

require('lib/simple_html_dom.php');

class Scarpi{
	private $unescape = false; 
	
	private function set_curlopts(&$curl){
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	}
	
	public function scrape($url, $targets, $unescape){
		$this->unescape = strtolower(trim($unescape)) == 'true';
		
		$curl = curl_init($url);
		
		$this->set_curlopts($curl);
		
		$html = str_get_html(curl_exec($curl));
		
		if(curl_error($curl))
			die(curl_error($curl));
		
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		curl_close($curl);
		
		// TREAT DATA HERE AND RETURN IT:
		return array("data" => $this->parse($html, $targets), "status" => $status);
	}
	
	private function parse(&$html, &$targets){
		$res_container = array();
		
		$i = 0;
		foreach($targets as $target){
			// Grab all options:
			$options = explode("|", $target);
		
			// Pick what kind of data we're scraping:
			$target_mode = "html"; // Default
			$target_index = null; // Default
			$target_maxlength = null; // Default
			
			// In case the request asks for custom data:
			$options_count = count($options);
			if($options_count > 1)
				if(!is_numeric(trim($options[1]))){
					$target_mode = strtolower(trim($options[1]));
					
					if($options_count > 2){
						$target_index = intval($options[2]);
						$target_maxlength = 1;
					}
					
					if($options_count > 3) 	// Length is being used with all options
						$target_maxlength = intval($options[3]);
						
				}else{
					$target_index = intval($options[1]);
					$target_maxlength = 1;
					
					if($options_count > 2) // Length is being used with the index
						$target_maxlength = intval($options[2]);
				}
				
			// Scrape:
			$res_index = "target".$i;
			$res_container[$res_index] = array();
			
			$forctr = 0;
			$elements = $html->find($options[0]);
			$elements_count = count($elements);
			// Check for indices invalid size:
			if($target_index > $elements_count) $target_index = $elements_count;
			if($target_maxlength > $elements_count) $target_maxlength = $elements_count - $target_index;
			
			// Iterate through the matches:
			$added_count = 0;
			
			foreach($elements as $el){
				if($target_index != null && $forctr <= $target_index){ $forctr++; continue;}
				
				$what_to_push = null;
				switch($target_mode){
					case "html": $what_to_push = ($this->unescape) ? stripslashes($el->outertext) : htmlspecialchars($el->outertext); break;
					case "text": $what_to_push = $el->plaintext; break;
					case "href": $what_to_push = $el->href; break;
					case "src" : $what_to_push = $el->src; break;
					case "tag" : $what_to_push = $el->tag; break;
					case "inner" : $what_to_push = $el->innertext; break;
					case "_all_" : $what_to_push = $el->getAllAttributes(); break;
					default: $what_to_push = $el->getAttribute($target_mode); break;
				}
				
				if(!empty($what_to_push)){
					array_push($res_container[$res_index], $what_to_push);
					$added_count++;
				}
				
				if($target_maxlength!=null && $added_count>=$target_maxlength) break;
				
				$forctr++;
			}
			
			$i++;
		}
		
		return $res_container;
	}	
}
	if(isset($_POST["data"])){
		$result = array();
		foreach($_POST["data"] as $site)
			if(isset($site["url"]) && isset($site["targets"]))
				array_push($result, (new Scarpi())->scrape($site["url"], $site["targets"], $site["unescape"]));
			
		echo json_encode($result);
	}
	
?>