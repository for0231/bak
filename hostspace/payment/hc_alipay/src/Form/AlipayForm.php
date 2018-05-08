<?php
/**
 * @file
 * Contains \Drupal\hc_alipay\Form\AlipayForm
 */
namespace Drupal\hc_alipay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
//use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\hostlog\HostLogFactory;
//use Drupal\Core\Routing\TrustedRedirectResponse;

class AlipayForm extends FormBase{
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'alipay';
  }

  /**
   * @desc 返回用户当前应该支付的金额
   * @todo 在执行此方法时应该对用户金额数据表进行加锁
   *       订单表也应该加锁
   */
  private function getOrderPaymentAmount($order_amount, $banlance, $status = FALSE) {
    if ($status) {
      // TRUE时使用信用额度
    } else if ($banlance > $order_amount) {
      $payment = 0;
    } else {
      $payment = $order_amount - $banlance;
    }
    return $payment;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = \Drupal::routeMatch();
    $order = $route_match->getParameter('order');
    $order_amount = $order->getSimpleValue('order_price') - $order->getSimpleValue('discount_price');
    $current_user_banlance = getCurrentUserBanlance();
    $payment = $this->getOrderPaymentAmount($order_amount, $current_user_banlance);
    $form['title'] = array(
      '#type' => 'container'
    );
    $form['title']['tip'] = array(
      '#markup' => t('Payment')
    );

    $form['oid'] = array(
      '#type' => 'item',
      '#title' => t('Order Code:'),
      '#markup' => $this->l($order->getSimpleValue('code'), new Url('user.order.detail', array('order' => $order->id()), array(
        'attributes' => array(
          'target' => '_blank',
        ),
      )))
    );
    $form['price'] = array(
      '#markup' => SafeMarkup::format(t('Order : <label>￥%price</label>', array('%price' => $order_amount)),array()),
      '#prefix' => '<div class="item price">',
      '#suffix' => '</div>',
    );

    $form['host_account'] = array(
      '#type' => 'item',
      '#markup' => SafeMarkup::format(t('Banlance: <label>￥%banlance</label>', array('%banlance' => $current_user_banlance)), array()),
      '#prefix' => '<div class="item price">',
      '#suffix' => '</div>',
    );

    $form['payment_account'] = array(
      '#type' => 'item',
      '#markup' => SafeMarkup::format(t('Payment: <label>￥%payment</label>', array('%payment' => $payment)),array()),
      '#prefix' => '<div class="item price">',
      '#suffix' => '</div>',
    );

    //====支付方式＝＝＝＝＝＝
    $form['pay_mode'] = array(
      '#type' => 'radios',
      '#title' => t('Payment method'),
      '#options' => array(1 => t('Online payment'), 2 => t('Offline payment')),
      '#default_value' => 1,
      '#attributes' => array(
        'class' => array('pay-mode')
      )
    );
    //在线支付
    $form['online_payment'] = array(
      '#type' => 'vertical_tabs',
      '#states' => array(
        'visible' => array(
          ':input[name="pay_mode"]' => array('value' => '1')
        )
      ),
      '#attributes' => array(
        'class' => array('online-payment')
      )
    );
    $form['website_payment'] = array(
      '#type' => 'details',
      '#title' => t('Website Payment'), //网站支付
      '#group' => 'online_payment',

    );
    $form['website_payment']['host'] = array(
      '#type' => 'checkbox',
      '#title' => t('Account'),
    );
    $form['website_payment']['credit'] = array(
      '#type' => 'checkbox',
      '#title' => t('Credit'),
    );
    $form['platform'] = array(
      '#type' => 'details',
      '#title' => t('Platform payment'), //平台支付
      '#group' => 'online_payment',
    );
    $form['platform']['alipay'] = array(
      '#type' => 'checkbox',
      '#title' => t('Alipay'),
    );

    //线下支付
    $form['offline_payment'] = array(
      '#type' => 'container',
      '#states' => array(
        'visible' => array(
          ':input[name="pay_mode"]' => array('value' => '2')
        )
      ),
      '#attributes' => array(
        'class' => array('item', 'offline-payment')
      )
    );
    $form['offline_payment']['info'] = array(
      '#markup' => '工商账号：XXXXXXXXXXXXXXXXX'
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Payment'),
    );
    $form['#attributes']['target'] = '_blank';
    $form['#attached']['library'][] = 'hc_alipay/alipay.payment';

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $account_finance = \Drupal::service('member.memberservice')->getClientCredit(\Drupal::currentUser()->id());
    $route_match = \Drupal::routeMatch();
    $order = $route_match->getParameter('order');
    $status = $order->getSimpleValue('status');
    if (!in_array($status, array(0, 1, 2, 9))) {
      $form_state->setErrorByName('oid', t('订单状态异常，请检查后再进行支付!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $route_match = \Drupal::routeMatch();
    $order = $route_match->getParameter('order');
    $payment_method = array(
      'account' => $form_state->getValue('host'),
      'credit' => $form_state->getValue('credit'),
    );
    if (($current_user->id() != $order->getSimpleValue('uid')) && ($order->getSimpleValue('status') == 3) ) {
      drupal_set_message('操作失败！非法用户或订单已支付!');
    } else {
      // 需要先设置用户的支付方式
      // 如果用户选用了账户支付
      // 如果用户选用了信用额度支付
      // 默认使用支付宝支付
      $session = \Drupal::service('session');
      $session->set('payment_method', $payment_method);
      // 转向支付宝的支付页面
      // 订单时
      $price = \Drupal::service('alipay.alipayservice')->getPriceByUserPayment($order);
      if ($price['price'] > 0 ) {
        // 订单价格大于账户总金额
        //订单时
        $form_state->setRedirectUrl(new Url('alipay.order.redirect', array('order' => $order->id())));
      }
      else {
        // 通过网站支付
        $status = \Drupal::service('alipay.alipayservice')->setHostPaymentAccount($order, $price['mode']);
        if ($status) {
          \Drupal::service('order.orderservice')->updateOrder($order);
          HostLogFactory::OperationLog('hc_alipay')->log($order, 'payment');
          $config = \Drupal::config('common.global');
          $config_audo_distribute = $config->get('auto_distribute');
          if($config_audo_distribute) {
            $form_state->setRedirectUrl(new Url('user.order.payment.success', array('order' => $order->id())));
          } else {
            $form_state->setRedirectUrl(new Url('user.order'));
          }
        }
        else {
          drupal_set_message('支付失败!', 'error');
        }
      }
    }
  }

}
