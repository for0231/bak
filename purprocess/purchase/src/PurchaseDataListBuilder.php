<?php

namespace Drupal\purchase;

use Drupal\Core\Url;

/**
 * Construct ListBuilder.
 */
class PurchaseDataListBuilder {
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
    $storage = \Drupal::entityManager()->getStorage('purchase');
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->sort('created', 'DESC');

    $this->customizeSearchCondition($entity_query);
    $this->customizeCondition($entity_query);

    $entity_query->sort('status', 'ASC');
    $entity_query->pager(500);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   * @description 构建自定义搜索查询条件--需求单模型
   */
  protected function customizeSearchCondition($entity_query) {
    $search_filters = $_SESSION['purchase_filter'];

    if (!empty($search_filters['begin'])) {
      $entity_query->condition('created', strtotime($search_filters['begin']), '>');
    }
    if (!empty($search_filters['end'])) {
      $entity_query->condition('created', strtotime($search_filters['end']), '<');
    }
    if (isset($search_filters['status'])) {
      if ($search_filters['status'] != -999 || $search_filters['status'] == 0) {
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
        // @description 查询采购单中所有处理待审批状态的需求单
        $ids = \Drupal::service('requirement.requirementservice')->getEntityIdsforCurrentUserbyEntityType('purchase');
        if (!empty($ids)) {
          $entity_query->condition('id', $ids, 'IN');
        }
        else {
          // 2. 待我处理的需求单模型.
          // @description 所有个人创建的并且状态为未审批的
          // 3. 自己的，未审批，审批中的单据
          // 需求单未处理状态.
          $entity_query->condition('status', [0, 2], 'IN');
          // 需求单未处理状态.
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
        'field' => 'id',
        'specifier' => 'id',
      ],
      'no' => [
        'data' => t('采购单编号'),
        'field' => 'no',
        'specifier' => 'no',
      ],
      'title' => [
        'data' => t('名称'),
        'field' => 'title',
        'specifier' => 'title',
      ],
      'uid' => [
        'data' => t('申请人'),
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'created' => [
        'data' => t('创建日期'),
        'field' => 'created',
        'specifier' => 'created',
      ],
      'amount' => [
        'data' => t('总金额'),
        'specifier' => 'amount',
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
      /*
      'pay_status' => [
        'data' => $this->t('支付状态'),
        'specifier' => 'pay_status',
      ],*/

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
    // $type = getRequirementType();
    $status = getPurchaseStatus();
    $audit = getAuditStatus();
    $row['id']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => new Url('entity.purchase.detail_form', ['purchase' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['no']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => new Url('entity.purchase.detail_form', ['purchase' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('title')->value) ? '-' : $entity->get('title')->value,
      '#url' => new Url('entity.purchase.detail_form', ['purchase' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['uid'] = ['data' => $username];
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
    $row['amount'] = \Drupal::service('purchase.purchaseservice')->setFontColor(\Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($entity), "red");
    $row['status'] = @$status[$entity->get('status')->value];
    $row['audit'] = @$audit[$entity->get('audit')->value];
    // $row['pay_status'] = '部分支付-todo';// @todo
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
    // $operations = parent::getOperations($entity);
    $operations = [];

    if ($entity->get('audit')->value == 0
      && \Drupal::currentUser()->hasPermission('administer purchase edit')
      && $entity->get('status')->value != 9) {

      $operations['edit'] = [
        'title' => t('edit'),
        'weight' => 10,
        'url' => new Url('entity.purchase.edit_form', ['purchase' => $entity->id()]),
      ];
    }

    $operations['detail'] = [
      'title' => t('detail'),
      'weight' => 10,
      'url' => new Url('entity.purchase.detail_form', ['purchase' => $entity->id()]),
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

    // 状态为已取消时，删除所有动作，只留查看详情.
    if ($entity->get('audit')->value != 0 || $entity->get('status')->value == 9 || \Drupal::currentUser()->id() != $entity->get('uid')->target_id) {
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
    $build['left'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['left'],
      ],
    ];
    $build['left']['list'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => '无数据',
    ];

    foreach ($items as $item) {
      if ($row = $this->buildRow($item)) {
        $build['left']['list']['#rows'][$item->id()] = $row;
      }
    }

    return drupal_render($build);
  }

  /**
   *
   */
  protected function CollectionData($datas) {
    return $datas;
  }

}
