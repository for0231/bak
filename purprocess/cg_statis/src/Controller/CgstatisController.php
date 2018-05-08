<?php

namespace Drupal\cg_statis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Component\Utility\SafeMarkup;

/**
 *
 */
class CgstatisController extends ControllerBase {

  /**
   * @description 主要物品采购单价分析表.
   */
  public function getStatisPartpricetrendPools() {
    $build = [];
    $build['paypro_part_trend_filter_form'] = \Drupal::service('form_builder')->getForm('Drupal\cg_statis\Form\CgstatisPayproPartTrendFilterForm');
    $build['part_trend']['#theme'] = 'cg_statis_paypro_part_trend_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_paypro_part_trend_statistic'];
    return $build;
  }

  /**
   * @description
   */
  public function getAjaxStatisPartpricetrendAutocomplete(Request $request) {
    list($entities, $matches) = $this->getStatisAjaxCollection($request, 'part');
    $i = 0;

    foreach ($entities as $entity) {
      $types = explode('>', $entity->get('parttype')->value);
      list($max, $min) = \Drupal::service('part.partservice')->getYearUnitpriceMinAndMaxByDate($entity);
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'parttype' => $types[0],
        'name' => $entity->label(),
        'ftype' => empty($entity->get('ftype')->target_id) ? '-' : $entity->get('ftype')->target_id,
        'mon' => \Drupal::service('part.partservice')->getAveragePriceByDate($entity, 'mon'),
        'latest' => \Drupal::service('part.partservice')->getLatestPartUnitpriceByDate($entity),
        'max' => $max,
        'min' => $min,
      ];

      $i++;
    }
    return new JsonResponse($matches);
  }
  /**
   * @description 统计相关.
   */
  public function getStatisAjaxCollection(Request $request, $entity_type) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $nid_query = db_query("select min(id) as id from part group by nid order by id asc");
    $nids = $nid_query->fetchCol();

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();

    $storage_query->condition('id', $nids, 'IN');

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * @description 采购统计列表 统计列表专用
   */
  private function getAjaxStatisticCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'part':
        $storage_query->condition('id', $ids, 'IN');
        break;

      case 'purchase':
        if (isset($_SESSION['purchase_statis_filter']) && !empty($_SESSION['purchase_statis_filter'])) {
          $group = $storage_query->andConditionGroup()
            ->condition('created', strtotime($_SESSION['purchase_statis_filter']['begin']), '>')
            ->condition('created', strtotime($_SESSION['purchase_statis_filter']['end']), '<');
          $storage_query->condition($group);
        }
        // 目前只统计采购单状态为审核已同意的，@todo 可能会添加14-已完成，.
        $storage_query->condition('status', [4, 5, 10, 13, 14], 'IN');
        $storage_query->condition('audit', 3);
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * @description 采购付款统计列表专用
   */
  private function getAjaxPaypreStatisticCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = isset($input['rows']) ? $input['rows'] : 10;
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'part':
        $storage_query->condition('id', $ids, 'IN');
        break;

      case 'purchase':
        if (isset($_SESSION['paypre_statis_filter']) && !empty($_SESSION['paypre_statis_filter'])) {
          $group = $storage_query->andConditionGroup()
            ->condition('created', strtotime($_SESSION['paypre_statis_filter']['begin']), '>')
            ->condition('created', strtotime($_SESSION['paypre_statis_filter']['end']), '<');
          $storage_query->condition($group);
        }

        // 目前只统计采购单状态为审核已同意的，@todo 可能会添加14-已完成，.
        $storage_query->condition('status', [4, 5, 10, 13, 14], 'IN');
        $storage_query->condition('audit', 3);
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * @description 统计列表专用
   */
  private function getAjaxPayproStatisticCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'part':
        $storage_query->condition('id', $ids, 'IN');
        break;

      case 'purchase':
        // @todo 数据时间不够，后期测试补充
        if ($input['_search']) {
          $data = $this->getSearchSqlString($request);
          if (!empty($data['groupOp']) && !empty($data['bw']) && !empty($data['ew'])) {
            $group = $storage_query->andConditionGroup()
              ->condition('created', strtotime($data['bw']), '>')
              ->condition('created', strtotime($data['ew']), '<');
            $storage_query->condition($group);
          }
        }
        else {
          $group = $storage_query->andConditionGroup()
            ->condition('created', strtotime(date('Y-m-d', time())), '>')
            ->condition('created', strtotime(strtotime('next month')), '<');
          $storage_query->condition($group);
        }

        // $storage_query->condition('id', 0, '<>');
        // 目前只统计采购单状态为审核已同意的，@todo 可能会添加14-已完成，.
        $storage_query->condition('status', [4, 5, 10, 13, 14], 'IN');
        $storage_query->condition('audit', 3);
        break;

      case 'paypro':
        $storage_query->condition('id', 0, '<>');
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   *
   */
  private function getAjaxPayproCustomCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'part':
        $purchase_entity = \Drupal::entityManager()->getStorage('purchase')->load($input['id']);
        $pids = $purchase_entity->get('pids');
        $ppids = [];
        foreach ($pids as $pid) {
          $ppids[] = $pid->entity->id();
        }
        $storage_query->condition('id', $ppids, 'IN');
        break;

      case 'purchase':
        if (!empty($input['id'])) {
          $paypre_entity = \Drupal::entityManager()->getStorage('paypre')->load($input['id']);
          $cnos = $paypre_entity->get('cnos');
          $cids = [];
          foreach ($cnos as $cno) {
            $cids[] = $cno->entity->id();
          }
          $storage_query->condition('id', $cids, 'IN');
        }
        else {
          $storage_query->condition('id', 0, '<>');
        }
        break;

      case 'paypre':
        $paypro_entity = \Drupal::entityManager()->getStorage('paypro')->load($input['id']);
        $fnos = $paypro_entity->get('fnos');
        $fids = [];
        foreach ($fnos as $fno) {
          $fids[] = $fno->entity->id();
        }
        $storage_query->condition('id', $fids, 'IN');
        break;

      case 'paypro':
        if (!empty($_SESSION['paypro_statis_filter'])) {
          if (!empty($_SESSION['paypro_statis_filter']['begin'])) {
            $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['begin']), '>');
          }
          if (!empty($_SESSION['paypro_statis_filter']['end'])) {
            $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['end']), '<');
          }
        }
        else {
          $storage_query->condition('id', 0, '<>');
        }
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * @param $request
   * @param $entity_type
   * @param  $status
   * @param 0 $op
   */
  private function getAjaxPartCloserCollection(Request $request, $entity_type, $status = 1, $op = 1) {
    $input = $request->query->all();

    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();

    switch ($entity_type) {
      case 'part':
        /**
         * @description 正常的需求池物品.
         */
        if ($status) {
          $storage_query->condition('save_status', 1);
          if ($op) {
            $storage_query->condition('rno', 0, '<>');
            $storage_query->condition('cno', 0);
            $storage_query->condition('fno', 0);
            $storage_query->condition('pno', 0);
            // 已通过需求单审批.
            $storage_query->condition('re_status', 5);
            // 已通过需求单审批.
            $storage_query->condition('re_audit', 3);
          }
          else {
            $storage_query->condition('id', 0, '<>');
          }
        }
        else {
          /**
           * @description 非正常的需求池物品.
           */
          $storage_query->condition('save_status', 0);
        }
        if (!empty($_SESSION['paypro_statis_filter'])) {
          if (!empty($_SESSION['paypro_statis_filter']['begin'])) {
            $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['begin']), '>');
          }
          if (!empty($_SESSION['paypro_statis_filter']['end'])) {
            $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['end']), '<');
          }
        }
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    // Count.
    $count_result = $storage_query->execute();

    $count = count($count_result);
    if ($page == 0 || $page == 1) {
      $page = 1;
    }

    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   *
   */
  private function getAjaxPayproZijinCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'paypro':
        // @todo 待添加搜索时间段
        $storage_query->condition('id', 0, '<>');
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * @description 采购统计列表.
   */
  public function getPurchaseStatisticCollection() {
    $build = [];
    $build['purchase_statis_filter'] = \Drupal::service('form_builder')->getForm('Drupal\purchase\Form\PurchaseStatisFilterForm');
    $build['purchase_statistic']['#theme'] = 'cg_statis_purchase_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_purchase_statistic'];
    return $build;
  }

  /**
   * @description ajax 采购统计列表.
   */
  public function getPurchaseStatisticAutocomplete(Request $request) {
    list($entities,) = $this->getAjaxStatisticCollection($request, 'purchase');
    $parts = [];
    foreach ($entities as $entity) {
      $pids = $entity->get('pids');
      foreach ($pids as $pid) {
        $parts[$pid->entity->id()] = $pid->entity->id();
      }
    }

    list($part_entities, $matches) = $this->getAjaxStatisticCollection($request, 'part', $parts);
    $trans_parts = \Drupal::service('part.partservice')->getSinglePartTransform($part_entities);
    $i = 0;
    foreach ($trans_parts as $key => $entity) {
      $current_entity = reset($entity);
      $matches->rows[$i]['id'] = $current_entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $current_entity->id(),
        'name' => $key,
        'parttype' => $current_entity->get('parttype')->value,
        'num' => \Drupal::service('part.partservice')->getSumPartNum($entity),
        'unit' => taxonomy_term_load($current_entity->get('unit')->target_id)->label(),
      // 已完成.
        'has' => \Drupal::service('part.partservice')->getSumPartNum($entity, 2),
      // 已完成.
        'having' => \Drupal::service('part.partservice')->getSumPartNum($entity, 3),
      // 未完成.
        'not' => \Drupal::service('part.partservice')->getSumPartNum($entity, 4),
        'in' => \Drupal::service('part.partservice')->getPercent($entity, 1),
        'out' => \Drupal::service('part.partservice')->getPercent($entity, 2),
        'begin' => date('Y-m-d H:i:s', \Drupal::service('part.partservice')->getPartCreated($entity, 1)),
        'end' => date('Y-m-d H:i:s', \Drupal::service('part.partservice')->getPartCreated($entity, 2)),
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 采购付款统计列表.
   */
  public function getPaypreStatisticCollection() {
    $build = [];
    $build['paypre_statis_filter'] = \Drupal::service('form_builder')->getForm('Drupal\paypre\Form\PaypreStatisFilterForm');
    $build['paypre_statistic']['#theme'] = 'cg_statis_paypre_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_paypre_statistic'];
    return $build;
  }

  /**
   * @description ajax 采购付款统计列表.
   */
  public function getPaypreStatisticAutocomplete(Request $request) {
    list($entities,) = $this->getAjaxPaypreStatisticCollection($request, 'purchase');
    $parts = \Drupal::service('purchase.purchaseservice')->getPartsIdsByPurchaseEntity($entities);

    list($part_entities, $matches) = $this->getAjaxPaypreStatisticCollection($request, 'part', $parts);
    $trans_parts = \Drupal::service('part.partservice')->getSingleSupplyPartTransform($part_entities);
    $i = 0;
    foreach ($trans_parts as $key => $val) {
      $supply_ftype = explode(',', $key);
      $matches->rows[$i]['id'] = $i;
      $matches->rows[$i]['cell'] = [
        'id' => $i,
        'name' => $supply_ftype[0],
        'ftype' => $supply_ftype[1],
        'num' => \Drupal::service('part.partservice')->getAmount($val),
      // @todo 付款与否的标志未进行处理，待后期补充
        'had' => '待后期补充',
        'not' => '待后期补充',
        'begin' => date('Y-m-d H:i:s', \Drupal::service('part.partservice')->getPartCreated($val, 1)),
        'end' => date('Y-m-d H:i:s', \Drupal::service('part.partservice')->getPartCreated($val, 2)),
      ];
      $i++;
    }
    return new JsonResponse($matches);
  }

  /**
   * @description 合同统计列表.
   */
  public function getPaypreContactStatisticCollection() {
    $build = [];
    $build['paypre_contact_statis_filter'] = \Drupal::service('form_builder')->getForm('Drupal\paypre\Form\PaypreContactStatisFilterForm');
    $build['paypre_contact']['#theme'] = 'cg_statis_paypre_contact_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_paypre_contact_statistic'];
    return $build;
  }

  /**
   * @description ajax 合同统计列表.
   * @todo 待添加查询条件.
   */
  public function getPaypreContactStatisticAutocomplete(Request $request) {

    return new JsonResponse($result);
  }

  /**
   * @description 收款统计列表
   */
  public function getPayproInStatisticCollection(Request $request) {
    $build = [];
    $build['paypro_in_statistic']['#theme'] = 'cg_statis_paypro_in_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_paypro_in_statistic'];
    return $build;
  }

  /**
   * @description 收款统计自动处理.
   */
  public function getPayproInStatisticAutocomplete(Request $request) {
    return new JsonResponse($result);
  }

  /**
   * @description 付款统计表
   */
  public function getPayproOutStatisticCollection(Request $request) {
    $build = [];
    $build['paypro_statis_filter'] = \Drupal::service('form_builder')->getForm('Drupal\paypro\Form\PayproStatisFilterForm');
    $build['paypro_out_statistic']['#theme'] = 'cg_statis_paypro_out_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_paypro_out_statistic'];
    return $build;
  }

  /**
   * @description 付款统计自动处理
   */
  public function getPayproOutStatisticAutocomplete(Request $request) {

    list($entities, $matches) = $this->getAjaxPayproStatisticCollection($request, 'paypro');

    $i = 0;
    foreach ($entities as $entity) {
      $matches->rows[$i]['id'] = $i;
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'name' => 0,
        'bank' => 0,
        'account' => 0,
        'ftype' => 1,
        'amount' => 0,
        'has' => 0,
        'not' => 0,
        'begin' => 0,
        'end' => 0,
      ];
      $i++;
    }
    return new JsonResponse($matches);
  }

  /**
   * @description 我司付款统计
   */
  public function getPayproOutFkStatisticAutocomplete(Request $request) {
    list($entities,) = $this->getAjaxPayproCustomCollection($request, 'paypro');
    // list($transform, $amount) = \Drupal::service('pcord.pcordservice')->getSinglePayproFkTransform($entities);
    list($transform, $amount) = \Drupal::service('paypro.payproservice')->getSinglePayproFkTransform($entities);

    $i = 0;
    foreach ($transform as $key => $entity_array) {
      $data = explode(',', $key);

      $matches->rows[$i]['id'] = $i;
      $matches->rows[$i]['cell'] = [
        'id' => $i,
        'name' => $data[0],
        'bank' => $data[1],
        'account' => $data[2],
        'ftype' => $data[3],
        'amount' => \Drupal::service('paypro.payproservice')->getPayproAmountByStatus($entity_array, 3),
        'has' => \Drupal::service('paypro.payproservice')->getPayproAmountByStatus($entity_array, 1),
        'not' => \Drupal::service('paypro.payproservice')->getPayproAmountByStatus($entity_array, 2),
        'begin' => \Drupal::service('paypro.payproservice')->getPayproCreated($entity_array, 1),
        'end' => \Drupal::service('paypro.payproservice')->getPayproCreated($entity_array, 2),
      ];
      $i++;

    }
    return new JsonResponse($matches);
  }

  /**
   * @description 自动获取所有资金总结
   *              1. 查询所有支付单-根据支付单创建时间
   *              2. 查询所有付款单
   *              3. 查询所有采购单
   *              4. 查询所有配件信息
   */
  public function getPayproZijinTotalAutocomplete(Request $request) {
    // 1. 查询所有支付单.
    list($entities_paypro, $matches) = $this->getAjaxPayproZijinCollection($request, 'paypro');
    $parts = \Drupal::service('paypro.payproservice')->getPaypresByPayproId($entities_paypro);
    // kint($parts);
    $i = 0;

    foreach ($parts as $key => $part) {
      foreach ($part as $ke => $paypre) {
        foreach ($paypre as $k => $p) {
          // $paypro_status = getPayproStatus();
          // $audit_status = getAuditStatus();
          $paypre = paypre_load($ke);
          $matches->rows[$i]['id'] = $k;
          $matches->rows[$i]['cell'] = [
            'id' => $p->id(),
            'uid' => $p->get('uid')->target_id,
            'depart' => 0,
            'name' => $p->get('name')->value,
            'parttype' => $p->get('parttype')->value,
            'supply_id' => $p->get('supply_id')->target_id,
            'num' => $p->get('num')->value,
            'unitprice' => $p->get('unitprice')->value,
            'ftype' => $p->get('ftype')->target_id,
            'thispaypre' => 0,
            'ispre' => 0,
            'amount' => $p->get('num')->value * $p->get('unitprice')->value,
            'haspay' => $paypre->get('amount')->value,
            'tocompany' => 0,
            'located' => $p->get('locate_id')->target_id,
            'thewhy' => 0,
            'planpayforcompany' => 0,
            'paydate' => 0,
            'diffamount' => 0,
            'description' => 0,
            'planpayforcompany' => 0,
            'planpayforcompany' => 0,
            'created' => date('Y-m-d', $p->get('created')->value),
          ];
          $i++;
        }
      }
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 获取所有配件明细.
   */
  public function getPartCloserAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxPartCloserCollection($request, 'part', 1, 0);
    $i = 0;
    foreach ($entities as $entity) {
      $entity_requirement = entity_load('requirement', $entity->get('rno')->value);
      $entity_purchase = entity_load('purchase', $entity->get('cno')->value);
      $entity_paypre = entity_load('paypre', $entity->get('fno')->value);
      $entity_paypro = entity_load('paypro', $entity->get('pno')->value);
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'name' => $entity->label(),
        'parttype' => $entity->get('parttype')->value,
        'num' => $entity->get('num')->value,
        'rno' => $entity->get('rno')->value == 0 ? SafeMarkup::format("<font color=red><strong>待</strong></font>", []) : \Drupal::l($entity_requirement->label(), new Url('entity.requirement.detail_form', ['requirement' => $entity->get('rno')->value], ['attributes' => ['target' => '_blank']])),
        'requiredate' => date('Y-m-d', $entity->get('requiredate')->value),
        'plandate' => $entity->get('plandate')->value == 0 ? SafeMarkup::format("<font color=red><strong>待</strong></font>", []) : date('Y-m-d', $entity->get('plandate')->value),
        'cno' => $entity->get('cno')->value == 0 ? SafeMarkup::format("<font color=red><strong>待</strong></font>", []) : \Drupal::l($entity_purchase->label(), new Url('entity.purchase.detail_form', ['purchase' => $entity->get('cno')->value], ['attributes' => ['target' => '_blank']])),
        'fno' => $entity->get('fno')->value == 0 ? SafeMarkup::format("<font color=red><strong>待</strong></font>", []) : \Drupal::l($entity_paypre->label(), new Url('entity.paypre.detail_form', ['paypre' => $entity->get('fno')->value], ['attributes' => ['target' => '_blank']])),
        'pno' => $entity->get('pno')->value == 0 ? SafeMarkup::format("<font color=red><strong>待</strong></font>", []) : \Drupal::l($entity_paypro->label(), new Url('entity.paypro.detail_form', ['paypro' => $entity->get('pno')->value], ['attributes' => ['target' => '_blank']])),
        'ship_supply_no' => empty($entity->get('ship_supply_no')->value) ? SafeMarkup::format("<font color=red><strong>待</strong></font>", []) : $entity->get('ship_supply_no')->value,
        'created' => date('Y-m-d', $entity->get('created')->value),
        'uid' => $entity->get('uid')->entity->get('realname')->value,
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 每月采购统计计划表.
   */
  public function getStatisPermonthplanPools() {
    $build = [];
    $build['paypro_part_trend_filter_form'] = \Drupal::service('form_builder')->getForm('Drupal\paypro\Form\PayproPartTrendFilterForm');
    $build['part_trend']['#theme'] = 'cg_statis_paypro_part_trend_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_plan_pools_statistic'];
    return $build;
  }

  /**
   * @description 每月采购统计计划表数据
   */
  public function getAjaxStatisPermonthplanPools() {
    $begin = empty($_SESSION['paypro_part_trend_filter']['begin']) ? 0 : strtotime($_SESSION['paypro_part_trend_filter']['begin']);
    $end = empty($_SESSION['paypro_part_trend_filter']['end']) ? 0 : strtotime($_SESSION['paypro_part_trend_filter']['end']);
    $data = \Drupal::service('paypro.payproservice')->getPlanList($begin, $end);
    $rows = [];
    if (!empty($data)) {
      foreach ($data as $item) {
        // 通过当前物品名称查询历史物品的采购单价.
        $prices = \Drupal::service('paypro.payproservice')->get_price($item->name);
        if (empty($prices)) {
          $price = 0;
        }
        else {
          $price = $prices[0]->unitprice;
        }
        $user = user_load($item->uid);
        $tmp = [
        // 序号.
          'id' => $item->id,
        // 需求单号.
          'no' => $item->no,
        // 申请部门.
          'uid' => empty($user->get('depart')->value) ? '-' : taxonomy_term_load($user->get('depart')->value)->label(),
        // 物品类别.
          'parttype' => $item->parttype,
        // 物品名称.
          'unit' => $item->name,
        // 申请人.
          'user' => $user->get('realname')->value,
        // 数量.
          'num' => $item->num,
        // 单价.
          'unitprice' => $price,
        // 币种.
          'ftype' => '无',
        // 金额.
          'money' => $item->num * $price,
        // 预计交付时间.
          'requiredate' => date('Y-m-d', $item->requiredate),
        // 所属公司.
          'company' => empty($user->get('company')->value) ? '-' : taxonomy_term_load($user->get('company')->value)->label(),
        // 存放地点.
          'locate_id' => entity_load('taxonomy_term', $item->locate_id)->label(),
        // 原因/备注.
          'description' => $item->description,
        ];
        $rows[] = $tmp;
      }
    }
    else {
      $rows[] = NULL;
    }
    return new JsonResponse($rows);
  }

  /**
   * @description 每月采购明细表（付款单已付）.
   */
  public function getStatisDetailsPools() {
    $build = [];
    $build['paypro_part_trend_filter_form'] = \Drupal::service('form_builder')->getForm('Drupal\paypro\Form\PayproPartTrendFilterForm');
    $build['part_trend']['#theme'] = 'cg_statis_paypro_part_trend_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_details_pools_statistic'];
    return $build;
  }

  /**
   * @description 每月已完全支付的采购数据
   */
  public function getAjaxPayproDetailsPools() {

    $statistic = \Drupal::service('paypro.payproservice');
    $begin = empty($_SESSION['paypro_part_trend_filter']['begin']) ? 0 : strtotime($_SESSION['paypro_part_trend_filter']['begin']);
    $end = empty($_SESSION['paypro_part_trend_filter']['end']) ? 0 : strtotime($_SESSION['paypro_part_trend_filter']['end']);
    // 获取已完全支付的采购数据.
    $Cgentitys = \Drupal::service('paypro.payproservice')->getCgIds_pay($status = 13);
    foreach ($Cgentitys as $entity) {
      // 获取满足条件的采购id.
      $cg_ids[] = $entity->id;
    }
    foreach ($cg_ids as $value) {
      $rno_ids[] = \Drupal::service('paypro.payproservice')->getRno_id($value);
    }
    foreach ($rno_ids as $item) {
      $rno_list[] = $item[0]->rno;
    }
    // 通过满足条件的需求id获取支付时间为一个月内的配置列表.
    $partEntitys = \Drupal::service('paypro.payproservice')->get_pay_PartList($rno_list, $begin, $end);
    if (!empty($partEntitys)) {
      foreach ($partEntitys as $item) {
        $user = user_load($item->uid);
        // 通过需求id获取需求no.
        $requirementEntity = entity_load('requirement', $item->rno);
        // 通过采购单号获取采购实体.
        $purchaseEntity = entity_load('purchase', $item->cno);
        // 通过付款单号获取付款实体.
        $paypreEntity = entity_load('paypre', $item->fno);
        // 通过支付单号获取支付实体.
        $payproEntity = entity_load('paypro', $item->pno);
        $tmp = [
        // 配件ID.
          'nid' => $item->nid,
        // 配件名称.
          'name' => $item->name,
        // 单位.
          'unit' => entity_load('taxonomy_term', $item->unit)->label(),
        // 需求数量.
          'num' => $item->num,
        // 需求单号.
          'rno' => $requirementEntity ? $requirementEntity->get('no')->value : '',
        // 创建人.
          'uid' => entity_load('user', $item->uid)->label(),
        // 创建时间.
          'created' => date('Y-m-d H:i:s', $item->created),
        // 需求时间.
          'requiredate' => date('Y-m-d H:i:s', $item->requiredate),
        // 预计交付时间.
          'plandate' => date('Y-m-d H:i:s', $item->plandate),
        // 采购单号.
          'cno' => $purchaseEntity->get('no')->value,
        // 采购人.
          'cg_user' => entity_load('user', $purchaseEntity->get('uid')->target_id)->label(),
        // 采购日期.
          'cg_time' => date('Y-m-d H:i:s', $purchaseEntity->get('created')->value),
        // 供应商.
          'gongying' => entity_load('taxonomy_term', $item->supply_id)->label(),
        // 申请付款日期.
          'accept_time' => date('Y-m-d H:i:s', $paypreEntity ? $paypreEntity->get('created')->value : 0),
        // 收款方.
          'acceptname' => $paypreEntity ? $paypreEntity->get('acceptname')->value : '',
        // 付款单号.
          'paypre_no' => $paypreEntity ? $paypreEntity->get('no')->value : '',
        // 支付日期.
          'pay_time' => date('Y-m-d H:i:s', $payproEntity ? $payproEntity->get('created')->value : 0),
        // 付款方.
          'fname' => $payproEntity ? $payproEntity->get('fname')->value : '',
        // 币种.
          'ftype' => $payproEntity ? $payproEntity->get('ftype')->value : '',
        // 金额.
          'amount' => $payproEntity ? $payproEntity->get('amount')->value : '',
        // 支付单号.
          'zhifu_no' => $payproEntity ? $payproEntity->get('no')->value : '',
        // 物流单.
          'ship_supply_no' => $item->ship_supply_no,
        // 所属公司.
          'company' => empty($user->get('company')->value) ? '-' : taxonomy_term_load($user->get('company')->value)->label(),
        // 存放地点.
          'locate_id' => entity_load('taxonomy_term', $item->locate_id)->label(),
        ];
        $rows[] = $tmp;
      }
    }
    else {
      $rows[] = NULL;
    }
    return new JsonResponse($rows);
  }

  /**
   * @description 每月采购未支付表（付款单未付）.
   */
  public function getStatisUnpayproPools() {
    $build = [];
    $build['paypro_part_trend_filter_form'] = \Drupal::service('form_builder')->getForm('Drupal\paypro\Form\PayproPartTrendFilterForm');
    $build['part_trend']['#theme'] = 'cg_statis_paypro_part_trend_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_unpaypro_pools_statistic'];
    return $build;
  }

  /**
   * @description 每月采购未支付表（付款单未付）.

  public function getAjaxStatisUnpayproPools() {
    $statistic = \Drupal::service('paypro.payproservice');
    $begin = empty($_SESSION['paypro_part_trend_filter']['begin']) ? 0 : strtotime($_SESSION['paypro_part_trend_filter']['begin']);
    $end = empty($_SESSION['paypro_part_trend_filter']['end']) ? 0 : strtotime($_SESSION['paypro_part_trend_filter']['end']);
    // 获取未完全支付的采购数据 采购表中status=11的采购数据.
    $Cgentitys = \Drupal::service('paypro.payproservice')->getCgIds_pay($status = 11);
    foreach ($Cgentitys as $entity) {
      // 获取满足条件的采购id.
      $cg_ids[] = $entity->id;
    }
    // 通过获取的采购的id获取需求id，获取需求id相同的配件信息.
    foreach ($cg_ids as $value) {
      $rno_ids[] = \Drupal::service('paypro.payproservice')->getRno_id($value);
    }
    foreach ($rno_ids as $item) {
      $rno_list[] = $item[0]->rno;
    }
    // 通过满足条件的需求id获取支付时间为一个月内的配置列表.
    $partEntitys = \Drupal::service('paypro.payproservice')->get_unpay_PartList($rno_list, $begin, $end);
    if (!empty($partEntitys)) {
      foreach ($partEntitys as $item) {
        // 通过付款单号获取付款实体.
        $paypreEntity = entity_load('paypre', $item->fno);
        // 通过需求id获取需求no.
        $requirementEntity = entity_load('requirement', $item->rno);
        $tmp = [
        // 配件ID.
          'nid' => $item->id,
        // 配件名称.
          'name' => $item->name,
        // 配件类型.
          'parttype' => $item->parttype,
        // 单位.
          'unit' => entity_load('taxonomy_term', $item->unit)->label(),
        // 需求数量.
          'num' => $item->num,
        // 需求单号.
          'rno' => $requirementEntity ? $requirementEntity->get('no')->value : '',
        // 币种.
          'ftype' => $item->ftype,
        // 单价.
          'unitprice' => $item->unitprice,
        // 付款单号.
          'paypre_no' => $paypreEntity ? $paypreEntity->get('no')->value : '',
        // 付款币种.
          'pay_ftype' => $paypreEntity ? $paypreEntity->get('ftype')->target_id : '',
        // 付款单金额.
          'amount' => $paypreEntity ? $paypreEntity->get('amount')->value : '',
        // 付款申请人.
          'uid' => $paypreEntity ? entity_load('user', $paypreEntity->get('uid')->target_id)->label() : '',
        // 付款单日期.
          'pay_time' => date('Y-m-d H:i:s', $paypreEntity ? $paypreEntity->get('created')->value : 0),
        // 创建人.
          'created_uid' => $item ? entity_load('user', $item->uid)->label() : '',
        // 创建时间.
          'created' => $paypreEntity ? $paypreEntity->get('created')->value : '',
        ];
        $rows[] = $tmp;
      }
    }
    else {
      $rows[] = NULL;
    }
    return new JsonResponse($rows);
  }
   */

  /**
   * @description 重构-每月采购未支付完成的配件列表.
   * @todo 未处理查询时间
   */
  public function getAjaxStatisUnpayproPools(Request $request) {
    list($entities, $matches) = $this->getStatisUnpayproAjaxCollection($request, 'part');
    $i = 0;
    foreach ($entities as $entity) {

      $entity_requirement = requirement_load($entity->get('rno')->value);
      $entity_paypre      = paypre_load($entity->get('fno')->value);
      $matches->row[$i][$id] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'nid' => $entity->id(),
        'parttype' => $entity->get('parttype')->value,
        'name' => $entity->label(),
        'ftype' => $entity->get('ftype')->target_id,
        'unit' => $entity->get('unit')->entity->label(),
        'num' => $entity->get('num')->value,
        // 需求单号.
        'rno' => $entity->get('rno')->value ? $entity_requirement->label() : '',
        'unitprice' => $entity->get('unitprice')->value,
        // 付款单号.
        'paypre_no' => $entity->get('fno')->value ? $entity_paypre->label() : '',
        // 付款币种.
        'pay_ftype' => $entity->get('ftype')->target_id,
        // 付款单金额.
        'amount' => \Drupal::service('paypre.paypreservice')->getPaypreAmount($entity_paypre),
        // 付款申请人.
        'uid' => $entity_paypre->get('uid')->target_id ? $entity_paypre->get('uid')->entity->get('realname')->value : '',
        // 付款单日期.
        'pay_time' => date("Y-m-d", $entity_paypre->get('created')->value),
        // 创建人.
        'created_uid' =>  $entity_paypre->get('uid')->target_id ? $entity_paypre->get('uid')->entity->get('realname')->value : '',
        // 创建时间.
        'created' => date('Y-m-d', $entity->get('created')->value),
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  private function getStatisUnpayproAjaxCollection(Request $request, $entity_type) {
    $input = $request->query->all();
    $page  = $input['page'];
    $limit = $input['rows'];
    $sidx  = $input['sidx'];
    $sord  = $input['sord'];

    /**
     * @description 未完全支付的采购单状态包括
     * - 10
     * - 11
     * - 12
     */
    $sql = "SELECT pids_target_id from purchase__pids where entity_id in (SELECT id from purchase where status in (10, 11, 12) $where)";
    $pids_target_id_list = \Drupal::service('database')->query($sql)->fetchAll();
    if (!empty($pids_target_id_list)) {
      foreach ($pids_target_id_list as $item) {
        $nids[] = $item->pids_target_id;
      }
    }

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();

    $storage_query->condition('id', $nids, 'IN');

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * @description 统计汇总表.
   */
  public function getStatisAggretotalPools() {
    $build = [];
    $build['paypro_aggretotal_filter_form'] = \Drupal::service('form_builder')->getForm('Drupal\paypro\Form\PayproAggretotalFilterForm');
    $build['part_trend']['#theme'] = 'cg_statis_paypro_aggretotal_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_paypro_aggretotal'];
    return $build;
  }

  /**
   * @description 自动获取采购汇总数据。
   */
  public function getAjaxStatisAggretotalAutocomplete(Request $request) {
    // @todo 待清空下列数据。
    // list($entities, $matches) = $this->getStatisAggretotalAjaxCollection($request, 'part');
    $matches = new \stdClass();
    // 找出采购物品的一级分类.
    $taxonomy_term_entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    $taxonomy_term_first = $taxonomy_term_entity_manager->loadTree('parts', 0, 1, TRUE);
    $i = 0;
    $amttot = 0; $taxtot = 0; $total = 0;
    $ftype_ids = [];
    foreach ($taxonomy_term_first as $entity) {
      // 根据第一级分类名称在part表里进行首部模糊查找.
      if (empty($_SESSION['paypro_aggretotal_filter']['level'])) {
        $level = 1;
      }
      else {
        $level = $_SESSION['paypro_aggretotal_filter']['level'];
      }

      $taxonomy_term_tree = $taxonomy_term_entity_manager->loadTree('parts', $entity->id(), $level, TRUE);
      $ftype_amount = [];
      foreach ($taxonomy_term_tree as $tree_alone) {
        $keyword = $this->getParttypeKeywordByTaxonomy($tree_alone);
        $parttype_storage = \Drupal::entityManager()->getStorage('part');
        $parttype_storage_query = $parttype_storage->getQuery();
        if (!empty($keyword)) {
          $parttype_storage_query->condition('parttype', '%' . $keyword . '%', 'LIKE');
        }
        else {
          error_log(print_r('no', 1));
        }

        /**
         * @todo 1. 临时管制.
         */
        $begin = isset($_SESSION['paypro_aggretotal_filter']['begin']) ? $_SESSION['paypro_aggretotal_filter']['begin'] : '';
        $end = isset($_SESSION['paypro_aggretotal_filter']['end']) ? $_SESSION['paypro_aggretotal_filter']['end'] : '';
        if (empty($begin) || empty($end)) {
          $group = $parttype_storage_query->andConditionGroup()
            ->condition('created', strtotime(date('Y-m-d', time())), '>')
            ->condition('created', strtotime(strtotime('next month')), '<');
          $parttype_storage_query->condition($group);
        }
        else {
          $parts = \Drupal::service('purchase.purchaseservice')->getPidsByCompletePurchaseStatus();
          if (!empty($parts)) {
            $parttype_storage_query->condition('id', $parts, 'IN');
          }
        }

        /**@todo 2. 临时管制
         **/
        // 采购单状态为完成状态时。.
        $parttype_storage_query->condition('ch_status', 13);

        $part_ids = $parttype_storage_query->execute();

        $part_entities = $parttype_storage->loadMultiple($part_ids);

        // 根据前面查出的配件id进行筛选，unique配件名称.
        $ftype_part_ids = \Drupal::service('part.partservice')->getPartIdByFtype($part_entities);

        $ftype_ids = array_merge_recursive($ftype_ids, $ftype_part_ids);

        foreach ($ftype_part_ids as $ftype => $part_arr) {
          $total += empty($part_ids) ? 0 : \Drupal::service('part.partservice')->getSumPartNum($ftype_part_entities);

          $ftype_part_entities = part_load_multiple($part_arr);

          $matches->rows[$i]['id'] = $i;
          $matches->rows[$i]['cell'] = [
            'id' => $i,
            'type' => $entity->label(),
            'name' => $keyword,
            'ftype' => $ftype,
            'num' => empty($part_ids) ? 0 : \Drupal::service('part.partservice')->getSumPartNum($ftype_part_entities),
            'amount' => empty($part_ids) ? 0 : \Drupal::service('part.partservice')->getAmount($ftype_part_entities),
          ];

          $i++;
        }

      }
    }

    foreach ($ftype_ids as $ftype => $part_ids) {
      $famount = 0;
      $parts = part_load_multiple($part_ids);
      $famount = \Drupal::service('part.partservice')->getAmount($parts);
      $famount_string .= $ftype . ':' . $famount . '-';
    }
    $famount_string = substr($famount_string, 0, -1);
    $matches->userdata['amount'] = $famount_string;
    $matches->userdata['num'] = $total;
    $matches->userdata['name'] = '合计:';

    return new JsonResponse($matches);
  }

  /**
   * @description 统计全年采购清单.   *
   */
  public function getStatisPeryearPools() {
    $build = [];
    $build['paypro_part_trend_filter_form'] = \Drupal::service('form_builder')->getForm('Drupal\paypro\Form\PayproPartTrendFilterForm');
    $build['part_trend']['#theme'] = 'cg_statis_paypro_part_trend_statistic';
    $build['#attached']['library'] = ['cg_statis/cg_statis_peryear_pools_statistic'];
    return $build;
  }


  /**
   * @description 重构-统计全年采购单物品数据.
   * @todo 未添加查询时间,目前是查整个系统的采购配件数据.
   */
  public function getAjaxStatisPeryearPools(Request $request) {
    list($entities, $matches) = $this->getStatisPeryearAjaxCollection($request, 'part');
    $i = 0;
    foreach ($entities as $entity) {
      $matches->row[$i][$id] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'parttype' => $entity->get('parttype')->value,
        'name' => $entity->label(),
        'ftype' => $entity->get('ftype')->target_id,
        'unit' => $entity->get('unit')->entity->label(),
        'num' => $entity->get('num')->value,
        'unitprice' => $entity->get('unitprice')->value,
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  private function getStatisPeryearAjaxCollection(Request $request, $entity_type) {
    $input = $request->query->all();
    $page  = $input['page'];
    $limit = $input['rows'];
    $sidx  = $input['sidx'];
    $sord  = $input['sord'];

    $sql = "SELECT pids_target_id from purchase__pids where entity_id in (SELECT id from purchase where status in (13, 14) $where)";
    $pids_target_id_list = \Drupal::service('database')->query($sql)->fetchAll();
    if (!empty($pids_target_id_list)) {
      foreach ($pids_target_id_list as $item) {
        $nids[] = $item->pids_target_id;
      }
    }

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();

    $storage_query->condition('id', $nids, 'IN');

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

}
