<?php

/**
 * @file
 * Contains \Drupal\hostlog\Controller\ReminderController.
 */

namespace Drupal\hostlog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\hostlog\OperationReminderTypeListBuild;

class ReminderController extends ControllerBase {

  /**
   * 按条件获取提醒数据
   */
  private function load() {
    $query_types = \Drupal::service('operation.reminder')->getReminderAllTypes();
    $types = array();
    foreach ($query_types as $row) {
      $types[$row->id] = $row->type;
    }
    if (empty($types))
      return array();
    $query = db_select('xunyunreminder', 're')
      ->fields('re')
      ->condition('expiration', REQUEST_TIME, '>')
      //->condition('role', array('authenticated'), 'NOT IN') // @todo 这个条件需要再考虑一下
      ->condition('type', $types, 'IN')
      ->condition('rank', array(1), 'NOT IN'); //设置已查看时等于1
    return $query->orderBy('id', 'DESC')
                 ->execute()
                 ->fetchAll();
  }


  public function render() {
    $query_types = \Drupal::service('operation.reminder')->getReminderAllTypes();
    if (empty($query_types))
      return new JsonResponse('nodata');

    $data = $this->load();
    $reminds = array();
    foreach ($data as $row) {
      if (array_key_exists($row->type, $reminds)) {
        $reminds[$row->type]['num']++;
      } else {
        $reminds[$row->type]['name'] = $row->type;
        $reminds[$row->type]['num'] = 1;
      }
    }
    foreach ($query_types as $row) {
      $types[$row->type] = $row->description;
    }
    foreach ($reminds as $key => $val) {
      $tips[$key]['name'] = $types[$key];
      $tips[$key]['num'] = $val['num'];
    }
    if (empty($tips)) {
      $tips = 'nodata';
    }
    return new JsonResponse($tips);
  }

  public function typeSettings() {
    $list = OperationReminderTypeListBuild::createInstance(\Drupal::getContainer());
    return $list->render();
  }
}
