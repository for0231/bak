<?php
/**
 * @file
 * Contains \Drupal\product\Controller\ProductController.
 */

namespace Drupal\product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ProductController extends ControllerBase {

  /**
   * 获取器列表
   */
  public function serverList() {
    $queryFactory = \Drupal::getContainer()->get('entity.query');
    $entity_query = $queryFactory->get('product');
    $entity_query->sort('pid');
    $entity_query->condition('front_Dispaly', true);
    $pids = $entity_query->execute();
    $entities = entity_load_multiple('product', $pids);

    $build['products'] = array(
      '#theme' => 'product_list',
      '#products' => $entities
    );
    return $build;
  }
}
