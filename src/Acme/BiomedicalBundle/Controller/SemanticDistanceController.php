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
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\EventListener\ParamFetcherListener;
use Acme\BiomedicalBundle\Model\TermConcept;
use Acme\BiomedicalBundle\Model\BioPortalApiRest;
use Acme\BiomedicalBundle\Entity\Ontology;
use Acme\BiomedicalBundle\Model\SearchLink;

class SemanticDistanceController extends FOSRestController{
	
	const SESSION_CONTEXT_DISTANCE = 'distance';
	
	public function indexAction(){
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',array('title'=>'Distance sémantique'));
	}
	
	public function indexConceptAction(){
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance_concept.html.twig',array('title'=>'Distance sémantique'));
	}
	
	/**
	 * Permet de faire l'affichage pour l'interface web
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function searchCalculateDistanceAction(){
		$concept_1=$_POST['concept_1'];
		$concept_2=$_POST['concept_2'];
		$ontology=$_POST['ontology'];
		
		$concept_1=$this->getSemSimId($concept_1, $ontology);
		$concept_2=$this->getSemSimId($concept_2, $ontology);
		
		$results=$this->singleDistanceParam($concept_1, $concept_2);
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',
				array('title'=>'Distance sémantique',
					'distances'=>$results,
					'concept_1'=>$_POST['concept_1'],
					'ontology'=>$ontology,
					'concept_2'=>$_POST['concept_2']));
	}
	
	public function searchConceptsInDistanceAction(){
		$concept_1=$_POST['concept_1'];
		$dist_id=$_POST['dist_id'];
		$distance_max=$_POST['distance_max'];//distance choisi par l'utilisateur
		$recup=$this->getConceptIdByName($concept_1);
		
		$dist_string=split(":", $dist_id)[1];//récupération par split du type de distance
		$distance_max=($distance_max*$this->getMaxDistance($dist_string))/100;
		//produit en croix en fonction du type de distance choisi par l'utilisateur

		$results=$this->multiDistances($dist_id, $distance_max, $recup);
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance_concept.html.twig',
				array('title'=>'Distance sémantique',
				'distances'=>$results));
	}
	
	private function getMaxDistance($dist_id){
		$quer = $this->getDoctrine()->getEntityManager();
		$query=$quer->createQuery("SELECT MAX(sd.".$dist_id.") AS distance_max
				FROM AcmeBiomedicalBundle:SemanticDistance sd");
		$recup = $query->getArrayResult();
		$max=array();
		foreach ( $recup as $data ) {
			array_push($max, $data['distance_max']);
		}
		
		return array_pop($max);
	}
	
	private function getConceptIdByName($concept){
		$em=$this->getDoctrine()->getEntityManager();
		$query=$em->createQuery("SELECT t.concept_id
				FROM AcmeBiomedicalBundle:Term t
				WHERE t.name= :name")
						->setParameter("name", $concept);
		$recup = $query->getArrayResult();
		$id=array();
		foreach ( $recup as $data ) {
			array_push($id, $data['concept_id']);
		}
		
		return array_pop($id);
	}
	
	private function getSemSimId($concept,$ontology){
		$quer = $this->getDoctrine()->getEntityManager();
		$query=$quer->createQuery("SELECT c.id
				FROM AcmeBiomedicalBundle:Ontology o,AcmeBiomedicalBundle:Term t,AcmeBiomedicalBundle:Concept c
				WHERE t.name=?1
				AND t.concept_id=c.id
				AND c.ontology_id=o.id
				AND o.name=?2 ")
						->setParameters(array(1=>$concept,2=>$ontology));
		$recup = $query->getArrayResult();
		$concepts=array();
		foreach ( $recup as $data ) {
			$concepts[] = $data ['id'];
		}
		return array_pop($concepts);
	}
	
	/**
	 * Calculate the distance between two concepts
	 *
	 * @ApiDoc(
	 *   resource = true,
	 *   output = "Acme\BiomedicalBundle\Entity\SemanticDistance",
	 *   statusCodes = {
	 *     200 = "Returned when successful",
	 *      404 = "Returned when concept_1 or concept_2 doesn't exist"
	 *   }
	 * )
	 *
	 * @Annotations\QueryParam(name="concept_1", requirements="(\d+|.+)", description="First id concept to compare.Or the URI of concept 1")
	 * @Annotations\QueryParam(name="concept_2", requirements="(\d+|.+)", description="Second id concept to compare.Or the URI of concept 2")
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", nullable=true, description="L'identifiant de la distance.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 *
	 * @Annotations\View(templateVar="distances")
	 *
	 *
	 * @return object
	 * 
	 * @throws NotFoundHttpException when concept_1 or concept_2 doesn't exist
	 */
	public function calculateDistanceAction(ParamFetcherInterface $paramFetcher){
		$concept_1=$paramFetcher->get('concept_1');
		$concept_2=$paramFetcher->get('concept_2');
		$dist_id=$paramFetcher->get('dist_id');
		$distances=$this->singleDistanceParam($concept_1, $concept_2, $dist_id);
		return $distances;
	}
	
