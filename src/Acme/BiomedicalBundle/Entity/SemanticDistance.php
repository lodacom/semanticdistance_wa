<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="obs_semsim")
 * @ORM\Entity
 */
class SemanticDistance {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $concept_1;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $concept_2;
	/**
	 * @ORM\Column(type="decimal", scale=2)
	 */
	protected $sim_lin;
	/**
	 * @ORM\Column(type="decimal", scale=2)
	 */
	protected $sim_wu_palmer;
	/**
	 * @ORM\Column(type="decimal", scale=2)
	 */
	protected $sim_resnik;
	/**
	 * @ORM\Column(type="decimal", scale=2)
	 */
	protected $sim_schlicker;
	public function getId() {
		return $this->id;
	}
	public function setId($id) {
		$this->id = $id;
		return $this;
	}
	public function getConcept1() {
		return $this->concept_1;
	}
	public function setConcept1($concept_1) {
		$this->concept_1 = $concept_1;
		return $this;
	}
	public function getConcept2() {
		return $this->concept_2;
	}
	public function setConcept2($concept_2) {
		$this->concept_2 = $concept_2;
		return $this;
	}
	public function getSimLin() {
		return $this->sim_lin;
	}
	public function setSimLin($sim_lin) {
		$this->sim_lin = $sim_lin;
		return $this;
	}
	public function getSimWuPalmer() {
		return $this->sim_wu_palmer;
	}
	public function setSimWuPalmer($sim_wu_palmer) {
		$this->sim_wu_palmer = $sim_wu_palmer;
		return $this;
	}
	public function getSimResnik() {
		return $this->sim_resnik;
	}
	public function setSimResnik($sim_resnik) {
		$this->sim_resnik = $sim_resnik;
		return $this;
	}
	public function getSimSchlicker() {
		return $this->sim_schlicker;
	}
	public function setSimSchlicker($sim_schlicker) {
		$this->sim_schlicker = $sim_schlicker;
		return $this;
	}
	
}