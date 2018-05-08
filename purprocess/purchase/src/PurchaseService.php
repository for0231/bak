<?php

namespace Drupal\purchase;

use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\SafeMarkup;

/**
 *
 */
class PurchaseService {

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
   * Part entity table save.
   */
  public function save($entity, $update = TRUE, $ids = []) {
    $part_id = 0;
    if ($update) {
      $parts = $entity->get('pids');

      $pids = [];

      foreach ($parts as $part) {
        $row = $part->entity;
        if ($row) {
          $pids[$row->id()] = $row->id();
        }
      }
      $entity->set('pids', $pids + $ids)->save();

    }
    else {
      $purchase_entity = \Drupal::entityTypeManager()->getStorage('purchase')->create([
        'pids' => $ids,
      ]);
      $purchase_entity->save();
      \Drupal::service('part.partservice')->setCno($purchase_entity, $ids);
    }

  }

  /**
   * @description 创建采购单
   *  使用配件ids创建采购单
   */
  public function create($title, $pids) {
    $purchase_entity = \Drupal::entityTypeManager()->getStorage('purchase')->create([
      'title' => $title,
      'pids' => $pids,
    ]);
    $purchase_entity->save();
    \Drupal::service('part.partservice')->setCno($purchase_entity, $pids);
  }

  /**
   * 采购单编辑时，配件列表的部分数据被删除时，操作此方法。.
   */
  public function deletePartFromPurchaseById($purchase, $ids) {
    if (is_numeric($purchase)) {
      $storage = \Drupal::entityTypeManager()->getStorage('purchase');
      $purchase = $storage->load($purchase);

      $pids = $purchase->get('pids');
      $old_pids = [];
      foreach ($pids as $pid) {
        $old_pids[$pid->entity->id()] = $pid->entity->id();
      }
      $new_pids = array_diff($old_pids, $ids);
      $purchase->set('pids', $new_pids)->save();
      error_log(print_r('success', 1));
    }
  }

  /**
   * @description 检查post的purchase id是否合法。
   *              检查是否符合创建采购单的基本要求---采购单的工单状态是否为0 --
   *              未审批状态。
   */
  public function checkPurchaseStatusById($ids = NULL) {
    if (empty($ids)) {
      return 0;
    }
    if (is_array($ids)) {
      foreach ($ids as $id) {
        $purchase = \Drupal::entityTypeManager()->getStorage('purchase')->load($id);
        if ($purchase->get('status')->value == 0) {
          return 1;
        }
        else {
          return 0;
        }
      }
    }
    else {
      $purchase = \Drupal::entityTypeManager()->getStorage('purchase')->load($ids);
      if ($purchase->get('status')->value == 0) {
        return 1;
      }
      else {
        return 0;
      }
    }
  }

  /**
   * 获取指定的purchase的所有parts。.
   */
  public function getPurchaseParts($ids = []) {
    if (empty($ids)) {
      return;
    }
    $purchase_ids = [];
    try {
      $purchases = \Drupal::entityTypeManager()->getStorage('purchase')->loadMultiple($ids);

      foreach ($purchases as $purchase) {
        // $purchase_ids[$purchase->id()][$part->id()] = $part->id();
        $pids = $purchase->get('pids');
        foreach ($pids as $pid) {
          $part = $pid->entity;
          $purchase_ids[$part->id()] = $part;
        }
      }
    }
    catch (\Exception $e) {
      error_log(print_r($e, 1));
    }

    return [$purchases, $purchase_ids];
  }

  /**
   * 获取指定的purchase的所有parts。.
   */
  public function getPartsIdsByPurchaseEntity($entity_purchases = []) {
    if (empty($entity_purchases)) {
      return;
    }
    $purchase_ids = [];
    foreach ($entity_purchases as $purchase) {
      try {
        $pids = $purchase->get('pids');
        foreach ($pids as $pid) {
          $part_ids[] = $pid->entity->id();
        }
      }
      catch (\Exception $e) {
        error_log(print_r($e, 1));
      }
    }

    return $part_ids;
  }

