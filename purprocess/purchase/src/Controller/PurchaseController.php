<?php

namespace Drupal\purchase\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\purchase\PurchaseDataListBuilder;
use Drupal\purchase\PurchaseHistoryListBuilder;
use Drupal\purchase\PurchaseAuditHistoryListBuilder;

/**
 *
 */
class PurchaseController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a PartLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatter $date_formatter, FormBuilderInterface $form_builder) {
    $this->database      = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder   = $form_builder;
  }

  /**
   * 需求池勾选part后，响应创建采购单.
   *
   * @deprecated 弃用
   */
  public function createPurchaseAutocomplete(Request $request) {
    $input = $request->request->all();
    if (is_array($input['choices'])) {
      $choices = current($input['choices']);
    }
    else {
      $choices = [];
    }
    // @todo 检查part里面是否已经存在cno，已存在，则不创建，返回error。
    $msg = 'ok';
    if (!empty($choices)) {
      $check = \Drupal::service('part.partservice')->checkPartStatusById($choices);
      if ($check) {
        // 创建采购单数据.
        \Drupal::service('purchase.purchaseservice')->save(NULL, FALSE, $choices);
        $msg = '创建采购单-成功';
      }
      else {
        $msg = '创建采购单-失败';
      }
    }
    else {
      $msg = '创建采购单-失败';
    }
    return new JsonResponse($msg);
  }

  /**
   * 需求池勾选part后，追加需求配件到采购单.
   */
  public function appendParts2PurchaseAutocomplete(Request $request, $purchase) {
    $input = $request->request->all();
    if (is_array($input['choices'])) {
      $choices = current($input['choices']);
    }
    else {
      $choices = [];
    }

    // @todo 检查part里面是否已经存在cno，已存在，则不创建，返回error。
    $msg = 'ok';
    if (!empty($choices)) {
      // 检查part 是否为正常的需求物品.
      $check_part = \Drupal::service('part.partservice')->checkPartStatusById($choices);

      // 检查purchase 工单状态是否为未审批.
      $check_purchase = \Drupal::service('purchase.purchaseservice')->checkPurchaseStatusById($purchase);
      if ($check_part && $check_purchase) {

        // 添加配件到采购单.
        $storage = $purchase_entity = \Drupal::entityTypeManager()->getStorage('purchase');
        $purchase_entity = $storage->load($purchase);

        // 将part的id保存到purchase里。.
        \Drupal::service('purchase.purchaseservice')->save($purchase_entity, TRUE, $choices);

        // 将purchase的id保存到每个part里。.
        $part_status = \Drupal::service('part.partservice')->savePartscno($purchase_entity, $choices);
        if ($part_status) {
          $msg = '添加配件-成功';
        }
        else {
          $msg = '添加配件-失败';
        }
      }
      else {
        $msg = '添加配件-失败';
      }
    }
    else {
      $msg = '添加配件-失败';
    }
    return new JsonResponse($msg);
  }

  /**
   * @description purchase edit page
   * @see ajax.purchase.parts.collection
   */
  public function getPurchasePartsAutocomplete(Request $request, $purchase) {
    $storage = \Drupal::entityTypeManager()->getStorage('purchase');
    $entity = $storage->load($purchase);
    $matches = [];
    try {
      $parts = $entity->get('pids');
    }
    catch (\Exception $e) {
      error_log(print_r($e, 1));
    }
    $ids = [];
    foreach ($parts as $row) {
      $ids[] = $row->entity->id();
    }

    list($entities, $matches) = $this->getAjaxCollection($request, 'part', $ids);
    $i = $am_num = 0;
    foreach ($entities as $entity) {
      $part = $entity;
      $name = $entity->get('name')->value;
      $unit = taxonomy_term_load($entity->get('unit')->target_id);
      $locate = taxonomy_term_load($entity->get('locate_id')->target_id);

      $supply = '';
      if ($entity->get('supply_id')->target_id) {
        $supply = taxonomy_term_load($entity->get('supply_id')->target_id);
      }
      else {
        $supply = '';
      }

      $ship_supply = !empty($entity->get('ship_supply_id')->target_id) ? taxonomy_term_load($entity->get('ship_supply_id')->target_id) : '';

      $entity_requirement = requirement_load($entity->get('rno')->value);
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'name' => $entity->get('name')->value,
        'rno' => \Drupal::l($entity_requirement->label(), new Url('entity.requirement.detail_form', ['requirement' => $entity_requirement->id()], ['attributes' => ['target' => '_blank']])),
        'type' => $entity->get('parttype')->value,
        'locate_id' => $locate->label(),
        'supply_id' => !empty($supply) ? $supply->label() : '-',
        'sdate' => \Drupal::service('date.formatter')->format($entity->get('requiredate')->value, 'html_date'),
        'pdate' => $entity->get('plandate')->value == 0 ? '-' : \Drupal::service('date.formatter')->format($entity->get('plandate')->value, 'html_date'),
        'wuliu' => !empty($ship_supply) ? $ship_supply->label() : '-',
        'wuliuno' => $entity->get('ship_supply_no')->value,
        'ftype' => empty($entity->get('ftype')->target_id) ? '-' : $entity->get('ftype')->entity->id(),
        'unitprice' => $entity->get('unitprice')->value,
        'wuliufee' => isset($entity->get('wuliufee')->value) ? $entity->get('wuliufee')->value : 0 ,
        'num' => $entity->get('num')->value,
        'unit' => $unit->label(),
        'amount' => $entity->get('num')->value * $entity->get('unitprice')->value,
      ];

      if ($entity->get('wuliufee')->value > 0) {
        $fee = $entity->get('wuliufee')->value;
      }
      else {
        $fee = 0;
      }
      $i++;
      $am_num += $entity->get('num')->value;
      $am_amount += $entity->get('num')->value * $entity->get('unitprice')->value + $fee;

    }
    // Userdata.
    $matches->userdata['unitprice'] = '总金额:';
    $matches->userdata['num'] = $am_num;
    $matches->userdata['amount'] = $am_amount;
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
        $storage_query->condition('id', $ids, 'IN');
        break;

      case 'purchase':
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
   * 勾选删除需求的配件信息.
   */
  public function operationPurchasePartsAutocomplete(Request $request, $purchase) {
    $result = ['false'];
    $items = $request->request->all();

    switch ($items['oper']) {
      case 'del':
        $ids = explode(',', $items['id']);
        \Drupal::service('part.partservice')->cancelPartscno($purchase, $ids);
        \Drupal::service('purchase.purchaseservice')->deletePartFromPurchaseById($purchase, $ids);

        $result = ['true'];
        break;

      case 'edit':
        if (!empty($items['pdate'])) {
          // 修改part的plandate日期.
          $pdate = strtotime($items['pdate']);
          $status = \Drupal::service('part.partservice')->setPlandate($items['id'], $pdate);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        if (!empty($items['supply_id'])) {
          // 修改part的供应商id.
          $status = \Drupal::service('part.partservice')->setSupplyCompany($items['id'], $items['supply_id']);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        if (!empty($items['ftype'])) {
          // @todo 处理币种保存问题
          $status = \Drupal::service('part.partservice')->setFtype($items['id'], $items['ftype']);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        if (!empty($items['unitprice'])) {
          // 修改part的unitprice.
          $status = \Drupal::service('part.partservice')->setUnitprice($items['id'], $items['unitprice']);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        if (!empty($items['wuliu'])) {
          // 修改part的供应商id.
          $status = \Drupal::service('part.partservice')->setShipSupplyId($items['id'], $items['wuliu']);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        if (!empty($items['wuliuno'])) {
          // 修改part的供应商id.
          $status = \Drupal::service('part.partservice')->setShipSupplyNo($items['id'], $items['wuliuno']);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        if (!empty($items['wuliufee'])) {
          // 修改part的供应商id.
          $status = \Drupal::service('part.partservice')->setWuliufeeNo($items['id'], $items['wuliufee']);
          $result = $status == 1 ? ['true'] : ['false'];
        }
        $result = ['true'];
        break;

      default:
        $result = ['false'];
    }

    return new JsonResponse($result);
  }

  /**
   * 处理采购池数据.
   *
   * @see entity.purchase.pools.collection
   */
  public function getPurchasePools(Request $request) {

    // @todo 采购池数据，需要过滤已经建立付款单后的采购单数据。

    $build = [];
    $build['part']['#theme'] = 'purchases_pool';
    $build['#attached']['library'] = ['purchase/purchasespool'];
    return $build;
  }

  /**
   * Ajax get parts.
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
  public function getPurchaseAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'purchase');
    $i = 0;
    foreach ($entities as $entity) {
      // $currency_storage = \Drupal::entityTypeManager()->getStorage('currency');
      $pids = $entity->get('pids');
      $parts = [];
      foreach ($pids as $row) {
        $parts[$row->entity->id()] = $row->entity;
      }
      // 取第一个part实体
      // 一个采购单只包含一类币种。.
      // @todo 一个采购单可能会包含多个供应商
      $part = current($parts);

      $ftype = empty($part->get('ftype')->target_id) ? 0 : $part->get('ftype')->entity->id();

      // 处理供应商字段内容.
      $supply = $part->get('supply_id')->target_id == 0 ? '-' : $part->get('supply_id')->entity->label();

      $amount_price = \Drupal::service('part.partservice')->getAmount($parts);
      $part_nums = \Drupal::service('purchase.purchaseservice')->getPurchaseofPartsNumberAccount($entity);
      $aver_price = round($amount_price / $part_nums, 3);

      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'no' => \Drupal::l($entity->label(), new Url('entity.purchase.detail_form', ['purchase' => $entity->id()], ['attributes' => ['target' => '_blank']])),
        'title' => $entity->get('title')->value,
        'supply_id' => $supply,
        'ftype' => $ftype,
        'part_nums' => $part_nums,
        'aver_price' => $aver_price,
        'amount' => SafeMarkup::format("<font color=red>" . $amount_price . "</font>", []),
      ];

      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   *
   */
  public function operatePurchaseAutocomplete(Request $request) {

    // 采购单的删除条件是: 采购单处于未审批状态，采购配件的rno, cno存在
    // 删除过后需要重置part的rno为0.
    $result = ['false'];

    // @todo
    // 判定该采购单的审批条件，如果已经被审批过，无法执行当前操作,只能是未审批状态下，方可执行后续操作
    if ($purchase->get('status')->value != 0) {
      return new JsonResponse($result);
    }

    $items = $request->request->all();
    if ($items['oper'] == 'del') {
      $ids = explode(',', $items['id']);
      // 只能是处理未审批的采购单才能删除。.
      $status_purchase = \Drupal::service('purchase.purchaseservice')->checkPurchaseStatusById($ids);
      if ($status_purchase) {

        // Part cno change.
        list($purchases, $parts) = \Drupal::service('purchase.purchaseservice')->getPurchaseParts($ids);

        foreach ($parts as $part) {
          $part->set('cno', 0)->save();
        }
        foreach ($purchases as $purchase) {
          $purchase->delete();
        }
      }
      $result = ['true'];
    }
    else {
      $result = ['false'];
    }

    return new JsonResponse($result);
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
   * @description 获取列表数据.
   */
  public function getPurchaseDataCollection() {
    $list = new PurchaseDataListBuilder();

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
   * @description 获取历史性采购数据.
   */
  public function getHistoryData() {
    $build['description'] = ['#markup' => '本列表将列出非待审批和审批中的采购单数据'];
    $history = PurchaseHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

  /**
   * @description 需求单审批历史.
   */
  public function getAuditHistoryData() {
    $history = PurchaseAuditHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

}
