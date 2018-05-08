<?php
/**
 * @file \Drupal\hc_alipay\AlipayService
 */
namespace Drupal\hc_alipay;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\hc_alipay\Alipay;
use Drupal\order\Entity\Order;

class AlipayService {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The alipay partner no.
   */
  protected $partner;

  /**
   * The alipay partner key.
   */
  protected $key;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Set alipay.
   */
  public function setAlipay($op_record) {
    $url = $this->alipayPayment($op_record);
    return $url;
  }

  /**
   * 静态化支付宝的配置
   */
  public function setStaticAlipayConfigPartnerNo() {
    $this->partner = $partner =  '2088511075204962';
    return $this->partner;
  }
  /**
   * 静态化支付宝的配置
   */
  public function setStaticAlipayConfigPartnerKey() {
    $this->key = $key = '74fco006ymrs4bt3vng1gvbc6yqqjaus';
    return $this->key;
  }
  /**
   * Alipay payment.
   * create_forex_trade
   */
  public function alipayPayment($order) {
    $configuration = $this->getAlipayParameters($order);
    $alipay = new Alipay($configuration['alipay_config']);
    $url = $alipay->create_url($configuration['parameter']);
    return $url;
  }

  /**
   * Get alipay config base data.
   */
  public function getAlipayBaseConfig($type = 'create_forex_trade') {
//    $config = \Drupal::config('hc_alipay.settings');
    $alipay_config = array(
      'service' => $type,
      //'partner' => $config->get('alipay.partner'),
      'partner' => $this->setStaticAlipayConfigPartnerNo(),
      //'key' => $config->get('alipay.key'),
      'key' => $this->setStaticAlipayConfigPartnerKey(),
      'sign_type' => strtoupper('MD5'),
      'input_charset' => strtolower('utf-8'),
      'cacert' => drupal_get_path('module', 'hc_alipay') . '/cacert.pem',
      //'transport' => $config->get('alipay.transport'),
      'transport' => 'http',
    );
    return $alipay_config;
  }

  /**
   * Get alipay configuration parameters.
   */
  public function getAlipayParameters($order) {
    $price = $this->getPaymentPriceArray($order);
    $base_path = 'http://' . $_SERVER['HTTP_HOST'] . base_path();
    $configuration['alipay_config'] = $this->getAlipayBaseConfig();
    $configuration['parameter'] = array(
      "service" => "create_forex_trade",
      "partner" => trim($this->setStaticAlipayConfigPartnerNo()),
      "return_url" => $base_path.'alipay/return_url',
      "notify_url" => $base_path.'alipay/notify_url',
      "subject" => $price['title'],
      'supplier' => 'Hostspace',
      "body" => 'BODY',
      "out_trade_no" => $price['code'],
      "currency" => 'USD',
      "rmb_fee" => $price['payment'],
      "_input_charset" => $configuration['alipay_config']['input_charset'],
    );
    return $configuration;
  }

  /**
   * Get order payment price.
   */
  public function getPaymentPriceArray($order) {
    if ($order instanceof Order) {
      $payment = $this->getPriceByUserPayment($order);
      //服务器订单支付
      $price['payment'] = $payment['price'];
      $price['code']    = $order->getSimpleValue('code');
      $price['title']   = $order->getSimpleValue('alias_order');
    } elseif (is_array($order)) {
      // 充值金额
      $price['payment'] = $order['amount'];
      $price['code'] = $order['order_code'];
      $price['title'] = $order['message'];
    }

    return $price;
  }

  /**
   * Checkout user payment method.
   */
  public function getUserPaymentMethod() {
    $session = \Drupal::service('session');
    $payment_method = $session->get('payment_method');

    if (($payment_method['account'] == 1) && ($payment_method['credit'] == 1)) {
      // 使用个人账户，信用额度账户，支付宝账户支付
      $payment_method = 1;
    }
    else if ($payment_method['account']) {
      // 使用个人账户支付
      $payment_method = 2;
    }
    else if ($payment_method['credit']) {
      // 使用信用额度支付
      $payment_method = 3;
    }
    else {
      $payment_method = 4;
    }
    return $payment_method;
  }

