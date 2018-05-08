<?php

namespace Drupal\paypre;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct ListBuilder.
 */
class PaypreHistoryListBuilder {

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
   * @description 自定义load.
   */
  protected function load() {
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $this->storage->getQuery();
    // $entity_query->condition('status', [7, 9, 11, 20], 'IN');.
    $entity_query->condition('status', [4, 5, 6, 7, 8, 9, 11, 20], 'IN');
    if (!\Drupal::currentUser()->hasPermission('administer paypre all history')) {
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    }
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
    $row['amount'] = SafeMarkup::format("<font color=red>" . $entity->get('amount')->value . "</font>", []);
    // 付款单每次生成并计算填充到付款单的数值;.
    $row['pre_amount'] = \Drupal::service('purchase.purchaseservice')->setFontColor($entity->get('pre_amount')->value, "gray");
    $row['all_amount'] = \Drupal::service('purchase.purchaseservice')->setFontColor($amount, "gray");
    $row['status'] = @$status[$entity->get('status')->value];
    $row['audit'] = @$audit[$entity->get('audit')->value];

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
    $build['tips'] = ['#markup' => '友情提醒: 前面几列的链接，可打开新窗口浏览，最后一列的按钮，则会本页跳转!'];
    return $build;

  }

}
