<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\AdhocConfig;
use Civi\Cxn\Rpc\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CxnCallCommand extends Command {

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
      ->setName('cxn:call')
      ->setDescription('Send an API call')
      ->addArgument('cxnId', InputArgument::REQUIRED, 'Connection ID')
      ->addArgument('Entity.action', InputArgument::REQUIRED, 'API etity and action')
      ->addArgument('key=value', InputArgument::IS_ARRAY, 'Any parameters, as key=value pairs');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $config = new AdhocConfig();

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

    $output->writeln("<info>CxnID</info>: $cxnId");

    $cxn = $config->getCxnStore()->getByCxnId($cxnId);
    if (!$cxn) {
      $output->writeln("<error>Invalid cxnID</error>");
      return 1;
    }

    $output->writeln("<info>Site URL</info>: " . $cxn['siteUrl']);
    $output->writeln("<info>Entity</info>: $entity");
    $output->writeln("<info>Action</info>: $action");
    $output->writeln("<info>Params</info>: " . print_r($params, TRUE));

    $apiClient = new ApiClient($config->getMetadata(), $config->getCxnStore(), $cxnId);
    $apiClient->setLog($config->getLog('ApiClient'));
    $result = $apiClient->call($entity, $action, $params);
    $output->writeln("<info>Result</info>: " . print_r($result, TRUE));
  }

}
