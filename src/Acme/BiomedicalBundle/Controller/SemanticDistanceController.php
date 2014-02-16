<?php

namespace Acme\BiomedicalBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Acme\BiomedicalBundle\Entity\SemanticDistance;
use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Concept;
use Acme\BiomedicalBundle\Model\SemanticDistanceCollection;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\EventListener\ParamFetcherListener;
use Acme\BiomedicalBundle\Model\TermConcept;
use Acme\BiomedicalBundle\Model\BioPortalApiRest;
use Acme\BiomedicalBundle\Entity\Ontology;
use Acme\BiomedicalBundle\Model\ConstructGraph;
use Acme\BiomedicalBundle\Model\SemanticDistanceTwoConcepts;

class SemanticDistanceController extends FOSRestController{
	
	const SESSION_CONTEXT_DISTANCE = 'distance';
	
	public function indexAction(){
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',array('title'=>'Distance sémantique'));
	}
	
	public function indexConceptAction(){
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance_concept.html.twig',array('title'=>'Distance sémantique'));
	}
	
	/**
	 * Permet de rediriger l'utilisateur vers l'interface de BioPortal
	 * après le clique sur le bouton de redirection pour un terme
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function indexRedirectToBioPortalAction(){
		$ontology=$_GET['ontology_acronym'];
		$full_id=$_GET['full_id'];

		$api_rest=new BioPortalApiRest();
		$link=$api_rest->searchLinkBioPortal($ontology, $full_id);
		return $this->redirect($link);
	}
	
	/**
	 * Permet de faire l'affichage pour l'interface web pour le service 1
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function searchCalculateDistanceAction(){
		$concept_1=$_POST['concept_1'];
		$concept_2=$_POST['concept_2'];
		$ontology=$_POST['ontology'];
		
		$concept_1=$this->getSemSimId($concept_1, $ontology);
		$concept_2=$this->getSemSimId($concept_2, $ontology);
		
		if (is_null($concept_1)&&is_null($concept_2)){
			$concept_1=$_POST['concept_1_full_id'];
			$concept_2=$_POST['concept_2_full_id'];
			if (!preg_match("(http.+)", $concept_1)&&!preg_match("(http.+)", $concept_2)){
				throw new HttpException(403,"Vous devez mettre un champ de type url!");
				//go to the hell
			}
		}
		
		$results=$this->singleDistanceParam($concept_1, $concept_2);
		$constructGraph=new ConstructGraph($this->getDoctrine());
		$constructGraph->getListAncestorsConcepts($concept_1, $concept_2);
		
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',
				array('title'=>'Distance sémantique',
					'distances'=>$results,
					'concept_1'=>$_POST['concept_1'],
					'ontology'=>$ontology,
					'concept_2'=>$_POST['concept_2'],
					'graph'=>$constructGraph		
					));
	}
	
	/**
	 * Permet de faire l'affichage pour l'interface web pour le service 2
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function searchConceptsInDistanceAction(){
		$concept_1=$_POST['concept_1'];
		$dist_id=$_POST['dist_id'];
		$distance_max=$_POST['distance_max'];//distance choisi par l'utilisateur
		$recup=$this->getConceptIdByName($concept_1);
		
		$dist_string=split(":", $dist_id);//récupération par split du type de distance
		$dist_string=$dist_string[1];
		$distance_max=($distance_max*$this->getMaxDistance($dist_string))/100;
		//produit en croix en fonction du type de distance choisi par l'utilisateur

		$results=$this->multiDistances($dist_id, $distance_max, $recup);
		//TODO: construct a graph
		$constructGraph=new ConstructGraph($this->getDoctrine());
		$constructGraph->getAllNodesAroundConcept($results->semantic_distances);
		
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance_concept.html.twig',
				array('title'=>'Distance sémantique',
				'distances'=>$results,
				'graph'=>$constructGraph));
	}
	
	/**
	 * 
	 * @param string $dist_id le nom du type de distance
	 * @return mixed la valeur maximale pour le type de distance
	 * donné en paramètre
	 */
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
	
	/**
	 * 
	 * @param string $concept
	 * @return mixed l'identifiant du concept
	 */
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
	
