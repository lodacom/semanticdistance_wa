<?php

namespace Acme\BiomedicalBundle\Controller;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Acme\BiomedicalBundle\Model\OntologyCollection;
use Acme\BiomedicalBundle\Entity\Ontology;

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
	 * @Annotations\QueryParam(name="offset", requirements="\d+", nullable=true, description="Offset from which to start listing notes.")
	 * @Annotations\QueryParam(name="limit", requirements="\d+", default="5", description="How many ontologies to return.")
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
		}else{
			$ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")
			->findOneBy(array('virtual_ontology_id'=>$id));
		}
		if ($ontology==null) {
			throw $this->createNotFoundException("Ontology avec l'identifiant: ".$id." n'existe pas!");
		}
		return $ontology;
	}
}