  /**
   * @description 为了创建付款单检查post的purchase id是否合法。
   *              1) 检查采购单的工单状态是否为已审核，已审核, 合法
   *              2) 检查采购单包含的part是否已存在fno，不存在，合法
   *              3) 检查采购单包含的part的币种是否一致，一致，合法
   *              4) 检查采购单包含的part的供应商是否一致，一致，合法
   *
   * @return 1 合法
   *   0 不合法
   */
  public function checkPurchaseStatusforPaypreById($ids = NULL) {
    if (empty($ids) || !is_array($ids)) {
      return 0;
    }
    $purchases = \Drupal::entityTypeManager()->getStorage('purchase')->loadMultiple($ids);

    foreach ($purchases as $purchase) {
      // 未审核为0.
      $status_purchase = $purchase->get('status')->value;

      $ids = [];
      $pids = $purchase->get('pids');
      foreach ($pids as $pid) {
        $ids[$pid->entity->id()] = $pid->entity;
      }
      $status_part = \Drupal::service('part.partservice')->checkfnoStatusById($ids);
      $status_part_ftype = \Drupal::service('part.partservice')->checkftypeStatusById($ids);
      $status_part_supply = \Drupal::service('part.partservice')->checkSupplyComStatusById($ids);
      if ($status_purchase == 5 && $status_part && $status_part_ftype && $status_part_supply) {
        return 1;
      }
      else {
        return 0;
      }
    }
  }

  /**
   * 获取所有的purchase实体.
   *
   * @param array $ids
   */
  public function getPurchases($ids = []) {
    $storage = \Drupal::entityManager()->getStorage('purchase');
    $purchases = $storage->loadMultiple($ids);

    return $purchases;
  }

  /**
   * @description 1. 修改所包含的采购单的状态为待付款
   *              2. 修改采购单所包含的配件的采购状态为待付款
   * @param  $entity_paypre
   */
  public function updatePurchaseStatus4Paypre($entity_paypre) {
    $purchases = $entity_paypre->get('cnos');
    foreach ($purchases as $purchase) {
      // 1. 采购单状态改为待付款.
      $purchase->entity
      // 采购单状态4: 表示待付款.
        ->set('status', 4)
        ->save();
      \Drupal::service('part.partservice')->updatePartStatus($purchase->entity);
    }
  }

  /**
   * @description 付款单支付完成后，更新对应的采购单的状态。
   */
  public function updatePurchaseAfterPaypreByPay($entity_paypre) {
    $purchases = $entity_paypre->get('cnos');
    foreach ($purchases as $purchase) {
      // 1. 采购单状态改为待付款.
      $purchase->entity
      // 采购单状态11: 表示已部分支付.
        ->set('status', 11)
        ->save();
      \Drupal::service('part.partservice')->updatePartStatus($purchase->entity);
    }
  }

  /**
   * @description 付款单被拒绝后，更新采购单及配件相关状态为采购单-拒绝。
   */
  public function updatePurchaseStatus4PaypreOnRejectAction($entity_paypre) {
    $purchases = $entity_paypre->get('cnos');
    foreach ($purchases as $purchase) {
      // 1. 采购单状态改为待付款.
      $purchase->entity
      // 采购单状态6: 表示采购单被拒绝.
        ->set('status', 6)
      // 通用审批模块状态，2：已拒绝.
        ->set('audit', 2)
        ->save();
      \Drupal::service('part.partservice')->updatePartStatus($purchase);
    }
  }

  /**
   * @description 获取采购单的物品总价
   * @param purchase $entity
   */
  public function getPurchaseAmountPrice($entity) {
    $parts = $entity->get('pids');
    foreach ($parts as $part) {
      $entity_parts[] = $part->entity;
    }
    $price = \Drupal::service('part.partservice')->getAmount($entity_parts);
    return $price;
  }

