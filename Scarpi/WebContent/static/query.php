<?php
	require('lib/scarpi/scarpi.php');

	if(isset($_POST["data"])){
		$result = array();
		foreach($_POST["data"] as $site){
			if(!isset($site["url"])) continue;
				
			$targets = isset($site["targets"]) ? $site["targets"] : Scarpi::guess();
			$isrender = isset($site["render"]) ? $site["render"] : "false";
			
			array_push($result, (new Scarpi())->scrape($site["url"], $targets, $isrender));
		}
			
		echo json_encode($result);
	}
	
?>