	/**
	 * 
	 * @param string $concept
	 * @param integer $ontology
	 * @return mixed l'identifiant du concept en fonction de son nom et de l'ontologie
	 * dans laquelle il se trouve
	 */
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
	 *   output = "\Acme\BiomedicalBundle\Model\SemanticDistanceTwoConcepts",
	 *   statusCodes = {
	 *     200 = "Returned when successful",
	 *      404 = "Returned when concept_1 or concept_2 doesn't exist"
	 *   }
	 * )
	 *
	 * @Annotations\QueryParam(name="concept_1", requirements="(\d+|(http.+))", description="First id concept to compare.Or the URI of concept 1")
	 * @Annotations\QueryParam(name="concept_2", requirements="(\d+|(http.+))", description="Second id concept to compare.Or the URI of concept 2")
	 * @Annotations\QueryParam(name="dist_id", requirements="\d+", nullable=true, description="The distance id.
	 * Si dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker")
	 * @Annotations\QueryParam(name="include", requirements="(concept|all)", nullable=true, description="If you want to retreive the full_id of the concept
	 * put concept. If you want the full_id and the ontology of concept put all")
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
		$include=$paramFetcher->get('include');
		
		$distances=$this->singleDistanceParam($concept_1, $concept_2, $dist_id, $include);
		if ($distances==null){
			throw new HttpException(404,"Nous sommes désolé le calcul de distance n'est pas possible avec
					le concept: ".$concept_1." et le concept: ".$concept_2);
		}
		return $distances;
	}
	
	/**
	 * Fonction permettant de construir l'objet complexe SemanticDistanceTwoConcepts
	 * en fonction des trois paramètres ci-dessous et de le renvoyer
	 * @param integer $semantic_id
	 * @param integer $concept_1
	 * @param integer $concept_2
	 * @param string  $include
	 * @return \Acme\BiomedicalBundle\Model\SemanticDistanceTwoConcepts
	 */
	private function returnSemanticDistanceTwoConcepts(SemanticDistance $semantic_object,$concept_1,$concept_2,$include=null){
		$name_concept_1=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Term")
		->findOneBy(array('concept_id'=>$concept_1));
		$name_concept_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Term")
		->findOneBy(array('concept_id'=>$concept_2));
		$retour=new SemanticDistanceTwoConcepts($semantic_object, $name_concept_1, $name_concept_2);
		
		if (!is_null($include)){
			$concept_object_1=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
			->find($concept_1);
			$concept_object_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
			->find($concept_2);
			if (strcmp($include, "concept")==0){
				$retour=new SemanticDistanceTwoConcepts($semantic_object, $name_concept_1, $name_concept_2,$concept_object_1,$concept_object_2);
			}else{
				$ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")
				->find($concept_object_1->getOntologyId());//on choisi le premier mais 
				//on aurait pu prendre le deuxième concept (car provient de la même ontologie)
				$retour=new SemanticDistanceTwoConcepts($semantic_object, $name_concept_1, $name_concept_2,$concept_object_1,$concept_object_2,$ontology);
			}
		}
		return $retour;
	}
	
	/**
	 * Fonction permettant de récupérer les identifiants et objet pour pouvoir construir
	 * l'objet complexe SemanticDistanceTwoConcepts
	 * @param string $concept_1 l'identifiant ou l'URI du concept
	 * @param string $concept_2 l'identifiant ou l'URI du concept
	 * @param string $dist_id l'identifiant du type de distance
	 * @return object \Acme\BiomedicalBundle\Model\SemanticDistanceTwoConcepts
	 */
	private function singleDistanceParam($concept_1,$concept_2,$dist_id=null,$include=null){
		if ((preg_match("[\d+]", $concept_1)&&preg_match("[\d+]", $concept_2))||(is_int($concept_1)&&is_int($concept_2))){
			if (!is_null($dist_id)){
				$recup_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				if (is_null($recup_id)){
					$recup_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2,'concept_2'=>$concept_1));
				}
				$distances=$this->singleDistance($recup_id,$dist_id);
				
				$distances=$this->returnSemanticDistanceTwoConcepts($distances, $concept_1, $concept_2,$include);
				
				return $distances;//OK fonctionne
			}else{
				$distances_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				if (is_null($distances_2)){
					$distances_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2,'concept_2'=>$concept_1));
				}
				$distances_2=$this->returnSemanticDistanceTwoConcepts($distances_2, $concept_1, $concept_2,$include);
				return $distances_2;//OK fonctionne
			}
		}else{
			if (!preg_match("(http.+)", $concept_1)&&!preg_match("(http.+)", $concept_2)){
				throw new HttpException(403,"Vous devez mettre un champ de type url pour le
						concept_1 et le concept_2!");
				//go to the hell
			}
			$concept_1_id=$this->retreiveConceptId(urldecode($concept_1));
			$concept_2_id=$this->retreiveConceptId(urldecode($concept_2));
			if (!is_null($dist_id)){
				$recup_id_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1_id,'concept_2'=>$concept_2_id));
				if (is_null($recup_id_2)){
					$recup_id_2=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2_id,'concept_2'=>$concept_1_id));
				}
				$distances=$this->singleDistance($recup_id_2,$dist_id);
				$distances=$this->returnSemanticDistanceTwoConcepts($distances, $concept_1_id, $concept_2_id,$include);
				
				return $distances;//OK fonctionne
			}else{
				$distances_4=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1_id,'concept_2'=>$concept_2_id));
				if (is_null($distances_4)){
					$distances_4=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2_id,'concept_2'=>$concept_1_id));
				}
				$distances_4=$this->returnSemanticDistanceTwoConcepts($distances_4, $concept_1_id, $concept_2_id,$include);
				return $distances_4;//OK fonctionne
			}
		}
	}
	
	/**
	 * Permet de renvoyer l'identifiant du concept
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
	 * @Annotations\QueryParam(name="concept_1", requirements="(http.+)", nullable=true, description="URI of concept.Should be 
	 * mandatory if you want to search by URI ")
	 * 
	 * @Annotations\View(templateVar="distances")
	 *
	 *	@param integer     $concept      the concept id or the URI string if you want to pass an URI 
	 *  
	 * @return object
	 */
	public function getDistanceAction($concept, ParamFetcherInterface $paramFetcher){
		$dist_id=$paramFetcher->get('dist_id');
		$distance_max=$paramFetcher->get('distance_max');
		if (is_int($concept)||preg_match("[\d+]", $concept)){
			$results=$this->multiDistances($dist_id, $distance_max, $concept);
			if ($results==null) {
				throw new HttpException(404,"Il n'y a pas de concepts pour la distance: ".$distance_max."
						et l'identifiant de distance: ".$dist_id);
			}
			return $results;
		}else{
			if (preg_match("[URI]", $concept)){
				$concept_recup=$paramFetcher->get('concept_1');
				if (!preg_match("(http.+)", $concept_recup)){
					throw new HttpException(403,"Vous devez mettre un champ de type url pour le
						concept_1!");
					//go to the hell
				}
				$concept_1=urldecode($concept_recup);
				$concept_id=$this->retreiveConceptId($concept_1);
			
				$results=$this->multiDistances($dist_id, $distance_max, $concept);
				if ($results==null) {
					throw new HttpException(404,"Il n'y a pas de concepts pour la distance: ".$distance_max."
						et l'identifiant de distance: ".$dist_id);
				}
				return $results;
			}
		}
	}
	
	/**
	 * @param string $dist_id le type de la distance
	 * @param integer $distance_max la distance maximale choisie par l'utilisateur
	 * @param integer $concept l'identifiant du concept
	 * @return array of TermConcept
	 */
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
		$query=$em->createQueryBuilder()
		->select("sd.".$dist_id.", sd.concept_1, sd.concept_2")
		->from("AcmeBiomedicalBundle:SemanticDistance", "sd")
		->where("sd.".$dist_id."<= :distance")
		->andWhere("sd.concept_1 = :id")
		->setParameters(array("distance"=>$distance_max,"id"=>$concept))
		->orderBy("sd.".$dist_id,"DESC")
		->distinct(true)
		->getQuery();

		$recup = $query->getArrayResult();
		$dist_array=new SemanticDistanceCollection($dist_id,$distance_max);
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
			
			$term_concept=new TermConcept($nom, $ontology_name, $retreive_ontology);
			$dist_array->ajouterTermConcept($term_concept);
		}
		return $dist_array;
	}
}