<?php
/**
 * @file
 * Contains \Drupal\order\Controller\OrderController.
 */
namespace Drupal\order\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\order\ServerDistribution;
use Drupal\order\UntreatedListBuilder;
use Drupal\order\PriceChangeListBuilder;
use Drupal\order\user\UserOrderListBuilder;
use Drupal\order\AdminOrderDetail;
use Drupal\order\TrialApplyListBuilder;
use Drupal\order\StopServerListBuilder;
use Drupal\order\HostclientListUntreated;
use Drupal\order\HostclientListNormal;
use Drupal\hostlog\HostLogFactory;

class OrderController extends ControllerBase {
  /**
   * 删除购物车
   *
   * @param $cartId int
   * 购物车项ID
   */
  public function cartDelete($cartId) {
    \Drupal::service('user.cart')->delete($cartId);
    return $this->redirect('user.cart');
  }

  /**
   * 我的订单列表
   */
  public function userOrder() {
    $list = UserOrderListBuilder::createInstance(\Drupal::getContainer());
    $build =  $list->render();
    $build['#theme'] = 'user_order_list';
    return $build;
  }

  /**
   * 前台显示订单详情
   */
  public function userOrderDetail(EntityInterface $order) {
   if($order->get('uid')->entity->id() != \Drupal::currentUser()->id()) {
     drupal_set_message(t('The order %order does not exist !', array('%order' => $order->getSimpleValue('code'))), 'error');
     return array();
   }
    $build['#title'] = $order->getSimpleValue('alias_order') ? $order->getSimpleValue('alias_order') : $order->getSimpleValue('code');
    $build['detail'] = array(
      '#theme' => 'user_order_detail',
      '#detail' => null,
      '#summary' => null,
      '#order' => $order
    );
    return $build;
  }

  /**
   * 后台显示订单详情
   */
  public function adminOrderDetail(EntityInterface $order) {
    $detail = new AdminOrderDetail();
    return $detail->render($order);
  }

  /**
   * 自动匹配,搜索
   */
  public function handleAutocomplete(Request $request, $room_id, $server_type, $current_ipm) {
    $items_typed = $request->query->get('q');
    $items_typed = Tags::explode($items_typed);
    $last_item = Unicode::strtolower(array_pop($items_typed));
    $dis = ServerDistribution::createInstance();
    $matches = $dis->getMatchServer($server_type, $room_id, $current_ipm, $last_item);
    return new JsonResponse($matches);
  }

