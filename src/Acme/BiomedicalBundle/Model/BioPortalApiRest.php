<?php

namespace Acme\BiomedicalBundle\Model;

class BioPortalApiRest {
	
	public static $api_key="7f45e84a-5930-4e1d-894e-a9c3fc71f7ec";
	
	/**
	 * 
	 * @param string $acronym acronyme de l'ontologie
	 * @param string $full_id URI du concept
	 */
	public function searchLinkBioPortal($acronym,$full_id){
		$url_2= "http://data.bioontology.org/ontologies/".$acronym."/classes/".urlencode($full_id)."?apikey="
				.self::$api_key;
		$raw_2 = file_get_contents($url_2);
		$json_2 = json_decode($raw_2,true);
		
		return $json_2['links']['ui'];
	}
}