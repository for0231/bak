<?php

namespace Drupal\audit;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct ListBuilder.
 */
class AuditHistoryListBuilder {

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
      $container->get('entity.manager')->getstorage('audit')
    );
  }

  /**
   * @description 自定义load.
   */
  protected function load() {
    $storage = \Drupal::entityManager()->getStorage('audit');
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');

    // @description 没有权限则只能查看自己审批过的数据
    if (!\Drupal::currentUser()->hasPermission('administer audit allhistory collection')) {
      $entity_query->condition('auid', \Drupal::currentUser()->id());
    }

    $entity_query->sort('status', 'DESC');
    $entity_query->pager(10);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);

    $ids = $entity_query->execute();

    return $storage->loadMultiple($ids);
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
      'uid' => [
        'data' => t('申请人'),
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'auid' => [
        'data' => t('审批人'),
        'field' => 'auid',
        'specifier' => 'auid',
      ],
      'type' => [
        'data' => t('类型'),
        'specifier' => 'type',
      ],
      'title' => [
        'data' => t('名称'),
        'specifier' => 'title',
      ],
      'description' => [
        'data' => t('审批意见'),
        'specifier' => 'description',
      ],
      'status' => [
        'data' => t('审核状态'),
        'field' => 'status',
        'specifier' => 'status',
      ],
      'created' => [
        'data' => t('创建日期'),
        'field' => 'created',
        'specifier' => 'created',
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

    $audit_username = [
      '#theme' => 'username',
      '#account' => user_load($entity->get('auid')->getString()),
    ];

    $audit_status = getAuditStatus();

    list($model_type, $model_title) = \Drupal::service('audit.auditservice')->getAuditModelData($entity->id());
    $row['id'] = $entity->id();
    $row['uid'] = ['data' => $username];
    $row['auid'] = ['data' => $audit_username];
    $row['type'] = $model_type;
    $row['title'] = SafeMarkup::format($model_title, []);

    $row['description'] = empty($entity->get('description')->value) ? '-' : SafeMarkup::format($entity->get('description')->value, []);
    $row['status'] = $audit_status[$entity->get('status')->value];
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);

    return $row;
  }

  /**
   *
   */
  private function getOperations($entity) {
    $operations = [];
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
    return $build;

  }

}
