<?php
namespace Civi\Cxn\AppBundle;

class BatchHelper {

  const BATCH_CODE_MIN = 0;
  const BATCH_CODE_MAX = 10000;

  /**
   * @param $batchExpr
   *   Ex: '0/1', '1/4', '2/4'.
   * @retun array
   *   Ex: array('id' => 1, 'count' => 4).
   */
  public static function parseBatchId($batchExpr) {
    $parts = explode('/', $batchExpr);
    if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
      var_dump($parts);
      throw new \InvalidArgumentException("Failed to parse batchId or batchCount");
    }
    return array('id' => (int) $parts[0], 'count' => (int) $parts[1]);
  }

  /**
   * @param array $batch
   *   Ex: array('id' => 1, 'count' => 4).
   * @return array
   *   Ex: array(2500, 4999).
   */
  public static function getBatchRange($batch) {
    $batchId = $batch['id'];
    $batchCount = $batch['count'];
    if (!is_int($batchId) || !is_int($batchCount) || $batchId < 0 || $batchCount < 1 || $batchId >= $batchCount) {
      throw new \InvalidArgumentException("Invalid batchNum or batchCount");
    }

    $batchSize = floor(self::BATCH_CODE_MAX / $batchCount);
    $first = $batchId * $batchSize;

    if ($batchId === $batchCount - 1) {
      $last = self::BATCH_CODE_MAX;
      return array($first, $last);
    }
    else {
      $last = (($batchId + 1) * $batchSize) - 1;
      return array($first, $last);
    }
  }

}