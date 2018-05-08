<?php

namespace Drupal\paypro\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PayproAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paypro_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $choices = [];
    $request = \Drupal::request();
    $destination = $request->request->get('data');

    $choices = $destination['choices'][0];
    if (!empty($choices)) {
      $_SESSION['paypre_pool_for_paypro_checked'][\Drupal::currentUser()->id()] = $choices;
    }
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('名称'),
    ];

    $form['choices'] = [
      '#type' => 'value',
      '#value' => isset($_SESSION['paypre_pool_for_paypro_checked']) ? $_SESSION['paypre_pool_for_paypro_checked'][\Drupal::currentUser()->id()] : '',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $choices = $form_state->getValue('choices');
    $title = $form_state->getValue('title');
    if (empty($choices)) {
      drupal_set_message('支付单创建失败，请重新创建支付单', 'error');
    }
    else {
      // @description 返回1， 则所有验证正常
      // 否则返回0
      $check = \Drupal::service('paypre.paypreservice')->checkPaypreStatusforPayproById($choices);
      if ($check) {
        // 创建支付单数据.
        // @deprecated
        // \Drupal::service('paypro.payproservice')->save(null, FALSE, $choices);.
        \Drupal::service('paypro.payproservice')->create($title, $choices);
        drupal_set_message('支付单创建成功!!!');
      }
      else {
        drupal_set_message('支付单创建失败，请检查配件状态!!!', 'error');
      }
    }

    $form_state->setRedirectUrl(new Url("entity.paypro.pools.collection"));
  }

}
