<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\Rpc\CxnStore\CxnStoreInterface;
use Civi\Cxn\Rpc\CxnStore\JsonFileCxnStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CxnGetCommand extends Command {

  /**
   * @var CxnStoreInterface
   */
  protected $cxnStore;

  /**
   * @param CxnStoreInterface $appStore
   */
  public function __construct(CxnStoreInterface $appStore) {
    parent::__construct();
    $this->cxnStore = $appStore;
  }

  protected function configure() {
    $this
      ->setName('cxn:get')
      ->setDescription('Get a list of connections')
      ->addArgument('cxnId', InputArgument::OPTIONAL, 'Connection ID');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getArgument('cxnId')) {
      $cxn = $this->cxnStore->getByCxnId($input->getArgument('cxnId'));
      print_r($cxn);
    }
    else {
      $rows = array();
      foreach ($this->cxnStore->getAll() as $cxn) {
        $rows[] = array($cxn['cxnId'], $cxn['siteUrl']);
      }

      $table = $this->getApplication()->getHelperSet()->get('table');
      $table
        ->setHeaders(array('Link ID', 'Site URL'))
        ->setRows($rows);
      $table->render($output);
    }
  }

}
