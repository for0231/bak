<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\CommonFooterBlock.
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'common footer' block.
 *
 * @Block(
 * 	 id = "common_footer",
 *   admin_label = @Translation("Common footer"),
 *   category = @Translation("Custom")
 * )
 */
class CommonFooterBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	return array('#theme' => 'common_footer');
  }

  public function getCacheMaxAge() {
    return 0;
  }
}
