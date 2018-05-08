<?php

namespace Drupal\purchase;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

use Drupal\Core\Url;

/**
 * Defines a class to build a listing of purchase entities.
 *
 * @see \Drupal\purchase\Entity\Purchase
 */
class PurchaseListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->sort('created', 'DESC');
    $entity_query->pager(50);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();

    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'no' => [
        'data' => $this->t('采购单编号'),
        'field' => 'no',
        'specifier' => 'no',
      ],
      'uid' => [
        'data' => $this->t('申请人'),
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'created' => [
        'data' => $this->t('创建日期'),
        'field' => 'created',
        'specifier' => 'created',
      ],

      'status' => [
        'data' => $this->t('工单状态'),
        'specifier' => 'status',
      ],
      'audit' => [
        'data' => $this->t('审核状态'),
        'specifier' => 'audit',
      ],
      /*
      'pay_status' => [
        'data' => $this->t('支付状态'),
        'specifier' => 'pay_status',
      ],*/

    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
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
    $row['uid'] = ['data' => $username];
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
    $row['status'] = @$status[$entity->get('status')->value];
    $row['audit'] = @$audit[$entity->get('audit')->value];
    // $row['pay_status'] = '部分支付-todo';// @todo
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    /*
    $operations['detail'] = array(
    'title' => $this->t('Detail'),
    'weight' => 1,
    'url' => $entity->urlInfo('detail-form'),
    );*/

    if (\Drupal::moduleHandler()->moduleExists('audit_locale') && $entity->get('audit')->value == 0 && \Drupal::currentUser()->hasPermission('administer purchase edit') && $entity->get('status')->value != 9) {
      $audit_user = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($entity->getEntityTypeId(), $entity->id());
      if (empty($audit_user)) {
        $operations['create_audit_locale'] = [
          'title' => $this->t('创建审批流程'),
          'weight' => 10,
          'url' => new Url('audit_locale.rule.specied.add', ['module' => $entity->getEntityTypeId(), 'id' => $entity->id()]),
        ];
      }
      else {
        $operations['update_audit_locale'] = [
          'title' => $this->t('更新审批流程'),
          'weight' => 10,
          'url' => new Url('audit_locale.rule.specied.add', ['module' => $entity->getEntityTypeId(), 'id' => $entity->id()]),
        ];
      }
    }

    if (isset($operations['edit'])) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      ];
      // unset($operations['edit']);.
    }
    // 状态为已取消时，删除所有动作，只留查看详情.
    if ($entity->get('audit')->value != 0 || $entity->get('status')->value == 9 || \Drupal::currentUser()->id() != $entity->get('uid')->target_id) {
      unset($operations['edit']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // $build = parent::render();
    //    $build['table']['#empty'] = $this->t('没有可用的采购单数据.');
    // 构建搜索框.
    $build['filter'] = \Drupal::service('form_builder')->getForm('Drupal\purchase\Form\PurchaseFilterForm');
    $build['filter']['#weight'] = -100;
    $build['mode'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline', 'list-filter', 'list-mode'],
      ],
      '#weight' => -99,
    ];

    // $modes = [1 => '全体模式', 2=>'部门模式', 3=>'个人模式', 4=>'精简模式'];.
    $build['mode']['title'] = [
      '#type' => 'label',
      '#title' => '查询模式：',
      '#title_display' => 'before',
    ];
    $modes = [];

    if (\Drupal::currentUser()->hasPermission('administer purchase data modes')) {
      // 有管理权限，则可查看全体模式.
      $modes = [1 => '全体模式', 4 => '精简模式'];
    }
    else {
      // 无管理权限，仅能查看个人模式.
      $modes = [4 => '精简模式'];
    }
    foreach ($modes as $key => $mode) {
      $build['mode']['mode' . $key] = [
        '#type' => 'radio',
        '#title' => $mode,
        '#name' => 'mode',
        '#attributes' => [
          'value' => $key,
        ],
      ];
    }
    $default_mode = 4;
    if (!empty($_SESSION['collection_data_list'])) {
      $default_mode = $_SESSION['collection_data_list']['mode'];
    }
    $build['mode']['mode' . $default_mode]['#attributes']['checked'] = 'checked';
    $build['content'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['ajax-content'],
        'ajax-path' => \Drupal::url('admin.purchase.collection.data'),
      ],
      '#weight' => -98,
      '#attached' => [
      // js使用通用格式.
        'library' => ['requirement/drupal.requirement.default'],
      ],
    ];

    $build['tips'] = ['#markup' => '全体模式: 所有未完成处理的数据</br>精简模式: 个人待处理数据-包括待审批<br/>友情提醒: <br/>前面几列的链接，可打开新窗口浏览，最后一列的按钮，则会本页跳转!<br/>统一工作台：包含所有类型的待处理数据列表。'];
    return $build;
  }

}
