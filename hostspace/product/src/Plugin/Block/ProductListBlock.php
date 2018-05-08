<?php
/**
 * @file
 * Contains \Drupal\product\Plugin\Block\ProductListBlock.
 */

namespace Drupal\product\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * provides a 'product ist' block
 *
 * @Block(
 *   id = "product_list_block",
 *   admin_label = @Translation("Product list")
 * )
 *
 */
class ProductListBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $entities = entity_load_multiple_by_properties('product', array(
      'front_Dispaly' => true
    ));
   
    $build['products'] = array(
      '#theme' => 'product_list',
      '#products' => $entities
    );
    
    return $build;  
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockAccess(AccountInterface $account) {
    return true; 
  }

}
