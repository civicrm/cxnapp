<?php

namespace Civi\Cxn\CronBundle\Entity;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * CronSettings
 *
 * @ORM\Table("CronSettings")
 * @ORM\Entity
 */
class CronSettings {

  /**
   * @var CxnEntity
   *
   * @ORM\Id
   * @ORM\OneToOne(targetEntity="Civi\Cxn\AppBundle\Entity\CxnEntity")
   * @ORM\JoinColumn(name="cxnId", referencedColumnName="cxnId", onDelete="CASCADE")
   */
  private $cxn;

  /**
   * @var string
   *
   * @ORM\Column(name="email", type="string", length=255, nullable=true)
   * @Assert\Email
   */
  private $email;

  /**
   * @return CxnEntity
   */
  public function getCxn() {
    return $this->cxn;
  }

  /**
   * @param CxnEntity $cxn
   */
  public function setCxn($cxn) {
    $this->cxn = $cxn;
  }

  /**
   * Set email
   *
   * @param string $email
   * @return CronSettings
   */
  public function setEmail($email) {
    $this->email = $email;

    return $this;
  }

  /**
   * Get email
   *
   * @return string
   */
  public function getEmail() {
    return $this->email;
  }

}
