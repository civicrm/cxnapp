<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\Rpc\ApiClient;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
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
   * @var AppStoreInterface
   */
  protected $appStore;

  /**
   * @var CxnStoreInterface
   */
  protected $cxnStore;

  /**
   * @var LoggerInterface
   */
  protected $log;

  public function __construct(AppStoreInterface $appStore, CxnStoreInterface $cxnStore, LoggerInterface $log) {
    parent::__construct();
    $this->appStore = $appStore;
    $this->cxnStore = $cxnStore;
    $this->log = $log;
  }

  protected function configure() {
    $this
      ->setName('cxn:cron')
      ->setDescription('Fire cron job for all connections')
      ->addArgument('appId', InputArgument::REQUIRED, 'The application which should fire the cron jobs');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $appId = $input->getArgument('appId');
    if (!preg_match('/^app:/', $appId)) {
      $appId = 'app:' . $appId;
    }
    $appMeta = $this->appStore->getAppMeta($appId);

    $errors = array();

    foreach ($this->cxnStore->getAll() as $cxnId => $cxn) {
      if ($cxn['appId'] !== $appId) {
        continue;
      }

      $apiClient = new ApiClient($appMeta, $this->cxnStore, $cxnId);
      $apiClient->setLog($this->log);
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