  /**
   *
   */
  public function getPurchaseAmount($entity) {
    return $this->getPurchaseAmountPrice($entity);
  }

  /**
   *
   */
  public function getPurchaseftype($entity) {
    $ftype = '';
    $parts = $entity->get('pids');
    foreach ($parts as $part) {
      $ftype = $part->entity->get('ftype')->target_id;
      break;
    }

    return $ftype;
  }

  /**
   *
   */
  public function updatePurchaseStatusforComplete($entity) {
    $entity->set('status', 14)
      ->save();
    // 更新配件状态及物流状态.
    \Drupal::service('part.partservice')->updatePartStatus($entity, 20);
  }

  /**
   * @description 付款单取消时，采购单数据回归到采购池中.
   * @param Entity $paypro
   */
  public function setPurchasefallbackfromPaypre($paypre) {
    $cnos = $paypre->get('cnos');
    foreach ($cnos as $cno) {
      $cno->entity->set('status', 5)
        ->save();
    }
  }

  /**
   * @description 当付款单金额完成支付时update purchase status
   * @param $ids
   *   purchase ids.
   * @param  $status
   *   当前接收13.
   */
  public function updatePurchaseStatus($ids, $status) {
    if (is_null($status)) {
      return 0;
    }
    $entity_purchases = purchase_load_multiple($ids);
    foreach ($entity_purchases as $purchase) {
      $purchase->set('status', $status)
        ->save();
      // 更新配件的状态.
      \Drupal::service('part.partservice')->updatePartStatus($purchase);
    }
    return 1;
  }

  /**
   * @description 获取完全支付的采购单包含的配件id.
   * 默认查询当前这月的所有采购单数据,否则查询指定时间段内的数据。
   */
  public function getPidsByCompletePurchaseStatus() {
    $storage = \Drupal::entityManager()->getStorage('purchase');
    $storage_query = $storage->getQuery();

    $begin = $_SESSION['paypro_aggretotal_filter']['begin'];
    $end = $_SESSION['paypro_aggretotal_filter']['end'];
    if (!empty($begin) && !empty($end)) {
      $group = $storage_query->andConditionGroup()
        ->condition('created', strtotime($begin), '>')
        ->condition('created', strtotime($end), '<');
      $storage_query->condition($group);
    }
    else {
      $group = $storage_query->andConditionGroup()
        ->condition('created', strtotime(date('Y-m-d', time())), '>')
        ->condition('created', strtotime(strtotime('next month')), '<');
      $storage_query->condition($group);
    }
    $ids = $storage_query->execute();
    $entities = $storage->loadMultiple($ids);
    $parts = [];
    foreach ($entities as $entity) {
      $pids = $entity->get('pids');
      foreach ($pids as $pid) {
        $parts[$pid->entity->id()] = $pid->entity->id();
      }
    }

    return $parts;
  }

  /**
   * @description 重定义各种单据的编码的计数规则
   */
  public function getIkNumberCounterCode() {
    $config = \Drupal::configFactory()->getEditable('purchase.settings');

    $counter = empty($config->get('start')) ? 100 : $config->get('start');
    $next_counter = ++$counter;
    $config->set('start', $next_counter);
    $config->save();
    $formatter = empty($config->get('formatter')) ? 'Ymd' : $config->get('formatter');
    $new_no = date($formatter, time()) . $next_counter;
    return $new_no;
  }

  /**
   * @description 获取该采购单内配件的总数量.
   * @param $entity
   *   purchase entity.
   */
  public function getPurchaseofPartsNumberAccount($entity) {
    $pids = $entity->get('pids');
    $num = 0;
    foreach ($pids as $pid) {
      $num += $pid->entity->get('num')->value;
    }

    return $num;
  }

  /**
   *
   */
  public function setFontColor($data, $color) {
    return SafeMarkup::format("<font color=" . $color . ">" . $data . "</font>", []);
  }

}
