<?php

namespace Drupal\paypre\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PaypreForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['title'] = [
      '#markup' => $this->entity->get('title')->value,
    ];
    $form['no'] = [
      '#markup' => $this->entity->get('no')->value,
      '#title' => '付款单编号',
      '#description' => '该编号结构为F+9位数字',
    ];

    $form['contact_no'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('contact_no')->value,
    ];
    // @todo 和currency关联
    $form['ftype'] = [
      '#type' => 'textfield',
      '#title' => '币种',
      '#prefix' => '<label class="input">',
      '#suffix' => '</label>',
      '#default_value' => $this->entity->get('ftype')->value,
    ];
    // @todo 从供应商列表里面进行筛选
    $form['acceptbank'] = [
      '#type' => 'textfield',
      '#title' => '收款开户行',
      '#default_value' => $this->entity->get('acceptbank')->value,
    ];
    $form['acceptname'] = [
      '#type' => 'textfield',
      '#title' => '收款账户名',
      '#default_value' => $this->entity->get('acceptname')->value,
    ];
    $form['acceptaccount'] = [
      '#type' => 'textfield',
      '#title' => '收款账号',
      '#default_value' => $this->entity->get('acceptaccount')->value,
    ];
    $form['ftype'] = [
      '#markup' => $this->entity->get('ftype')->target_id,
    ];
    $all_amount = 0;
    $cnos = $this->entity->get('cnos');
    foreach ($cnos as $cno) {
      $all_amount += \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($cno->entity);
    }
    $form['all_amount'] = [
      '#markup' => $all_amount,
    ];

    $pre_amount = \Drupal::service('paypre.paypreservice')->getPayprePreamount($this->entity);
    $form['pre_amount'] = [
      '#type' => 'value',
    // 这个值是计算出来的.
      '#value' => $all_amount - $pre_amount,
    ];
    $form['diff_pre_amount'] = [
    // 这个值是计算出来的.
      '#markup' => $all_amount - $pre_amount,
    ];
    $form['amount'] = [
      '#title' => '预付金额',
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('amount')->value,
    ];

    $form['big_amount'] = [
      '#markup' => toChineseNumber($all_amount - $pre_amount),
    ];

    $form['#attached']['library'] = ['paypre/paypres'];
    $form['#attached']['drupalSettings']['paypre']['id'] = $this->entity->id();
    $member_info = \Drupal::service('member.memberservice')->getMemberInfo($this->entity->get('uid')->entity);
    $form['member_info'] = [
      '#markup' => $member_info,
    ];

    $form['#theme'] = 'paypre_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $pre_amount = $form_state->getValue('pre_amount');

    $amount = $form_state->getValue('amount');
    if ($amount > $pre_amount) {
      $form_state->setErrorByName('amount', '输入金额等于应付金额，请重新填写。');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->set('pre_amount', $form_state->getValue('pre_amount') - $form_state->getValue('amount'));
    $this->entity->save();
    $form_state->setRedirectUrl(new Url("entity.paypre.collection"));
    drupal_set_message('付款单: ID-' . $this->entity->id() . ' ,编号: ' . $this->entity->label() . ' 保存成功');
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if ($actions['submit'] && $this->entity->get('status')->value != 0) {
      unset($actions['submit']);
    }
    return $actions;
  }

}
