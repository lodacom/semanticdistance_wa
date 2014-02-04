<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="obs_path_to_root")
 * @ORM\Entity
 */
class PathToRoot {
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
	 * @ORM\Column(type="string", length=512)
	 */
	protected $path_to_root;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $last_parent_processed;
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
	public function getPathToRoot() {
		return $this->path_to_root;
	}
	public function setPathToRoot($path_to_root) {
		$this->path_to_root = $path_to_root;
		return $this;
	}
	public function getLastParentProcessed() {
		return $this->last_parent_processed;
	}
	public function setLastParentProcessed($last_parent_processed) {
		$this->last_parent_processed = $last_parent_processed;
		return $this;
	}
	
	
}