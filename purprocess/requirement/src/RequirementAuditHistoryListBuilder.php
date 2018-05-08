<?php

namespace Drupal\requirement;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 *
 */
class RequirementAuditHistoryListBuilder {

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
   * @description 获取个人已审批和待审批数据.
   */
  private function getPersonalAudit($auid) {
    $query = db_select('requirement__aids', 'ra');
    $query->leftJoin('audit', 'at', 'ra.aids_target_id = at.id');
    $query->addField('ra', 'entity_id');
    $query->condition('at.auid', $auid);
    $rs = $query->execute()
      ->fetchCol();
    return $rs;
  }

  /**
   * 数据查询.
   */
  public function load() {
    $entity_query = $this->storage->getQuery();

    $entity_query->condition('deleted', 1);

    $audit_nids = $this->getPersonalAudit(\Drupal::currentUser()->id());
    if (empty($audit_nids)) {
      $entity_query->condition('id', 0);
    }
    else {
      $entity_query->condition('id', $audit_nids, 'IN');
    }
    // 所有类需求.
    $entity_query->condition('requiretype', [0, 1, 2], 'IN');
    $entity_query->condition('status', [5, 14, 16], 'IN');
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
    $aid_description = '';
    $aids = $entity->get('aids');
    foreach ($aids as $aid) {
      $aid_entity = $aid->entity;
      if ($aid_entity->get('auid')->target_id == \Drupal::currentUser()->id()) {
        $aid_description = $aid_entity->get('description')->value;
        break;
      }
    }

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
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('title')->value) ? '-' : $entity->get('title')->value,
      '#url' => new Url('entity.requirement.detail_form', ['requirement' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['has'] = \Drupal::service('requirement.requirementservice')->getPartFromRequirement($entity, 1);
    $row['not'] = \Drupal::service('requirement.requirementservice')->getPartFromRequirement($entity, 3);
    $row['uid'] = ['data' => $username];
    $row['created'] = date('Y-m-d', $entity->get('created')->value);
    $row['requiretype'] = $type[$entity->get('requiretype')->value];
    $row['requiredate'] = isset($entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($entity->get('requiredate')->value, 'html_date') : '-';
    // 暂时抑制错误报告.
    $row['status'] = @$status[$entity->get('status')->value];
    // @todo
    $row['audit'] = @$audit[$entity->get('audit')->value];
    $row['audit_description'] = SafeMarkup::format("<font color=red>$aid_description</font>", []);
    // $row['pay_status'] = '部分支付';.
    return $row;
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
    $build['tips'] = ['#markup' => SafeMarkup::format('友情提醒: 前面几列的链接，可打开新窗口浏览，本页仅列出当前用户已审批过的数据! <br/><font color=red>审批意见列</font>仅列出第一条审批意见,如需更多，进入详情页面查看！', [])];
    return $build;
  }

}
