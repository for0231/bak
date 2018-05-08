<?php

namespace Drupal\paypro\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\paypro\PayproDataListBuilder;

use Drupal\paypro\PayproHistoryListBuilder;
use Drupal\paypro\PayproAuditHistoryListBuilder;

/**
 *
 */
class PayproController extends ControllerBase {

  /**
   * 付款池勾选paypre后，响应创建支付单.
   */
  public function createPayproAutocomplete(Request $request) {
    $input = $request->request->all();

    if (is_array($input['choices'])) {
      $choices = current($input['choices']);
    }
    else {
      $choices = [];
    }
    $msg = '';
    if (!empty($choices)) {
      // @description 返回1， 则所有验证正常
      // 否则返回0
      $check = \Drupal::service('paypre.paypreservice')->checkPaypreStatusforPayproById($choices);

      if ($check) {
        // 创建支付单数据.
        \Drupal::service('paypro.payproservice')->save(NULL, FALSE, $choices);
        $msg = '创建支付单-成功';
      }
      else {
        $msg = '创建支付单-失败';
      }
    }
    else {
      $msg = '创建支付单-失败';
    }
    return new JsonResponse($msg);
  }

  /**
   * @description 获取支付单的所有数据.
   */
  public function getPayproStatisticCollection() {
    $build = [];
    $build['paypro']['#theme'] = 'paypro_statistic';
    $build['#attached']['library'] = ['paypro/statistic'];
    return $build;
  }

