<?php

namespace Civi\Cxn\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cxn
 *
 * @ORM\Table("Cxn",
 *   uniqueConstraints={@ORM\UniqueConstraint(name="cxnId_idx", columns={"cxnId"})},
 *   indexes={@ORM\Index(name="appId_idx", columns={"appId"})}
 * )
 * @ORM\Entity(
 *   repositoryClass="Civi\Cxn\AppBundle\Entity\CxnEntityRepository"
 * )
 */
class CxnEntity {

  /**
   * Create a CxnEntity object using a Cxn array.
   *
   * @param array $cxn
   * @return CxnEntity
   */
  public static function create($cxn) {
    $thisl = new CxnEntity();
    $thisl->mergeArray($cxn);
    return $thisl;
  }

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
   * @ORM\Column(name="secret", type="string", length=64)
   */
  private $secret;

  /**
   * @var string
   *
   * @ORM\Column(name="appId", type="string", length=128)
   */
  private $appId;

  /**
   * @var string
   *
   * @ORM\Column(name="appUrl", type="string", length=255)
   */
  private $appUrl;

  /**
   * @var string
   *
   * @ORM\Column(name="siteUrl", type="string", length=255)
   */
  private $siteUrl;

  /**
   * @var array
   *
   * @ORM\Column(name="perm", type="json_array")
   */
  private $perm;


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
   * @return Cxn
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
   * Set secret
   *
   * @param string $secret
   * @return Cxn
   */
  public function setSecret($secret) {
    $this->secret = $secret;

    return $this;
  }

  /**
   * Get secret
   *
   * @return string
   */
  public function getSecret() {
    return $this->secret;
  }

  /**
   * Set appId
   *
   * @param string $appId
   * @return Cxn
   */
  public function setAppId($appId) {
    $this->appId = $appId;

    return $this;
  }

  /**
   * Get appId
   *
   * @return string
   */
  public function getAppId() {
    return $this->appId;
  }

  /**
   * Set appUrl
   *
   * @param string $appUrl
   * @return Cxn
   */
  public function setAppUrl($appUrl) {
    $this->appUrl = $appUrl;

    return $this;
  }

  /**
   * Get appUrl
   *
   * @return string
   */
  public function getAppUrl() {
    return $this->appUrl;
  }

  /**
   * Set siteUrl
   *
   * @param string $siteUrl
   * @return Cxn
   */
  public function setSiteUrl($siteUrl) {
    $this->siteUrl = $siteUrl;

    return $this;
  }

  /**
   * Get siteUrl
   *
   * @return string
   */
  public function getSiteUrl() {
    return $this->siteUrl;
  }

  /**
   * Set perm
   *
   * @param array $perm
   * @return Cxn
   */
  public function setPerm($perm) {
    $this->perm = $perm;

    return $this;
  }

  /**
   * Get perm
   *
   * @return array
   */
  public function getPerm() {
    return $this->perm;
  }

  /**
   * Generate an array-formatted record as expected by, eg, Cxn::validate.
   *
   * @return array
   * @see Cxn::validate
   */
  public function toArray() {
    return array(
      'appId' => $this->getAppId(),
      'appUrl' => $this->getAppUrl(),
      'cxnId' => $this->getCxnId(),
      'perm' => $this->getPerm(),
      'secret' => $this->getSecret(),
      'siteUrl' => $this->getSiteUrl(),
    );;
  }

  public function mergeArray($cxn) {
    if (isset($cxn['appId'])) {
      $this->setAppId($cxn['appId']);
    }
    if (isset($cxn['appUrl'])) {
      $this->setAppUrl($cxn['appUrl']);
    }
    if (isset($cxn['cxnId'])) {
      $this->setCxnId($cxn['cxnId']);
    }
    if (isset($cxn['perm'])) {
      $this->setPerm($cxn['perm']);
    }
    if (isset($cxn['secret'])) {
      $this->setSecret($cxn['secret']);
    }
    if (isset($cxn['siteUrl'])) {
      $this->setSiteUrl($cxn['siteUrl']);
    }
  }

}
