<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Ontology;

class ConstructGraph {
	
	public $doctrine;
	protected $tab_of_Node_1;
	protected $tab_of_Node_2;
	
	public function __construct($doctrine){
		$this->doctrine=$doctrine;
	}
	
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
	 * 
	 * @param integer $concept1
	 * @param integer $concept2
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
		->findOneBy(array('concept_id'=>$common_ancestor));
		$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
		->find($common_ancestor);
		$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
		->find($concept->getOntologyId());
		//on met l'ancêtre commun en début de pile
		//\Doctrine\Common\Util\Debug::dump($term->getName());
		
		$node=new Node($term->getName(), $concept,$ontology);
		array_push($node_array, $node);
		
		while ($i>0&&strcmp($tab[$i], $common_ancestor)!=0){
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
		->findOneBy(array('concept_id'=>$concept_leaf));
		$concept=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
		->find($concept_leaf);
		$ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
		->find($concept->getOntologyId());
		//on met la feuille en fin de pile
		//\Doctrine\Common\Util\Debug::dump($term->getName());
		
		$node=new Node($term->getName(), $concept,$ontology);
		array_push($node_array, $node);
		
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
		while ($i<count($tab_1)&&strcmp($tab_1[$i], $tab_2[$i])!=0) {
			$i++;//on s'arrête quand on a trouvé un identifiant égal (donc l'ancêtre commun)
		}
		return $tab_1[$i];
	}
}