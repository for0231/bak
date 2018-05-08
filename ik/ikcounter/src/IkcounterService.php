<?php

namespace Drupal\ikcounter;

use Drupal\Core\Database\Connection;

/**
 *
 */
class IkcounterService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   *
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * @description 重定义各种单据的编码的计数规则
   */

  function getIkNumberCounterCode() {
    $config = \Drupal::configFactory()->getEditable('ikcounter.settings');

    $counter = $config->get('start');
    $next_counter = ++$counter;
    $config->set('start', $next_counter);
    $config->save();

    $new_no = date($config->get('formatter'), time()) . $next_counter;
    return $new_no;
  }

}
