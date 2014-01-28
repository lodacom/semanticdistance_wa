<?php
namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="obs_definition")
 * @ORM\Entity
 */
class Definition{
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @ORM\Column(type="text")
	 */
	protected $definition;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $concept_id;
	
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getDefinition() {
		return $this->definition;
	}
	public function setDefinition($definition) {
		$this->definition = $definition;
		return $this;
	}
	public function getConceptId() {
		return $this->concept_id;
	}
	public function setConceptId($concept_id) {
		$this->concept_id = $concept_id;
		return $this;
	}
	
}