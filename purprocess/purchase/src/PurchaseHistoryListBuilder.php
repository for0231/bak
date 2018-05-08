<?php

namespace Drupal\purchase;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Construct ListBuilder.
 */
class PurchaseHistoryListBuilder {

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
   * @description 自定义load.
   */
  protected function load() {
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $this->storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    if (!\Drupal::currentUser()->hasPermission('administer purchase all history')) {
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    }
    // $entity_query->condition('status', [9, 13, 14], 'IN');.
    $entity_query->condition('status', [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], 'IN');
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
        'specifier' => 'id',
      ],
      'no' => [
        'data' => t('采购单编号'),
        'specifier' => 'no',
      ],
      'title' => [
        'data' => t('名称'),
        'specifier' => 'title',
      ],
      'uid' => [
        'data' => t('申请人'),
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'amount' => [
        'data' => t('总金额'),
        'specifier' => 'amount',
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
    $row['amount'] = \Drupal::service('purchase.purchaseservice')->setFontColor(\Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($entity), "red");
    $row['created'] = \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short');
    $status_color = '';
    switch ($entity->get('status')->value) {
      case 6:
      case 7:
      case 8:
      case 9:
      case 15:
        $status_color = SafeMarkup::format("<font color=red>" . $status[$entity->get('status')->value] . "</font>", []);
        break;

      case 14:
        $status_color = SafeMarkup::format("<font color=green>" . $status[$entity->get('status')->value] . "</font>", []);
        break;

      default:
        $status_color = SafeMarkup::format("<font color=orange>" . $status[$entity->get('status')->value] . "</font>", []);
        break;
    }
    // @$status[$entity->get('status')->value];
    $row['status'] = $status_color;
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
