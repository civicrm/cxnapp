<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\CxnLinks;
use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CxnLinkCommand extends Command {

  const DEFAULT_VERSION = 3;

  /**
   * @var CxnLinks
   */
  protected $cxnLinks;

  /**
   * @var CxnStoreInterface
   */
  protected $cxnStore;

  /**
   * @var LoggerInterface
   */
  protected $log;

  public function __construct(CxnLinks $cxnLinks, CxnStoreInterface $cxnStore, LoggerInterface $log) {
    parent::__construct();
    $this->cxnLinks = $cxnLinks;
    $this->cxnStore = $cxnStore;
    $this->log = $log;
  }

  protected function configure() {
    $this
      ->setName('cxn:link')
      ->setDescription('Generate a link on behalf of a connection')
      ->addArgument('cxnId', InputArgument::REQUIRED, 'Connection ID')
      ->addArgument('page', InputArgument::REQUIRED, 'Page name (eg "settings")')
      ->addArgument('key=value', InputArgument::IS_ARRAY, 'Any parameters, as key=value pairs');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $cxnId = $input->getArgument('cxnId');
    if (!preg_match('/^cxn:/', $cxnId)) {
      $cxnId = 'cxn:' . $cxnId;
    }

    $params = array();
    $params['page'] = $input->getArgument('page');
    foreach ($input->getArgument('key=value') as $expr) {
      list ($key, $value) = explode('=', $expr, 2);
      $params[$key] = $value;
    }

    $cxn = $this->cxnStore->getByCxnId($cxnId);
    if (!$cxn) {
      $output->writeln("<error>Invalid cxnID</error>: $cxnId");
      return 1;
    }

    if (!$this->cxnLinks->validate($params)) {
      $output->writeln("<error>Invalid params</error>: " . print_r($params, TRUE));
      return;
    }
    $output->writeln($this->cxnLinks->generate($cxn, $params));
  }

}
