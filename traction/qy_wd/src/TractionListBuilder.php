<?php
/**
 * @file
 * Contains \Drupal\qy_wd\TractionListBuilder.
 */

namespace Drupal\qy_wd;

use Drupal\Core\Url;
use Drupal\qy\TractionListBase;

class TractionListBuilder extends TractionListBase {
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
    if($this->list_type == 'traction') {
      $options['query']['destination'] = \Drupal::url('admin.wd.traction');
    } else if ($this->list_type == 'tractionfilter') {
      $options['query']['destination'] = \Drupal::url('admin.wd.traction_filter');
    } else if ($this->list_type == 'ipstop') {
      $options['query']['destination'] = \Drupal::url('admin.wd.ip_stop');
    }
    $operations['remove'] = array(
      'title' =>'解除牵引',
      'url' => new Url('admin.wd.traction.remove', array('traction_id' => $item->id), $options)
    );

    $operations['edit_time'] = array(
      'title' =>'修改时间',
      'url' => new Url('admin.wd.traction.time', array('traction_id' => $item->id), $options)
    );
    return $operations;
  }
}


