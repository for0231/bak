<?php

namespace Drupal\paypre;

use Drupal\Core\Database\Connection;

/**
 *
 */
class PaypreService {

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
   * 获取所有已通过审核并且没有生成过支付单的付款单数据。.
   */
  public function getAllAuditedPaypre() {
    $query = $this->database->select('paypre', 'pre')
      ->fields('pre');

    // @todo 添加条件识别没有生成过支付单的付款单数据。
    return $query->execute()->fetchAll();
  }

  /**
   * Paypre entity table save.
   *
   * @deprecated ##创建采购单 ajax.parts.pool.purchase.create
   */
  public function save($entity, $update = TRUE, $ids = []) {
    $part_id = 0;
    if ($update) {

    }
    else {
      try {
        $purchases = \Drupal::entityTypeManager()->getStorage('purchase')->loadMultiple($ids);

        $paypre_entity = \Drupal::entityTypeManager()->getStorage('paypre')->create([
          'cnos' => $ids,
        ]);
        $paypre_entity->save();

        $parts = [];
        foreach ($purchases as $purchase) {
          // @todo 创建付款单后，采购单的状态应该更新为付款审核
          // 付款单审核通过后，状态则为待付款
          $purchase->set('status', 10)->save();
          $pids = $purchase->get('pids');
          foreach ($pids as $pid) {
            $parts[$pid->entity->id()] = $pid->entity;
          }
        }
        \Drupal::service('part.partservice')->setFno($paypre_entity, $parts);
      }
      catch (\Exception $e) {
        error_log(print_r('payservice create', 1));
      }
    }

  }

  /**
   * @description 创建付款单
   *  使用配件ids创建付款单
   */
  public function create($title, $ids) {
    $purchases = \Drupal::entityTypeManager()->getStorage('purchase')->loadMultiple($ids);

    $paypre_entity = \Drupal::entityTypeManager()->getStorage('paypre')->create([
      'title' => $title,
      'cnos' => $ids,
    ]);
    $paypre_entity->save();

    $amount = 0;
    $cnos = $paypre_entity->get('cnos');
    foreach ($cnos as $cno) {
      $amount += \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity);
    }

