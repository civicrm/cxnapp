<?php

namespace Civi\Cxn\CronBundle\Entity;

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
   * @var string
   *
   * @ORM\Id
   * @ORM\Column(name="cxnId", type="string", length=64)
   */
  private $cxnId;

  /**
   * @var string
   *
   * @ORM\Column(name="email", type="string", length=255, nullable=true)
   * @Assert\Email
   */
  private $email;

  /**
   * @return string
   */
  public function getCxnId() {
    return $this->cxnId;
  }

  /**
   * @param string $cxnId
   */
  public function setCxnId($cxnId) {
    $this->cxnId = $cxnId;
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
