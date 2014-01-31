<?php

namespace Acme\BiomedicalBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SemanticDistanceController extends Controller{
	
	public function indexAction(){
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',array('title'=>'Distance sémantique'));
	}
}