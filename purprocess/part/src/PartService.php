<?php

namespace Drupal\part;

use Drupal\Core\Database\Connection;

/**
 *
 */
class PartService {

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
  public function save($entity, $update = TRUE) {

  }

  /**
   * 根据请求，如果请求的数量小于配件实体的数量，则拆分成多个配件实体.
   */
  public function setSplitEntity($input) {
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($input['id']);
      if ($input['ccsnum'] > 0 && $input['ccsnum'] < $part->get('num')->value) {
        $new_part = $part->createDuplicate();
        $new_part
          ->set('num', $input['ccsnum'])
          ->save();
        $part
          ->set('num', $part->get('num')->value - $input['ccsnum'])
          ->save();

        // 保存新的part.
        \Drupal::service('requirement.requirementservice')->save($new_part, $update = TRUE);
      }
    }
    catch (\Exception $e) {
      error_log(print_r($e, 1));
    }
  }

  /**
   * Update save_status.
   *
   * @param requirement $entity
   *
   * @description save_status
   *    - 0: 表示由part页添加的，
   *    - 1: 表示由需求配件页面正常添加。
   *
   *    已移植
   */
  public function setSaveStatus($entity) {
    // Status == 0, 需求单状态为未审批.
    if ($entity->get('status')->value == 0) {
      $pids = $entity->get('pids');
      foreach ($pids as $row) {
        $row->entity->set('save_status', 1)->save();
      }
    }
    else {
      // Status != 0, 需求单为其他状态.
      // @description 此时无法再添加新的需求配件
      // @todo 待处理
    }
  }

  /**
   * @description
   * 需求页面编辑时，添加配件时需要对requirement实体进行相应的操作。
   */
  public function savePartsrno($part, $rid) {
    if (\Drupal::moduleHandler()->moduleExists('requirement')) {
      $requirement = \Drupal::entityTypeManager()->getStorage('requirement')->load($rid);
      if ($requirement) {
        $part->set('rno', $requirement->id())
        // 需求交付时间.
          ->set('requiredate', $requirement->get('requiredate')->value)
          ->save();
        return 1;
      }
      else {
        return 0;
      }
    }
    else {
      return 0;
    }
  }

  /**
   * @description
   */
  public function savePartscno($purchase, $ids = []) {
    if (empty($ids)) {
      return 0;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('part');
    $parts = $storage->loadMultiple($ids);
    foreach ($parts as $part) {
      $part->set('cno', $purchase->id())->save();
    }
    return 1;
  }

  /**
   * @description
   */
  public function cancelPartscno($purchase, $ids = []) {
    if (empty($ids)) {
      return 0;
    }
    $storage = \Drupal::entityTypeManager()->getStorage('part');
    $parts = $storage->loadMultiple($ids);
    foreach ($parts as $part) {
      $part->set('cno', 0)->save();
    }
    return 1;
  }

  /**
   * @description 检查post的配件id是否合法。
   *              检查是否符合创建采购单的基本要求-rno是否为0，0 合法。
   */
  public function checkPartStatusById($ids = NULL) {
    foreach ($ids as $id) {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      if (!empty($part->get('rno')->value) && ($part->get('cno')->value == 0)) {
        return 1;
      }
      else {
        return 0;
      }
    }

  }

  /**
   * @description 检查post的配件id是否合法。
   *              检查是否符合创建付款单的基本要求。
   */
  public function checkfnoStatusById($parts = []) {
    foreach ($parts as $part) {
      if ($part->get('fno')->value) {
        return 0;
      }
    }
    return 1;
  }

  /**
   * @description 检查post的配件id是否合法。
   *              检查是否符合创建支付单的基本要求。
   */
  public function checkfnoNoneExistStatusById($parts = []) {
    foreach ($parts as $part) {
      if (!$part->get('fno')->value) {
        error_log(print_r($part->id(), 1));
        return 0;
      }
    }
    return 1;
  }

  /**
   * @description 检查post的配件id是否合法。
   *              检查币种是否符合创建付款单的基本要求。
   */
  public function checkftypeStatusById($parts = []) {
    $ftypes = [];
    foreach ($parts as $part) {
      if (empty($part->get('ftype')->target_id)) {
        return 0;
      }
      $ftypes[$part->id()] = $part->get('ftype')->entity->id();
    }
    if (count(array_unique($ftypes)) > 1) {
      return 0;
    }
    else {
      return 1;
    }
  }

  /**
   * @description 检查post的配件id是否合法。
   *              检查供应商是否符合创建付款单的基本要求。
   */
  public function checkSupplyComStatusById($parts = []) {
    $coms = [];
    foreach ($parts as $part) {
      if (!$part->get('supply_id')->target_id) {
        return 0;
      }
      $coms[$part->id()] = $part->get('supply_id')->target_id;
    }

    if (count(array_unique($coms)) > 1) {
      return 0;
    }
    else {
      return 1;
    }
  }

  /**
   * @description 根据purchase的ID,更新part的cno.
   */
  public function setCno($entity, $ids = []) {
    $storage = \Drupal::entityManager()->getStorage('part');
    $parts = $storage->loadMultiple($ids);
    foreach ($parts as $part) {
      $part->set('cno', $entity->id())->save();
    }
  }

  /**
   * @description 根据paypre的ID,更新part的fno.
   * @param  $parts
   */
  public function setFno($entity, $parts = []) {
    foreach ($parts as $part) {
      $part->set('fno', $entity->id())->save();
      $pftype = $part->get('ftype')->target_id;
    }
    // 额外的在purchase里面设置ftype的值.
    $ftype = $pftype;
    $entity->set('ftype', $ftype)->save();
  }

  /**
   * @description 根据paypro的ID,更新part的pno.
   * @param paypro $entity
   * @param  $parts
   */
  public function setPno($entity, $parts = []) {
    $p = [];
    foreach ($parts as $part) {
      $part->set('pno', $entity->id())->save();
      $p['ftype'][] = $part->get('ftype')->target_id;
    }
    // 额外的在paypro里面设置ftype的值.
    $ftype_arr = array_unique($p['ftype']);
    $ftype = current($ftype_arr);
    $entity->set('ftype', $ftype)->save();
  }

  /**
   * Delete un pool parts.
   *
   * @param $ids
   *   array 将要删除的配件ids
   *
   * @todo 函数应该改为getRequirementParts
   * @code
   * \Drupal::service('purchase.purchaseservice')->getPurchaseParts($ids);
   * @endcode
   */
  public function delete($ids = []) {
    if (empty($ids)) {
      return;
    }
    $requirement_ids = [];
    try {
      $parts = \Drupal::entityTypeManager()->getStorage('part')->loadMultiple($ids);

      foreach ($parts as $part) {
        $requirement_ids[$part->get('rno')->value][$part->id()] = $part->id();
      }
    }
    catch (\Exception $e) {
      error_log(print_r('un pool delete', 1));
      error_log(print_r($e, 1));
    }

    return $requirement_ids;
  }

  /**
   * Update plandate in part
   * 更新part的预计交付时间.
   */
  public function setPlandate($id, $time) {
    if (empty($id)) {
      return 0;
    }
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('plandate', $time)->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase edit - plandate', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * Update plandate in part
   * 更新part的供应商id.
   */
  public function setSupplyCompany($id, $sid) {
    if (empty($id)) {
      return 0;
    }
    $term = taxonomy_term_load($sid);
    if (!$term->id()) {
      error_log(print_r('ajax purchase edit - supply', 1));
      return 0;
    }
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('supply_id', $sid)->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase edit - supply', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * Update unitprice in part.
   * 更新part的单价.
   */
  public function setUnitprice($id, $price) {
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('unitprice', $price)->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase edit - unitprice', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * 更新配件的物流信息.
   */
  public function setShipSupplyId($id, $ship_id) {
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('ship_supply_id', $ship_id)->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase detail - ship id', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * 更新配件的物流信息.
   */
  public function setShipSupplyNo($id, $ship_no) {
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('ship_supply_no', $ship_no)
        ->set('re_ship_status', 5)
        ->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase detail - ship no', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * 更新配件的物流信息.
   */
  public function setWuliufeeNo($id, $wuliufee) {
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('wuliufee', $wuliufee)
        ->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase detail - wuliufee', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * Update ftype in part.
   * 更新part的币种.
   */
  public function setFtype($id, $type) {
    try {
      $part = \Drupal::entityTypeManager()->getStorage('part')->load($id);
      $part->set('ftype', $type)->save();
      return 1;
    }
    catch (\Exception $e) {
      error_log(print_r('ajax purchase edit - ftype', 1));
      error_log(print_r($e, 1));
      return 0;
    }
  }

  /**
   * @description 统计配件数组的物流总费用.
   * @param $parts
   *   entity array.
   */
  public function getWuliufee($parts) {
    $amount = 0;
    if (is_array($parts)) {
      foreach ($parts as $part) {
        $amount += $this->getSinglePartWuliufee($part);
      }
    }
    else {
      $amount = $this->getSinglePartWuliufee($part);
    }

    return $amount;
  }

  /**
   * @description 统计单个配件的物流费用.
   * @param $part
   *   part entity.
   */
  public function getSinglePartWuliufee($part) {
    return isset($part->get('wuliufee')->value) ? $part->get('wuliufee')->value : 0;
  }

  /**
   * 单价，数量和物流费.
   */
  public function getAmount($parts) {
    $amount = 0;
    if (empty($parts)) {
      return $amount;
    }
    if (is_array($parts)) {
      foreach ($parts as $part) {
        $amount += $this->getSinglePartAmount($part);
      }
    }
    else {
      $amount = $this->getSinglePartAmount($part);
    }

    return $amount;
  }

  /**
   *
   */
  public function getSinglePartAmount($part) {
    $amount = 0;
    $unitprice = $part->get('unitprice')->value;
    $num = $part->get('num')->value;
    $amount = $unitprice * $num + $this->getSinglePartWuliufee($part);

    return $amount;
  }

  /**
   * @description 检查post的配件id是否合法。
   *              检查是否符合创建支付单的基本要求。
   *
   * @todo 配件为克隆单时，支付单已存在，
   *       则下面的判断条件不会成立，考虑是否删除该条件判断。
   */
  public function checkpnoStatusById($parts = []) {
    /*
    foreach ($parts as $part) {
    if ($part->get('pno')->value) {
    return 0;
    }
    }*/
    return 1;
  }

  /**
   * 当需求单状态变更时，修改对应的配件的状态
   * 包括审批状态，工单状态.
   *
   * @param $entity
   */
  public function updatePartStatus($entity, $ship_status = 0) {
    $entity_type = $entity->getEntityTypeId();

    switch ($entity_type) {
      // 为需求单时，发起审核按钮会操作以下动作.
      case 'requirement':
        $pids = $entity->get('pids');
        foreach ($pids as $pid) {
          $pid->entity
            ->set('re_status', $entity->get('status')->value)
            ->set('re_audit', $entity->get('audit')->value)
            ->save();
        }
        break;

      // 为采购单时，发起审核按钮会操作以下动作.
      case 'purchase':
        $pids = $entity->get('pids');
        // @descrition
        // 1. 其他
        // 2. 付款单被拒绝时，修改采购单的状态，并影响配件的状态
        // 3. 支付单被拒绝时，修改采购单的状态，并影响配件的状态
        // @code
        //  2. function updatePurchaseStatus4PaypreOnRejectAction()
        //  3. function updatePaypreStatus4PayproOnRejectAction()
        // @endcode
        foreach ($pids as $pid) {
          $pid->entity
            ->set('ch_status', $entity->get('status')->value)
            ->set('ch_audit', $entity->get('audit')->value)
            ->save();
          if ($ship_status == 20) {
            $pid->entity
              ->set('re_ship_status', 20)
              ->save();
          }
        }
        break;

      // @todo为付款单时，发起审核按钮会操作以下动作
      // 当付款单发起审核时，修改配件的状态
      // 这涉及采购单及需求单的相关状态
      // 1. 修改采购单及需求单包含的配件的状态
      // 2. 检测采购单或需求单包含的配件是否全部处理，
      //    如果是，则修改对应的需求单或采购单的状态
      case 'paypre':
        // paypre模式下不包含任何配件信息.
        break;

      case 'paypro':
        // 支付单提交审批时不需要处理各种状态. @todo 待追加.
        error_log(print_r('update part status for paypro', 1));
        break;

    }
  }

  /**
   * @description 付款单审批全部同意后，传入的付款单实体，修改对应的配件状态.
   * purchase在part上的对应状态.
   */
  public function setPartStatus4Paypre($entity) {
    // 保存配件的对应实体相关状态
    // 比如当前entity为requirement时，修改re_status, re_audit
    // 当entity为purchase时，修改ch_status, ch_audit
    // 当entity为paypre时，无.
    switch ($entity->getEntityTypeId()) {
      case 'paypre':
        $parts = \Drupal::service('paypre.paypreservice')->getPartsInPaypre($entity);;
        foreach ($parts as $part) {
          // 付款单所属的配件状态更新为待付款状态.
          // @todo 付款单全部审批同意时.
          $part->entity
          // ->set('re_status', 6) //  配件的需求状态，此时保持不变
          // purchase: 4 待付款.
            ->set('ch_status', 4)
            ->save();
        }
        break;
    }
  }

  /**
   * @description 获取配件总数
   * @param part $entity_array
   * @param $status
   *   0: 不考虑状态，
   * @return $num 配件总数
   */
  public function getSumPartNum($entity_array, $status = 1) {
    $num = 0;
    foreach ($entity_array as $entity) {
      switch ($status) {
        // 2: 已完成.
        case 2:
          // 14 采购已完成@todo 可能会加其他状态.
          if (in_array($entity->get('ch_status')->value, [14])) {
            $num += $entity->get('num')->value;
          }
          break;

        // 3: 采购中.
        case 3:
          if (in_array($entity->get('ch_status')->value, [4, 5, 6, 7, 8, 9, 10, 12, 13])) {
            $num += $entity->get('num')->value;
          }
          break;

        // 4: 未采购.
        case 4:
          if (in_array($entity->get('ch_status')->value, [0, 2])) {
            $num += $entity->get('num')->value;
          }
          break;

        default:
          $num += $entity->get('num')->value;
      }
    }
    return $num;
  }

  /**
   * @description 获取百分率.
   * @param  $entity_array
   * @param 1 $status
   *   2:超时.
   */
  public function getPercent($entity_array, $status = 1) {
    $num = 0;
    foreach ($entity_array as $entity) {
      switch ($status) {
        case 1:
          if ($entity->get('requiredate')->value >= $entity->get('plandate')->value) {
            $num += $entity->get('num')->value;
          }
          break;

        case 2:
          if ($entity->get('requiredate')->value < $entity->get('plandate')->value) {
            $num += $entity->get('num')->value;
          }
          break;
      }
    }
    $total = \Drupal::service('part.partservice')->getSumPartNum($entity_array);
    $percent = round($num / $total, 4);
    return $percent * 100 . '%';
  }

  /**
   * @description 获取配件的最早时间或最晚时间
   * @param  $entity_array
   * @param 1 $status
   *   2:最晚.
   */
  public function getPartCreated($entity_array, $status = 1) {
    $num = $i = 0;
    $old_entity = 0;
    foreach ($entity_array as $entity) {
      if ($i == 0) {
        $old_entity = $entity;
      }
      else {
        switch ($status) {
          case 1:
            // 取最小值.
            if ($entity->get('created')->value < $old_entity->get('created')->value) {
              $old_entity = $entity;
            }
            break;

          case 2:
            // 取最大值.
            if ($entity->get('created')->value > $old_entity->get('created')->value) {
              $old_entity = $entity;
            }
            break;
        }
      }

      $i++;
    }

    return empty($old_entity) ? 0 : $old_entity->get('created')->value;
  }

  /**
   * @description 配件转换
   */
  public function getSinglePartTransform($part_entities) {
    $result = [];
    foreach ($part_entities as $part) {
      $result[$part->get('name')->value][$part->id()] = $part;
    }
    return $result;
  }

  /**
   * @description 获取供应商单币种的配件结构数据
   */
  public function getSingleSupplyPartTransform($part_entities) {
    $result = [];
    foreach ($part_entities as $part) {
      $result[$part->get('supply_id')->entity->label() . ',' . $part->get('ftype')->target_id][] = $part;
    }
    return $result;
  }

  /**
   * @description 获取配件总数
   * @param part $entity_array
   * @param $status
   *   0: 不考虑状态，
   * @return $num 配件总数
   */
  public function getSumPartAmount($entity_array, $status = 1) {
    $num = 0;
    foreach ($entity_array as $entity) {
      switch ($status) {
        // 2: 已完成.
        case 2:
          // 14 采购已完成@todo 可能会加其他状态.
          if (in_array($entity->get('ch_status')->value, [14])) {
            $num += $entity->get('num')->value;
          }
          break;

        // 3: 采购中.
        case 3:
          if (in_array($entity->get('ch_status')->value, [4, 5, 6, 7, 8, 9, 10, 12, 13])) {
            $num += $entity->get('num')->value;
          }
          break;

        // 4: 未采购.
        case 4:
          if (in_array($entity->get('ch_status')->value, [0, 2])) {
            $num += $entity->get('num')->value;
          }
          break;

        // 2: 采购审批中.
        case 5:
          if (in_array($entity->get('ch_status')->value, [2])) {
            $num += $entity->get('num')->value;
          }
          break;

        // 0: 未采购.
        case 6:
          if (in_array($entity->get('ch_status')->value, [0])) {
            $num += $entity->get('num')->value;
          }
          break;

        default:
          $num += $entity->get('num')->value;
      }
    }
    return $num;
  }

  /**
   * @description 采购单验证配件参数是否正确。
   * @return 0 正常
   *   1 失败
   */
  public function checkPurchasePartStatus($part) {
    $part_supply_id = $part->get('supply_id')->target_id;
    $part_ftype = $part->get('ftype')->target_id;
    $part_unitprice = $part->get('unitprice')->value;

    if (empty($part_supply_id) || empty($part_ftype)) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * @description 检测需求单内的配件是否采购完成。
   * @param $entity
   *   Entity(requirement, purchase).
   */
  public function checkPartWuliuStatusfromRequirement($entity) {
    $pids = $entity->get('pids');

    // 非审批同意状态下的需求，无法完成需求采购动作。.
    if ($entity->get('audit')->value != 3) {
      return 0;
    }
    foreach ($pids as $pid) {
      if (empty($pid->entity->get('ship_supply_id')->target_id) && empty($pid->entity->get('ship_supply_no')->value)) {
        return 0;
      }
    }
    return 1;
  }

  /**
   * @description 按时间查询统计配件的价格(本月平均价).
   * @param entity $part
   * @param monlatestmaxmin $type
   */
  public function getAveragePriceByDate($part, $type = NULL) {
    $storage = \Drupal::entityManager()->getStorage('part');

    $storage_query = $storage->getQuery();
    $storage_query->condition('nid', $part->get('nid')->target_id);

    switch ($type) {
      // 查询本月该配件的平均价格.
      case 'mon':
        if (empty($_SESSION['paypro_part_trend_filter']['begin']) && empty($_SESSION['paypro_part_trend_filter']['begin'])) {
          $group = $storage_query->andConditionGroup()
            ->condition('created', strtotime($_SESSION['paypro_part_trend_filter']['begin']), '>')
            ->condition('created', strtotime($_SESSION['paypro_part_trend_filter']['end']), '<');
          $storage_query->condition($group);
        }
        break;
    }

    $ids = $storage_query->execute();

    if (empty($ids)) {
      return 0;
    }
    else {
      $entities = $storage->loadMultiple($ids);
    }

    // 查询单价-按数据条目数为基准查询.
    $unit_price = $unit_num = 0;
    $max = 0;
    foreach ($entities as $entity) {
      $unit_price += $entity->get('unitprice')->value;
      ++$unit_num;
    }
    if ($unit_num == 0) {
      $unit_num = 1;
    }

    $mon = round($unit_price / $unit_num, 2);

    return $mon;

  }

  /**
   * @description 查询最近一次单价.
   * 查询单个物品最新一次的单价。
   * @param entity $part
   */
  public function getLatestPartUnitpriceByDate($part) {
    $storage = \Drupal::entityManager()->getStorage('part');

    $storage_query = $storage->getQuery();
    $storage_query->condition('nid', $part->get('nid')->target_id);
    if (empty($_SESSION['paypro_part_trend_filter']['begin']) && empty($_SESSION['paypro_part_trend_filter']['begin'])) {
      $group = $storage_query->andConditionGroup()
        ->condition('created', strtotime($_SESSION['paypro_part_trend_filter']['begin']), '>')
        ->condition('created', strtotime($_SESSION['paypro_part_trend_filter']['end']), '<');
      $storage_query->condition($group);
    }
    $storage_query->sort('created', 'DESC');
    $ids = $storage_query->execute();
    $latest_entity = [];
    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);
      $latest_entity = reset($entities);
      $latest = $latest_entity->get('unitprice')->value;
    }
    else {
      $latest = 0;
    }

    // 最新一个单价.
    return $latest;
  }

  /**
   * @description 根据输入的时间查询全年最高、最低单价.
   * @param entity $part
   */
  public function getYearUnitpriceMinAndMaxByDate($part) {
    $storage = \Drupal::entityManager()->getStorage('part');

    $storage_query = $storage->getQuery();
    $storage_query->condition('nid', $part->get('nid')->target_id);

    if (empty($_SESSION['paypro_part_trend_filter']['begin']) && empty($_SESSION['paypro_part_trend_filter']['begin'])) {
      $group = $storage_query->andConditionGroup()
        ->condition('created', strtotime(date('Y', strtotime($_SESSION['paypro_part_trend_filter']['begin']))), '>')
        ->condition('created', strtotime(date('Y', strtotime("+1 year", strtotime($_SESSION['paypro_part_trend_filter']['end'])))), '<');
      $storage_query->condition($group);
    }
    $ids = $storage_query->execute();
    $entities = $storage->loadMultiple($ids);

    $parts = [];
    foreach ($entities as $entity) {
      $parts[$entity->id()] = $entity->get('unitprice')->value;
    }
    $max = array_search(max($parts), $parts);
    $min = array_search(min($parts), $parts);

    return [$parts[$max], $parts[$min]];
  }

  /**
   * @description 采购单取消时，配件数据回归到需求池中.
   * @param Entity $purchase
   */
  public function setPartsfallbackfromPurchase($purchase) {
    $pids = $purchase->get('pids');
    foreach ($pids as $pid) {
      $pid->entity->set('re_status', 5)
        ->set('cno', 0)
        ->set('fno', 0)
        ->set('pno', 0)
        ->save();
    }
  }

  /**
   * @description 按币种分组配件id.
   */
  public function getPartIdByFtype($parts) {
    $pids = [];
    foreach ($parts as $part) {
      if (!empty($part->get('ftype')->target_id)) {
        $pids[$part->get('ftype')->target_id][] = $part->id();
      }
      else {
        $pids['None'][] = $part->id();
      }
    }

    return $pids;
  }

}
