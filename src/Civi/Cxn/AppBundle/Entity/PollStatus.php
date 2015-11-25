<?php

namespace Civi\Cxn\AppBundle\Entity;

use Civi\Cxn\AppBundle\Entity\CxnEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PollStatus
 *
 * @ORM\Table(name="PollStatus",
 *   uniqueConstraints={@ORM\UniqueConstraint(name="cxn_job",columns={"cxnId","jobName"})},)
 * @ORM\Entity
 */
class PollStatus {

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
   * @ORM\Column(name="jobName", type="string", length=64)
   */
  private $jobName;

  /**
   * @var int
   * @ORM\Column(name="pollLevel", type="integer", nullable=false, options={"unsigned":true, "default":0})
   */
  private $pollLevel;

  /**
   * @var int
   * @ORM\Column(name="pollCount", type="integer", nullable=false, options={"unsigned":true, "default":0})
   */
  private $pollCount;

  /**
   * @var int
   *   Seconds since epoch.
   * @ORM\Column(name="lastRun", type="integer", nullable=false, options={"unsigned":true, "default":0})
   */
  private $lastRun;

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
   * @return string
   */
  public function getJobName() {
    return $this->jobName;
  }

  /**
   * @param string $jobName
   * @return $this
   */
  public function setJobName($jobName) {
    $this->jobName = $jobName;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getPollLevel() {
    return $this->pollLevel;
  }

  /**
   * @param mixed $pollLevel
   * @return $this
   */
  public function setPollLevel($pollLevel) {
    $this->pollLevel = $pollLevel;
    return $this;
  }

  /**
   * @return int
   */
  public function getPollCount() {
    return $this->pollCount;
  }

  /**
   * @param int $pollCount
   * @return $this
   */
  public function setPollCount($pollCount) {
    $this->pollCount = $pollCount;
    return $this;
  }

  /**
   * @return int
   */
  public function getLastRun() {
    return $this->lastRun;
  }

  /**
   * @param int $lastRun
   * @return $this
   */
  public function setLastRun($lastRun) {
    $this->lastRun = $lastRun;
    return $this;
  }

}
