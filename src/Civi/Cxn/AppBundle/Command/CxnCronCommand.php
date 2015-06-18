<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\AdhocConfig;
use Civi\Cxn\Rpc\ApiClient;
use Civi\Cxn\Rpc\Exception\GarbledMessageException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CronCommand
 *
 * This is a dumb implementation which fires cron on
 * each site sequentially.
 *
 * @package Civi\Cxn\AppBundle\Command
 */
class CxnCronCommand extends Command {

  const DEFAULT_VERSION = 3;

  /**
   * @var LoggerInterface
   */
  protected $log;

  public function __construct(LoggerInterface $log) {
    parent::__construct();
    $this->log = $log;
  }

  protected function configure() {
    $this
      ->setName('cxn:cron')
      ->setDescription('Fire cron job all connections');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $config = new AdhocConfig();

    $errors = array();

    foreach ($config->getCxnStore()->getAll() as $cxnId => $cxn) {

      $apiClient = new ApiClient($config->getMetadata(), $config->getCxnStore(), $cxnId);
      $apiClient->setLog($config->getLog('ApiClient'));
      try {
        $result = $apiClient->call('Job', 'execute', array(
          'debug' => 1,
          'version' => 3,
        ));
      }
      catch (GarbledMessageException $e) {
        $result = NULL;
        $output->writeln(sprintf("%s (%s): <error>garbled</error>", $cxnId, $cxn['siteUrl']));
        $output->writeln(substr($e->getGarbledMessage()->getData(), 0, 77) . '...');
      }
      catch (\Exception $e) {
        $result = NULL;
        $output->writeln(sprintf("%s (%s): <error>exception</error>", $cxnId, $cxn['siteUrl']));
        $output->writeln($e->getTraceAsString());
      }

      if (!empty($result['is_error'])) {
        $output->writeln(sprintf("%s (%s): <error>error</error>", $cxnId, $cxn['siteUrl']));
        var_dump($result);
        $errors[] = $cxnId;
      }

      if (is_array($result) && empty($result['is_error'])) {
        $output->write(sprintf("%s (%s): <info>ok</info>", $cxnId, $cxn['siteUrl']));
      }

      $output->writeln("");

      if (count($errors) > 0) {
        $output->writeln(sprintf("<error>Errors: %d</error>", count($errors)));
        return 1;
      }
    }
  }

}
