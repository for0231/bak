<?php

namespace Drupal\paypro;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Construct ListBuilder.
 */
class PayproHistoryListBuilder {

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
      $container->get('entity.manager')->getstorage('paypro')
    );
  }

  /**
   * @description 自定义load.
   */
  protected function load() {
    $storage = \Drupal::entityManager()->getStorage('paypro');
    // $entity_query = $storage->getBaseQuery(); // 自定义storage类.
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    // $entity_query->condition('status', [10, 12], 'IN');.
    $entity_query->condition('status', [4, 5, 6, 8, 10, 12], 'IN');
    if (!\Drupal::currentUser()->hasPermission('administer paypro all history')) {
      $entity_query->condition('uid', \Drupal::currentUser()->id());
    }
    $entity_query->sort('created', 'DESC');

    $entity_query->sort('status', 'ASC');
    $entity_query->pager(20);
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
        'data' => t('金额'),
        'specifier' => 'amount',
      ],
      'faccount' => [
        'data' => t('付款账号'),
        'field' => 'faccount',
        'specifier' => 'faccount',
      ],
      'acceptaccount' => [
        'data' => t('收款账号'),
        'specifier' => 'acceptaccount',
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
    $status = getPayproStatus();
    $audit = getAuditStatus();
    $fnos = $entity->get('fnos');
    $amount = 0;
    $paypre = [];
    foreach ($fnos as $fno) {
      $paypre[] = $fno->entity;
      // $amount += \Drupal::service('paypre.paypreservice')->getPaypresAmount($fno->entity);.
    }
    $current_paypre = reset($paypre);

    $row['id']['data'] = [
      '#type' => 'link',
      '#title' => $entity->id(),
      '#url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['no']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('title')->value) ? '-' : $entity->get('title')->value,
      '#url' => new Url('entity.paypro.detail_form', ['paypro' => $entity->id()]),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['uid'] = ['data' => $username];
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
    $row['ftype'] = $entity->get('ftype')->value;
    // @todo 总金额时显时不显
    $row['amount'] = SafeMarkup::format("<font color=red>" . $entity->get('amount')->value . "</font>", []);
    $row['faccount'] = $entity->get('faccount')->value;
    // 根据付款单的收款账号获取.
    $row['acceptaccount'] = !empty($current_paypre) ? $current_paypre->get('acceptaccount')->value : '-';
    $row['status'] = $status[$entity->get('status')->value];
    $row['audit'] = $audit[$entity->get('audit')->value];

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
