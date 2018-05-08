<?php
/**
 * @file
 * Contains \Drupal\member\EventSubscriber\MemberSubscriber.
 */

namespace Drupal\member\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\Site\MaintenanceModeInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Maintenance mode subscriber to logout users.
 */
class MemberSubscriber implements EventSubscriberInterface {
  use UrlGeneratorTrait;
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new redirect subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(AccountInterface $account, URLGeneratorInterface $url_generator) {
    $this->account = $account;
    $this->setUrlGenerator($url_generator);
  }

  public function redirectToUserAccount(GetResponseEvent $event) {
    $request = $event->getRequest();
    $route_match = RouteMatch::createFromRequest($request);
    $route_name = $route_match->getRouteName();
    if ($this->account->isAuthenticated()) {
      $user_type = entity_load('user', $this->account->id())->get('user_type')->value ;
      switch ($route_name) {
        case 'entity.user.canonical';
          if($user_type == 'client') {
            $redirect = 'member.account_center';
          } else {
            $redirect = 'system.admin';
          }
          $redirect ? $event->setResponse($this->redirect($redirect)) : '';
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirectToUserAccount', 31];
    return $events;
  }

}
