<?php

namespace Acme\BiomedicalBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Acme\BiomedicalBundle\Entity\Ontology;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OntologyController extends FOSRestController{
	
	const SESSION_CONTEXT_ONTOLOGY = 'ontology';
	
	/**
	 * List all ontologies.
	 *
	 * @ApiDoc(
	 *   resource = true,
	 *   statusCodes = {
	 *     200 = "Returned when successful"
	 *   }
	 * )
	 *
	 * @Annotations\View(templateVar="ontologies")
	 *
	 *
	 * @return array
	 */
	public function ontologiesAction(){
		$ontologies=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")
		->findAll();
		return $ontologies;
	}
	
	/**
	 * Get a single ontology.
	 *
	 * @ApiDoc(
	 *   output = "Acme\BiomedicalBundle\Entity\Ontology",
	 *   statusCodes = {
	 *     200 = "Returned when successful",
	 *     404 = "Returned when the ontology is not found"
	 *   }
	 * )
	 *
	 * @Annotations\View(templateVar="ontology")
	 *
	 * @param integer     $id      the ontology id or the ontology acronym
	 *
	 * @return object
	 *
	 * @throws NotFoundHttpException when ontology not exist
	 */
	public function getOntologyAction($id){
		$ontology=null;
		if (preg_match("[\d+]", $id)||is_int($id)){
			$ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")->find($id);
			if ($ontology==null) {
				throw new HttpException(404,"L'ontologie avec l'identifiant: ".$id." n'existe pas!");
			}
		}else{
			$ontology=null;
			if (preg_match("/[a-zA-Z]+/",$id)){
				$ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")
				->findOneBy(array('virtual_ontology_id'=>$id));
				if ($ontology==null) {
					throw new HttpException(404,"L'ontologie avec l'acronyme: ".$id." n'existe pas!");
				}
			}else{
				throw new HttpException(403,"Vous devez mettre un champ de type entier ou lettre!");
			}
		}
		return $ontology;
	}
}