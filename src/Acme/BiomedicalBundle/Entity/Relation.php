<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="obs_relation")
 * @ORM\Entity
 */
class Relation {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $concept_id;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $parent_concept_id;
	
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $level;
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getConceptId() {
		return $this->concept_id;
	}
	public function setConceptId($concept_id) {
		$this->concept_id = $concept_id;
		return $this;
	}
	public function getParentConceptId() {
		return $this->parent_concept_id;
	}
	public function setParentConceptId($parent_concept_id) {
		$this->parent_concept_id = $parent_concept_id;
		return $this;
	}
	public function getLevel() {
		return $this->level;
	}
	public function setLevel($level) {
		$this->level = $level;
		return $this;
	}
	
	
}