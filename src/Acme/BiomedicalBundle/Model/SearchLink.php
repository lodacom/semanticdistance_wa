<?php

namespace Acme\BiomedicalBundle\Model;

class SearchLink extends Thread {

	public $array;
	
	public function __construct($array_subset){
		$this->array=$array_subset;
	}
	
	public function run(){
		
	}
}