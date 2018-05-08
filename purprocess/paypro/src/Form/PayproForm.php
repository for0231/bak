<?php

namespace Drupal\paypro\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PayproForm extends ContentEntityForm {

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
      '#title' => '支付单编号',
    ];
    $fnos = $this->entity->get('fnos');
    foreach ($fnos as $fno) {
      $paypre_entities[] = $fno->entity;
      // $paypre_ftype = $fno->entity->get('ftype')->target_id;.
    }
    $paypre_data = [];
    foreach ($paypre_entities as $paypre_entity) {
      $paypre_data = [
        'ftype' => $paypre_entity->get('ftype')->target_id,
        'acceptname' => $paypre_entity->get('acceptname')->value,
        'acceptbank' => $paypre_entity->get('acceptbank')->value,
        'acceptaccount' => $paypre_entity->get('acceptaccount')->value,
        'amount' => \Drupal::service('paypro.payproservice')->getCalAmountforPaypro($this->entity),
        'big_amount' => toChineseNumber(\Drupal::service('paypro.payproservice')->getCalAmountforPaypro($this->entity)),
      ];
    }
    $form['paypre'] = [
      '#markup' => $paypre_data,
    ];
    $form['paypre_data'] = [
      '#type' => 'value',
      '#value' => $paypre_data,
    ];
    $form['ftype'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('ftype')->value,
    ];

    $entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    // 获取支付公司信息.
    $payment_enterprise = $entity_manager->loadTree('payment_enterprise', 0, 1, TRUE);
    $fbank_companies = [];
    foreach ($payment_enterprise as $row) {
      $fbank_companies[$row->label()] = $row->label();
    }
    $form['fbank'] = [
      '#type' => 'select',
      '#options' => $fbank_companies,
      '#required' => TRUE,
      '#default_value' => $this->entity->get('fbank')->value,
    ];
    $form['amount'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->entity->get('amount')->value,
    ];

    $form['faccount'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('faccount')->value,
    ];
    $form['fname'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('fname')->value,
    ];

    $form['fbserial'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('fbserial')->value,
    ];

    $member_info = \Drupal::service('member.memberservice')->getMemberInfo($this->entity->get('uid')->entity);
    $form['member_info'] = [
      '#markup' => $member_info,
    ];
    $form['#attached']['library'] = ['paypro/paypro_detail', 'paypro/paypro_payment_detail'];
    $form['#attached']['drupalSettings']['paypro'] = [
      'id' => $this->entity->id(),
    ];

    $form['#theme'] = 'paypro_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $paypre_data = $form_state->getValue('paypre_data');
    if ($form_state->getValue('amount') != $paypre_data['amount']) {
      $form_state->setErrorByName('amount', '支付金额需和应付金额相等,否则可取消该支付单!');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();

    $form_state->setRedirectUrl(new Url("entity.paypro.collection"));
    drupal_set_message('需求单: ID-' . $this->entity->id() . ' ,编号: ' . $this->entity->label() . ' 保存成功');
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
