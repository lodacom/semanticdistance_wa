<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Ontology;
use Acme\BiomedicalBundle\Entity\Relation;

class ConstructGraph {
	
	public $doctrine;
	//....................................
	//varibales pour le service 1
	protected $tab_of_Node_1;
	protected $tab_of_Node_2;
	//....................................
	//variable pour le service 2
	protected $tab_of_Node;
	protected $save_results;
	protected $parent_viewed=array();
	
	public function __construct($doctrine){
		$this->doctrine=$doctrine;
	}
	
	/*
	 * Partie correspondant au service 1 pour la récupération
	* de tous les noeuds pour le List Common Ancestor.
	* Par la suite dans le template twig génération
	* du graphe à partir des données récupérées.
	*/
	
	public function getSizeOfTab1(){
		return count($this->tab_of_Node_1)-1;	
	}
	
	public function getSizeOfTab2(){
		return count($this->tab_of_Node_2)-1;
	}
	
	public function getNode($id_tab,$index){
		if ($id_tab==1){
			return $this->tab_of_Node_1[$index];
		}else{
			return $this->tab_of_Node_2[$index];
		}
	}
	
	/**
	 * Rempli les deux tableaux de noeuds dans l'ordre suivant:
	 * racine vers feuille
	 * @param integer $concept1 l'identifiant du concept
	 * @param integer $concept2 l'identifiant du concept
	 */
	public function getListAncestorsConcepts($concept1,$concept2){
		$concept1_path=$this->doctrine->getRepository("AcmeBiomedicalBundle:PathToRoot")
		->findOneBy(array('concept_id'=>$concept1));
		$concept2_path=$this->doctrine->getRepository("AcmeBiomedicalBundle:PathToRoot")
		->findOneBy(array('concept_id'=>$concept2));
		
		$path_to_root_1=$concept1_path->getPathToRoot();//de la racine jusqu'à son parent
		$path_to_root_2=$concept2_path->getPathToRoot();//OK
		
		$common_ancestor=null;
		if (strlen($path_to_root_1)>=strlen($path_to_root_2)){
			$common_ancestor=$this->getCommonAncestor($path_to_root_2, $path_to_root_1);
		}else{
			$common_ancestor=$this->getCommonAncestor($path_to_root_1, $path_to_root_2);
		}//OK
		//\Doctrine\Common\Util\Debug::dump($common_ancestor);
		$tab_1=split("\.", $path_to_root_1);
		$tab_2=split("\.", $path_to_root_2);
		$this->tab_of_Node_1=$this->getTermLinkToBioPortal($tab_1, $common_ancestor,$concept1);
		$this->tab_of_Node_2=$this->getTermLinkToBioPortal($tab_2, $common_ancestor,$concept2);
	}
	
	/**
	 * 
	 * @param array $tab
	 * @param string $common_ancestor
	 * @return array of Node
	 */
	private function getTermLinkToBioPortal($tab,$common_ancestor,$concept_leaf){
		$i=count($tab)-1;//on commence par la fin sinon on s'arrête tout de suite
		$node_array=array();
		
		$term=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
		->findOneBy(array('concept_id'=>$concept_leaf));
		$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
		->find($concept_leaf);
		$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
		->find($concept->getOntologyId());
		//on met la feuille en fin de pile (voir le reverse)
		//\Doctrine\Common\Util\Debug::dump($term->getName());
		
		$node=new Node($term->getName(), $concept,$ontology);
		array_push($node_array, $node);
		
		while ($i>0&&($tab[$i]!=$common_ancestor)){
			$concept=$tab[$i];
			$term=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
			->findOneBy(array('concept_id'=>$concept));
			
			//\Doctrine\Common\Util\Debug::dump($term->getName());
			
			$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
			->find($concept);
			$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
			->find($concept->getOntologyId());
			
			$node=new Node($term->getName(), $concept,$ontology);
			array_push($node_array, $node);
			$i--;
		}
		
		$term=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
		->findOneBy(array('concept_id'=>$common_ancestor));
		$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
		->find($common_ancestor);
		$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
		->find($concept->getOntologyId());
		//on met l'ancêtre commun en début de pile (voir le reverse)
		//\Doctrine\Common\Util\Debug::dump($term->getName());
		$node=new Node($term->getName(), $concept,$ontology);
		array_push($node_array, $node);
		
		$node_array=array_reverse($node_array);//on inverse le tableau pour obtenir le bon ordre
		return $node_array;
	}
	
	/**
	 * 
	 * @param string $path_to_root_1 path le plus court vers la racine
	 * @param string $path_to_root_2 path le plus long vers la racine
	 * @return string l'identifiant de l'ancêtre commun
	 */
	private function getCommonAncestor($path_to_root_1,$path_to_root_2){
		$tab_1=split("\.", $path_to_root_1);
		$tab_2=split("\.", $path_to_root_2);
		
		//on parcours le tableau de plus petit donc le path le plus court d'où tab1
		$i=0;
		while ($i<count($tab_1)&&($tab_1[$i]==$tab_2[$i])) {
			$i++;//on s'arrête quand on a trouvé un identifiant égal (donc l'ancêtre commun)
		}
		return $tab_1[$i-1];
	}
	