  /**
   * 自动匹配，新工单管理IP
   */
  public function handleSopMipAutocomplete(Request $request) {
    $matches = array();
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      $typed_array = explode('$', $typed_string);
      $product = entity_load('product', $typed_array[1]);
      $dis = ServerDistribution::createInstance();
      $matches = $dis->getMatchServer($product->getObjectId('server_type'), 0, $current_ipm, $typed_array[0]);
    }
    return new JsonResponse($matches);
  }
  /**
   * 自动匹配
   */
  public function handleSopRoomAutocomplete(Request $request) {
    $matches = array();
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      $typed_array = explode('$', $typed_string);
      $dis = ServerDistribution::createInstance();
      $matches = $dis->getMatchAllServer($typed_array);
    }
    return new JsonResponse($matches);
  }

  /**
   * 自动匹配
   */
  public function handleSopFailureAutocomplete(Request $request) {
    $matches = array();
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      $dis = ServerDistribution::createInstance();
      $matches = $dis->getMatchClientStaticServer($typed_array);
    }
    return new JsonResponse($matches);
  }
  /**
   * 改价的列表
   */
  public function PriceChangeList() {
    $list = PriceChangeListBuilder::createInstance(\Drupal::getContainer());
    return $list->render();
  }

  /**
   * 试用订单列表
   */
  public function TrialApplyList() {
    $list = TrialApplyListBuilder::createInstance(\Drupal::getContainer());
    return $list->render();
  }

  /**
   *未处理服务器列表
   */
  public function untreatedBusinessList() {
    $list = UntreatedListBuilder::createInstance(\Drupal::getContainer());
    return $list->render('business');
  }

    /**
   *未处理服务器列表
   */
  public function untreatedTechnologyList() {
    $list = UntreatedListBuilder::createInstance(\Drupal::getContainer());
    return $list->render('technology');
  }

  /**
   * 支付成功, 显示IP
   */
  public function paymentSuccess(EntityInterface $order) {
    if($order->getObjectId('uid') != $this->currentUser()->id()) {
      return $this->redirect('user.order');
    }
    return array(
      '#theme' => 'user_payment_success_msg',
      '#order' => $order
    );
  }

  /**
   * 服务器详细
   */
  public function userHostclientDetail(EntityInterface $hostclient) {
    if($hostclient->get('client_uid')->entity->id() != \Drupal::currentUser()->id()) {
       drupal_set_message(t('The server %server does not exist !', array('%server' => $hostclient->get('server_id')->entity->label())), 'error');
       return array();
    }
    $build['host_detail'] = array(
     '#theme' => 'user_hostclient_detail',
     '#hostclient' => $hostclient,
    );
    return $build;
  }

  /**
   * 用户服务器控制面板
   */
  public function userHostclientPanel(EntityInterface $hostclient) {
    if($hostclient->get('client_uid')->entity->id() != \Drupal::currentUser()->id()) {
       drupal_set_message(t('The server %server does not exist !', array('%server' => $hostclient->get('server_id')->entity->label())), 'error');
       return array();
    }
    $build['host_panel'] = array(
     '#theme' => 'user_hostclient_panel',
     '#hostclient' => $hostclient,
     '#attached' => array(
       'library' => array('xunyun/bootstrap'),
     ),
    );
    return $build;
  }

  /**
   * 待处理服务器
   */
  public function untreatedHostclient() {
    $list = HostclientListUntreated::createInstance(\Drupal::getContainer());
    return $list->render();
  }

  /**
   * 正常服务器列表
   */
  public function NormalHostclient() {
    $list = HostclientListNormal::createInstance(\Drupal::getContainer());
    return $list->render();
  }


  /**
   * 停用服务器列表
   */
  public function stopServerList() {
    $list = StopServerListBuilder::createInstance();
    return $list->render();
  }

  /**
   * 停用服务器恢复
   */
  public function stopServerRecover($stop_id) {
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $hostclient = $hostclient_service->stopRecover($stop_id);
    if(!empty($hostclient)) {
      $stop_info_log = $hostclient_service->loadStopInfo($stop_id);
      $hostclient->other_data = array('data_id' => $stop_id, 'data_name' => 'hostclient_stop_info', 'data' => (array)$stop_info_log);
      $hostclient->other_status = 'server_stop';
      HostLogFactory::OperationLog('order')->log($hostclient, 'server_stop_audit');
    }
    return $this->redirect('admin.hostclient.stop.list');
  }

  /**
   * 停用服务器入库
   */
  public function stopServerStorage($stop_id) {
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $hostclient = $hostclient_service->stopStorage($stop_id);
    if(!empty($hostclient)) {
      $stop_info_log = $hostclient_service->loadStopInfo($stop_id);
      $hostclient->other_data = array('data_id' => $stop_id, 'data_name' => 'hostclient_stop_info', 'data' => (array)$stop_info_log);
      $hostclient->other_status = 'server_stop';
      HostLogFactory::OperationLog('order')->log($hostclient, 'server_stop_audit'); 
    }
    return $this->redirect('admin.hostclient.stop.list');
  }

  /**
   * 用户停用记录
   */
  public function userStopInfo() {
    $build['page_stopinfo'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('Order_title')
      )
    );
    $build['page_stopinfo']['title'] = array(
      '#markup' => '<b>'. t('Certificate management') .'</b>'
    );
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $list = $hostclient_service->getStopPageList(array('status' => 1, 'client_uid' => \Drupal::currentUser()->id()));
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array(t('Server code'), 'IP', t('Apply date'), t('Unsubscribe time'), t('User')),
      '#rows' => array(),
      '#attributes' => array('class' => array('user-table')),
      '#empty' => t('No data.')
    );
    $name = \Drupal::currentUser()->getUsername();
    foreach($list as $item) {
      $info = json_decode($item->info, true);
      $build['list']['#rows'][] = array(
        $info['server'],
        SafeMarkup::format(implode('<br>', $info['ipb']), array()),
        format_date($item->apply_date, 'custom' ,'Y-m-d H:i:s'),
        format_date($item->handle_date, 'custom' ,'Y-m-d H:i:s'),
        $name
      );
    }
    $build['page'] = array('#type' => 'pager');
    return $build;
  }

  /**
   * 获取业务IP对应客户的邮件地址。
   */
  private function getClientEmailByBusinessIP($ip) {
    $entity_ipb_arr = entity_load_multiple_by_properties('ipb', array('ip' => $ip));
    $entity_ipb = reset($entity_ipb_arr);
    $client_mail = '';
    if (!empty($entity_ipb)) {
      $entities = entity_load_multiple_by_properties('hostclient', array('ipb_id' => $entity_ipb->id()));
      $hostclient = reset($entities);
      if (!empty($hostclient)) {
        $mail = $hostclient->get('client_uid')->entity->get('mail')->value;
        $username = $hostclient->get('client_uid')->entity->getUserName();
        $client_mail = isset($mail) ? $mail.'#'. $username : '';
      }
    }
    return $client_mail;
  }
  /**
   * 重新处理客户被牵引的业务IP和邮箱属性
   */
  private function getRevertDataForUserMailAndIP($ipdata) {
    $emailtoip = array();

    foreach ($ipdata as $key=>$val) {
      if (!empty($val)) {
        $emailtoip[$val][] = $key;
      }
      else {
        $emailtoip['#empty'][] = $key;
      }
    }
    return $emailtoip;
  }


  /**
   * 验证IP格式
   */
  private function checkIPfromQianYin($ip_addr = '') {
    //first of all the format of the ip address is matched
    if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip_addr))
    {
      //now all the intger values are separated
      $parts=explode(".",$ip_addr);
      //now we need to check each part can range from 0-255
      foreach($parts as $ip_parts)
      {
        if(intval($ip_parts)>255 || intval($ip_parts)<0)
        return false; //if number is not within range of 0-255
      }
      return true;
    }
    else
      return false; //if format of ip address doesn't matches
  }


  /**
   * 处理用户被牵引的业务IP
   * @param $string 业务IP是使用逗号连接并串行化的数据,如s:35:"58.84.55.22,58.84.55.53,58.84.55.38";
   */
  public function detachBusinessIPFromQianYinAutoComplete($string) {
    $ips = unserialize($string);
    $ips_array = explode(',', $ips);
    $match = array();
    foreach ($ips_array as $ip) {
      $check = $this->checkIPfromQianYin($ip);
      if ($check) {
        $match[$ip] = $this->getClientEmailByBusinessIP($ip);
      }
    }
    $data = $this->getRevertDataForUserMailAndIP($match);
    //return new JsonResponse($data);
    return new Response(serialize($data));
  }

}
