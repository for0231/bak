<?php

/**
 * @file
 * Contains Drupal\common\Plugin\Block\CommonNotLoginTipBlock.
 */

namespace Drupal\common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
/**
 * Provides a 'common footer' block.
 *
 * @Block(
 * 	 id = "common_not_login_tip",
 *   admin_label = @Translation("Common user not login tip"),
 *   category = @Translation("Custom")
 * )
 */
class CommonNotLoginTipBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'common_not_login_tip',
      '#tips' => array(),
    );
  }

  public function getCacheMaxAge() {
    return 0;
  }
  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $roles = $account->getRoles();
    $allow = array('anonymous');
    if(in_array('anonymous', $roles)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}

