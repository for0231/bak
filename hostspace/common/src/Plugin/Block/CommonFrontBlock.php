<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\CommonFrontBlock.
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'common front' block.
 *
 * @Block(
 * 	 id = "common_front",
 *   admin_label = @Translation("Common front"),
 *   category = @Translation("Custom")
 * )
 */
class CommonFrontBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	return array( '#theme' => 'common_front');
  }

  public function getCacheMaxAge() {
    return 0;
  }
}
