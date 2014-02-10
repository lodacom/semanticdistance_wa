<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Concept;

class TermConcept {
	protected  $term;
	protected  $concept;
	
	public function __construct(Term $term,Concept $concept){
		$this->term=$term;
		$this->concept=$concept;
	}
	
	public function getTerm() {
		return $this->term;
	}
	public function getConcept() {
		return $this->concept;
	}
}