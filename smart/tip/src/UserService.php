<?php

namespace Drupal\tip;

use Drupal\Core\Database\Connection;

/**
 *
 */
class UserService {

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
   * 匹配用户名称.
   */
  public function getMatchClients($type_string) {
    $options = [];
    $query = $this->database->query('select u.uid,u.name from users_field_data as u where name like :string  order by uid desc limit 0,10', [
      ':string' => '%' . $type_string . '%',
    ]);
    $results = $query->fetchAll();
    foreach ($results as $item) {
      $options[] = [
        'value' => $item->name,
        'label' => $item->name,
      ];
    }
    return $options;
  }

}
