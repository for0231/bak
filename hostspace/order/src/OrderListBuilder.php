<?php

/**
 * @file
 * Contains \Drupal\order\OrderListBuilder.
 */

namespace Drupal\order;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Defines a class to build a listing of switch ip entities.
 *
 * @see \Drupal\order\Entity\order
 */
class OrderListBuilder extends EntityListBuilder {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory.
   */
   protected $queryFactory;

   /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
   protected $formBuilder;

   /**
   * Constructs a new BusinessIpListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *  The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *  The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  The entity query factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage,  QueryFactory $query_factory, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
			$container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('order');
    $entity_query->pager(PER_PAGE_COUNT);
    // 条件筛选
	  $this->filterForm($entity_query);

  	$header = $this->buildHeader();
    $entity_query->tableSort($header);

    $order_id = $entity_query->execute();
    return $this->storage->loadMultiple($order_id);
  }

  /**
   * 筛选表单
   *
   * @param $entity_query
   *
   */
  private function filterForm($entity_query) {
    if(!empty($_SESSION['order_filter'])) {
      if(!empty($_SESSION['order_filter']['oid'])) {
        $entity_query->condition('code',$_SESSION['order_filter']['oid'],'CONTAINS');
      }
      if(!empty($_SESSION['order_filter']['title'])) {
        $entity_query->condition('alias_order',$_SESSION['order_filter']['title'],'CONTAINS');
      }
      if($_SESSION['order_filter']['status'] != -1) {
        $entity_query->condition('status',$_SESSION['order_filter']['status'],'=');
      }
      if(!empty($_SESSION['order_filter']['uid'])) {
        $entity_query->condition('uid',$_SESSION['order_filter']['uid'],'=');
      }
      if(!empty($_SESSION['order_filter']['client_service'])) {
        $entity_query->condition('client_service',$_SESSION['order_filter']['client_service'],'=');
      }

      $start = isset($_SESSION['order_filter']['start']) ? strtotime($_SESSION['order_filter']['start']) : '' ;
      $expire = isset($_SESSION['order_filter']['expire']) ? strtotime($_SESSION['order_filter']['expire']) : '' ;
      if(!empty($start)) {
        $entity_query->condition('created',$start,'>=');
      }
      if(!empty($expire)) {
        $entity_query->condition('created',$expire,'<=');
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['code'] = array(
       'data' => $this->t('Order code'),
       'field' => 'code',
       'specifier' => 'code'
    );
    $header['client'] = array(
      'data' => $this->t('Client'),
      'field' => 'uid',
      'specifier' => 'uid'
    );
    $header['count'] = array(
      'data' => $this->t('Product count'),
    );
   	$header['order_price'] = array(
      'data' => $this->t('Order price'),
      'field' => 'order_price',
      'specifier' => 'order_price'
    );
		$header['paid_price'] = array(
      'data' => $this->t('Actually paid'),
      'field' => 'paid_price',
      'specifier' => 'paid_price'
    );
		$header['client_service'] = array(
      'data' => $this->t('Commissioner'),
      'field' => 'client_service',
      'specifier' => 'client_service'
    );
    $header['status'] = array(
      'data' => $this->t('Order Status'),
      'field' => 'status',
      'specifier' => 'status'
    );
    $header['created'] = array(
      'data' => $this->t('Order time'),
      'field' => 'created',
      'sort' => 'desc',
      'specifier' => 'created'
    );
   return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['code'] = $entity->getSimpleValue('code');

    //用户
    //@todo 需要重新处理这两个用户名问题
    //如果是管理人员下单，出现报错问题
    $client_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client',$entity->get('uid')->entity->id());
    if ($client_obj instanceof \Drupal\user\Entity\User) {
      $client = $client_obj->get('name')->value;
    } else {
      $client = $client_obj->corporate_name ? $client_obj->corporate_name : $client_obj->client_name;
    }

    $row['client'] = $client;
    //产品数量
    $count = 0;
    $products = $this->getProductByOrderId($entity);
    foreach($products as $product) {
      $count += $product->product_num;
    }
    $row['count'] = $count;
    $row['price'] = '￥' . ($entity->getSimpleValue('order_price') - $entity->getsimpleValue('discount_price'));
    $row['paid_price'] = '￥' .$entity->getSimpleValue('paid_price');
    // 客服专员
    //@todo 需要重新处理这两个用户名问题
    if (!empty($entity->getObjectId('client_service'))) {
      $serverId = $entity->getObjectId('client_service');
      $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $serverId);
      if(!empty($user_obj) && !empty($user_obj->employee_name)) {
        $row['client_service'] = $user_obj->employee_name;
      } else {
        $row['client_service'] = entity_load('user', $serverId)->label();
      }
    } else {
      if(empty($client_obj->commissioner)) {
        $row['client_service'] = t('NULL');
      } else {
        $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $client_obj->commissioner);
        if(!empty($user_obj) && !empty($user_obj->employee_name)) {
          $row['client_service'] = $user_obj->employee_name;
        } else {
          $row['client_service'] = entity_load('user', $client_obj->commissioner)->label();
        }
      }
    }
    $row['status'] = orderStatus()[$entity->getSimpleValue('status')]	;
    $row['created'] = format_date($entity->getSimpleValue('created'), 'custom', 'Y-m-d H:i:s');
   	return $row + parent::buildRow($entity);
  }

  /**
   * 构建行 得到每个订单下的产品
   *
   * @return $products
   *   该订单下订购的产品数组
   */
  private function getProductByOrderId($order) {
    $products = \Drupal::service('order.product')->getProductByOrderId($order->id());
    return $products;
  }

  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if($entity->access('accept') && $entity->hasLinkTemplate('accept-form') ) {
      $operations['accept'] = array(
        'title' => $this->t('Distribution'),
        'weight' => 1,
        'url' => $entity->urlInfo('accept-form'),
      );
    }
    if($entity->access('detail') && $entity->hasLinkTemplate('detail-view')) {
      $operations['detail'] = array(
        'title' => $this->t('Detail'),
        'weight' => 2,
        'url' => $entity->urlInfo('detail-view'),
      );
    }
    if($entity->access('change') && $entity->hasLinkTemplate('change_price-form')) {
      $operations['change_price'] = array(
        'title' => $this->t('Apply to change price'),
        'weight' => 3,
        'url' => $entity->urlInfo('change_price-form'),
      );
    }
    if($entity->access('trial') && $entity->hasLinkTemplate('trial-form')) {
      $operations['trial'] = array(
        'title' => $this->t('Apply for trial'),
        'weight' => 4,
        'url' => $entity->urlInfo('trial-form'),
      );
    }

    return $operations;
  }

	/**
   * {@inheritdoc}
   */
  public function render() {
    $build['order_filter'] = $this->formBuilder->getForm('\Drupal\order\Form\OrderFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No order data.');
    return $build;
  }
}

