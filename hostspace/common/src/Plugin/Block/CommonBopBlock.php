<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\CommonBopBlock.
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'common bottom content' block.
 *
 * @Block(
 * 	 id = "common_bop",
 *   admin_label = @Translation("Common bop content"),
 *   category = @Translation("Custom")
 * )
 */
class CommonBopBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
  	//return array('#theme' => 'common_bop_content');
  	return array('#markup' => 'fdsaf');
  }
}
