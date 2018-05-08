<?php

namespace Drupal\paypre;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct ListBuilder.
 */
class PaypreAuditHistoryListBuilder {

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
      $container->get('entity.manager')->getstorage('paypre')
    );
  }

  /**
   * @description 获取个人已审批和待审批数据.
   */
  private function getPersonalAudit($auid) {
    $query = db_select('paypre__aids', 'ra');
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

    $entity_query->condition('status', [7, 9, 11, 20], 'IN');
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
        'data' => t('No.'),
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
      'contact_no' => [
        'data' => t('合同号'),
        'field' => 'contact_no',
        'specifier' => 'contact_no',
      ],
      'created' => [
        'data' => t('创建日期'),
        'field' => 'created',
        'specifier' => 'created',
      ],
      'ftype' => [
        'data' => t('币种'),
        'field' => 'ftype',
        'specifier' => 'ftype',
      ],
      'amount' => [
        'data' => t('预付金额'),
        'specifier' => 'amount',
      ],
      'pre_amount' => [
        'data' => t('应付金额'),
      ],
      'all_amount' => [
        'data' => t('总金额'),
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
    $status = getPaypreStatus();
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

    $cnos = $entity->get('cnos');
    $amount = 0;
    foreach ($cnos as $cno) {
      $amount += \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity);
    }
    $row['id']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => new Url('entity.paypre.detail_form', ['paypre' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['no']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => new Url('entity.paypre.detail_form', ['paypre' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('title')->value) ? '-' : $entity->get('title')->value,
      '#url' => new Url('entity.paypre.detail_form', ['paypre' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['uid'] = ['data' => $username];
    $row['contact_no'] = empty($entity->get('contact_no')->value) ? '-' : $entity->get('contact_no')->value;
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
    $row['ftype'] = $entity->get('ftype')->target_id;
    $row['amount'] = SafeMarkup::format("<font color=red>" . $entity->get('amount')->value . "</font>", []);;
    // 付款单每次生成并计算填充到付款单的数值;.
    $row['pre_amount'] = $entity->get('pre_amount')->value;
    $row['all_amount'] = $amount;
    $row['status'] = @$status[$entity->get('status')->value];
    $row['audit'] = @$audit[$entity->get('audit')->value];
    $row['audit_description'] = SafeMarkup::format("<font color=red>$aid_description</font>", []);
    return $row;
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
