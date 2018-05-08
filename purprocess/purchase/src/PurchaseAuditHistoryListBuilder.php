<?php

namespace Drupal\purchase;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct ListBuilder.
 */
class PurchaseAuditHistoryListBuilder {

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
  public static function createinstance(containerinterface $container) {
    return new static(
      $container->get('entity.manager')->getstorage('purchase')
    );
  }

  /**
   * @description 获取个人已审批和待审批数据.
   */
  private function getPersonalAudit($auid) {
    $query = db_select('purchase__aids', 'ra');
    $query->leftJoin('audit', 'at', 'ra.aids_target_id = at.id');
    $query->addField('ra', 'entity_id');
    $query->condition('at.auid', $auid);
    $rs = $query->execute()
      ->fetchCol();
    return $rs;
  }

  /**
   * @description 自定义load.
   */
  protected function load() {
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $this->storage->getQuery();
    $audit_nids = $this->getPersonalAudit(\Drupal::currentUser()->id());
    if (empty($audit_nids)) {
      $entity_query->condition('id', 0);
    }
    else {
      $entity_query->condition('id', $audit_nids, 'IN');
    }
    $entity_query->condition('status', [9, 13, 14], 'IN');
    $entity_query->sort('created', 'DESC');

    $entity_query->sort('status', 'ASC');
    $entity_query->pager(50);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();

    return $this->storage->loadMultiple($ids);
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

      'status' => [
        'data' => t('工单状态'),
        'specifier' => 'status',
      ],
      'audit' => [
        'data' => t('审核状态'),
        'specifier' => 'audit',
      ],
      'audit_description' => [
        'data' => t('审批意见'),
        'specifier' => 'audit_description',
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

    $aid_description = '';
    $aids = $entity->get('aids');
    foreach ($aids as $aid) {
      $aid_entity = $aid->entity;
      if ($aid_entity->get('auid')->target_id == \Drupal::currentUser()->id()) {
        $aid_description = $aid_entity->get('description')->value;
        break;
      }
    }

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
    $row['created'] = date('Y-m-d', $entity->get('created')->value);
    $row['status'] = @$status[$entity->get('status')->value];
    $row['audit'] = @$audit[$entity->get('audit')->value];
    $row['audit_description'] = SafeMarkup::format("<font color=red>$aid_description</font>", []);
    // $row['pay_status'] = '部分支付-todo';// @todo
    return $row;
  }

  /**
   *
   */
  private function getOperations($entity) {
    // $operations = parent::getOperations($entity);
    $operations = [];
    $operations['detail'] = [
      'title' => t('detail'),
      'weight' => 10,
      'url' => new Url('entity.purchase.detail_form', ['purchase' => $entity->id()]),
    ];
    if (\Drupal::moduleHandler()->moduleExists('audit_locale')
      && $entity->get('audit')->value == 0
      && \Drupal::currentUser()->hasPermission('administer purchase edit')
      && $entity->get('status')->value != 9) {

      $operations['edit'] = [
        'title' => t('edit'),
        'weight' => 10,
        'url' => new Url('entity.purchase.edit_form', ['purchase' => $entity->id()]),
      ];
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
    }

    // 状态为已取消时，删除所有动作，只留查看详情.
    if ($entity->get('audit')->value != 0 || $entity->get('status')->value == 9 || \Drupal::currentUser()->id() != $entity->get('uid')->target_id) {
      unset($operations['edit']);
    }

    return $operations;
  }

  /**
   *
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => '无数据',
    ];
    $data = $this->load();
    foreach ($data as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }
    $build['pager'] = ['#type' => 'pager'];
    $build['tips'] = ['#markup' => SafeMarkup::format('友情提醒: 前面几列的链接，可打开新窗口浏览，本页仅列出当前用户已审批过的数据! <br/><font color=red>审批意见列</font>仅列出第一条审批意见,如需更多，进入详情页面查看！', [])];
    return $build;

  }

}
