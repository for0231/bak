<?php
/**
 * @file
 * Contains \Drupal\member\Routing\RouteSubscriber.
 */

namespace Drupal\member\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change path '/user/{user}/edit' to '/user/account/{user}'.
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setPath('/user/account/{user}/edit');
    }
    // Change path 'user/login' to 'account/login'
    if($route = $collection->get('user.login')) {
      $route->setPath('/account/login');
    }
    // Change path 'user/register' to 'account/register'
    if($route = $collection->get('user.register')) {
      $route->setPath('/account/register');
    }
    // Change path 'user/password' to 'account/password'
    if($route = $collection->get('user.pass')) {
      $route->setPath('/account/password');
    }
  }

}
