<?php

namespace Civi\Cxn\AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cxn
 *
 * @ORM\Table("Cxn",
 *   indexes={@ORM\Index(name="appId_idx", columns={"appId","batchCode"})}
 * )
 * @ORM\Entity(
 *   repositoryClass="Civi\Cxn\AppBundle\Entity\CxnEntityRepository"
 * )
 */
class CxnEntity {

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
   * @var int
   *
   * The batchCode is a randomly generated integer which can be
   * used to breakdown the list of cxn's into batches. For example, to
   * fetch the first of three batches, one might select either:
   *
   *   WHERE batchCode BETWEEN 0 and 3333
   *   WHERE MOD(batchCode, 3) = 0
   *
   * @ORM\Column(name="batchCode", type="integer", nullable=false, options={"unsigned":true, "default":0})
   */
  private $batchCode;

  /**
   * @ORM\OneToMany(targetEntity="Civi\Cxn\AppBundle\Entity\PollStatus", mappedBy="cxn")
   */
  private $pollStatuses;

  /**
   * CxnEntity constructor.
   */
  public function __construct() {
    $this->pollStatuses = new ArrayCollection();
  }

  /**
   * Set cxnId
   *
   * @param string $cxnId
   * @return CxnEntity
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
   * @return CxnEntity
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
   * @return CxnEntity
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
   * @return CxnEntity
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
   * @return int
   */
  public function getBatchCode() {
    return $this->batchCode;
  }

  /**
   * @param int $batchCode
   * @return CxnEntity
   */
  public function setBatchCode($batchCode) {
    $this->batchCode = $batchCode;

    return $this;
  }

  /**
   * Set siteUrl
   *
   * @param string $siteUrl
   * @return CxnEntity
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
   * @return CxnEntity
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
    );
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
