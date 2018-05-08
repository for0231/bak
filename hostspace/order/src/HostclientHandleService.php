<?php

namespace Drupal\order;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class HostclientHandleService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * 获取处理信息
   * @param $hostclientid 在线服务器ID
   * @return Object 服务器处理信息对象
   */
  public function loadHandleInfo4sop($hostclientid) {
    return $this->database->select('hostclient_handle_info', 'h')
      ->fields('h')
      ->condition('hostclient_id', $hostclientid)
      ->execute()
      ->fetchObject();
  }
  /**
   * 获取处理信息
   * @param $hostclientid 在线服务器ID
   * @return Object 服务器处理信息对象
   */
  public function loadHandleInfo4SopByHandleId($hid) {
    return $this->database->select('hostclient_handle_info', 'h')
      ->fields('h')
      ->condition('hid', $hid)
      ->execute()
      ->fetchObject();
  }
}
