<?php
/**
 * @file
 * Contains \Drupal\qy_jd\LogsListBuilder.
 */

namespace Drupal\qy_jd;

use Drupal\Core\Url;
use Drupal\qy\LogsListBase;

class LogsListBuilder extends LogsListBase {
  /**
   * {@inheritdoc}
   */
  public static function create($list_type) {
    return new static($list_type);
  }

  private function __construct($list_type) {
    $this->list_type = $list_type;
    $this->db_service = \Drupal::service('qy_jd.db_service');
  }
}
