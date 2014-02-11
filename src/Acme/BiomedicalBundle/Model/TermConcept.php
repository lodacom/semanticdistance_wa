<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Term;

class TermConcept {
	protected  $term;
	protected  $link;
	
	public function __construct(Term $term,$link){
		$this->term=$term;
		$this->link=$link;
	}
	
	public function getTerm() {
		return $this->term;
	}
	public function getLink() {
		return $this->link;
	}
	
}