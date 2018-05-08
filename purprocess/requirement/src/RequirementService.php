<?php

namespace Drupal\requirement;

use Drupal\Core\Database\Connection;

/**
 *
 */
class RequirementService {

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
   * Requirement entity table save.
   */
  public function save($entity, $update = TRUE) {
    if ($update) {
      $new_id = [
        $entity->id() => $entity->id(),
      ];
      $requirement = \Drupal::entityTypeManager()->getStorage('requirement')->load($entity->get('rno')->value);
      $old_pids = $requirement->get('pids');

      // 获取requirement原本的part ids.再合并当前id.
      $ids = [];
      foreach ($old_pids as $old_pid) {
        $pid_entity = $old_pid->entity;
        if ($pid_entity) {
          $ids[$pid_entity->id()] = $pid_entity->id();
        }
      }
      $requirement->set('pids', $ids + $new_id)
        ->save();
    }
  }

  /**
   * Delete requirement parts.
   *
   * @param int $requirement
   * @param $ids
   *   array 将要删除的配件ids
   */
  public function delete($requirement, $ids = []) {
    try {
      $requirement = \Drupal::entityTypeManager()->getStorage('requirement')->load($requirement);
      $old_pids = $requirement->get('pids');
      /*
      $audit_status = $requirement->get('audit')->value;

      $has_permission = \Drupal::currentUser()->hasPermission('administer requirement delete');
      if (!$has_permission || !$re_audit_status) {
      drupal_set_message('无权或该需求单已进入审批阶段', 'error');
      return -1;
      }
       */
      $oids = [];
      $parts = [];

      foreach ($old_pids as $old_pid) {
        $pid_entity = $old_pid->entity;
        if ($pid_entity) {
          $oids[$pid_entity->id()] = $pid_entity->id();
          $parts[$pid_entity->id()] = $pid_entity;
        }
      }
      // Delete requirement parts id.
      $new_ids = array_diff($oids, $ids);
      $requirement->set('pids', $new_ids)
        ->save();

      // Delete parts.
      foreach ($parts as $key => $val) {
        if (in_array($key, $ids)) {
          $val->delete();
        }
      }
    }
    catch (\Exception $e) {
      error_log(print_r($e, 1));
    }
  }

  /**
   * @description 获取需求单里面已采购与未采购的配件数量.
   */
  public function getPartFromRequirement($entity, $status = 0) {
    $num = 0;
    $pids = $entity->get('pids');
    switch ($status) {
      case 1:
        foreach ($pids as $pid) {
          if ($pid->entity->get('cno')->value != 0) {
            $num += $pid->entity->get('num')->value;
          }
        }
        break;

      case 2:
        foreach ($pids as $pid) {
          if ($pid->entity->get('cno')->value == 0) {
            $num += $pid->entity->get('num')->value;
          }
        }
        break;

      case 3:
        // 查询已审批，未采购的.
        foreach ($pids as $pid) {
          if ($pid->entity->get('cno')->value == 0 && in_array($pid->entity->get('re_status')->value, [5, 6])) {
            $num += $pid->entity->get('num')->value;
          }
        }
        break;
    }

    return $num;
  }

  /**
   * @description 查询所有的需求单
   * @param $ids
   */
  public function loadEntityDatabyEntityType($entity_type) {
    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->condition('deleted', 1);

    $entity_query->sort('created', 'DESC');
    $ids = $entity_query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   * @description 获取所有待审批的需求单。
   * @param $un_audit_entities;
   */
  public function getUnAuditDatabyEntityType($entity_type) {
    $entities = $this->loadEntityDatabyEntityType($entity_type);
    $un_audit_entities = [];
    foreach ($entities as $entity) {
      // 审批中工单.
      if (in_array($entity->get('status')->value, [2])) {
        $un_audit_entities[$entity->id()] = $entity;
      }
    }

    return $un_audit_entities;
  }

  /**
   * @description 获取该我审批的单
   */
  public function getEntityIdsforCurrentUserbyEntityType($entity_type) {
    $entities = $this->getUnAuditDatabyEntityType($entity_type);
    $current_uid = \Drupal::currentUser()->id();
    $ids = [];
    // 此例为requirement实体.
    foreach ($entities as $entity) {
      $aids = $entity->get('aids');
      foreach ($aids as $aid) {
        $audit_entity = $aid->entity;
        if ($audit_entity->get('auid')->target_id == $current_uid) {
          $ids[$entity->id()] = $entity->id();
        }
      }
    }

    return $ids;
  }

  /**
   *
   */
  public function updateRequirementStatusforComplete($entity) {
    $entity->set('status', 16)
      ->save();
    \Drupal::service('part.partservice')->updatePartStatus($entity);
  }

  /**
   * @description 重定义各种单据的编码的计数规则
   */
  public function getIkNumberCounterCode() {
    $config = \Drupal::configFactory()->getEditable('requirement.settings');

    $counter = empty($config->get('start')) ? 100 : $config->get('start');
    $next_counter = ++$counter;
    $config->set('start', $next_counter);
    $config->save();
    $formatter = empty($config->get('formatter')) ? 'Ymd' : $config->get('formatter');
    $new_no = date($formatter, time()) . $next_counter;
    return $new_no;
  }

}
