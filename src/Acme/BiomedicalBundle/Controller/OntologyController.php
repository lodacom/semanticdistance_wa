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
	 * @Annotations\View()
	 *
	 *
	 * @return array
	 */
	public function allOntologyAction(Request $request, ParamFetcherInterface $paramFetcher){
		$session = $request->getSession();
		
		$offset = $paramFetcher->get('offset');
		$start = null == $offset ? 0 : $offset + 1;
		$limit = $paramFetcher->get('limit');
		
		$ontologies = $session->get(self::SESSION_CONTEXT_ONTOLOGY, array());
		$ontologies = array_slice($ontologies, $start, $limit, true);
		
		return new OntologyCollection($ontologies);
	}
	
	/**
	 * Get a single ontology.
	 *
	 * @ApiDoc(
	 *   output = "Acme\BiomedicalBundle\Entity\Ontology",
	 *   statusCodes = {
	 *     200 = "Returned when successful",
	 *     404 = "Returned when the note is not found"
	 *   }
	 * )
	 *
	 * @Annotations\View(templateVar="ontology")
	 *
	 * @param integer     $id      the ontology id
	 *
	 * @return object
	 *
	 * @throws NotFoundHttpException when ontology not exist
	 */
	public function getOntologyAction($id){
		$ontology=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:Ontology")->find($id);
		/*$view = new View($ontology);
		$group = $this->container->get('security.context')->isGranted('ROLE_API') ? 'restapi' : 'standard';
		$view->getSerializationContext()->setGroups(array('Default', $group));*/
		if ($ontology==null) {
			throw $this->createNotFoundException("Ontology avec l'identifiant: ".$id." n'existe pas!");
		}
		return $ontology;
	}
}