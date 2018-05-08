<?php
/**
 * @file
 * Contains \Drupal\member\Front\MemberRechargeForm.
 */

namespace Drupal\member\Front;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormBuilder;

class MemberRechargeForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'member_recharge';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $this->getCurrentUser();
    $form['charge'] = array(
      '#type' => 'item',
      '#markup' => t('用户充值'),
    );
    $form['user'] = array(
      '#type' => 'item',
      '#title' => t('支付用户:'),
      '#markup' => $user->getUsername(),
    );
    $form['num'] = array(
      '#type' => 'textfield',
      '#title' => t('支付金额:'),
      '#default_value' => 0,
      '#description' => t('最少充值1人民币'),
    );
    $form['method'] = array(
      '#type' => 'item',
      '#markup' => t('提示: 默认使用支付宝充值。'),
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Recharge'),
      '#attributes' => array(
        'class' => array('btn btn-primary'),
      ),
    );
    $form['#theme'] = array('user_recharge');
    $form['#attached']['library'][] = 'member/member.recharge';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $account = $form_state->getValue('num');
/*
  if ($account < 0 | $account < 1) {
      $form_state->setErrorByName('num', $this->t('This number can not be less than 1'));
  }
 */
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->getCurrentUser();
    $amount = $form_state->getValue('num');

    $op_record = array(
      'amount' => $amount,
      'message' => '用户充值',
      'op_uid' => \Drupal::currentUser()->id(),
      'client_uid' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
      'order_code' => getHostRandomCode(),
    );
    // 网站支付宝充值
    $user_payment = array(
      'uid' => $this->getCurrentUser()->id(),
      'order_code' => $op_record['order_code'],
      'rmb_fee' => $amount,
      'trade_status' => 'TRADE_CLOSED',
    );
    $user_payment_alipay = \Drupal::service('alipay.alipayservice')->setUserPaymentAccount($user_payment);
    if ($user_payment_alipay) {
      return $form_state->setRedirectUrl(new Url('alipay.user.redirect', array('amount' => $amount, 'orderno' => $op_record['order_code'])));
    }
    else {
      drupal_set_message($this->t('unsuccess!'), 'error');
    }
  }

  private function getCurrentUser() {
    return \Drupal::currentUser();
  }
}