	/**
	 * 
	 * @param string $concept_1
	 * @param string $concept_2
	 * @param string $dist_id
	 * @return object \Acme\BiomedicalBundle\Entity\SemanticDistance
	 */
	private function singleDistanceParam($concept_1,$concept_2,$dist_id=null){
		if ((preg_match("[\d+]", $concept_1)&&preg_match("[\d+]", $concept_2))||(is_int($concept_1)&&is_int($concept_2))){
			if (isset($dist_id)){
				$recup_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				$distances=$this->singleDistance($recup_id,$dist_id);
				return $distances;//OK fonctionne
			}else{
				$distances_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				if (!isset($distances_2)){
					$distances_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2,'concept_2'=>$concept_1));
				}
				return $distances_2;//OK fonctionne
			}
		}else{
			$concept_1_id=$this->retreiveConceptId(urldecode($concept_1));
			$concept_2_id=$this->retreiveConceptId(urldecode($concept_2));
			if (isset($dist_id)){
				$recup_id_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1_id,'concept_2'=>$concept_2_id));
				if (!isset($recup_id_2)){
					$recup_id_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2_id,'concept_2'=>$concept_1_id));
				}
				$distances=$this->singleDistance($recup_id_2,$dist_id);
				
				return $distances;//OK fonctionne
			}else{
				$distances_4=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1_id,'concept_2'=>$concept_2_id));
				if (!isset($distances_4)){
					$distances_4=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2_id,'concept_2'=>$concept_1_id));
				}
				
				return $distances_4;//OK fonctionne
			}
		}
	}
	
	/**
	 * 
	 * @param string $concept l'URI du concept
	 * @return mixed l'identifiant du concept
	 */
	private function retreiveConceptId($concept){
		$em=$this->getDoctrine()->getEntityManager();
		$query=$em->createQuery("SELECT c.id
				FROM AcmeBiomedicalBundle:Concept c
				WHERE c.full_id= :id")
				->setParameter("id", $concept);
		$recup = $query->getArrayResult();
		$id=array();
		foreach ( $recup as $data ) {
			array_push($id, $data['id']);
		}

		return array_pop($id);
	}
	
	/**
	 * 
	 * @param SemanticDistance $recup_id
	 * @return \Acme\BiomedicalBundle\Entity\SemanticDistance
	 */
	private function singleDistance(SemanticDistance $recup_id,$dist_id){
		$distances=new SemanticDistance();
		$distances->setConcept1($recup_id->getConcept1());
		$distances->setConcept2($recup_id->getConcept2());
		$distances->setId($recup_id->getId());
		
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
	 *   output = "Acme\BiomedicalBundle\Model\SemanticDistanceCollection",
	 *   statusCodes = {
	 *     200 = "Returned when successful",
	 *     404 = "Returned when concept doesn't exist"
	 *   }
	 * )
	 *
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", description="L'identifiant de la distance.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 *	@Annotations\QueryParam(name="distance_max", requirements="(\d+|\d+.\d+)", description="The maximum distance to search.")
	 * @Annotations\QueryParam(name="concept_1", requirements=".+", nullable=true, description="URI of concept.Should be 
	 * mandatory if you want to serach by URI ")
	 * 
	 * @Annotations\View(templateVar="distances")
	 *
	 *	@param integer     $concept      the concept id or if you want the URI string if you want to pass an URI 
	 *  
	 * @return object
	 */
	public function getDistanceAction($concept, ParamFetcherInterface $paramFetcher){
		$dist_id=$paramFetcher->get('dist_id');
		$distance_max=$paramFetcher->get('distance_max');
		if (is_int($concept)||preg_match("[\d+]", $concept)){
			
			return $this->multiDistances($dist_id, $distance_max, $concept);
		}else{
			if (preg_match("[URI]", $concept)){
				$concept_recup=$paramFetcher->get('concept_1');
				$concept_1=urldecode($concept_recup);
				$concept_id=$this->retreiveConceptId($concept_1);
			
				return $this->multiDistances($dist_id, $distance_max, $concept_id);
			}
		}
	}
	
	private function multiDistances($dist_id,$distance_max,$concept){
		$tab=split(":", $dist_id);
		switch ($tab[0]){
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
					ORDER BY sd.".$dist_id." DESC")
							->setMaxResults(10)
							->setParameters(array("distance"=>$distance_max,"id"=>$concept));
		$recup = $query->getArrayResult();
		$dist_array=new SemanticDistanceCollection($dist_id,$distance_max);
		/*for ($i=0;$i<count($dist_array);$i+10){
			$length=$i+10;
			$tab_to_thread=array_slice($dist_array, $i, $length);
			$thread=new SearchLink($tab_to_thread,$this->getDoctrine());
			$thread->start();
		}*/
		foreach ( $recup as $data ) {
			$concept_1=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Term")
			->findOneBy(array('concept_id'=>$data ['concept_1']));
			$dist_array->concept_1=$concept_1;
			$retreive_ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
			->find($data ['concept_2']);
			$ontology_name=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")
			->find($retreive_ontology->getOntologyId());
			$nom=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Term")
			->findOneBy(array('concept_id'=>$data ['concept_2']));
			
			$api_rest=new BioPortalApiRest();
			$link=$api_rest->searchLinkBioPortal($ontology_name->getVirtualOntologyId(), $nom->getName());
			
			$term_concept=new TermConcept($nom, $link);
			$dist_array->ajouterTermConcept($term_concept);
		}
		return $dist_array;
	}
}