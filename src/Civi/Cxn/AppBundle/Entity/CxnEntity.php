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
    $e = new CxnEntity();
    $e->setAppId(isset($cxn['appId']) ? $cxn['appId'] : NULL);
    $e->setAppUrl(isset($cxn['appUrl']) ? $cxn['appUrl'] : NULL);
    $e->setCxnId(isset($cxn['cxnId']) ? $cxn['cxnId'] : NULL);
    $e->setPerm(isset($cxn['perm']) ? $cxn['perm'] : NULL);
    $e->setSecret(isset($cxn['secret']) ? $cxn['secret'] : NULL);
    $e->setSiteUrl(isset($cxn['siteUrl']) ? $cxn['siteUrl'] : NULL);
    return $e;
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
    return (array) $this;
  }
}
