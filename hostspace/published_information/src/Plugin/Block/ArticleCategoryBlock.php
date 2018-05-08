<?php

/**
 * @file
 * Contains Drupal\published_information\Plugin\Block\ArticleCategoryBlock.
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
 * Provides a 'Catagory' block.
 *
 * @Block(
 * 	 id = "article_category",
 *   admin_label = @Translation("Article Category"),
 *   category = @Translation("Custom")
 * )
 */
class ArticleCategoryBlock extends BlockBase implements ContainerFactoryPluginInterface {
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
    $category_data = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('articleCategory',0,1);
  	return array(
      '#theme' => 'article_catgeory_block',
      '#categorys' => $category_data
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $route_name = $this->routeMatch->getRouteName();
    $node_type = $this->routeMatch->getParameter('node_type');
    if(in_array($route_name, array('published.front.list')) && $node_type == 'articles') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