    $paypre_entity->set('pre_amount', $amount)
      ->save();
    $parts = [];
    foreach ($purchases as $purchase) {
      // @todo 创建付款单后，采购单的状态应该更新为付款审核
      // 付款单审核通过后，状态则为待付款
      $purchase->set('status', 10)->save();
      $pids = $purchase->get('pids');
      foreach ($pids as $pid) {
        $parts[$pid->entity->id()] = $pid->entity;
      }
    }
    \Drupal::service('part.partservice')->setFno($paypre_entity, $parts);
  }

  /**
   * @description 为了创建付款单检查post的purchase id是否合法。
   *              1) 检查付款单的工单状态是否为已审核，已审核, 合法
   *              2) 检查付款单包含的币种是否一致，一致，合法
   *              3) 检查采购单包含的part是否已存在fno，存在，合法
   *              4) 检查采购单包含的part是否已存在pno，不存在，合法
   *
   * @return 1 合法
   *   0 不合法
   */
  public function checkPaypreStatusforPayproById($ids = NULL) {
    if (empty($ids) || !is_array($ids)) {
      return 0;
    }
    $paypres = \Drupal::entityTypeManager()->getStorage('paypre')->loadMultiple($ids);

    foreach ($paypres as $paypre) {
      // 未审核为0.
      // @todo 添加审核状态判定,目前默认为0
      $status_paypre = $paypre->get('status')->value;

      $ids = [];
      $cnos = $paypre->get('cnos');
      foreach ($cnos as $cno) {
        $pids = $cno->entity->get('pids');
        foreach ($pids as $pid) {
          $ids[$pid->entity->id()] = $pid->entity;
        }
      }
      // 4) 检查采购单包含的part是否已存在pno，不存在，合法.
      $status_part = \Drupal::service('part.partservice')->checkpnoStatusById($ids);
      // 2) 检查付款单包含的币种是否一致，一致，合法.
      $status_part_ftype = \Drupal::service('part.partservice')->checkftypeStatusById($ids);
      // 3) 检查采购单包含的part是否已存在fno，存在，合法.
      $status_part_fno = \Drupal::service('part.partservice')->checkfnoNoneExistStatusById($ids);

      $status = $status_paypre == 5 && $status_part && $status_part_ftype && $status_part_fno;
      if ($status) {
        return 1;
      }
      else {
        return 0;
      }
    }
  }

  /**
   * 获取付款单包含的所有part。.
   */
  public function getPartsInPaypre($entity) {
    $parts = [];
    $cnos = $entity->get('cnos');
    foreach ($cnos as $cno) {
      $pids = $cno->entity->get('pids');
      foreach ($pids as $pid) {
        $parts[$pid->entity->id()] = $pid->entity;
      }
    }

    return $parts;
  }

  /**
   * @description 获取付款单所包括的采购单的总价.
   */
  public function getPaypresAmount($paypre) {
    return $this->getPaypreAmount($paypre);
  }

  /**
   * @description 获取付款单所包括的采购单的总价.
   */
  public function getPaypreAmount($paypre) {
    $cnos = $paypre->get('cnos');
    $purchase_amount = 0;
    foreach ($cnos as $cno) {
      $purchase_amount += \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity);
    }

    return $purchase_amount;
  }

  /**
   * @description 当支付审批被拒绝时，更新付款单、采购单及配件相关状态为采购单-拒绝。
   */
  public function updatePaypreStatus4PayproOnRejectAction($entity_paypre) {
    // @todo paypre的状态
    $paypres = $entity_paypre->get('fnos');
    $pay = [];
    foreach ($paypres as $row) {
      $pay[] = $row->entity;
    }
    // paypro里面只包含了一个paypre实体，.
    $paypre = current($pay);
    $paypre->set('status', 20)
      ->save();

    $purchases = $paypre->get('cnos');
    foreach ($purchases as $purchase) {
      // 1. 采购单状态改为待付款.
      $purchase->entity
      // 采购单状态8: 表示支付单已拒绝.
        ->set('status', 8)
      // 通用审批模块状态，2：已拒绝.
        ->set('audit', 2)
        ->save();
      \Drupal::service('part.partservice')->updatePartStatus($purchase->entity);
    }
  }

  /**
   * @description 支付单审批完成时
   * 1. 更新支付单为已完成
   * 2. 更新付款单为已完成
   * 3. 更新配件的采购单为已完成
   *
   * @todo 下面这个注解有问题，需要再审核一下 setAuditAllSuccess里面的updatePurchaseStatus4Paypre方法。
   *  1. 修改所有包含付款单的状态为待支付 ****
   *  2. 修改所包含的采购单的状态为待付款
   *  3. 修改采购单所包含的配件的采购状态为待付款
   * @param  $entity_paypre
   */
  public function updatePaypreStatus4Paypro($entity_paypro) {
    $fnos = $entity_paypro->get('fnos');
    foreach ($fnos as $fno) {
      $entity_paypre = $fno->entity;
      // 1. 先设置当前支付单包含的付款单的状态为部分支付.
      $entity_paypre->set('status', 8)->save();

      // 2.
      // 更新付款单包含的各采购单状态-常规设置为部分支付。
      // 待验证后再设置为支付完成.
      \Drupal::service('purchase.purchaseservice')->updatePurchaseAfterPaypreByPay($entity_paypre);
    }
  }

  /**
   * @description 获取当前付款单的预付金额值。
   * @param Entity $paypre
   *   1. 查出当前付款单包含的采购单在哪些付款单里面；
   *   2. 采购单总金额减去其他付款单里面的支付金额；= 当前付款单的预付金额.
   */
  public function getPayprePreamount($paypre) {
    $fnos = $this->getfnosbySamecnos($paypre);

    // 获取其他付款单nos.
    $fids = array_diff($fnos, [$paypre->id()]);

    // 根据包含相同采购单的付款单获取总的已付金额.
    $total_pre_amount = $this->getTotalamount($fids);

    return $total_pre_amount;
  }

  /**
   * @description 获取与当前付款包含的采购单信息相同的所有付款单信息
   * @param Entity $paypre
   */
  public function getfnosbySamecnos($paypre) {
    // 查询当前付款单里面的采购单号.
    $current_cnos = $this->getCnosfromPaypre($paypre);

    // 查询其他付款单里面的采购单号.
    $allpaypres = $this->getAllPaypreEntities($paypre->get('iscancel')->value);

    $other_cnos = [];
    foreach ($allpaypres as $dependence_paypre) {
      $other_cnos[$dependence_paypre->id()] = $this->getCnosfromPaypre($dependence_paypre);
    }

    // 取出包含相同的采购单的所有付款单.
    $ids = $this->getIdsBySameCnosfromPaypres($other_cnos, $current_cnos);

    return $ids;
  }

  /**
   * @description 获取总的已付金额.
   * @param ids $ids
   */
  public function getTotalamount($ids) {
    $amount = 0;
    if (empty($ids)) {
      return $amount;
    }
    $entities_paypres = \Drupal::entityTypeManager()->getStorage('paypre')->loadMultiple($ids);

    foreach ($entities_paypres as $entities_paypre) {
      $amount += $entities_paypre->get('amount')->value;
    }

    return $amount;
  }

  /**
   * @description 取出包含相同的采购单的所有付款单.
   */
  public function getIdsBySameCnosfromPaypres($other_cnos, $current_cnos) {
    $ids = [];

    foreach ($other_cnos as $key => $cno) {
      $result = array_diff($cno, $current_cnos);
      // 查询包含相同的采购单的付款单集合.
      if (empty($result)) {
        $ids[] = $key;
      }
    }
    return $ids;
  }

  /**
   * @description 查询所有付款单信息
   */
  public function getAllPaypreEntities($iscancel = 0) {
    // $entities_paypre = \Drupal::entityTypeManager()->getStorage('paypre')->loadMultiple();
    $storage = \Drupal::entityManager()->getStorage('paypre');
    $storage_query = $storage->getQuery();
    $storage_query->condition('iscancel', $iscancel);
    $ids = $storage_query->execute();
    $entities_paypre = $storage->loadMultiple($ids);

    $result = [];
    foreach ($entities_paypre as $entity_paypre) {
      $result[$entity_paypre->id()] = $entity_paypre;
    }

    return $result;
  }

  /**
   * @description 获取付款单的采购单数组.
   */
  public function getCnosfromPaypre($paypre) {
    $cnos = $paypre->get('cnos');
    $cnoids = [];
    foreach ($cnos as $cno) {
      $cnoids[] = $cno->entity->id();
    }

    return $cnoids;
  }

  /**
   * @description 验证付款单是否还需要再创建新的复本。
   */
  public function checkPaypreStatusforDuplicate($entity_paypre) {
    $total_amount = \Drupal::service('paypre.paypreservice')->getPaypresAmount($entity_paypre);
    $same_fnos = \Drupal::service('paypre.paypreservice')->getfnosbySamecnos($entity_paypre);

    $entity_paypres = paypre_load_multiple($same_fnos);

    $total_pre_amount = 0;
    foreach ($entity_paypres as $entity_paypre) {
      $total_pre_amount += $entity_paypre->get('amount')->value;
    }

    return $total_pre_amount < $total_amount ? 1 : 0;
  }

  /**
   * @description 获取指定配件被引用的付款单数据.
   */
  public function getPaypresByPartId($part_id) {
    $ret = [];
    $paypres = $this->getAllPaypreEntities();
    foreach ($paypres as $paypre) {
      $cnos = $paypre->get('cnos');
      foreach ($cnos as $cno) {
        $purchase_ids[$cno->entity->id()] = $cno->entity->id();
      }
      list(, $pids) = \Drupal::service('purchase.purchaseservice')->getPurchaseParts($purchase_ids);
      if (array_key_exists($part_id, $pids)) {
        $ret[$part_id][$paypre->id()] = $paypre->id();
      }

    }
    return $ret;
  }

  /**
   * @description 支付单取消时，付款单数据回归到付款池中.
   * @param Entity $paypro
   */
  public function setPayprefallbackfromPaypro($paypro) {
    $fnos = $paypro->get('fnos');
    foreach ($fnos as $fno) {
      $fno->entity->set('status', 5)
        ->save();
    }
  }

  /**
   * @description 模拟定时任务.
   */
  public function updateCronStatus() {
    $storage = \Drupal::entityManager()->getStorage('paypre');
    $storage_query = $storage->getQuery();

    // 1. 查询所有状态为部分付款-8 的付款单。.
    $storage_query->condition('status', 8);
    $storage_query->condition('iscancel', 0);
    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $paypre_ids = $relate_fnos = [];
    // 2. 轮询所有付款单，查看是否有分批申请支付的情况。.
    foreach ($entities as $entity) {
      $total_amount = $this->getPaypresAmount($entity);
      // 当前付款单的付款金额.
      $amount = $entity->get('amount')->value;

      // 当前未分批付款.
      if ($total_amount == $amount) {
        $paypre_ids[] = $entity->id();
      }
      // 当前付款单包含的采购金额大于当前付款的支付金额时
      // 说明是分批申请的付款单.
      if ($total_amount > $amount) {
        // 查询所有包含相同采购单的付款单.
        $same_fnos = $this->getfnosbySamecnos($entity);
        // 获取所有含有相同采购单的付款单的付款金额总和。.
        $same_fnos_total_amount = $this->getTotalamount($same_fnos);
        // 1. 含有相同采购单的付款单全部已支付。
        // 2. 并验证所有付款单是否都为部分支付状态.
        if ($same_fnos_total_amount == $total_amount && $this->getStatusValidateByPaypreIds($same_fnos)) {
          $relate_fnos = $same_fnos;
        }
      }
      $paypre_ids = array_merge($paypre_ids, $relate_fnos);
    }

    // 查出了所有都部分付款的付款单。.
    return $paypre_ids;
  }

  /**
   * @description 验证付款单是否全部处于部分付款状态.
   * @param $ids
   *   paypre ids
   * @return 0 验证失败
   *   1 验证成功
   */
  public function getStatusValidateByPaypreIds($ids) {
    $entities = paypre_load_multiple($ids);
    foreach ($entities as $entity) {
      if ($entity->get('status')->value != 8) {
        return 0;
      }
    }
    return 1;
  }

  /**
   * @description update付款单支付完成状态.
   * @param $ids
   *   paypre ids.
   * @param paypre $status
   *   当前接收为8.
   */
  public function updatePaypreStatus($ids, $status) {
    if (is_null($status)) {
      return 0;
    }
    $entity_paypres = paypre_load_multiple($ids);
    foreach ($entity_paypres as $paypre) {
      $paypre->set('status', $status)
        ->save();
      // 同步更新付款单包含的采购单状态。.
      $cnos = $this->getCnosfromPaypre($paypre);
      \Drupal::service('purchase.purchaseservice')->updatePurchaseStatus($cnos, 13);
    }
    return 1;
  }

  /**
   * @description 重定义各种单据的编码的计数规则
   */
  public function getIkNumberCounterCode() {
    $config = \Drupal::configFactory()->getEditable('paypre.settings');

    $counter = empty($config->get('start')) ? 100 : $config->get('start');
    $next_counter = ++$counter;
    $config->set('start', $next_counter);
    $config->save();
    $formatter = empty($config->get('formatter')) ? 'Ymd' : $config->get('formatter');
    $new_no = date($formatter, time()) . $next_counter;
    return $new_no;
  }

  /**
   * @description 构建ajax数据.
   */
  public function getAjaxDataCollection($modules) {
    $entities = [];
    $i = 0;
    foreach ($modules as $module) {
      $datas[] = $this->getModuleUnsolveData($module);
    }
    $entities = [];
    $index = 0;
    foreach ($datas as $data) {
      foreach ($data as $row) {
        $entities[$index] = $row;
        $index++;
      }
    }

    return [$entities];
  }

  /**
   * @description 获取所有模块内的待处理数据.
   */
  public function getModuleUnsolveData($module = NULL) {
    if (empty($module)) {
      return [];
    }
    $storage = \Drupal::entityManager()->getStorage($module);
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->sort('created', 'DESC');

    $this->customizeCondition($entity_query, $module);

    $entity_query->sort('status', 'ASC');
    $entity_query->pager(500);

    $ids = $entity_query->execute();
    return $storage->loadMultiple($ids);
  }

  /**
   * @description 构建自定义查询条件-需求单模型
   * @todo 查询条件待进一步补充完善。
   */
  protected function customizeCondition($entity_query, $module) {
    // 1. 待我批准的.
    // @description 查询需求单中所有处理待审批状态的需求单
    // @description 查询采购单中所有处理待审批状态的采购单
    // @description 查询付款单中所有处理待审批状态的付款单
    // @description 查询支付单中所有处理待审批状态的支付单
    $ids = \Drupal::service('requirement.requirementservice')->getEntityIdsforCurrentUserbyEntityType($module);
    if (!empty($ids)) {
      $entity_query->condition('id', $ids, 'IN');
    }
    // 2. 待我处理的需求单模型.
    // @description 所有个人创建的并且状态为未审批的
    // 3. 自己的，未审批，审批中的单据.
    else {
      // 支付单未处理状态.
      $entity_query->condition('status', [0, 2], 'IN');
      // 支付单未处理状态.
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    }
  }

}
