<?php
namespace Civi\Cxn\AppBundle;

use Civi\Cxn\AppBundle\Event\RegistrationServerEvent;
use Civi\Cxn\Rpc\RegistrationServer;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class AppRegistrationServer extends RegistrationServer {

  /**
   * @var CxnLinks
   */
  protected $cxnLinks;

  /**
   * @var EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * Extend the dispatcher to emit Symfony events for parsing requests, executing them, and
   * returning responses.
   *
   * @param array $reqData
   * @return array|mixed|NULL
   */
  public function call($reqData) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $reqData['entity'])
      || !preg_match('/^[a-zA-Z0-9_]+$/', $reqData['action'])
      || !preg_match('/^app:[a-zA-Z0-9_\.]+$/', $reqData['cxn']['appId'])
    ) {
      return $this->createError('Unrecognized entity or action');
    }
    $eventPrefix = $reqData['cxn']['appId'] . ':' . strtolower($reqData['entity']) . '.' . strtolower($reqData['action']);

    $parseEvent = new RegistrationServerEvent($this, $reqData['cxn'], $reqData['entity'], $reqData['action'], $reqData['params']);
    $this->dispatchEach(array("$eventPrefix:parse", RegistrationServerEvents::PARSE), $parseEvent);

    $callEvent = new RegistrationServerEvent($this, $parseEvent->wireCxn, $parseEvent->entity, $parseEvent->action, $parseEvent->params);
    $this->dispatchEach(array("$eventPrefix:call", RegistrationServerEvents::CALL), $callEvent);

    $respondEvent = new RegistrationServerEvent($this, $callEvent->wireCxn, $callEvent->entity, $callEvent->action, $callEvent->params, $callEvent->response);
    $this->dispatchEach(array("$eventPrefix:respond", RegistrationServerEvents::RESPOND), $respondEvent);

    return $respondEvent->response;
  }

  /**
   * Dispatch an event which has multiple names/aliases.
   *
   * @param array $names
   * @param \Symfony\Component\EventDispatcher\Event $event
   */
  private function dispatchEach($names, Event $event) {
    foreach ($names as $name) {
      if ($event->isPropagationStopped()) {
        break;
      }
      $this->dispatcher->dispatch($name, $event);
    }
  }

  /**
   * Use the default registration handlers (onCxnRegister, onCxnUnregister, etc).
   *
   * @param \Civi\Cxn\AppBundle\Event\RegistrationServerEvent $event
   */
  public function defaultCall(RegistrationServerEvent $event) {
    if ($event->registrationServer !== $this) {
      return;
    }

    $reqData = array();
    $reqData['cxn'] = $event->wireCxn;
    $reqData['entity'] = $event->entity;
    $reqData['action'] = $event->action;
    $reqData['params'] = $event->params;

    $event->response = parent::call($reqData);
    if ($event->response !== NULL) {
      $event->stopPropagation();
    }
  }

  /**
   * Compose a secure link to a settings page.
   *
   * TODO: Move this to an event listener (RegistrationEvents::CALL).
   *
   * @param $cxn
   * @param $params
   * @return array
   */
  public function onCxnGetlink($cxn, $params) {
    $storedCxn = $this->cxnStore->getByCxnId($cxn['cxnId']);

    if (!$storedCxn || $storedCxn['secret'] !== $cxn['secret']) {
      return $this->createError('"cxnId" or "secret" is invalid.');
    }

    try {
      return $this->createSuccess($this->getCxnLinks()->generate($storedCxn, $params));
    }
    catch (\InvalidArgumentException $e) {
      return $this->createError($e->getMessage());
    }
  }

  /**
   * @return CxnLinks
   */
  public function getCxnLinks() {
    return $this->cxnLinks;
  }

  /**
   * @param CxnLinks $cxnLinks
   */
  public function setCxnLinks($cxnLinks) {
    $this->cxnLinks = $cxnLinks;
  }

  /**
   * @return EventDispatcherInterface
   */
  public function getDispatcher() {
    return $this->dispatcher;
  }

  /**
   * @param EventDispatcherInterface $dispatcher
   */
  public function setDispatcher($dispatcher) {
    $this->dispatcher = $dispatcher;

    if ($dispatcher) {
      // FIXME: Put in a sane place.
      $listener = array($this, 'defaultCall');
      $this->dispatcher->removeListener(RegistrationServerEvents::CALL, $listener);
      $this->dispatcher->addListener(RegistrationServerEvents::CALL, $listener, -100);
    }
  }

}
