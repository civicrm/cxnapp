<?php
namespace Civi\Cxn\DirBundle\Command;

use Civi\Cxn\Rpc\AppMeta;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command {

  /**
   * @var string
   */
  protected $appsFile;

  /**
   * @param string $appsFile
   *   The path to a JSON file.
   */
  public function __construct($appsFile) {
    parent::__construct();
    $this->appsFile = $appsFile;
  }

  protected function configure() {
    $this
      ->setName('dirsvc:import')
      ->setDescription('Import the metadata of a remote application')
      ->setHelp('Example: cxndir import http://app.example.com/cxn/metadata.json')
      ->addArgument('url', InputArgument::REQUIRED, 'The application\'s metadata URL');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $appMeta = json_decode(file_get_contents($input->getArgument('url')), TRUE);
    AppMeta::validate($appMeta);

    $apps = json_decode(file_get_contents($this->appsFile), TRUE);
    $apps[$appMeta['appId']] = $appMeta;
    file_put_contents($this->appsFile,
      json_encode($apps, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0));

    print_r($appMeta);
  }

}
