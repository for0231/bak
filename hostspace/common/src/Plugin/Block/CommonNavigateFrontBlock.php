<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\CommonNavigateFrontBlock.
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'common navigate front' block.
 *
 * @Block(
 * 	 id = "common_navigate_front",
 *   admin_label = @Translation("Common navigate front"),
 *   category = @Translation("Custom")
 * )
 */
class CommonNavigateFrontBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	return array('#markup' => 'common_navigate_front');
  }

  public function getCacheMaxAge() {
    return 0;
  }
}
