<?php

namespace Acme\BiomedicalBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction(){
        return $this->render('AcmeBiomedicalBundle:Default:index.html.twig',array('title'=>'BioMedicalSemantic'));
    }
    
    public function goToOntologieAction(){
    	$request = $this->get('request');
    	//$recup=null;
    	//if($request->isXmlHttpRequest()){
    		$term = $request->request->get('searchOntology');
    		$envoi=array();
    		array_push($envoi, $term);
    		$recup = $request->create('/api/ontologies','GET',$envoi);
    		$truc=$recup->get('retour');
    	//}
    	return $this->render('AcmeBiomedicalBundle:Default:ontology.html.twig',array('title'=>'Ontology','results'=>$truc));
    }
    
    public function showConceptsAction(){
        $request = $this->get('request');
         
        if($request->isXmlHttpRequest()){
            $term = $request->request->get('search');
            
            $quer = $this->getDoctrine()->getEntityManager();
            $query=$quer->createQuery("SELECT t.name as term,o.name as ontology
            		FROM AcmeBiomedicalBundle:Term t, AcmeBiomedicalBundle:Concept c,AcmeBiomedicalBundle:Ontology o
            		WHERE t.name LIKE ?1
            		AND t.concept_id=c.id
            		AND c.ontology_id=o.id
            		ORDER BY t.name ASC")
            ->setParameter(1,$term."%")
            ->setMaxResults(10);
            $recup = $query->getArrayResult();
            $concepts=array();
			foreach ( $recup as $data ) {
				$concepts[] = $data ['term']." (".$data ['ontology'].")";
			}
			if (count($recup)==0){
				array_push($concepts, "Aucune proposition");
			}
            $response = new Response(json_encode($concepts));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
    
    public function showOntologiesAction(){
    	$request = $this->get('request');
    	 
    	if($request->isXmlHttpRequest()){
    		$term = $request->request->get('searchOntology');
    	
    		$quer = $this->getDoctrine()->getEntityManager();
    		$query=$quer->createQuery("SELECT o.name
            		FROM AcmeBiomedicalBundle:Ontology o
            		WHERE o.name LIKE ?1
    				ORDER BY o.name ASC")
    	            		->setParameter(1,$term."%")
    	            		->setMaxResults(10);
    		$recup = $query->getArrayResult();
    		$concepts=array();
    		foreach ( $recup as $data ) {
    			$concepts[] = $data ['name'];
    		}
    		if (count($recup)==0){
    			array_push($concepts, "Aucune proposition");
    		}
    		$response = new Response(json_encode($concepts));
    		$response->headers->set('Content-Type', 'application/json');
    		return $response;
    	}
    }
}
