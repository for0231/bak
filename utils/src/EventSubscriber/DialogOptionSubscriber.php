<?php

namespace Drupal\utils\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SetDialogOptionCommand;

/**
 * Defines a test session subscriber that checks whether the session is empty.
 */
class DialogOptionSubscriber implements EventSubscriberInterface {


  /**
   * Performs tasks for session_test module on kernel.response.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The Event to process.
   */
  public function onResponseDialogOption(FilterResponseEvent $event) {
    // Set header for session testing.
    $response = $event->getResponse();
    if($response instanceof AjaxResponse) {
      $commands = $response->getCommands();
      foreach($commands as $key => $command ) {
        if ($command['command'] == 'openDialog' && !array_key_exists('width', $command['dialogOptions'])){
          //$response->getCommands()[$key]['dialogOptions']['width'] = 'auto'; //宽度自适应，可以
          $response->addCommand(new SetDialogOptionCommand('', 'width', 'auto')); //宽度自适应，可以
          break;
        }
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponseDialogOption');
    return $events;
  }

}
