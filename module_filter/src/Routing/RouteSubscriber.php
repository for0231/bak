<?php
/**
 * @file
 * Contains \Drupal\module_filter\Routing\RouteSubscriber.
 */

namespace Drupal\module_filter\Routing;

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
    // Change form 'Drupal\system\Form\ModulesListForm' to '\Drupal\module_filter\Form\ModuleListFilterForm'.
    // var $route = Symfony\Component\Routing\Route.
    if ($route = $collection->get('system.modules_list')) {
      $route->setDefault('_form', '\Drupal\module_filter\Form\ModuleListFilterForm');
    }
  }

}
