<?php

namespace Drupal\cg_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Returns responses for cg_integration module administrative routes.
 */
class CGIntegrationController extends ControllerBase {

  /**
   *
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => t('ID'),
      ],
      'type' => [
        'data' => t('类型'),
      ],
      'title' => [
        'data' => t('名称'),
      ],
      'amount' => [
        'data' => t('金额'),
      ],
      'uid' => [
        'data' => t('申请人'),
      ],
      'created' => [
        'data' => t('创建日期'),
      ],
      'audit' => [
        'data' => t('审核状态'),
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
    $entity_type = $entity->getEntityTypeId();
    $audit = getAuditStatus();
    $row['id']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => new Url('entity.' . $entity_type . '.detail_form', [$entity_type => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['type'] = \Drupal::entityManager()->getStorage($entity_type)->getEntityType()->getLabel()->getUntranslatedString();
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('title')->value) ? '-' : $entity->get('title')->value,
      '#url' => new Url('entity.' . $entity_type . '.detail_form', [$entity_type => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['amount'] = \Drupal::service('purchase.purchaseservice')->setFontColor($this->getAmountforModuleEntity($entity), 'red');
    $row['uid'] = ['data' => $username];
    $row['created'] = date('Y-m-d', $entity->get('created')->value);
    $row['audit'] = @$audit[$entity->get('audit')->value];
    $row['operations']['data'] = [
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    ];
    return $row;
  }

  /**
   *
   */
  private function getAmountforModuleEntity($entity) {
    $amount = 0;
    switch ($entity->getEntityTypeId()) {
      case 'requirement':
        break;

      case 'purchase':
        $amount = \Drupal::service('purchase.purchaseservice')->getPurchaseAmount($entity);
        break;

      case 'paypre':
        $amount = \Drupal::service('paypre.paypreservice')->getPaypreAmount($entity);
        break;

      case 'paypro':
        $amount = \Drupal::service('paypro.payproservice')->getSinglePayproAmount($entity);
        break;
    }

    return $amount;
  }

  /**
   *
   */
  private function getOperations($entity) {
    // $operations = parent::getOperations($entity);
    $operations = [];

    $entity_type = $entity->getEntityTypeId();

    if ($entity->get('audit')->value == 0
      && \Drupal::currentUser()->hasPermission('administer requirement edit')
      && $entity->get('status')->value != 8) {
      $operations['edit'] = [
        'title' => t('Edit'),
        'weight' => 10,
        'url' => new Url('entity.' . $entity_type . '.edit_form', [$entity_type => $entity->id()]),
      ];
    }
    $operations['detail'] = [
      'title' => t('detail'),
      'weight' => 10,
      'url' => new Url('entity.' . $entity_type . '.detail_form', [$entity_type => $entity->id()]),
    ];
    /*
    if (\Drupal::moduleHandler()->moduleExists('audit_locale')) {
    $audit_user = \Drupal::service('audit_locale.audit_localeservice')
    ->getModuleAuditLocale($entity->getEntityTypeId(), $entity->id());
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
    if ($entity->get('audit')->value != 0 || $entity->get('status')->value == 8 || \Drupal::currentUser()->id() != $entity->get('uid')->target_id) {
      unset($operations['edit']);
    }
    return $operations;
  }

  /**
   * @description 重构admin首页页面结构.
   */
  public function mainPage() {
    $modules = ['requirement', 'purchase', 'paypre', 'paypro'];

    list($entities) = \Drupal::service('paypre.paypreservice')->getAjaxDataCollection($modules);

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
    foreach ($entities as $item) {
      if ($row = $this->buildRow($item)) {
        $build['left']['list']['#rows'][$item->id()] = $row;
      }
    }

    $build['tips'] = ['#markup' => '友情提醒: 前面几列的链接，可打开新窗口浏览，最后一列的各项按钮，则会本页跳转!<br/>当前列表是全部类型的待处理数据。<br/>正常的操作顺序是：先编辑对应的页面，然后在详情页面发起审批。<br/> 创建审批流程: 当前单据的审批人不满足当前的审批流程时，可以自定义审批人。'];
    return $build;
  }

  /**
   * @description 重构admin首页页面结构.
   */
  public function indexPage() {
    return ['#markup' => 'index page'];
  }

}
