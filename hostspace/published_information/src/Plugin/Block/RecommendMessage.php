<?php

/**
 * @file
 * Contains Drupal\published_information\Plugin\Block\RecommendMessage.
 */

namespace Drupal\published_information\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Routing\RedirectDestinationTrait;
/**
 * Provides a 'recommend message' block.
 *
 * @Block(
 * 	 id = "recommend_message",
 *   admin_label = @Translation("Recommend Message"),
 *   category = @Translation("Custom")
 * )
 */
class RecommendMessage extends BlockBase implements ContainerFactoryPluginInterface {
  use UrlGeneratorTrait;
  use RedirectDestinationTrait;
  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
   $entity = array_reverse(entity_load_multiple_by_properties('node', array('type' => 'articles', 'sticky'=> 1)));
   	return array(
      '#theme' => 'recommend_message_block',
      '#recommend_data' => $entity
    );
  }
  
    /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();
    $allow = array('published.front.list', 'entity.node.canonical ');
    if(in_array($route_name, $allow)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }


}
