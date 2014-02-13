<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation;

/**
 * 
 *@ORM\Table(name="obs_ontology")
 *@ORM\Entity
 */
class Ontology {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @Annotation\Exclude
	 * @ORM\Column(type="string", length=246)
	 */
	protected $local_ontology_id;
	/**
	 * @ORM\Column(type="string", length=246)
	 */
	protected $name;
	/**
	 * @ORM\Column(type="string", length=246)
	 */
	protected $version;
	/**
	 * @Annotation\Exclude
	 * @ORM\Column(type="string", length=246)
	 */
	protected $description;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $status;
	/**
	 * @ORM\Column(type="string", length=246)
	 */
	protected $virtual_ontology_id;
	/**
	 * @ORM\Column(type="string", length=32)
	 */
	protected $format;
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getLocalOntologyId() {
		return $this->local_ontology_id;
	}
	public function setLocalOntologyId($local_ontology_id) {
		$this->local_ontology_id = $local_ontology_id;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	public function getVersion() {
		return $this->version;
	}
	public function setVersion($version) {
		$this->version = $version;
		return $this;
	}
	public function getDescription() {
		return $this->description;
	}
	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}
	public function getStatus() {
		return $this->status;
	}
	public function setStatus($status) {
		$this->status = $status;
		return $this;
	}
	public function getVirtualOntologyId() {
		return $this->virtual_ontology_id;
	}
	public function setVirtualOntologyId($virtual_ontology_id) {
		$this->virtual_ontology_id = $virtual_ontology_id;
		return $this;
	}
	public function getFormat() {
		return $this->format;
	}
	public function setFormat($format) {
		$this->format = $format;
		return $this;
	}
	
}