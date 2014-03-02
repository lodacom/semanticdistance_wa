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
use Symfony\Component\HttpFoundation\Session\Session;

class SemanticDistanceController extends FOSRestController{
	
	const SESSION_CONTEXT_DISTANCE = 'distance';
	const MAX_CONCEPTS = 50;
	
	public function indexAction(){
		$this->changeLanguage();
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance.html.twig',array('title'=>'Distance sémantique'));
	}
	
	public function indexConceptAction(){
		$this->changeLanguage();
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance_concept.html.twig',array('title'=>'Distance sémantique'));
	}
	
	/**
	 * Méthode permettant à l'utilisateur et le developpeur de changer de langue
	 * @param string $langue Permet de passer la langue en param (utilisation avec API)
	 * @param boolean $api_ask Permet de savoir si l'appel vient de l'API ou non
	 */
	public function changeLanguage($langue=null,$api_ask=false){
		$tab_langue=array("fr","en");
		if (in_array($langue, $tab_langue)&&$api_ask){
			$request = $this->getRequest();
			$request->setDefaultLocale($langue);
			$request->setLocale($langue);
			return;
		}
		$container_langue=null;
		if (!is_null($this->container)){
			$container_langue = $this->container->get('request')->get("_locale");
		}
		if (!is_null($container_langue) && in_array($container_langue, $tab_langue)){
			//on prend en compte en priorité l'action de l'utilisateur (changement de langue)
			$request = $this->getRequest();
			$request->setDefaultLocale($container_langue);
			$request->setLocale($container_langue);
			$session = new Session();
			$session->start();
			$session->set('_locale', $container_langue);
		}else{
			$session = new Session();
			$session->start();
			if (!is_null($session->get('_locale'))){
				$langue=$session->get('_locale');
			}
			if (!is_null($langue) && in_array($langue, $tab_langue)){
				//si aucune action on regarde s'il y en a déjà une qui a été effectuée à travers la session
				$request = $this->getRequest();
				$request->setDefaultLocale($langue);
				$request->setLocale($langue);
			}
		}
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
		$this->changeLanguage();
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
			$concept_1=$this->retreiveConceptId($concept_1);
			$concept_2=$this->retreiveConceptId($concept_2);
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
	public function searchConceptsInDistanceAction($page){
		$this->changeLanguage();
		$concept_1=null;
		if (isset($_POST['concept_1'])){
			$concept_1=$_POST['concept_1'];
			$dist_id=$_POST['dist_id'];
			$distance_max=$_POST['distance_max'];//distance choisi par l'utilisateur
			$ontology=$_POST['ontology'];
		}
		
		$session = new Session();
		if(version_compare(phpversion(),'5.4.0','<')){
			//version pour le serveur tubo.lirmm.fr (version php 5.3.10)
			if (isset($_SESSION['concept_1'])&&is_null($concept_1)){
				$concept_1=$_SESSION['concept_1'];//l'utilisateur consulte les autres pages
				$dist_id=$_SESSION['dist_id'];
				$distance_max=$_SESSION['distance_max'];
				$ontology=$_SESSION['ontology'];
			}else{
				$_SESSION['concept_1']=$concept_1;//l'utilisateur n'a pas encore fait de recherche
				$_SESSION['dist_id']=$dist_id;
				$_SESSION['distance_max']=$distance_max;
				$_SESSION['ontology']=$ontology;
			}
		}else{
			if (!is_null($session->get('concept_1'))&&is_null($concept_1)){
				$concept_1=$session->get('concept_1');//l'utilisateur consulte les autres pages
				$dist_id=$session->get('dist_id');
				$distance_max=$session->get('distance_max');
				$ontology=$session->get('ontology');
			}else{
				$session->set('concept_1', $concept_1);//l'utilisateur n'a pas encore fait de recherche
				$session->set('dist_id', $dist_id);
				$session->set('distance_max', $distance_max);
				$session->set('ontology', $ontology);
			}
		}
		
		$recup=$this->getSemSimId($concept_1, $ontology);
		//produit en croix effectué dans la méthode multiDistances
		$results=$this->multiDistances($dist_id, $distance_max, $recup, $page);
		$concepts_count=$this->countMultiDistances($dist_id, $distance_max, $recup);
		//\Doctrine\Common\Util\Debug::dump($concepts_count);
		if (empty($results->semantic_distances)){
			$results=null;
			$constructGraph=null;
		}else{
			//TODO: construct a graph
			$constructGraph=new ConstructGraph($this->getDoctrine());
			$constructGraph->getAllNodesAroundConcept($results->semantic_distances);
		}
		
		$pagination = array(
				'page' => $page,
				'route' => 'acme_biomedical_all_concepts_in_distance',
				'pages_count' => ceil($concepts_count/self::MAX_CONCEPTS),
				'route_params' => array()
		);
		
		return $this->render('AcmeBiomedicalBundle:Default:semantic_distance_concept.html.twig',
				array('title'=>'Distance sémantique',
				'distances'=>$results,
				'pagination'=>$pagination,		
				'ontology'=>$ontology,	
				'concept_1'=>$concept_1,
				'distance_max'=>$distance_max,					
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
	 * @param string $ontology
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
	 * @Annotations\QueryParam(name="concept_1", requirements="(\d+|(http.+))", description="First id concept to compare.Or the URI of concept 1.Mandatory")
	 * @Annotations\QueryParam(name="concept_2", requirements="(\d+|(http.+))", description="Second id concept to compare.Or the URI of concept 2.Mandatory")
	 * @Annotations\QueryParam(name="dist_id", requirements="\d+", nullable=true, description="The distance id.
	 * If dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker.Optional")
	 * @Annotations\QueryParam(name="include", requirements="(concept|all)", nullable=true, description="If you want to retreive the full_id of the concept
	 * put concept. If you want the full_id and the ontology of concept put all.Optional")
	 * @Annotations\QueryParam(name="lang", requirements="(fr|en)", nullable=true, description="If you want an english return put en, by default it is a french return (fr).Optional") 
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
		$include=$paramFetcher->get('include');
		$lang=$paramFetcher->get('lang');
		
		if (!is_null($lang)){
			$this->changeLanguage($lang,true);
		}
		$distances=$this->singleDistanceParam($concept_1, $concept_2, $dist_id, $include);
		if ($distances==null){
			$partie_1=$this->get('translator')->trans("calcul.distance.pas.possible");
			$partie_2=$this->get('translator')->trans("et.concept");
			throw new HttpException(404,$partie_1.": ".$concept_1." ".$partie_2.": ".$concept_2);
		}
		return $distances;
	}
	
	/**
	 * Fonction permettant de construir l'objet complexe SemanticDistanceTwoConcepts
	 * en fonction des trois paramètres ci-dessous et de le renvoyer
	 * @param SemanticDistance $semantic_object
	 * @param integer $concept_1
	 * @param integer $concept_2
	 * @param string  $include
	 * @return \Acme\BiomedicalBundle\Model\SemanticDistanceTwoConcepts
	 */
	private function returnSemanticDistanceTwoConcepts($semantic_object,$concept_1,$concept_2,$include=null){
		if (is_null($semantic_object)){
			return null;
		}
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
		if (empty($concept_1)||empty($concept_2)){
			$filtre=$this->get('translator')->trans("filtres.concept_1.concept_2.obligatoires");
			throw new HttpException(403,$filtre."!");
		}
		if ((preg_match("[\d+]", $concept_1)&&preg_match("[\d+]", $concept_2))||(is_int($concept_1)&&is_int($concept_2))){
			if (!is_null($dist_id)){
				$recup_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
				->findOneBy(array('concept_1'=>$concept_1,'concept_2'=>$concept_2));
				if (is_null($recup_id)){
					$recup_id=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:SemanticDistance")
					->findOneBy(array('concept_1'=>$concept_2,'concept_2'=>$concept_1));
					if (is_null($recup_id)){
						throw new HttpException(404,"Nous sommes désolé le calcul de distance n'est pas possible avec le concept: ".$concept_1."
						et le concept: ".$concept_2);
						return null;
					}
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
				$url=$this->get('translator')->trans("champ.type.url.concept_1.concept_2");
				throw new HttpException(403,$url."!");
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
					if (is_null($recup_id_2)){
						throw new HttpException(404,"Nous sommes désolé le calcul de distance n'est pas possible avec le concept: ".$concept_1_id."
						et le concept: ".$concept_2_id);
						return null;
					}
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
					if (is_null($distances_4)){
						throw new HttpException(404,"Nous sommes désolé le calcul de distance n'est pas possible avec le concept: ".$concept_1_id."
						et le concept: ".$concept_2_id);
						return null;
					}
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
	 *	@Annotations\QueryParam(name="dist_id", requirements="\d+", description="The distance id.
	 * If dist_id=1 -> sim_lin, 2=sim_wu_palmer, 3=sim_resnik, 4=sim_schlicker.Mandatory")
	 *	@Annotations\QueryParam(name="distance_max", requirements="(\d+)", description="The maximum distance to search between 0 to 100.Mandatory")
	 * @Annotations\QueryParam(name="concept_1", requirements="(http.+)", nullable=true, description="URI of concept.Should be 
	 * mandatory if you want to search by URI.Optional")
	 * @Annotations\QueryParam(name="page_number", requirements="(\d+)", nullable=true, description="You can run through other results by changing the page number(By default the page number is 1. Each page contains 50 results).Optional")
	 * @Annotations\QueryParam(name="lang", requirements="(fr|en)", nullable=true, description="If you want an english return put en, by default it is a french return (fr).Optional")
	 * 
	 * @Annotations\View(templateVar="distances")
	 *
	 *	@param integer     $concept      the concept id or the string "URI" if you want to pass an URI.Mandatory. 
	 *  
	 * @return object
	 */
	public function getDistanceAction($concept, ParamFetcherInterface $paramFetcher){
		$dist_id=$paramFetcher->get('dist_id');
		$distance_max=$paramFetcher->get('distance_max');
		$lang=$paramFetcher->get('lang');
		$page_number=$paramFetcher->get('page_number');
		
		if (!is_null($lang)){
			$this->changeLanguage($lang,true);
		}
		if (is_null($page_number)||($page_number>$this->countMultiDistances($dist_id, $distance_max, $concept))){
			$page_number=1;
		}
		
		if (empty($dist_id)||empty($distance_max)||empty($concept)){
			$filtres=$this->get('translator')->trans("filtres.dist_id.distance_max.obligatoires");
			throw new HttpException(403,$filtres."!");
		}
		if (is_int($concept)||preg_match("[\d+]", $concept)){
			$results=$this->multiDistances($dist_id, $distance_max, $concept,$page_number);
			if (empty($results->semantic_distances)) {
				$partie_1=$this->get('translator')->trans("pas.concepts.pour.distance");
				$partie_2=$this->get('translator')->trans("identifiant.distance");
				throw new HttpException(404,$partie_1.": ".$distance_max." ".$partie_2.": ".$dist_id);
			}
			
			return $results;
		}else{
			if (preg_match("(URI)", $concept)){
				$concept_recup=$paramFetcher->get('concept_1');
				if (!preg_match("(http.+)", $concept_recup)){
					$url=$this->get('translator')->trans("champ.type.url.concept_1");
					throw new HttpException(403,$url."!");
					//go to the hell
				}
				$concept_1=urldecode($concept_recup);
				$concept_id=$this->retreiveConceptId($concept_1);
			
				$results=$this->multiDistances($dist_id, $distance_max, $concept_id,$page_number);
				if (empty($results->semantic_distances)) {
					$partie_1=$this->get('translator')->trans("pas.concepts.pour.distance");
					$partie_2=$this->get('translator')->trans("identifiant.distance");
					throw new HttpException(404,$partie_1.": ".$distance_max." ".$partie_2.": ".$dist_id);
				}
				return $results;
			}else{
				$uri=$this->get('translator')->trans("rentrer.URI.passer.URI");
				throw new HttpException(403,$uri."!");
			}
		}
	}
	
	/**
	 * @param integer $dist_id le type de la distance
	 * @param integer $distance_max la distance maximale choisie par l'utilisateur
	 * @param integer $concept l'identifiant du concept
	 * @return integer number of results returned by query
	 */
	private function countMultiDistances($dist_id,$distance_max,$concept){
		$tab=split(":", $dist_id);
		if (!preg_match("[\d+]", $dist_id)&&!preg_match("[\d+]", $distance_max)){
			throw new HttpException(403,"Vous devez mettre un champ de type entier pour le champ
						dist_id et distance_max!");
			//go to the hell
		}
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
	
		$distance_max=($distance_max*$this->getMaxDistance($dist_id))/100;
		//produit en croix en fonction du type de distance choisi par l'utilisateur
	
		$em=$this->getDoctrine()->getEntityManager();
		$query=$em->createQueryBuilder()
		->select("sd.".$dist_id.", sd.concept_1, sd.concept_2")
		->from("AcmeBiomedicalBundle:SemanticDistance", "sd")
		->where("sd.".$dist_id.">= :distance")
		->andWhere("sd.concept_1 = :id")
		->setParameters(array("distance"=>$distance_max,"id"=>$concept))
		->orderBy("sd.".$dist_id,"DESC")
		->distinct(true)
		->getQuery();
		$recup = $query->getArrayResult();
		return count($recup);
	}
	
	/**
	 * @param integer $dist_id le type de la distance
	 * @param integer $distance_max la distance maximale choisie par l'utilisateur
	 * @param integer $concept l'identifiant du concept
	 * @return SemanticDistanceCollection
	 */
	private function multiDistances($dist_id,$distance_max,$concept,$page=1){
		$tab=split(":", $dist_id);
		if (!preg_match("[\d+]", $dist_id)&&!preg_match("[\d+]", $distance_max)){
			throw new HttpException(403,"Vous devez mettre un champ de type entier pour le champ
						dist_id et distance_max!");
			//go to the hell
		}
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
		
		$distance_max=($distance_max*$this->getMaxDistance($dist_id))/100;
		//produit en croix en fonction du type de distance choisi par l'utilisateur
		
		$em=$this->getDoctrine()->getEntityManager();
		$query=$em->createQueryBuilder()
		->select("sd.".$dist_id.", sd.concept_1, sd.concept_2")
		->from("AcmeBiomedicalBundle:SemanticDistance", "sd")
		->where("sd.".$dist_id.">= :distance")
		->andWhere("sd.concept_1 = :id")
		->setParameters(array("distance"=>$distance_max,"id"=>$concept))
		->orderBy("sd.".$dist_id,"DESC")
		->distinct(true)
		->setFirstResult(($page-1)*self::MAX_CONCEPTS)
		->setMaxResults(self::MAX_CONCEPTS)
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