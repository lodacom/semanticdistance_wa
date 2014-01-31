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
}