  /**
   * @description 自动获取所有支付单
   */
  public function getPayproStatisticCollectionAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'paypro');
    $i = 0;
    foreach ($entities as $entity) {
      $fnos = $entity->get('fnos');
      foreach ($fnos as $fno) {
        // 一个支付单只包含一个付款单.
        $fno_entity = $fno->entity;
      }
      $paypro_status = getPayproStatus();
      $audit_status = getAuditStatus();
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => \Drupal::l($entity->id(), new Url('entity.paypro.detail_form', ['paypro' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'no' => \Drupal::l($entity->label(), new Url('entity.paypro.detail_form', ['paypro' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'depart' => empty($entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity->get('uid')->entity->get('depart')->value)->label(),
        'uid' => $entity->get('uid')->entity->get('realname')->value,
        'ftype' => $entity->get('ftype')->value,
        'amount' => \Drupal::service('paypre.paypreservice')->getPaypresAmount($fno_entity),
        'status' => $paypro_status[$entity->get('status')->value],
        'audit' => $audit_status[$entity->get('audit')->value],
        'created' => date('Y-m-d H:i', $entity->get('created')->value),
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   *
   */
  private function getAjaxCollection(Request $request, $entity_type, $ids = []) {
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
   * @description 自动获取支付单包含的付款单
   * @param $entities
   *   ----> paypre entity
   */
  public function getPaypreStatisticCollectionAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'paypre');
    $i = 0;
    foreach ($entities as $entity) {
      $paypre_status = getPaypreStatus();
      $audit_status = getAuditStatus();
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => \Drupal::l($entity->id(), new Url('entity.paypre.detail_form', ['paypre' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'no' => \Drupal::l($entity->label(), new Url('entity.paypre.detail_form', ['paypre' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'depart' => empty($entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity->get('uid')->entity->get('depart')->value)->label(),
        'uid' => $entity->get('uid')->entity->get('realname')->value,
        'ftype' => $entity->get('ftype')->target_id,
        'amount' => \Drupal::service('paypre.paypreservice')->getPaypresAmount($entity),
        'status' => $paypre_status[$entity->get('status')->value],
        'audit' => $audit_status[$entity->get('audit')->value],
        'created' => date('Y-m-d H:i', $entity->get('created')->value),
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 自动获取支付单包含的付款单
   * @param $entities
   *   ----> paypre entity
   */
  public function getPurchaseStatisticCollectionAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'purchase');
    $i = 0;
    foreach ($entities as $entity) {
      $purchase_status = getPurchaseStatus();
      $audit_status = getAuditStatus();
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => \Drupal::l($entity->id(), new Url('entity.purchase.detail_form', ['purchase' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'no' => \Drupal::l($entity->label(), new Url('entity.purchase.detail_form', ['purchase' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'title' => $entity->get('title')->value,
        'depart' => empty($entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity->get('uid')->entity->get('depart')->value)->label(),
        'uid' => $entity->get('uid')->entity->get('realname')->value,
        'amount' => \Drupal::service('purchase.purchaseservice')->getPurchaseftype($entity) . ': ' . \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($entity),
        'status' => $purchase_status[$entity->get('status')->value],
        'audit' => $audit_status[$entity->get('audit')->value],
        'created' => date('Y-m-d H:i', $entity->get('created')->value),
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 自动获取支付单包含的付款单
   * @param $entities
   *   ----> paypre entity
   */
  public function getPartsStatisticCollectionAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'part');
    $i = 0;
    foreach ($entities as $entity) {
      $requirement_status = getRequirementStatus();
      $audit_status = getAuditStatus();

      $entity_requirement = entity_load('requirement', $entity->get('rno')->value);
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'no' => $entity->label(),
        'parttype' => $entity->get('parttype')->value,
        'depart' => empty($entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity->get('uid')->entity->get('depart')->value)->label(),
        'uid' => $entity->get('uid')->entity->get('realname')->value,
        'ftype' => $entity->get('ftype')->target_id,
        'amount' => \Drupal::service('part.partservice')->getSinglePartAmount($entity),
        'unitprice' => $entity->get('unitprice')->value,
        'num' => $entity->get('num')->value,
        'wuliufee' => isset($entity->get('wuliufee')->value) ? $entity->get('wuliufee')->value : 0,
        'supply_id' => $entity->get('supply_id')->target_id,
        'rno' => \Drupal::l($entity_requirement->label(), new Url('entity.requirement.detail_form', ['requirement' => $entity->get('rno')->value], ['attributes' => ['target' => '_blank']])),
        'status' => $requirement_status[$entity->get('re_status')->value],
        'audit' => $audit_status[$entity->get('re_audit')->value],
        'created' => date('Y-m-d H:i', $entity->get('created')->value),
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 获取指定支付单的详情
   */
  public function getAjaxPayproDetailsAutocomplete(Request $request, $paypro) {
    $storage = \Drupal::entityManager()->getStorage('paypro');
    $entity_paypro = $storage->load($paypro);

    $fnos = $entity_paypro->get('fnos');
    $i = 0;
    $matches = new \stdClass();
    $audit = getAuditStatus();
    // $status = getPurchaseStatus();
    $paypre_status = getPaypreStatus();
    foreach ($fnos as $fno) {
      $matches->rows[$i]['id'] = $fno->entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $fno->entity->id(),
        'no' => $fno->entity->label(),
        'title' => $fno->entity->get('title')->value,
        'contact_no' => $fno->entity->get('contact_no')->value,
        'acceptaccount' => $fno->entity->get('acceptaccount')->value,
        'uid' => empty($fno->entity->get('uid')->entity->get('realname')->value) ? $fno->entity->get('uid')->entity->label() : $fno->entity->get('uid')->entity->get('realname')->value,
        'depart' => empty($fno->entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($fno->entity->get('uid')->entity->get('depart')->value)->label(),
        'created' => date('Y-m-d H:i', $fno->entity->get('created')->value),
        'ftype' => $fno->entity->get('ftype')->target_id,
        // 'amount' => \Drupal::service('paypre.paypreservice')->getPaypresAmount($fno->entity),.
        'amount' => $fno->entity->get('amount')->value,
        'pre_amount' => $fno->entity->get('pre_amount')->value,
        'all_amount' => \Drupal::service('paypre.paypreservice')->getPaypresAmount($fno->entity),
        'status' => $paypre_status[$fno->entity->get('status')->value],
        'audit' => $audit[$fno->entity->get('audit')->value],
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 统计列表专用
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
   * @description 获取支付单的支付记录.
   * @deprecated
   *
   * public function getPayproPcordAutocomplete(Request $request, $paypro) {
   * $input = $request->request->all();
   * $paypro_entity = \Drupal::entityManager()->getStorage('paypro')->load($paypro);
   * if (\Drupal::currentUser()->hasPermission('administer paypro record delete')) {
   * if ($input['oper'] == 'del') {
   * $ids = explode(',', $input['id']);
   * \Drupal::service('paypro.payproservice')->deletePayproPcnos($paypro_entity, $ids);
   * }
   * }
   * $pcnos = $paypro_entity->get('pcnos');
   * foreach ($pcnos as $pcno) {
   * $pids[] = $pcno->entity->id();
   * }
   * list($entities, $matches) = $this->getAjaxCollection($request, 'pcord', $pids);
   * $entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
   * // 获取公司信息.
   * $payment_enterprise = $entity_manager->loadTree('payment_enterprise', 0, 1, TRUE);
   * $fbank_companies = [
   * '请选择支付公司',
   * ];
   * foreach ($payment_enterprise as $row) {
   * $fbank_companies[$row->id()] = $row->label();
   * }
   * $i = 0;
   * foreach ($entities as $entity) {
   * $matches->rows[$i]['id'] = $entity->id();
   * $matches->rows[$i]['cell'] = [
   * 'id' => $entity->id(),
   * 'fbank' => $fbank_companies[$entity->get('fbank')->value],
   * 'fname' => $entity->get('fname')->value,
   * 'faccount' => $entity->get('faccount')->value,
   * 'ftype' => $entity->get('ftype')->value,
   * // 'fbank' => $entity->get('fbank')->value,.
   * 'amount' => $entity->get('amount')->value,
   * 'fbserial' => $entity->get('fbserial')->value,
   * 'description' => $entity->get('description')->value,
   * 'uid' => $entity->get('uid')->entity->get('realname')->value,
   * 'created' => date('Y-m-d H:i', $entity->get('created')->value),
   * ];
   *
   * $i++;
   *
   * $am_amount += $entity->get('amount')->value;
   * }
   * $matches->userdata['amount'] = $am_amount;
   * return new JsonResponse($matches);
   * }
   */
  /**
   * 处理付款池数据.
   *
   * @see entity.paypre.pools.collection
   */

  /**
   * Public function getPayprePools(Request $request) {.
   */
  public function getPayproPools(Request $request) {

    $build = [];
    $build['part']['#theme'] = 'paypro_paypres_pool';
    $build['#attached']['library'] = ['paypro/paypro_payprespool'];
    return $build;
  }

  /**
   * Ajax get paypre.
   *
   * @param $input
   *
   * @code
   *  $input = [
   *    '_search' => false,
   *    'nd' => 1496842536048,
   *    'rows' => 50,
   *    'page' => 1,
   *    'sidx' => "name",
   *    'sord' => "asc",
   *    'filters' => "",
   *  ];
   * @endcode
   * @see entity.purchase.pools
   */

  /**
   * Public function getPaypreAutocomplete(Request $request) {.
   */
  public function getPayproAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxPoolCollection($request, 'paypre');
    $i = 0;
    $status = getPaypreStatus();
    $audit = getAuditStatus();
    foreach ($entities as $entity) {

      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'no' => \Drupal::l($entity->label(), new Url('entity.paypre.detail_form', ['paypre' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'title' => \Drupal::l($entity->get('title')->value, new Url('entity.paypre.detail_form', ['paypre' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'contact_no' => $entity->get('contact_no')->value,
        'acceptaccount' => $entity->get('acceptaccount')->value,
        'depart' => empty($entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity->get('uid')->entity->get('depart')->value)->label(),
        'uid' => $entity->get('uid')->entity->get('realname')->value,
        'created' => date('Y-m-d H:i', $entity->get('created')->value),
        'ftype' => $entity->get('ftype')->target_id,
        'amount' => $entity->get('amount')->value,
        'pre_amount' => $entity->get('pre_amount')->value,
        'all_amount' => \Drupal::service('paypre.paypreservice')->getPaypresAmount($entity),
        'status' => $status[$entity->get('status')->value],
        'audit' => $audit[$entity->get('audit')->value],
        'op' => \Drupal::l('详情', new Url('entity.paypre.detail_form', ['paypre' => $entity->id()], ['attributes' => ['target' => '_blank']])),
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 专用付款池.
   */
  private function getAjaxPoolCollection(Request $request, $entity_type, $ids = []) {
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

      case 'paypre':
        $storage_query->condition('status', 5);
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
   * @description 获取列表数据.
   */
  public function getPayproDataCollection() {
    $list = new PayproDataListBuilder();

    $mode = isset($_GET['mode']) ? $_GET['mode'] : 4;

    if (empty($_SESSION['collection_data_list'])) {
      $_SESSION['collection_data_list'] = [
        'mode' => $mode,
      ];
    }
    else {
      if ($_SESSION['collection_data_list']['mode'] != $mode) {
        $_SESSION['collection_data_list']['mode'] = $mode;
      }
    }

    $list->setMode($mode);
    $data = $list->build();
    return new Response($data);

  }

  /**
   * @description 统计相关.
   * @description 通用函数
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

    $entities = part_load_multiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];

  }

  /**
   * @description 重构获取术语数组.
   * @param id $parent_id
   */
  public function getTaxonomyTreeArrayByParentId($parent_id) {
    $taxonomy_term_entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    $terms = $ids = [];
    if (isset($parent_id)) {
      $terms = $taxonomy_term_entity_manager->loadTree('parts', $parent_id, 1, TRUE);
    }
    else {
      $terms = $taxonomy_term_entity_manager->loadTree('parts', NULL, 0, TRUE);
    }

    foreach ($terms as $row) {
      $ids[] = $row->id();
    }
    return $ids;
  }

  /**
   * @descrition  获取配件分类关键词
   * @param entity $entity_taxonomy
   * @param  $level
   * @return keyword
   */
  public function getParttypeKeywordByTaxonomy($entity_taxonomy) {
    $taxonomy_term_entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    $tmp_parents = $taxonomy_term_entity_manager->loadAllParents($entity_taxonomy->id());
    $search_type = '';
    // 组合层级字符串。.
    foreach (array_reverse($tmp_parents) as $parent) {
      $search_type .= $parent->label();
      $search_type .= '>';
    }
    $search_type = substr($search_type, 0, -1);

    return $search_type;
  }

  /**
   * @description 历史支付单数据.
   */
  public function getHistoryData() {
    $build['description'] = ['#markup' => '当前列表将会列出支付状态为非待审批和审批中的数据'];
    $history = PayproHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    $build['tips'] = ['#markup' => '友情提醒: 前面几列的链接，可打开新窗口浏览，最后一列的按钮，则会本页跳转!'];
    return $build;
  }

  /**
   * @description 支付单审批历史.
   */
  public function getAuditHistoryData() {
    $history = PayproAuditHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

}
