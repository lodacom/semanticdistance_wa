<?php

namespace Acme\BiomedicalBundle\Model;

class BioPortalApiRest {
	
	public static $api_key="7f45e84a-5930-4e1d-894e-a9c3fc71f7ec";
	public $links;
	
	public function searchLinkBioPortal($acronym,$term){
		//\Doctrine\Common\Util\Debug::dump($acronym);
		$url_2= "http://data.bioontology.org/search?apikey="
				.self::$api_key."&q=".urlencode($term)."&ontologies=".$acronym;
		$raw_2 = file_get_contents($url_2);
		$json_2 = json_decode($raw_2,true);
		
		foreach ($json_2['collection'] as $key => $value) {
			$this->links=$value['links']['ui'];
			break;
		}
		return $this->links;
	}
}