<?php

namespace Acme\BiomedicalBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Acme\BiomedicalBundle\Entity\SemanticDistance;
use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Concept;

class SemanticDistanceController extends FOSRestController{
	
	const SESSION_CONTEXT_DISTANCE = 'distance';
	
	public function indexAction(){
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',array('title'=>'Distance sémantique'));
	}
	
	/**
	 * Calculate the distance between two concepts
	 *
	 * @ApiDoc(
	 *   resource = true,
	 *   statusCodes = {
	 *     200 = "Returned when successful"
	 *   }
	 * )
	 *
	 * @Annotations\QueryParam(name="concept_1", requirements="\d+", nullable=false, description="First concept to compare.")
	 * @Annotations\QueryParam(name="concept_2", requirements="\d+", nullable=false, description="Second concept to compare.")
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", nullable=true, description="L'identifiant de la distance.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 *
	 * @Annotations\View()
	 *
	 *
	 * @return array
	 */
	public function calculateDistanceAction(Request $request, ParamFetcherInterface $paramFetcher){
		$concept_1=$paramFetcher->get('concept_1');
		$concept_2=$paramFetcher->get('concept_2');
		$dist_id=$paramFetcher->get('dist_id');
		
		if (is_integer($concept_1)&&is_integer($concept_2)){
			if (isset($dist_id)){
				$recup_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				
				return $this->singleDistance($recup_id);
			}else{
				$distances=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				return $distances;
			}	
		}else{
			$concept_1_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
			->findOneBy(array('full_id'=>urldecode($concept_1)));
			$concept_2_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
			->findOneBy(array('full_id'=>urldecode($concept_2)));
			
			if (isset($dist_id)){
				$recup_id_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1_id->getId(),'concept_2'=>$concept_2_id->getId()));
				
				return $this->singleDistance($recup_id_2);
			}else{
				$distances=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1_id->getId(),'concept_2'=>$concept_2_id->getId()));
				return $distances;
			}	
		}
	}
	
	private function singleDistance(SemanticDistance $recup_id){
		$distances=new SemanticDistance();
		$distances->setConcept1($recup_id->getConcept1());
		$distances->setConcept2($recup_id->getConcept2());
		
		switch ($dist_id){
			case 1: $distances->setSimLin($recup_id->getSimLin());
			break;
			case 2:$distances->setSimWuPalmer($recup_id->getSimWuPalmer());
			break;
			case 3:$distances->setSimResnik($recup_id->getSimResnik());
			break;
			case 4:$distances->setSimSchlicker($recup_id->getSimSchlicker());
			break;
		}
		return $distances;
	}
	
	/**
	 * Retreive concepts 
	 *
	 * @ApiDoc(
	 *   resource = true,
	 *   statusCodes = {
	 *     200 = "Returned when successful"
	 *   }
	 * )
	 *
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", nullable=true, description="L'identifiant de la distance.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 *	@Annotations\QueryParam(name="distance_max", requirements="\d+", nullable=false, description="The maximum distance to search.")
	 * @Annotations\View()
	 *
	 *
	 * @return array
	 */
	public function getDistanceAction($concept, ParamFetcherInterface $paramFetcher){
		$dist_id=$paramFetcher->get('dist_id');
		$distance_max=$paramFetcher->get('distance_max');
		if (is_integer($concept)){
			
		}else{
			$concept=urldecode($concept);
		}
	}
}