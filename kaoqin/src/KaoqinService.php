<?php

namespace Drupal\kaoqin;

use Drupal\Core\Database\Connection;

/**
 *
 */
class KaoqinService {

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
   * Kaoqin entity table save.
   */
  public function saveImportData($datas) {
    foreach ($datas as $data) {
      $kaoqin_entity = \Drupal::entityTypeManager()
        ->getStorage('kaoqin')
        ->create($data);
      $kaoqin_entity->save();
    }
  }

  /**
   * @description 保存考勤排班计划.
   */
  public function saveKaoqinUpon($data) {
    $kaoqin_upon_entity = \Drupal::entityTypeManager()
      ->getStorage('upon')
      ->create($data);
    $kaoqin_upon_entity->save();

    return $kaoqin_upon_entity->id();
  }

  /**
   * @description 更新考勤排班.
   * @return 1. success 0. fail
   */
  public function updateKaoqinUpon($id, $data) {
    $entity_kaoqin_upon = \Drupal::entityTypeManager()->getStorage('upon')->load($id);
    if (empty($entity_kaoqin_upon)) {
      return 0;
    }
    $entity_kaoqin_upon->set('datetime', $data['datetime']);
    $entity_kaoqin_upon->set('morningsign', $data['morningsign']);
    $entity_kaoqin_upon->set('afternoonsign', $data['afternoonsign']);
    $entity_kaoqin_upon->save();

    return $entity_kaoqin_upon->id();
  }
}

