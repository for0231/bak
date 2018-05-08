<?php
/**
 * @file
 * Contains \Drupal\qy_wd\PolicyListBuilder.
 */

namespace Drupal\qy_wd;

use Drupal\Core\Url;
use Drupal\qy\PolicyListBase;

class PolicyListBuilder extends PolicyListBase {
  /**
   * {@inheritdoc}
   */
  public static function create($list_type) {
    return new static($list_type);
  }

  private function __construct($list_type) {
    $this->list_type = $list_type;
    $this->db_service = \Drupal::service('qy_wd.db_service');
  }

  protected function getOperations($item) {
    $options = array();
    $operations = array();
    if($this->list_type == 'policy') {
      $options['query']['destination'] = \Drupal::url('admin.wd.policy');
      $operations['update'] = array(
        'title' =>'修改',
        'url' => new Url('admin.wd.policy.edit', array('policy_id' => $item->id))
      );
    } else if ($this->list_type == 'policytmp') {
      $options['query']['destination'] = \Drupal::url('admin.wd.policy_tmp');
      $operations['update'] = array(
        'title' =>'修改',
        'url' => new Url('admin.wd.policy_tmp.edit', array('policy_id' => $item->id))
      );
      $status = $item->zt ? 'open' : 'close';
      $operations['status'] = array(
        'title' => $item->zt ? '开启' : '暂停',
        'url' => new Url('admin.wd.policy_tmp.status', array('policy_id' => $item->id, 'status' =>$status))
      );
    }
    $operations['delete'] = array(
      'title' =>'删除',
      'url' => new Url('admin.wd.policy.delete', array('policy_id' => $item->id), $options)
    );
    return $operations;
  }

  protected function getWholeOperations($item) {
    if($this->list_type == 'policy') {
      $options['query']['destination'] = \Drupal::url('admin.wd.policy');
      $operations['delete'] = array(
        'title' =>'删除此段',
        'url' => new Url('admin.wd.policy.segment.delete', array('policy_id' => $item->id), $options)
      );
    }
    return $operations;
  }
}