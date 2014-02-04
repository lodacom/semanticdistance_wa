<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="obs_map")
 * @ORM\Entity
 */
Class Map {
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
	protected $mapped_concept_id;
	/**
	 * @ORM\Column(type="string", length=256)
	 */
	protected $mapping_type;
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
	public function getMappedConceptId() {
		return $this->mapped_concept_id;
	}
	public function setMappedConceptId($mapped_concept_id) {
		$this->mapped_concept_id = $mapped_concept_id;
		return $this;
	}
	public function getMappingType() {
		return $this->mapping_type;
	}
	public function setMappingType($mapping_type) {
		$this->mapping_type = $mapping_type;
		return $this;
	}
	
}