	/*
	 * Partie correspondant au service 2 pour la récupération
	 * de tous les noeuds autour d'un noeud choisi par l'utilisateur. 
	 */
	
	/**
	 * 
	 * @param array of TermConcept $term_concept
	 */
	public function getAllNodesAroundConcept($term_concept){
		$this->save_results=$term_concept;
		$node_array=array();
		for ($i=0;$i<count($term_concept);$i++){
			$node=new Node($term_concept[$i]->getTerm()->getName(), $term_concept[$i]->getConcept(),$term_concept[$i]->getOntology());
			array_push($node_array, $node);
		}
		$this->tab_of_Node=$node_array;
	}
	
	public function getSizeOfTab(){
		return (count($this->tab_of_Node)-1);
	}
	
	public function getNodeForService2($index){
		return $this->tab_of_Node[$index];
	}
	
	/**
	 * Permet de renvoyer le noeud père 
	 * @param Node $node
	 * @return  \Acme\BiomedicalBundle\Model\Node
	 */
	public function getParentOfNode(Node $node){
		$concept_id=$node->getConcept()->getId();
		$relation=$this->doctrine->getRepository("AcmeBiomedicalBundle:Relation")
		->findOneBy(array('concept_id'=>$concept_id));
		$node=null;
		if (!is_null($relation)){
			$parent_concept_id=$relation->getParentConceptId();//on récupère l'identifiant du père
			if (!$this->isInResults($parent_concept_id)){
				return null;
			}
			
			$term=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
			->findOneBy(array('concept_id'=>$parent_concept_id));
			$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
			->find($parent_concept_id);
			$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
			->find($concept->getOntologyId());
			$node=new Node($term->getName(), $concept,$ontology);
		}
		return $node;
	}
	
	/**
	 * 
	 * @param Node $node le fils potentiel
	 * @return boolean Vrai si le noeud a un parent dans les résultats.
	 * Faux sinon (ce qui veut dire qu'il est en position d'ancêtre commun)
	 */
	public function hasParentInResults(Node $node){
		if (is_null($this->getParentOfNode($node))){
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * 
	 * @param Node $node le père potentiel
	 * @return boolean Vrai si le père a au moins un noeud fils 
	 * dans les résultats trouvés.Sinon Faux.
	 */
	public function hasChildInResults(Node $node){
		$concept_id=$node->getConcept()->getId();
		$relation=$this->doctrine->getRepository("AcmeBiomedicalBundle:Relation")
		->findOneBy(array('parent_concept_id'=>$concept_id));
		if (!is_null($relation)){
			$concept_id=$relation->getConceptId();//le noeud fils
			if (!$this->isInResults($concept_id)){
				return false;
			}else{
				return true;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * 
	 * @param Node $parent_node le noeud parent du noeud inspecté
	 * @param Node $child_node_inspected le noeud fils inscpecté
	 * @return array of Node un tableau de noeuds (fils)
	 */
	public function getAllChildren(Node $parent_node,Node $child_node_inspected){
		$relation=$this->doctrine->getRepository("AcmeBiomedicalBundle:Relation")
		->findBy(array('parent_concept_id'=>$parent_node));
		$retour=array();
		for ($i=0;$i<count($relation);$i++){
			$child=$relation[$i]->getConceptId();
			if ($child!=$child_node_inspected->getConcept()->getId()){
				if ($this->isInResults($child)){
					
					$term=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
					->findOneBy(array('concept_id'=>$child));
					$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
					->find($child);
					$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
					->find($concept->getOntologyId());
					$node=new Node($term->getName(), $concept,$ontology);
					
					array_push($retour, $node);
				}
			}
		}
		return $retour;
	}
	
	/**
	 * 
	 * @param Node $node
	 * @return boolean Vrai si le noeud a été vu en tant que parent.
	 * Faux sinon.
	 */
	public function parentAlreadyViewed(Node $node){
		$i=0;
		if (empty($this->parent_viewed)){
			array_push($this->parent_viewed, $node);
			return false;
		}
		while ($i<count($this->parent_viewed)&&strcmp($this->parent_viewed[$i]->getName(), $node->getName())!=0){
			$i++;
		}
		if ($i==count($this->save_results)){
			array_push($this->parent_viewed, $node);
			return false;
		}else{
			return true;
		}
	}
	
	/**
	 * 
	 * @param integer $concept_id l'identifiant du concept.
	 * @return boolean Vrai si l'identifiant du concept est dans les résultats.
	 * Faux sinon.
	 */
	private function isInResults($concept_id){
		$i=0;
		while ($i<count($this->save_results)&&($this->save_results[$i]->getConcept()->getId()!=$concept_id)){
			$i++;
		}
		if ($i==count($this->save_results)){
			return false;
		}else{
			return true;
		}
	}
}