<?php

namespace Drupal\paypre\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Drupal\paypre\PaypreDataListBuilder;
use Drupal\paypre\PaypreHistoryListBuilder;
use Drupal\paypre\PaypreAuditHistoryListBuilder;

/**
 *
 */
class PaypreController extends ControllerBase {

  /**
   *
   */
  public function buildPageList(array $items) {
    $build = [
      '#type' => 'container',
    ];

    if ($items) {
      $build['items'] = $this->entityManager()->getViewBuilder('paypre')
        ->viewMultiple($items, 'default');
      $build['pager'] = ['#type' => 'pager'];
    }

    return $build;
  }

  /**
   * 生成付款单工作池，用于选择指定的付款单生成支付单.
   */
  public function viewPool() {
    return ['#markup' => 'afdfas'];
  }

  /**
   * 需求池勾选paypre后，响应创建付款单.
   */
  public function createPaypreAutocomplete(Request $request) {
    $input = $request->request->all();

    if (is_array($input['choices'])) {
      $choices = current($input['choices']);
    }
    else {
      $choices = [];
    }
    $msg = '';
    if (!empty($choices)) {
      $check = \Drupal::service('purchase.purchaseservice')->checkPurchaseStatusforPaypreById($choices);

      if ($check) {
        // 创建付款单数据.
        \Drupal::service('paypre.paypreservice')->save(NULL, FALSE, $choices);
        $msg = '创建付款单-成功';
      }
      else {
        $msg = '创建付款单-失败';
      }
    }
    else {
      $msg = '创建付款单-失败';
    }
    return new JsonResponse($msg);
  }

