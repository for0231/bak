<?php
/**
 * @file
 * Contains \Drupal\hc_alipay\Controller\AlipayController.
 */
namespace Drupal\hc_alipay\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\hc_alipay\AlipayNotify;
use Drupal\hc_alipay\Form\AlipayFilterForm;
use Drupal\order\ServerDistribution;
use Drupal\order\Entity\Order;
use Drupal\Core\Routing\TrustedRedirectResponse;

class AlipayController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a PartLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *  A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *  A module handler.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *  The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *  The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatter $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder   = $form_builder;
  }

  protected function buildFilterQuery() {

  }
  /**
   * Displays a listing of alipay payment order's records.
   *
   * @return array
   */
  public function overView() {
    $build['alipay_payment_filter_form'] = $this->formBuilder->getForm('Drupal\hc_alipay\Form\AlipayFilterForm');
    $header = array(
      '',
      array(
        'data' => $this->t('ID'),
      ),
      array(
        'data' => $this->t('Order ID'),
      ),
      array(
        'data' => $this->t('Code'),
      ),
      array(
        'data' => $this->t('Currency'),
      ),
      array(
        'data' => $this->t('USD'),
        'field' => 'a.total_fee',
      ),
      array(
        'data' => $this->t('RMB'),
        'field' => 'a.rmb_fee',
      ),
      array(
        'data' => $this->t('Trade Status'),
      ),
      array(
        'data' => $this->t('Trade No.'),
        'field' => 'a.trade_no',
      ),
      array(
        'data' => $this->t('Received Time'),
        'field' => 'a.received',
      ),
    );
    $query = $this->database->select('user_payment_alipay', 'a')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');

    $query->fields('a', array(
      'id',
      'order_id',
      'order_code',
      'currency',
      'total_fee',
      'rmb_fee',
      'trade_status',
      'trade_no',
      'received',
    ));

    $result = $query
      ->limit(1)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $record) {
      $rows[] = array(
        'data' => array(
          array('class' => array('icon')),
          $record->id,
          $this->l($record->order_id, new Url('user.order.detail', array('order' => $record->order_id), array(
            '#attributes' => array(
              'title' => Unicode::truncate(strip_tags($record->order_id), 256, TRUE, TRUE),
            ),
            'html' => TRUE,
          ))),
          $this->l($record->order_code, new Url('user.order.detail', array('order' => $record->order_id), array(
            '#attributes' => array(
              'title' => Unicode::truncate(strip_tags($record->order_id), 256, TRUE, TRUE),
            ),
            'html' => TRUE,
          ))),
          $record->currency,
          $record->total_fee,
          $record->rmb_fee,
          $record->trade_status,
          $record->trade_no,
          $this->dateFormatter->format($record->received, 'short'),
        ),
        'class' => array(Html::getClass('payment-alipay-' . $record->order_id), $classes['']),
      );
    }

    $build['payment_alipay_list'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array('id' => 'admin-payment-alipay-list', 'class' => array('admin-payment-alipay-list')),
      '#empty' => $this->t('No payment data'),
      '#attached' => array(),
    );
    $build['payment_alipay_list_pager'] = array('#type' => 'pager');

    return $build;
  }


  public function alipayReturn() {
    $alipay_config = \Drupal::service('alipay.alipayservice')->getAlipayBaseConfig();
    $notify = new AlipayNotify($alipay_config);
    $verify_result = $notify->verifyReturn();
    $notify_time = \Drupal::request()->query->get('notify_time');
    $return['out_trade_no'] = \Drupal::request()->query->get('out_trade_no');
    $return['currency'] = \Drupal::request()->query->get('currency'); //获取货币类型，默认为USD
    $return['total_fee'] = \Drupal::request()->query->get('total_fee'); //获取总价格
    $return['trade_status'] = \Drupal::request()->query->get('trade_status');
    $return['trade_no'] = \Drupal::request()->query->get('trade_no');//	获取支付宝交易ID
    $return['received'] = isset($notify_time) ? strtotime($notify_time) : time();
    if ($verify_result) { // verify success
      $orders = entity_load_multiple_by_properties('order', array('code' => $return['out_trade_no']));
      $order = reset($orders);
      // 该订单是服务器订单
      if ($order instanceof Order) {
        if (!in_array($order->getSimpleValue('status'), array(4, 5))) {
          $status = $this->hc_alipay_payment_save($return);
          if ($status == 1) {
            return $this->redirect('user.order.payment.success', array('order' => $order->id()));
          } else {
            return array('#markup' => '处理异常!');
          }
        }
        else {
          return array('#markup' => '不能重复支付相同的订单!');
        }
      }
      else {
        // 该订单是因为客户充值生成的订单
        $status = $this->hc_alipay_payment_save($return);
        if ($status) {
          drupal_set_message('充值成功!');
          return $this->redirect('member.account_center');
        }
        else {
          return array('#markup' => '账户更新失败');
        }
      }
    }
    else {
      return array('#markup' => '验证失败!');
    }
  }
  /**
   * @desc 这个支付宝验证需要重新编写,并把相关的数据写入到数据库中
   */
  public function alipayNotify() {
    $alipay_config = \Drupal::service('alipay.alipayservice')->getAlipayBaseConfig();
    $notify = new AlipayNotify($alipay_config);
    $verify_result = $notify->verifyNotify();
    if ($verify_result) { // verify success
      $v['notify_type'] = \Drupal::request()->request->get('notify_type'); //string forex_trade_status_sync
      $v['notify_id'] = \Drupal::request()->request->get('notify_id'); //string
      $v['notify_time'] = \Drupal::request()->request->get('notify_time'); //Timestamp
      $v['out_trade_no'] = \Drupal::request()->request->get('out_trade_no');
      $v['currency'] = \Drupal::request()->request->get('currency');
      $v['total_fee'] = \Drupal::request()->request->get('total_fee');
      $v['trade_no'] = \Drupal::request()->request->get('trade_no');
      return array('#markup' => 'success');
    }
    else {
      return array('#markup' => 'fail');
    }
  }

  /**
   * Save and update alipay data array.
   */
  public function updateAlipayRecord($edit) {
    $payment = $this->database->select('user_payment_alipay', 'a')
      ->fields('a')
      ->condition('uid', $edit['uid'])
      ->condition('order_code', $edit['order_code'])
      ->execute()
      ->fetchAssoc();
    if (empty($payment)) {
      $this->database->insert('user_payment_alipay')
        ->fields($edit)
        ->execute();
      return TRUE;
    } elseif (!in_array($payment['trade_status'], array('TRADE_FINISHED', 'TRADE_SUCCESS'), true)) {
      $this->database->update('user_payment_alipay')
        ->fields($edit)
        ->condition('uid', $edit['uid'])
        ->condition('order_code', $edit['order_code'])
        ->execute();
      return TRUE;
    } else {
      return FALSE;
    }

  }

  /**
   * Save alipay data array.
   */
  public function hc_alipay_payment_save($edit = array())  {
    $orders = entity_load_multiple_by_properties('order', array('code' => $edit['out_trade_no']));
    $order = reset($orders);
    $price = \Drupal::service('alipay.alipayservice')->getPriceByUserPayment($order);
    /**
     * 以下是基于Order产生的代码
     */
    if (!empty($order) && ($order instanceof \Drupal\order\Entity\Order)) {
      $a = array(
        'uid' => $order->getObjectId('uid'),
        'order_code' => $edit['out_trade_no'],
        'rmb_fee' => $price['price'], //用户支付金额
      );
      $edit = array_merge($edit, $a);

      unset($edit['out_trade_no']);
      /**
       * update alipay payment record.
       */
      $status = $this->updateAlipayRecord($edit);

      /**
       * update user account
       */
      $status_user = $this->updateUserAccountWithPayment($order, $edit);

      if ($status && $status_user) {
        \Drupal::service('order.orderservice')->updateOrder($order);
        return 1;
      }
      else {
        return 0;
      }
    } else {
      $current_user = \Drupal::currentUser();
      $a = array(
        'uid' => $current_user->id(),
        'order_code' => $edit['out_trade_no'],
      );
      $edit = array_merge($edit, $a);
      unset($edit['out_trade_no']);
      /**
       * update alipay.
       */
      $status = $this->updateAlipayRecord($edit);
      if (!$status)
        return 0;
      else {
        $update_user_account_status = $this->updateUserAccount($edit);
        return $update_user_account_status;
      }
    }
  }
  /**
   * update user account when alipay payment order.
   */
  public function updateUserAccountWithPayment($order, $edit) {
     $price = \Drupal::service('alipay.alipayservice')->getPriceByUserPayment($order);
     if ($price['price'] <= 0) {
      return -1; //用户余额足够支付订单金额, 无需操作
     }
    // 设置充值
    $status_in = $this->updateUserAccountWithAlipayPayment($order, $price);
    // 设置消费
    $status_consumer = \Drupal::service('alipay.alipayservice')->setHostPaymentAccount($order, $price['mode']);

    if ($status_in && $status_consumer) {
      return 1; // 处理成功
    }
    else {
      return 0; // 处理失败
    }
  }

  /**
   * update user account when payment with alipay.
   */
  public function updateUserAccountWithAlipayPayment($order, $price) {
    // 这里应该再更新用户个人账户
    //$account_finance = \Drupal::service('member.memberservice')->getClientCredit(\Drupal::currentUser()->id());
    $record['funds'] = array(
      'uid' => \Drupal::currentUser()->id(),
      // 这里应该是用户当前订单支付的金额
      'cash' => $price['price'],
    );
    // 保存充值记录
    $record['op_record'] = array(
      'amount' => $price['price'], // 当前支付的金额
      'message' => '用户充值',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
      'order_code' => $order->getSimpleValue('code'),
      // update
      'type' => 1, //充值
      'method' => 1, //支付宝
    );
    $status = \Drupal::service('member.memberservice')->setClientCredit($record['funds'], $record['op_record']);
    if ($status) {
      return 1; // 用支付宝支付剩余金额时，用户账户更新成功。
    }
    else {
      return 0; // 更新失败。
    }
  }
  /**
   * update user account
   * @para $edit alipay array.
   * @return bool
   */
  public function updateUserAccount($edit) {
    $current_user = \Drupal::currentUser();
    // $edit
    $funds = array(
      'cash' => \Drupal::service('alipay.alipayservice')->getUserPaymentAmount($edit),
      'uid' => $current_user->id(),
    );
    $op_record = array(
      'amount' => \Drupal::service('alipay.alipayservice')->getUserPaymentAmount($edit),
      'message' => '用户充值',
      'op_uid' => $current_user->id(),
      'client_uid' => $current_user->id(),
      'created' => REQUEST_TIME,
      'order_code' => $edit['order_code'],
      // update
      'type' => 1, //充值
      'method' => 1, //支付宝
    );

    $host_rs = \Drupal::service('member.memberservice')->setClientCredit($funds , $op_record);
    if ($host_rs) {
      return 1;
    } else {
      return 0;
    }
  }
  /**
   * redirect external url for order.
   */
  public function alipayForOrderRedirectUrl($order) {
    // 订单时
    $order = entity_load('order', $order);
    $url = \Drupal::service('alipay.alipayservice')->alipayPayment($order);
    return new TrustedRedirectResponse($url);
  }
  /**
   * redirect external url for user recharge.
   */
  public function alipayForRechargeRedirectUrl($amount, $orderno) {
    // 充值时
    $op_record = array(
      'amount' => $amount,
      'message' => '用户充值',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
      'order_code' => $orderno,
    );
    $url = \Drupal::service('alipay.alipayservice')->setAlipay($op_record);
    return new TrustedRedirectResponse($url);
  }
}
