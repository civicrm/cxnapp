<?php
namespace Civi\Cxn\AppBundle\Command;

use Civi\Cxn\AppBundle\CxnLinks;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppCleanupCommand extends Command {

  /**
   * @var CxnLinks
   */
  protected $cxnLinks;

  /**
   * @param CxnLinks $cxnLinks
   */
  public function __construct(CxnLinks $cxnLinks) {
    parent::__construct();
    $this->cxnLinks = $cxnLinks;
  }

  protected function configure() {
    $this
      ->setName('cxnapp:cleanup')
      ->setDescription('Clean temporary database data');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->cxnLinks->cleanup();
  }

}
