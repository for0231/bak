<?php

namespace Drupal\tip;

use Drupal\Core\Database\Connection;

/**
 *
 */
class TipService {

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
   * Tip entity table save.
   */
  public function save($entity, $update = TRUE) {
    $tip_id = 0;
    if ($update) {
      $type = $entity->getEntityTypeId();
      $ppid = $entity->id();
      $arr_entity = \Drupal::entityTypeManager()->getStorage('tip')
        ->loadByProperties([
          'ttid' => $ppid,
          'type' => $type,
        ]
      );
      $tip_entity = current($arr_entity);
      $tip_entity->set('isreaded', $entity->get('isreaded')->value);
      $tip_entity->set('isdeleted', $entity->get('isdeleted')->value);
      $tip_entity->save();
    }
    else {
      $tip_entity = entity_create('tip', [
        'type' => $entity->getEntityTypeId(),
        'ttid' => $entity->id(),
        'isreaded' => $entity->get('isreaded')->value,
        'isdeleted' => $entity->get('isdeleted')->value,
        'uid' => $entity->get('uid')->target_id,
        'cid' => $entity->get('cid')->target_id,
      ]);
      $tip_entity->save();
    }

  }

  /**
   * 统计未读信息总数.
   *
   * @return int
   */
  public function getTipStatistic() {
    $query = $this->database->query("select count(*) as counter from smart_tip where isreaded=0");
    $results = $query->fetchObject();
    current($results);
    return $results->counter;
  }

}