  /**
   * 处理付款池数据.
   *
   * @see entity.paypre.pools.collection
   *
   * public function getPayprePools(Request $request) {
   *
   * $build = [];
   * $build['part']['#theme'] = 'paypres_pool';
   * $build['#attached']['library'] = ['paypre/payprespool'];
   * return $build;
   * }
   */

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
   *
   * public function getPaypreAutocomplete(Request $request) {
   * list($entities, $matches) = $this->getAjaxCollection($request, 'paypre');
   * $i = 0;
   * $status = getPaypreStatus();
   * $audit = getAuditStatus();
   * foreach ($entities as $entity) {
   * $cnos = $entity->get('cnos');
   * $amount = 0;
   * foreach ($cnos as $cno) {
   * $amount += \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity);
   * }
   * $matches->rows[$i]['id'] = $entity->id();
   * $matches->rows[$i]['cell'] = [
   * 'id' => $entity->id(),
   * 'no' => $entity->label(),
   * 'acceptaccount' => $entity->get('acceptaccount')->value,
   * 'depart' => empty($entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity->get('uid')->entity->get('depart')->value)->label(),
   * 'uid' => $entity->get('uid')->entity->get('realname')->value,
   * 'created' => date('Y-m-d H:i', $entity->get('created')->value),
   * 'ftype' =>  $entity->get('ftype')->target_id,
   * 'amount' => $amount,
   * 'status' => $status[$entity->get('status')->value],
   * 'audit' => $audit[$entity->get('audit')->value],
   * 'op' => \Drupal::l('详情',new Url('entity.paypre.detail_form', ['paypre' => $entity->id()], ['attributes' => ['target' => '_blank']])),
   * ];
   *
   * $i++;
   * }
   *
   * return new JsonResponse($matches);
   * }
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
        $storage_query->condition('id', $ids, 'IN');
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
        $storage_query->condition('status', 5);
        $storage_query->condition('audit', 3);
        break;

      case 'paypre_same':
        $storage_query->condition('id', $ids, 'IN');
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
   *
   */
  private function getAjaxPaypreSameIdsCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'paypre':
        $storage_query->condition('id', $ids, 'IN');
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
  public function getAjaxPaypreDetailsAutocomplete(Request $request, $paypre) {
    $storage = \Drupal::entityManager()->getStorage('paypre');
    $entity_paypre = $storage->load($paypre);

    $cnos = $entity_paypre->get('cnos');
    $i = 0;
    $matches = new \stdClass();
    $audit = getAuditStatus();
    $status = getPurchaseStatus();
    foreach ($cnos as $cno) {
      $matches->rows[$i]['id'] = $cno->entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $cno->entity->id(),
        'no' => $cno->entity->label(),
        'uid' => empty($cno->entity->get('uid')->entity->get('realname')->value) ? $cno->entity->get('uid')->entity->label() : $cno->entity->get('uid')->entity->get('realname')->value,
        'depart' => empty($cno->entity->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($cno->entity->get('uid')->entity->get('depart')->value)->label(),
        'created' => date('Y-m-d H:i', $cno->entity->get('created')->value),
        'ftype' => $entity_paypre->get('ftype')->target_id,
        'amount' => \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity),
        'status' => $status[$cno->entity->get('status')->value],
        'audit' => $audit[$cno->entity->get('audit')->value],
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 获取付款单包含相同的采购单据的所有付款单列表.
   */
  public function getAjaxPaypreDetailsSamecnosAutocomplete(Request $request, $paypre) {
    if (empty($paypre)) {
      return new JsonResponse([]);
    }
    $storage = \Drupal::entityManager()->getStorage('paypre');
    $entity_paypre = $storage->load($paypre);

    $same_ids = \Drupal::service('paypre.paypreservice')->getfnosbySamecnos($entity_paypre);

    list($entities_paypre, $matches) = $this->getAjaxPaypreSameIdsCollection($request, 'paypre', $same_ids);
    // @todo 更新配件列表问题，当前只能显示出一个配件信息
    $audit = getAuditStatus();
    $status = getPaypreStatus();
    // $matches = new \stdClass();
    $i = 0;
    foreach ($entities_paypre as $entity_paypre) {
      $matches->rows[$i]['id'] = $entity_paypre->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity_paypre->id(),
        'no' => $entity_paypre->label(),
        'title' => $entity_paypre->get('title')->value,
        'contact_no' => $entity_paypre->get('contact_no')->value,
        'uid' => empty($entity_paypre->get('uid')->entity->get('realname')->value) ? $entity_paypre->get('uid')->entity->label() : $entity_paypre->get('uid')->entity->get('realname')->value,
        'depart' => empty($entity_paypre->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity_paypre->get('uid')->entity->get('depart')->value)->label(),
        'created' => date('Y-m-d H:i', $entity_paypre->get('created')->value),
        'ftype' => $entity_paypre->get('ftype')->target_id,
        'amount' => $entity_paypre->get('amount')->value,
        'pre_amount' => $entity_paypre->get('pre_amount')->value,
        'all_amount' => \Drupal::service('paypre.paypreservice')->getPaypresAmount($entity_paypre),
        'status' => $status[$entity_paypre->get('status')->value],
        'audit' => $audit[$entity_paypre->get('audit')->value],
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @todo 支付单详情页面，物品列表有异常。
   */
  public function getAjaxPayprePartsAutocomplete(Request $request) {
    $input = $request->query->all();
    $storage = \Drupal::entityManager()->getStorage('paypre');
    $entity_paypre = $storage->load($input['id']);

    $entity_purchases = $pidsarray = [];
    $cnos = $entity_paypre->get('cnos');
    foreach ($cnos as $cno) {
      $entity_purchases[] = $cno->entity;
    }
    $part_ids = [];
    foreach ($entity_purchases as $entity_purchase) {
      foreach ($entity_purchase->get('pids') as $pids) {
        $part_ids[] = $pids->entity->id();
      }
    }

    list($part_entities, $matches) = $this->getAjaxCollection($request, 'part', $part_ids);
    // @todo 更新配件列表问题，当前只能显示出一个配件信息
    $audit = getAuditStatus();
    $status = getPurchaseStatus();
    // $matches = new \stdClass();
    $i = 0;
    foreach ($part_entities as $entity_part) {
      $matches->rows[$i]['id'] = $entity_part->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity_part->id(),
        'no' => $entity_part->label(),
        'parttype' => $entity_part->get('parttype')->value,
        'uid' => empty($entity_part->get('uid')->entity->get('realname')->value) ? $entity_part->get('uid')->entity->label() : $entity_part->get('uid')->entity->get('realname')->value,
        'depart' => empty($entity_part->get('uid')->entity->get('depart')->value) ? '-' : taxonomy_term_load($entity_part->get('uid')->entity->get('depart')->value)->label(),
        'created' => date('Y-m-d H:i', $entity_part->get('created')->value),
        'ftype' => $entity_part->get('ftype')->target_id,
      // \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity),.
        'amount' => $entity_part->get('num')->value * $entity_part->get('unitprice')->value,
        'status' => $status[$entity_part->get('ch_status')->value],
        'audit' => $audit[$entity_part->get('ch_audit')->value],
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @description 获取搜索的字符串参数.
   */
  public function getSearchSqlString(Request $request, $storage_query) {
    $input = $request->query->all();
    $filters = json_decode($input['filters']);
    $data = [];
    $data['groupOp'] = $filters->groupOp;
    foreach ($filters->rules as $row) {
      $data[$row->op] = $row->data;
    }
    return $data;
  }

  /**
   * @description 数据导出测试
   */
  public function exportPaypreAutocomplete(Request $request) {
    $input = $request->request->all();
    module_load_include('inc', 'phpexcel');
    $msg = ['false'];
    phpexcel_export(
      [
        'Worksheet1' => [
          'Header1',
          'Header2',
        ],
      ],
      [[
        ['A1', 'B1'],
        ['A2', 'B2'],
      ],
      ],
      '/tmp/file.xls'
    );
    return new JsonResponse($msg);
  }

  /**
   * @description 获取列表数据.
   */
  public function getPaypreDataCollection() {
    $list = new PaypreDataListBuilder();

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
   * @description 自动获取支付单包含的付款单
   * @param $entities
   *   ----> paypre entity
   */
  public function getPurchaseDetailStatisticCollectionAutocomplete(Request $request) {
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
   * @description 历史付款单数据.
   */
  public function getHistoryData() {
    $build['description'] = ['#markup' => '当前列表将会列出付款状态为非待审批和审批中的数据'];
    $history = PaypreHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

  /**
   * @description 付款单审批历史.
   */
  public function getAuditHistoryData() {
    $history = PaypreAuditHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

}
