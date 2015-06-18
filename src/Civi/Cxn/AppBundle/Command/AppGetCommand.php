<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppGetCommand extends Command {

  /**
   * @var AppStoreInterface
   */
  protected $appStore;

  /**
   * @param AppStoreInterface $appStore
   */
  public function __construct(AppStoreInterface $appStore) {
    parent::__construct();
    $this->appStore = $appStore;
  }

  protected function configure() {
    $this
      ->setName('cxnapp:get')
      ->setDescription('Get a list of locally registered applications')
      ->addArgument('appId', InputArgument::OPTIONAL, 'Application ID');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getArgument('appId')) {
      $appId = $input->getArgument('appId');
      if (!preg_match('/^app:/', $appId)) {
        $appId = 'app:' . $appId;
      }
      $appMeta = $this->appStore->getAppMeta($appId);
      print_r($appMeta);
    }
    else {
      $rows = array();
      foreach ($this->appStore->getAppIds() as $appId) {
        $appMeta = $this->appStore->getAppMeta($appId);
        $rows[] = array($appId, $appMeta['title']);
      }

      $table = $this->getApplication()->getHelperSet()->get('table');
      $table
        ->setHeaders(array('App ID', 'Title'))
        ->setRows($rows);
      $table->render($output);
    }
  }

}
