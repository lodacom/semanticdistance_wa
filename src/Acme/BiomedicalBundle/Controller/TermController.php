<?php

namespace Acme\BiomedicalBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Model\TermConcept;
use Acme\BiomedicalBundle\Entity\Concept;
use Acme\BiomedicalBundle\Entity\Ontology;

class TermController extends FOSRestController{
	
	const SESSION_CONTEXT_TERM = 'term';
	
	/**
	 * Get a single term.
	 *
	 * @ApiDoc(
	 *   output = "Acme\BiomedicalBundle\Model\TermConcept",
	 *   statusCodes = {
	 *     200 = "Returned when successful",
	 *     404 = "Returned when the ontology is not found"
	 *   }
	 * )
	 * @Annotations\QueryParam(name="include", requirements="(concept|all)", nullable=true, description="If you want to retreive the full_id of the concept
	 * put concept. If you want the full_id and the ontology of concept put all")
	 * @Annotations\View(templateVar="term")
	 *
	 * @param integer     $id      the term id or the term name
	 *
	 * @return object
	 *
	 * @throws NotFoundHttpException when term not exist
	 */
	public function getTermAction($id,ParamFetcherInterface $paramFetcher){
		$include=$paramFetcher->get('include');
		$term=null;
		$retour=null;
		if (preg_match("[\d+]", $id)||is_int($id)){
			$term=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Term")->find($id);
			if (!is_null($include)){
				$retour=$this->retreiveOptionalObjects($include, $term);
			}else{
				$retour=new TermConcept($term);
			}
			if ($term==null) {
				throw new HttpException(404,"Le terme avec l'identifiant: ".$id." n'existe pas!");
			}
		}else{
			if (preg_match("([[:alnum:]]+)",$id)){
				$term=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Term")
				->findOneBy(array('name'=>$id));
				if ($term==null) {
					throw new HttpException(404,"Le terme avec le nom: ".$id." n'existe pas!");
				}
				if (!is_null($include)){
					$retour=$this->retreiveOptionalObjects($include, $term);
				}else{
					$retour=new TermConcept($term);
				}
			}else{
				throw new HttpException(403,"Vous devez mettre un champ de type entier ou lettre!");
			}
		}
		return $retour;
	}
	
	/**
	 * Permet de renvoyer un objet TermConcept plus complet en fonction
	 * de la demande de l'utilisateur sur include
	 * @param string $include
	 * @param Term $term
	 * @return \Acme\BiomedicalBundle\Model\TermConcept
	 */
	private function retreiveOptionalObjects($include,Term $term){
		$concept=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Concept")
		->find($term->getConceptId());
		if (strcmp($include, "concept")==0){
			return new TermConcept($term,null,$concept);
		}else{
			$ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")
			->find($concept->getOntologyId());
			return new TermConcept($term,$ontology,$concept);
		}
	}
}