  /**
   * Accord user's payment method get the price.
   * @para $order 订单数据
   *
   * @return $payment array
   *  - price: 返回支付宝应该支付的价格
   *  - mode : 返回订单的支付模式
   *    11: 1) 订单金额减去个人账户余额-集1 |个人账户余额为0
   *        2) 个人信用额度减去集1 |结余个人信用额度
   *        3) 不再需要支付宝
   *    12: 1) 订单金额减去人个账户余额再减去个人信用额度-集1
   *        2) 支付宝付款 | 支付宝交易
   *    10: 1) 订单金额直减个人账户余额和个人信用额
   *        2) 支付宝交易
   *    21: 1) 个人账户大于订单金额
   *        2) 不再需要支付宝交易
   *    22: 1) 个人账户小于订单金额
   *        2) 使用支付宝交易
   *    31: 1) 个人信用额度大于订单金额
   *        2) 不再需要支付宝交易
   *    32: 1) 个人信用额度小于订单金额
   *        2) 使用支付宝交易
   *    1:  1) 使用支付宝交易
   *
   *    $payment:
   *      为正: 需要支付宝支付
   *      为负: 不需要支付宝支付
   */
  public function getPriceByUserPayment($order) {
    if ($order instanceof Order) {
      $method = $this->getUserPaymentMethod();
    	$order_price = $order->getSimpleValue('order_price') - $order->getSimpleValue('discount_price');
		}
		else {
			$order_price = $order['rmb_fee'];
		}
    //查询当前用户账号余额
    $account_finance = \Drupal::service('member.memberservice')->getClientCredit(\Drupal::currentUser()->id());
    switch ($method) {
      case 1:
        if ($account_finance->cash <= $order_price) {
          // 这是个人账户余额小于订单金额时
          // account, credit, alipay
          // accout,credit大于订单金额时
          $temp_total = $account_finance->cash + $account_finance->credit;
          if ($temp_total > $order_price) {
            $temp = $order_price - $account_finance->cash;
            if ($temp < $account_finance->credit) {
              // 信用额度够用时, 则不再需要支付宝支付
              //$price_client = $account_finance->credit - $temp;
              $payment['mode'] = 11;
            }
            else {
              // 信用额度不够用时
              $payment['mode'] = 12;
            }
            $payment['price'] = $temp - $account_finance->credit;
          }
          else {
            // accout,credit小于订单金额时
            $payment['price'] =  $order_price - $temp_total;
            $payment['mode'] = 10;
          }
        }
        else {
          // 这是个人账户余额大于订单金额时
          // 个人账户金额足够，不再跳转到其他支付页面
          $payment['price'] = $order_price - $account_finance->cash;
          $payment['mode'] = 13;
        }
        break;
      case 2:
        // account
        if ($account_finance->cash >= $order_price) {
          // 个人账户金额足够，不再跳转到其他支付页面
          $payment['price'] = $order_price - $account_finance->cash;
          $payment['mode'] = 21;
        }
        else {
          $payment['price'] = $order_price - $account_finance->cash;
          $payment['mode'] = 22;
        }
        break;
      case 3:
        // 个人账户Credit足够，不再跳转到其他支付页面
        if ($account_finance->credit > $order_price) {
          $payment['price'] = $order_price - $account_finance->credit;
          $payment['mode'] = 31;
        }
        else {
          $payment['price'] = $order_price - $account_finance->credit;
          $payment['mode'] = 32;
        }
        break;
      case 4:
        // alipay
        $payment = array(
          'price' => $order_price,
          'mode' => 1,
        );
        break;
    }

    return $payment;
  }

  /**
   * 用户充值时
   */
  public function setUserPaymentAccount($user_payment_data) {
    $query = $this->database->select('user_payment_alipay', 'upa')
      ->fields('upa')
      ->condition('uid', $user_payment_data['uid'])
      ->condition('order_code', $user_payment_data['order_code'])
      ->execute()
      ->fetchObject();
    if (!empty($query)) {
      // 订单编码重复
      return 0;
    }
    else {
      // 订单编码未重复
      $this->database->insert('user_payment_alipay')
        ->fields($user_payment_data)
        ->execute();
      return 1;
    }
  }
  /**
   * 查询用户充值订单的RMB金额
   */
  public function getUserPaymentAmount($edit) {
    $query = $this->database->select('user_payment_alipay', 'upa')
      ->fields('upa')
      ->condition('uid', $edit['uid'])
      ->condition('order_code', $edit['order_code'])
      ->execute()
      ->fetchObject();
    return $query->rmb_fee;
  }

  /**
   * 设置网站个人账户的余额
   */
  public function setHostPaymentAccount($order, $mode) {
    $order_price = $order->getSimpleValue('order_price') - $order->getSimpleValue('discount_price');
    $account_finance = \Drupal::service('member.memberservice')->getClientCredit(\Drupal::currentUser()->id());
    $record = array();
    $b = array();
    switch ($mode) {
      case 11:
        $record['funds'] = array(
          'cash' => 0,
          'credit' => $account_finance->credit + $account_finance->cash - $order_price,
          'uid' => $account_finance->uid,
        );
        $b = array(
          //// update
          'type' => 2, //消费类型
          'method' => 4, //消费方式
        );
        break;
      case 31:
        $record['funds'] = array(
          'credit' => $account_finance->credit - $order_price,
          'uid' => $account_finance->uid,
        );
        $b = array(
          //// update
          'type' => 2, //消费类型
          'method' => 3, //消费方式
        );
        break;
      default:
        $record['funds'] = array(
          'cash' => $account_finance->cash - $order_price,
          'uid' => $account_finance->uid,
        );
        $b = array(
          //// update
          'type' => 2, //消费类型
          'method' => 2, //消费方式
        );
    }
    $record['op_record'] = array(
      'amount' => -$order_price,
      'message' => '订单消费',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => $account_finance->uid,
      'created' => REQUEST_TIME,
      'order_code' => $order->getSimpleValue('code'),
    );
    $record['op_record'] = array_merge($record['op_record'], $b);
    $status = \Drupal::service('member.memberservice')->setClientConsumerCredit($record);

    if ($status)
      return 1; // 插入记录成功
    else
      return 0; // 插入记录失败
  }
}
