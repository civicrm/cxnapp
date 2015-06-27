<?php

namespace Civi\Cxn\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CxnToken
 *
 * @ORM\Table("CxnToken",
 *   uniqueConstraints={@ORM\UniqueConstraint(name="cxnToken_idx", columns={"cxnToken"})},
 *   indexes={@ORM\Index(name="expires_idx", columns={"expires"})}
 * )
 * @ORM\Entity
 */
class CxnToken {
  /**
   * @var integer
   *
   * @ORM\Column(name="id", type="integer")
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   */
  private $id;

  /**
   * @var string
   *
   * @ORM\Column(name="cxnId", type="string", length=64)
   */
  private $cxnId;

  /**
   * @var string
   *
   * @ORM\Column(name="cxnToken", type="string", length=64)
   */
  private $cxnToken;

  /**
   * @var string
   *
   * @ORM\Column(name="page", type="string", length=64)
   */
  private $page;

  /**
   * @var \DateTime
   *
   * @ORM\Column(name="expires", type="datetimetz")
   */
  private $expires;


  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set cxnId
   *
   * @param string $cxnId
   * @return CxnToken
   */
  public function setCxnId($cxnId) {
    $this->cxnId = $cxnId;

    return $this;
  }

  /**
   * Get cxnId
   *
   * @return string
   */
  public function getCxnId() {
    return $this->cxnId;
  }

  /**
   * Set cxnToken
   *
   * @param string $cxnToken
   * @return CxnToken
   */
  public function setCxnToken($cxnToken) {
    $this->cxnToken = $cxnToken;

    return $this;
  }

  /**
   * Get cxnToken
   *
   * @return string
   */
  public function getCxnToken() {
    return $this->cxnToken;
  }

  /**
   * Set page
   *
   * @param string $page
   * @return CxnToken
   */
  public function setPage($page) {
    $this->page = $page;

    return $this;
  }

  /**
   * Get page
   *
   * @return string
   */
  public function getPage() {
    return $this->page;
  }

  /**
   * Set expires
   *
   * @param \DateTime $expires
   * @return CxnToken
   */
  public function setExpires($expires) {
    $this->expires = $expires;

    return $this;
  }

  /**
   * Get expires
   *
   * @return \DateTime
   */
  public function getExpires() {
    return $this->expires;
  }

}
