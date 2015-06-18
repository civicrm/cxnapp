<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\Rpc\ApiClient;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CxnCallCommand extends Command {

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
      ->setName('cxn:call')
      ->setDescription('Send an API call')
      ->addArgument('cxnId', InputArgument::REQUIRED, 'Connection ID')
      ->addArgument('Entity.action', InputArgument::REQUIRED, 'API etity and action')
      ->addArgument('key=value', InputArgument::IS_ARRAY, 'Any parameters, as key=value pairs');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $cxnId = $input->getArgument('cxnId');
    if (!preg_match('/^cxn:/', $cxnId)) {
      $cxnId = 'cxn:' . $cxnId;
    }
    list ($entity, $action) = explode('.', $input->getArgument('Entity.action'));
    $params = array();
    foreach ($input->getArgument('key=value') as $expr) {
      list ($key, $value) = explode('=', $expr, 2);
      $params[$key] = $value;
    }
    if (empty($params['version'])) {
      $params['version'] = self::DEFAULT_VERSION;
    }

    $cxn = $this->cxnStore->getByCxnId($cxnId);
    if (!$cxn) {
      $output->writeln("<error>Invalid cxnID</error>: $cxnId");
      return 1;
    }

    $output->writeln("<info>App ID</info>: " . $cxn['appId']);
    $output->writeln("<info>Cxn ID</info>: $cxnId");
    $output->writeln("<info>Site URL</info>: " . $cxn['siteUrl']);
    $output->writeln("<info>Entity</info>: $entity");
    $output->writeln("<info>Action</info>: $action");
    $output->writeln("<info>Params</info>: " . print_r($params, TRUE));

    $apiClient = new ApiClient($this->appStore->getAppMeta($cxn['appId']), $this->cxnStore, $cxnId);
    $apiClient->setLog($this->log);
    $result = $apiClient->call($entity, $action, $params);
    $output->writeln("<info>Result</info>: " . print_r($result, TRUE));
  }

}
