<?php
/**
 * @file 
 * Contains \Drupal\order\AdminOrderDetail.
 */

namespace Drupal\order;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdminOrderDetail {
  /**
   * 订单模板
   */
  public function render($order) {
    //如果 订单已经付款 并且没有被接受 则显示接受按钮
    if($order->getSimpleValue('status') == 3 && $order->getSimpleValue('accept') == 0) {
      $build['accept'] = array(
        '#type' => 'link',
        '#title' => 'Accept',
        '#attributes' => array(
          'class' => array('button', 'button--foo'),
        ),
        '#url' => new Url('entity.order.accept_form',array('order'=>$order->id()))
      );
    }

    // 订单的客户信息
    $build['order_client'] = array(
      '#theme' => 'admin_order_client',
      '#client_obj' => $order->getObject('uid'),
    );

    //显示订单详情的模板
    $build['detail'] = array(
      '#theme' => 'admin_order_detail',
      '#order_obj' => $order
    );
    return $build;
  }
}

