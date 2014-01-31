<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="obs_term")
 * @ORM\Entity
 */
class Term {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @ORM\Column(type="string", length=246)
	 */
	protected $name;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $concept_id;
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $is_prefered;
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	public function getConceptId() {
		return $this->concept_id;
	}
	public function setConceptId($concept_id) {
		$this->concept_id = $concept_id;
		return $this;
	}
	public function getIsPrefered() {
		return $this->is_prefered;
	}
	public function setIsPrefered($is_prefered) {
		$this->is_prefered = $is_prefered;
		return $this;
	}
	
	
	
}