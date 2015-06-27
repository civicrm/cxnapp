<?php
namespace Civi\Cxn\DirBundle\Command;

use Civi\Cxn\Rpc\AppMeta;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PreviewCommand extends Command {

  protected function configure() {
    $this
      ->setName('dirsvc:preview')
      ->setDescription('Preview the metadata of a remote application')
      ->setHelp('Example: cxndir preview http://app.example.com/cxn/metadata.json')
      ->addArgument('url', InputArgument::REQUIRED, 'The application\'s metadata URL');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $appMeta = json_decode(file_get_contents($input->getArgument('url')), TRUE);
    AppMeta::validate($appMeta);
    print_r($appMeta);
  }

}
