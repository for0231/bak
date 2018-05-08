<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\ContactusBlock.
 */

namespace Drupal\common\Plugin\Block;

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
 * Provides a 'contact us ' block.
 *
 * @Block(
 * 	 id = "contact_us",
 *   admin_label = @Translation("Contact Us"),
 *   category = @Translation("Custom")
 * )
 */
class ContactusBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
  	return array( '#theme' => 'contact_us');
  }

    /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();
    if(in_array($route_name, array('contact.site_page'))) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  public function getCacheMaxAge() {
    return 0;
  }
}
