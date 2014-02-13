<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation;

/**
 *
 * @ORM\Table(name="obs_concept")
 * @ORM\Entity
 */
class Concept {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @Annotation\Exclude
	 * @ORM\Column(type="string", length=355)
	 */
	protected $local_concept_id;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $ontology_id;
	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $is_toplevel;
	/**
	 * @ORM\Column(type="text")
	 */
	protected $full_id;
	
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getLocalConceptId() {
		return $this->local_concept_id;
	}
	public function setLocalConceptId($local_concept_id) {
		$this->local_concept_id = $local_concept_id;
		return $this;
	}
	public function getOntologyId() {
		return $this->ontology_id;
	}
	public function setOntologyId($ontology_id) {
		$this->ontology_id = $ontology_id;
		return $this;
	}
	public function getIsToplevel() {
		return $this->is_toplevel;
	}
	public function setIsToplevel($is_toplevel) {
		$this->is_toplevel = $is_toplevel;
		return $this;
	}
	public function getFullId() {
		return $this->full_id;
	}
	public function setFullId($full_id) {
		$this->full_id = $full_id;
		return $this;
	}
}
