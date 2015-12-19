<?php

namespace Civi\Cxn\ProfileBundle\Entity;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProfileSiteId
 *
 * A site's profile at a specific point in time.
 *
 * @ORM\Table(name="ProfileSiteId")
 * @ORM\Entity
 */
class ProfileSiteId {

  /**
   * @var CxnEntity
   *
   * @ORM\Id
   * @ORM\OneToOne(targetEntity="Civi\Cxn\AppBundle\Entity\CxnEntity")
   * @ORM\JoinColumn(name="cxnId", referencedColumnName="cxnId", onDelete="CASCADE")
   */
  private $cxn;

  /**
   * @var \DateTime
   *
   * @ORM\Column(type="datetime", nullable=false)
   */
  private $timestamp;

  /**
   * @var string|NULL
   *
   * @ORM\Column(name="siteId", type="string", nullable=true)
   */
  private $siteId;

  /**
   * ProfileSiteId constructor.
   * @param \Civi\Cxn\AppBundle\Entity\CxnEntity $cxn
   * @param \DateTime $timestamp
   * @param string $siteId
   */
  public function __construct(CxnEntity $cxn, \DateTime $timestamp, $siteId) {
    $this->cxn = $cxn;
    $this->timestamp = $timestamp;
    $this->siteId = $siteId;
  }

  /**
   * @return CxnEntity
   */
  public function getCxn() {
    return $this->cxn;
  }

  /**
   * @param CxnEntity $cxn
   * @return $this
   */
  public function setCxn($cxn) {
    $this->cxn = $cxn;
    return $this;
  }

  /**
   * @return \DateTime
   */
  public function getTimestamp() {
    return $this->timestamp;
  }

  /**
   * @param \DateTime $timestamp
   * @return $this
   */
  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
    return $this;
  }

  /**
   * @return string|NULL
   */
  public function getSiteId() {
    return $this->siteId;
  }

  /**
   * @param string|NULL $siteId
   * @return $this
   */
  public function setSiteId($siteId) {
    $this->siteId = $siteId;
    return $this;
  }

}
