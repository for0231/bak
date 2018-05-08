<?php

namespace Drupal\paypro\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class PayproDetailForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['title'] = [
      '#markup' => $this->entity->get('title')->value,
    ];
    // @todo 申请人相关信息未补充。
    $form['no'] = [
      '#markup' => $this->entity->get('no')->value,
      '#title' => '支付单编号',
    ];
    $paypre_data = [];
    $fnos = $this->entity->get('fnos');
    if (!empty($fnos)) {
      foreach ($fnos as $fno) {
        $paypre_entity = $fno->entity;
        $paypre_data = [
          'ftype' => $paypre_entity->get('ftype')->target_id,
          'acceptname' => $paypre_entity->get('acceptname')->value,
          'acceptbank' => $paypre_entity->get('acceptbank')->value,
          'acceptaccount' => $paypre_entity->get('acceptaccount')->value,
          'amount' => \Drupal::service('paypro.payproservice')->getCalAmountforPaypro($this->entity),
          'big_amount' => toChineseNumber(\Drupal::service('paypro.payproservice')->getCalAmountforPaypro($this->entity)),
        ];
      }
    }
    $form['paypre'] = [
      '#markup' => $paypre_data,
    ];

    $form['#theme'] = 'paypro_detail_form';

    $form['#attached']['drupalSettings']['audit'] = [
      'module' => $this->entity->getEntityTypeId(),
      'id' => $this->entity->id(),
    ];
    $form['audit_locale'] = \Drupal::service('audit_locale.audit_localeservice')->getAuditLocaleWorkflowStatus($this->entity);

    $member_info = \Drupal::service('member.memberservice')->getMemberInfo($this->entity->get('uid')->entity);
    $form['member_info'] = [
      '#markup' => $member_info,
    ];
    $form['#attached']['library'] = ['paypre/payprespool', 'paypro/paypro_detail', 'paypro/paypro_payment_detail'];
    $form['#attached']['drupalSettings']['paypro'] = [
      'id' => $this->entity->id(),
    ];

    $form['ftype'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('ftype')->value,
    ];

    $form['fbank'] = [
      '#markup' => $this->entity->get('fbank')->value,
    ];
    $form['validate_fbank'] = [
      '#type' => 'value',
      '#value' => $this->entity->get('fbank')->value,
    ];
    $form['amount'] = [
      '#markup' => $this->entity->get('amount')->value,
    ];
    $form['validate_amount'] = [
      '#type' => 'value',
      '#value' => $this->entity->get('amount')->value,
    ];
    $form['faccount'] = [
      '#markup' => $this->entity->get('faccount')->value,
    ];
    $form['fname'] = [
      '#markup' => $this->entity->get('fname')->value,
    ];

    $form['fbserial'] = [
      '#markup' => $this->entity->get('fbserial')->value,
    ];

    $form['description'] = [
      '#markup' => $this->entity->get('description')->value,
    ];

    // @todo 收款方信息待补充
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->entity->get('status')->value == 12) {
      drupal_set_message('该工单已经被取消，无法再进行其他操作。', 'error');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message('保存成功');
  }

  /**
   *
   */
  public function revokeSubmitForm(array $form, FormStateInterface $form_state) {
    // @todo 撤销支付单动作
    drupal_set_message('撤销支付单动作');
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $aids = $this->entity->get('aids');
    $audits = [];
    foreach ($aids as $row) {
      $audits[] = $row->entity;
    }
    // 审批状态需要一直可见.
    if (count($audits) == 0 && \Drupal::currentUser()->id() == $this->entity->get('uid')->target_id) {
      // If (count($audits) == 0) {//审批状态需要一直可见.
      $actions['create_audit_submit'] = [
        '#type' => 'submit',
        '#value' => '发起审核',
        '#validate' => ['::validateCreateAuditSubmitForm'],
        '#submit' => ['::createAuditSubmitForm'],
        '#attributes' => [
          'class' => ['btn-danger'],
        ],
      ];
    }
    if (!in_array($this->entity->get('status')->value, [0, 1])) {
      $actions['audit_status_submit'] = [
        '#type' => 'submit',
        '#value' => '审核状态',
        '#submit' => ['::auditStatusSubmitForm'],
        '#id' => 'check_audit',
        '#attributes' => [
          'class' => ['btn-primary'],
          'onclick' => 'return false;',
        ],
      ];
    }
    // 支付单的创建不是本人创建，因此取消本人识别条件.
    if ($this->entity->get('status')->value == 0 && \Drupal::currentUser()->hasPermission('administer paypro edit')) {
      $actions['cancel_submit'] = [
        '#type' => 'submit',
        '#value' => '取消工单',
        '#submit' => ['::cancelSubmitForm'],
        '#attributes' => [
          'class' => ['btn-warning'],
        ],
      ];
    }

    if ($actions['submit']) {
      unset($actions['submit']);
    }
    if ($this->entity->get('status')->value == 12) {
      unset($actions['create_audit_submit']);
      unset($actions['audit_status_submit']);
    }

    return $actions;
  }

  /**
   * @description 取消工单动作
   */
  public function cancelSubmitForm(array $form, FormStateInterface $form_state) {
    \Drupal::service('paypre.paypreservice')->setPayprefallbackfromPaypro($this->entity);
    $this->entity->set('status', 12)
      ->set('iscancel', 1)
      // ->set('fnos', []) //@todo 这里是否删除付款单信息,待后期补充.
      ->save();

    drupal_set_message('取消成功。');
  }

  /**
   * @description 验证发起审核时的状态是否正确.
   */
  public function validateCreateAuditSubmitForm(array $form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $fbank = $form_state->getValue('validate_fbank');
    $amount = $form_state->getValue('validate_amount');

    if (empty($fbank) || empty($amount)) {
      $form_state->setErrorByName('validate_fbank', '请重新检查支付单的付款银行和付款金额是否有误！！');
    }

    $check_ftype_status = \Drupal::service('paypro.payproservice')->checkftypeforPaypresArray(\Drupal::service('paypro.payproservice')->getPaypresByPaypro($this->entity));
    $check_acceptaccount_status = \Drupal::service('paypro.payproservice')->checkacceptaccountforPaypresArray(\Drupal::service('paypro.payproservice')->getPaypresByPaypro($this->entity));
    if ($check_ftype_status || $check_acceptaccount_status) {
      $form_state->setErrorByName('validate_fbank', '收款方的账号或币种存在多个，请确认账号和币种是否一致！！');
    }
  }

  /**
   * @description 发起审核动作
   *
   * 1. 首先保存该模型的审批流程用户列表到audit, 并把audit的id保存到该模型.
   * 2. 更改该支付单的审核状态为待审批
   * 3. 并修改付款单的工单状态为待支付 -- ******
   */
  public function createAuditSubmitForm(array $form, FormStateInterface $form_state) {
    // 1. 保存audit的id到当前实体内。.
    $audit_status = \Drupal::service('audit_locale.audit_localeservice')->setAudits4Module($this->entity);
    if ($audit_status) {
      // 2. 更新当前实体的状态.
      $this->entity
      // 审批中--工单状态.
        ->set('status', 2)
      // 待审批--审批状态.
        ->set('audit', 1)
        ->save();
      // 3. 更新付款单包含的所有配件的状态
      //    此模式下不包含任何配件信息,但包含采购单信息.
      \Drupal::service('part.partservice')->updatePartStatus($this->entity);
      drupal_set_message('当前单成功发起审批！！！');
    }
    else {
      drupal_set_message('当前单无审批人，无法发起审批动作', 'error');
    }

  }

}
