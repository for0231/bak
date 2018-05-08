<?php

namespace Drupal\paypro;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Construct ListBuilder.
 */
class PayproDataListBuilder {
  protected $mode = 4;

  /**
   *
   */
  public function setMode($mode) {
    $this->mode = $mode;
  }

  /**
   * @description 自定义load.
   */
  protected function load() {
    $storage = \Drupal::entityManager()->getStorage('paypro');
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->sort('created', 'DESC');

    $this->customizeSearchCondition($entity_query);
    $this->customizeCondition($entity_query);

    $entity_query->sort('status', 'ASC');
    $entity_query->pager(20);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);

    $ids = $entity_query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   * @description 构建自定义搜索查询条件--需求单模型
   */
  protected function customizeSearchCondition($entity_query) {
    $search_filters = $_SESSION['paypro_filter'];

    if (!empty($search_filters['begin'])) {
      $entity_query->condition('created', strtotime($search_filters['begin']), '>');
    }
    if (!empty($search_filters['end'])) {
      $entity_query->condition('created', strtotime($search_filters['end']), '<');
    }
    if (isset($search_filters['status'])) {
      if ($search_filters['status'] != -999) {
        $entity_query->condition('status', $search_filters['status']);
      }
    }
  }

  /**
   * @description 构建自定义查询条件-需求单模型
   * @todo 查询条件待进一步补充完善。
   */
  protected function customizeCondition($entity_query) {
    $mode = $this->mode;
    switch ($mode) {
      case 1:
        // 1. 全部需求单，按需求单状态升序排序.
        $entity_query->condition('status', [0, 2], 'IN');
        break;

      case 2:
        break;

      case 3:
        // 1. 我发起的需求单模型 -- 列出所有个人的需求单.
        $entity_query->condition('uid', \Drupal::currentUser()->id());
        // 2. 按需求单状态升序排序.
        break;

      case 4:
        // 1. 待我批准的.
        // @description 查询需求单中所有处理待审批状态的需求单
        // @description 查询采购单中所有处理待审批状态的采购单
        // @description 查询付款单中所有处理待审批状态的付款单
        // @description 查询支付单中所有处理待审批状态的支付单
        $ids = \Drupal::service('requirement.requirementservice')->getEntityIdsforCurrentUserbyEntityType('paypro');
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
        break;
    }
  }

  /**
   *
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => t('ID'),
        'specifier' => 'id',
      ],
      'no' => [
        'data' => t('No.'),
        'specifier' => 'no',
      ],
      'title' => [
        'data' => t('名称'),
        'specifier' => 'title',
      ],
      'uid' => [
        'data' => t('申请人'),
        'specifier' => 'uid',
      ],
      'created' => [
        'data' => t('创建日期'),
        'specifier' => 'created',
      ],
      'ftype' => [
        'data' => t('币种'),
        'specifier' => 'ftype',
      ],
      'amount' => [
        'data' => t('金额'),
        'specifier' => 'amount',
      ],
      'faccount' => [
        'data' => t('付款账号'),
        'specifier' => 'faccount',
      ],
      'acceptaccount' => [
        'data' => t('收款账号'),
        'specifier' => 'acceptaccount',
      ],
      'status' => [
        'data' => t('工单状态'),
        'specifier' => 'status',
      ],
      'audit' => [
        'data' => t('审核状态'),
        'specifier' => 'audit',
      ],
      'operations' => [
        'data' => t('操作'),
      ],
    ];

    return $header;
  }

  /**
   *
   */
  protected function buildRow($entity) {
    $username = [
      '#theme' => 'username',
      '#account' => user_load($entity->get('uid')->getString()),
    ];
    $status = getPayproStatus();
    $audit = getAuditStatus();
    $fnos = $entity->get('fnos');
    $amount = 0;
    $paypre = [];
    foreach ($fnos as $fno) {
      $paypre[] = $fno->entity;
      // $amount += \Drupal::service('paypre.paypreservice')->getPaypresAmount($fno->entity);.
    }
    $current_paypre = reset($paypre);

    $row['id']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['no']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('title')->value) ? '-' : $entity->get('title')->value,
      '#url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['uid'] = ['data' => $username];
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
    $row['ftype'] = $entity->get('ftype')->value;
    // @todo 总金额时显时不显
    $row['amount'] = SafeMarkup::format("<font color=red>" . $entity->get('amount')->value . "</font>", []);
    $row['faccount'] = $entity->get('faccount')->value;
    // 根据付款单的收款账号获取.
    $row['acceptaccount'] = !empty($current_paypre) ? $current_paypre->get('acceptaccount')->value : '-';
    $row['status'] = $status[$entity->get('status')->value];
    $row['audit'] = $audit[$entity->get('audit')->value];
    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    ];
    return $row;
  }

  /**
   *
   */
  private function getOperations($entity) {
    $operations = [];

    /**
     * @description 条件设置
     * 1. 工单状态处于[0, 12] 未审批和已取消时，可操作审批流程
     */
    if ($entity->get('audit')->value == 0 && \Drupal::currentUser()->hasPermission('administer paypro edit') && in_array($entity->get('status')->value, [0])) {
      $operations['edit'] = [
        'title' => t('edit'),
        'weight' => 20,
        'url' => new Url('entity.paypro.edit_form', ['paypro' => $entity->id()]),
      ];
    }

    $operations['detail'] = [
      'title' => t('detail'),
      'type' => 'link',
      'weight' => 20,
      'url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
    ];

    /*
    if (\Drupal::moduleHandler()->moduleExists('audit_locale')) {
    $audit_user = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($entity->getEntityTypeId(), $entity->id());
    if (empty($audit_user)) {
    $operations['create_audit_locale'] = [
    'title' => t('创建审批流程'),
    'weight' => 10,
    'url' => new Url('audit_locale.rule.specied.add', ['module' => $entity->getEntityTypeId(), 'id' => $entity->id()]),
    ];
    }
    else {
    $operations['update_audit_locale'] = [
    'title' => t('更新审批流程'),
    'weight' => 10,
    'url' => new Url('audit_locale.rule.specied.add', ['module' => $entity->getEntityTypeId(), 'id' => $entity->id()]),
    ];
    }
    }*/

    if ($entity->get('audit')->value != 0 || $entity->get('status')->value == 12) {
      unset($operations['edit']);
    }

    return $operations;
  }

  /**
   *
   */
  public function build() {
    $datas = $this->load();
    $items = $this->CollectionData($datas);

    $build['list'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => '无数据',
    ];

    foreach ($items as $item) {
      if ($row = $this->buildRow($item)) {
        $build['list']['#rows'][$item->id()] = $row;
      }
    }

    $build['#attached']['library'] = ['requirement/drupal.requirement.default'];
    return drupal_render($build);
  }

  /**
   *
   */
  protected function CollectionData($datas) {
    return $datas;
  }

}
