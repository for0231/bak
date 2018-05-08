<?php
/**
 * @file
 * Contains \Drupal\order\user\UserOrderListBuilder.
 */

namespace Drupal\order\user;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserOrderListBuilder {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
			$container->get('form_builder')
    );
  }

  /**
   * 加载数据
   */
  public function load() {
    $user = \Drupal::currentUser();
    $filter_condtion = $this->filterOrder();
    $filter_condtion['uid'] =  $user->id();
    $oids = \Drupal::service('order.orderservice')->userOrderList($filter_condtion);
    return entity_load_multiple('order', $oids);
  }

  /**
   * 筛选订单 组装筛选条件
   *
   * @return $conditiond
   *   筛选条件组成的数组
   */
  public function filterOrder() {
    $conditions = array();
    if(!empty($_SESSION['my_order_filter'])) {
      if(!empty($_SESSION['my_order_filter']['oid'])) {
        $conditions['code'] = array('field' => 'code', 'op' => 'like', 'value' => $_SESSION['my_order_filter']['oid']);
      }
      if(!empty($_SESSION['my_order_filter']['title'])) {
        $conditions['alias_order'] = array('field' => 'alias_order', 'op'=> 'like', 'value' => $_SESSION['my_order_filter']['title']);
      }
      if($_SESSION['my_order_filter']['status'] != -1) {
        $conditions['status'] = $_SESSION['my_order_filter']['status'];
      }
      $start = isset($_SESSION['my_order_filter']['start']) ? strtotime($_SESSION['my_order_filter']['start']) : '' ;
      $expire = isset($_SESSION['my_order_filter']['expire']) ? strtotime($_SESSION['my_order_filter']['expire']) : '' ;

      if(!empty($start)) {
        $conditions['start'] = array('field' => 'created' , 'op' => '>=', 'value' =>$start);
      }
      if(!empty($expire)) {
        $conditions['expire'] = array('field' => 'created' , 'op' => '<=','value' => $expire);
      }
    }
    return $conditions;
  }

  /**
   * 我的订单表格
   */
  public function render() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\order\user\UserOrderFilterForm');
    $orders = $this->load();
    if(!$orders) {
      $build['empty'] = array(
        '#type' => 'label',
        '#title' => t('There is no data to show.'),
        '#prefix' => '<div class="empty">',
        '#suffix' => '</div>',
        '#title_display' => 'before'
      );
      return $build;
    }
    foreach($orders as $key => $order) {
      $build['order_' . $key] = array(
        '#theme' => 'user_order',
        '#order' => $order
      );
    }
    $build['pager'] = array('#type' => 'pager');
    return $build;
  }
}
