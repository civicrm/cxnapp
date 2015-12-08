<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Civi\Cxn\AppBundle;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Logger;

/**
 * Formats incoming records for console output by coloring them depending on log level.
 *
 * This is different from the standard ConsoleFormatter in the Symfony-Monolog bridge
 * in that it supports PSR-3 interpolation.
 *
 * @author Tobias Schultze <http://tobion.de>
 * @author Tim Otten
 */
class ConsoleFormatter extends NormalizerFormatter {
  const DEFAULT_FORMAT = "%start_tag%[%datetime%] %channel%.%level_name%:%end_tag% %message% %context%\n";

  protected $format;

  public function __construct($format = NULL, $dateFormat = NULL) {
    parent::__construct($dateFormat);
    $this->format = ($format) ? $format : self::DEFAULT_FORMAT;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $record) {

    if ($record['level'] >= Logger::ERROR) {
      $record['start_tag'] = '<error>';
      $record['end_tag'] = '</error>';
    }
    elseif ($record['level'] >= Logger::NOTICE) {
      $record['start_tag'] = '<comment>';
      $record['end_tag'] = '</comment>';
    }
    elseif ($record['level'] >= Logger::INFO) {
      $record['start_tag'] = '<info>';
      $record['end_tag'] = '</info>';
    }
    else {
      $record['start_tag'] = '';
      $record['end_tag'] = '';
    }

    $vars = parent::format($record);

    $output = $this->format;

    list ($vars['message'], $vars['context']) = $this->interpolate($vars['message'], $vars['context']);

    if (empty($vars['context'])) {
       $output = str_replace('%context%', '', $output);
    }

    foreach ($vars as $var => $val) {
      if (FALSE !== strpos($output, '%' . $var . '%')) {
        $output = str_replace('%' . $var . '%', print_r($val, 1), $output);
      }
    }

    return $output;
  }

  /**
   * @param $message
   * @param array $context
   * @return array
   *   Array(string $message, array $leftoverContext).
   */
  private function interpolate($message, array $context) {
    $leftovers = array();

    // build a replacement array with braces around the context keys
    $replace = array();
    foreach ($context as $key => $val) {
      $token = sprintf('{%s}', $key);
      if (strpos($message, $token) !== FALSE && !is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
        $replace[$token] = $val;
      }
      else {
        $leftovers[$key] = $val;
      }
    }

    // interpolate replacement values into the message and return
    return array(
      strtr($message, $replace),
      $leftovers,
    );
  }

}
