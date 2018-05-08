<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Plugin\Menu\TractionAddLocalAction.
 */

namespace Drupal\qy_jd\Plugin\Menu;

use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;

/**
 * Modifies the 'Add custom block' local action.
 */
class TractionAddLocalAction extends LocalActionDefault {
  use UrlGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options['query']['destination'] = $this->url('<current>');
    return $options;
  }
}
