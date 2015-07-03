<?php
namespace Civi\Cxn\CrlBundle;

use Civi\Cxn\Rpc\Constants;
use Civi\Cxn\Rpc\X509Util;

/**
 * Class CrlGenerator
 * @package Civi\Cxn\CrlBundle
 */
class CrlGenerator {

  protected $crlDistCertPem, $crlDistKeyPairPems, $caCertPem, $ttl;

  /**
   * @param string $crlDistCertPem
   * @param array $crlDistKeyPairPems
   * @param string $caCertPem
   * @param string $ttl
   *   Time specification (per strtotime()).
   */
  public function __construct($crlDistCertPem, $crlDistKeyPairPems, $caCertPem, $ttl) {
    $this->caCertPem = $caCertPem;
    $this->crlDistCertPem = $crlDistCertPem;
    $this->crlDistKeyPairPems = $crlDistKeyPairPems;
    $this->ttl = $ttl;
  }

  /**
   * @param array $revocations
   *   With keys `serialNumber` and `certs`.
   * @return string
   *   Encoded CRL.
   */
  public function generate($revocations) {
    $crlDistCertObj = X509Util::loadCert($this->crlDistCertPem, $this->crlDistKeyPairPems, $this->caCertPem);

    $crlObj = new \File_X509();
    if (isset($revocations['serialNumber'])) {
      $crlObj->setSerialNumber(self::asDecimal($revocations['serialNumber']), 10);
    }
    elseif (isset($revocations['serialNumberNonce'])) {
      // FIXME: Patch '00' constant before 2286 AD arrives.
      $crlObj->setSerialNumber(self::asDecimal($revocations['serialNumberNonce']) . '00' . time(), 10);
    }
    else {
      throw new \RuntimeException("revocations.yml: Missing serialNumber or serialNumberNonce");
    }
    $crlObj->setEndDate('now');
    $crlPem = $crlObj->saveCRL($crlObj->signCRL($crlDistCertObj, $crlObj, Constants::CERT_SIGNATURE_ALGORITHM));
    $crlObj->loadCRL($crlPem);

    foreach ($revocations['certs'] as $certId => $reason) {
      $crlObj->setRevokedCertificateExtension(self::asDecimal($certId), 'id-ce-cRLReasons', $reason);
    }
    $crlObj->setEndDate($this->ttl);
    return $crlObj->saveCRL($crlObj->signCRL($crlDistCertObj, $crlObj));
  }

  /**
   * Convert a number to decimal notation.
   *
   * @param string $value
   *   Either a decimal number ("123456") or
   *   a colon-delimited hex ("12:34:ab").
   * @return string
   *   Decimal number
   */
  protected static function asDecimal($value) {
    if (strpos($value, ':') !== FALSE) {
      // Convert long hex to long dec.
      $bigInt = new \Math_BigInteger(str_replace(':', '', $value), 16);
      $value = $bigInt->toString();
      return $value;
    }
    return $value;
  }

}
