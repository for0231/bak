<?php

namespace Drupal\requirement;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class RequirementHistoryListBuilder {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Protected $hostclient_service;.
   */
  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
    // $this->hostclient_service = \Drupal::service('hostclient.serverservice');.
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('requirement')
    );
  }

  /**
   * 数据查询.
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->condition('deleted', 1);
    if (!\Drupal::currentUser()->hasPermission('administer requirement all history')) {
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    }
    // 所有类需求.
    $entity_query->condition('requiretype', [0, 1, 2], 'IN');
    // $entity_query->condition('status', [14, 16], 'IN');.
    $entity_query->condition('status', [4, 5, 6, 8, 10, 12, 14, 16], 'IN');
    $entity_query->condition('audit', 3);
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
        'data' => t('ID'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'no' => [
        'data' => t('No.'),
        'field' => 'no',
        'specifier' => 'no',
      ],
      'title' => [
        'data' => t('名称'),
        'field' => 'title',
        'specifier' => 'title',
      ],
      'num' => [
        'data' => t('数量'),
        'field' => 'num',
        'specifier' => 'num',
      ],
      'has' => [
        'data' => t('已采购'),
      ],
      'not' => [
        'data' => t('未采购'),
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
      'requiretype' => [
        'data' => t('类型'),
        'field' => 'requiretype',
        'specifier' => 'requiretype',
      ],
      'requiredate' => [
        'data' => t('需求交付时间'),
        'field' => 'requiredate',
        'specifier' => 'requiredate',
      ],
      'status' => [
        'data' => t('工单状态'),
        'specifier' => 'status',
      ],
      'audit' => [
        'data' => t('审核状态'),
        'specifier' => 'audit',
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
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $username = [
      '#theme' => 'username',
      '#account' => user_load($entity->get('uid')->getString()),
    ];
    $type = getRequirementType();
    $status = getRequirementStatus();
    $audit = getAuditStatus();
    $ship_status = getShipStatus();
    // $row['no'] = $entity->label();
    $row['id']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => new Url('entity.requirement.detail_form', ['requirement' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['no']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => new Url('entity.requirement.detail_form', ['requirement' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['title'] = empty($entity->get('title')->value) ? '-' : $entity->get('title')->value;
    $row['num'] = $entity->get('num')->value;
    $row['has'] = \Drupal::service('requirement.requirementservice')->getPartFromRequirement($entity, 1);
    $row['not'] = \Drupal::service('requirement.requirementservice')->getPartFromRequirement($entity, 3);
    $row['uid'] = ['data' => $username];
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
    $row['requiretype'] = $type[$entity->get('requiretype')->value];
    $row['requiredate'] = isset($entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($entity->get('requiredate')->value, 'html_date') : '-';
    // 暂时抑制错误报告.
    $row['status'] = @$status[$entity->get('status')->value];
    // @todo
    $row['audit'] = @$audit[$entity->get('audit')->value];
    // $row['ship_status'] = $ship_status[$entity->get('ship_status')->value];
    // $row['pay_status'] = '部分支付';.
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = [];
    $operations['detail'] = [
      'title' => $this->t('detail'),
      'weight' => 10,
      'url' => $entity->urlInfo('detail-form'),
    ];
    // 非审批状态下可对需求单编辑.
    if (isset($operations['edit'])) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      ];
    }

    if (\Drupal::moduleHandler()->moduleExists('audit_locale') && $entity->get('audit')->value == 0 && \Drupal::currentUser()->hasPermission('administer requirement edit') && $entity->get('status')->value != 8) {
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
    // 状态为已取消时，删除所有动作，只留查看详情.
    if ($entity->get('audit')->value != 0 || $entity->get('status')->value == 8 || \Drupal::currentUser()->id() != $entity->get('uid')->target_id) {
      unset($operations['edit']);
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => '没有未处理数据',
    ];
    $data = $this->load();
    foreach ($data as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }
    $build['pager'] = ['#type' => 'pager'];
    $build['tips'] = ['#markup' => '友情提醒: 前面几列的链接，可打开新窗口浏览，最后一列的按钮，则会本页跳转!'];
    return $build;
  }

}
