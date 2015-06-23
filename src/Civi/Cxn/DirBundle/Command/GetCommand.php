<?php
namespace Civi\Cxn\DirBundle\Command;

use Civi\Cxn\DirBundle\DirConfig;
use Civi\Cxn\Rpc\AppStore\AppStoreInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends Command {

  /**
   * @var AppStoreInterface
   */
  protected $appStore;

  function __construct(AppStoreInterface $appStore) {
    parent::__construct();
    $this->appStore = $appStore;
  }

  protected function configure() {
    $this
      ->setName('dirsvc:get')
      ->setDescription('Get a list of known applications')
      ->setHelp('Example: cxndir get')
      ->addArgument('appId', InputArgument::OPTIONAL, 'The Appplication GUID');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getArgument('appId')) {
      foreach (array($input->getArgument('appId'), 'app:' . $input->getArgument('appId')) as $appId) {
        $appIds = $this->appStore->getAppIds();
        if (in_array($appId, $appIds)) {
          print_r($this->appStore->getAppMeta($appId));
        }
      }
    }
    else {
      $rows = array();
      foreach ($this->appStore->getAppIds() as $appId) {
        $app = $this->appStore->getAppMeta($appId);
        $rows[] = array($app['appId'], $app['appUrl']);
      }

      $table = $this->getApplication()->getHelperSet()->get('table');
      $table
        ->setHeaders(array('App ID', 'App URL'))
        ->setRows($rows);
      $table->render($output);
    }
  }

}
