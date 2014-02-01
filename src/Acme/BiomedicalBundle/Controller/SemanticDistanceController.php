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
use Acme\BiomedicalBundle\Model\SemanticDistanceCollection;

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
	 * @Annotations\QueryParam(name="concept_1", requirements="(\d+|\w+)", description="First id concept to compare.Or the URI of concept 1")
	 * @Annotations\QueryParam(name="concept_2", requirements="(\d+|\w+)", description="Second id concept to compare.Or the URI of concept 2")
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", nullable=true, description="L'identifiant de la distance.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 *
	 * @Annotations\View()
	 *
	 *
	 * @return array
	 */
	public function calculateDistanceAction(ParamFetcherInterface $paramFetcher){
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
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", description="L'identifiant de la distance.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 *	@Annotations\QueryParam(name="distance_max", requirements="\d+", description="The maximum distance to search.")
	 * @Annotations\View()
	 *
	 *	@param integer     $concept      the concept id or the URI of concept    
	 * @return array
	 */
	public function getDistanceAction($concept, ParamFetcherInterface $paramFetcher){
		$dist_id=$paramFetcher->get('dist_id');
		$distance_max=$paramFetcher->get('distance_max');
		if (is_integer($concept)){
			
			return $this->multiDistances($dist_id, $distance_max, $concept);
		}else{
			$concept_1=urldecode($concept);
			$concept_1_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
			->findOneBy(array('full_id'=>urldecode($concept_1)));
			$concept_id=$concept_1_id->getId();
			
			return $this->multiDistances($dist_id, $distance_max, $concept_id);
		}
	}
	
	private function multiDistances($dist_id,$distance_max,$concept){
		switch ($dist_id){
			case 1:$dist_id="sim_lin";
			break;
			case 2:$dist_id="sim_wu_palmer";
			break;
			case 3:$dist_id="sim_resnik";
			break;
			case 4:$dist_id="sim_schlicker";
			break;
		}
			
		$em=$this->getDoctrine()->getEntityManager();
		$query=$em->createQuery("SELECT sd.".$dist_id.", sd.concept_1, sd.concept_2
					FROM AcmeBiomedicalBundle:SemanticDistance sd
					WHERE sd.".$dist_id."<= :distance
					AND sd.concept_1 = :id
					ORDER BY sd.".$dist_id." ASC")
							->setParameters(array("distance"=>$distance_max,"id"=>$concept));
		$recup = $query->getArrayResult();
		$dist_array=array();
		foreach ( $recup as $data ) {
			$semantic_dist=new SemanticDistance();
			$semantic_dist->setConcept1($data ['concept_1']);
			$semantic_dist->setConcept1($data ['concept_2']);
			$semantic_dist->setConcept1($data [$dist_id]);
			array_push($dist_array, $semantic_dist);
		}
		return new SemanticDistanceCollection($dist_array);
	}
}