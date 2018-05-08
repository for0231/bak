<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\CommonBottomContentBlock.
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'common bottom content' block.
 *
 * @Block(
 * 	 id = "common_bottom_content",
 *   admin_label = @Translation("Common bottom content"),
 *   category = @Translation("Custom")
 * )
 */
class CommonBottomContentBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	return array('#theme' => 'common_bottom_content');
  }

  public function getCacheMaxAge() {
    return 0;
  }
}
