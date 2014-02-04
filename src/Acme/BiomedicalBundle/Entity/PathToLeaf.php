<?php

namespace Acme\BiomedicalBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 
 *@ORM\Table(name="obs_path_to_leaf")
 *@ORM\Entity
 */
class PathToLeaf {
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
	 * @ORM\Column(type="text")
	 */
	protected $path_to_leaf;
	/**
	 * @ORM\Column(type="integer")
	 */
	protected $last_child_processed;
}