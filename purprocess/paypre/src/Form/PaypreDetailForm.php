<?php

namespace Drupal\paypre\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PaypreDetailForm extends ContentEntityForm {

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
    $form['ftype'] = [
      '#markup' => $this->entity->get('ftype')->value,
      '#title' => '币种',
    ];
    // @todo 币种、金额将从采购单里面获取数据,然后输出到模板
    $form['acceptbank'] = [
      '#markup' => $this->entity->get('acceptbank')->value,
      '#title' => '开户行',
    ];
    $form['acceptname'] = [
      '#markup' => $this->entity->get('acceptname')->value,
      '#title' => '收款账户名',
    ];
    $form['acceptaccount'] = [
      '#markup' => $this->entity->get('acceptaccount')->value,
      '#title' => '收款账号',
    ];
    $form['contact_no'] = [
      '#markup' => empty($this->entity->get('contact_no')->value) ? '-' : $this->entity->get('contact_no')->value,
    ];
    $form['#theme'] = 'paypre_detail_form';
    $form['#attached']['library'] = ['paypre/paypres'];
    $form['#attached']['drupalSettings']['audit'] = [
      'module' => $this->entity->getEntityTypeId(),
      'id' => $this->entity->id(),
    ];
    $form['audit_locale'] = \Drupal::service('audit_locale.audit_localeservice')->getAuditLocaleWorkflowStatus($this->entity);
    $form['#attached']['drupalSettings']['paypre']['id'] = $this->entity->id();
    $member_info = \Drupal::service('member.memberservice')->getMemberInfo($this->entity->get('uid')->entity);
    $form['member_info'] = [
      '#markup' => $member_info,
    ];

    $form['acceptbank'] = [
      '#markup' => $this->entity->get('acceptbank')->value,
    ];
    $form['acceptname'] = [
      '#markup' => $this->entity->get('acceptname')->value,
    ];
    $form['acceptaccount'] = [
      '#markup' => $this->entity->get('acceptaccount')->value,
    ];
    $form['ftype'] = [
      '#markup' => $this->entity->get('ftype')->target_id,
    ];

    $amount = \Drupal::service('paypre.paypreservice')->getPaypreAmount($this->entity);
    $form['all_amount'] = [
      '#markup' => $amount,
    ];
    $pre_amount = \Drupal::service('paypre.paypreservice')->getPayprePreamount($this->entity);
    $form['pre_amount'] = [
      '#markup' => $this->entity->get('pre_amount')->value,
    ];
    $form['amount'] = [
      '#markup' => $this->entity->get('amount')->value,
    ];
    $form['big_amount'] = [
      '#markup' => toChineseNumber($this->entity->get('amount')->value),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->entity->get('status')->value == 7) {
      drupal_set_message('该工单已经被取消，无法再进行其他操作。', 'error');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {

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

    // 付款单状态为已同意、已支付.
    // @todo 这里的状态估计还会再修改。
    // if (in_array($this->entity->get('status')->value, [5,9])) {//审批状态需要一直可见.
    // @description 还有应付款，则显示复用按钮.
    if (\Drupal::service('paypre.paypreservice')->checkPaypreStatusforDuplicate($this->entity) && !in_array($this->entity->get('status')->value, [7])) {
      $actions['duplicate_paypre_submit'] = [
        '#type' => 'submit',
        '#value' => '复用当前付款单',
        '#submit' => ['::duplicatePaypreSubmitForm'],
        '#attributes' => [
          'class' => ['btn-danger'],
        ],
      ];
    }
    // 审批状态需要一直可见.
    if (count($audits) == 0 && \Drupal::currentUser()->id() == $this->entity->get('uid')->target_id) {
      $actions['create_audit_submit'] = [
        '#type' => 'submit',
        '#value' => '发起审核',
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

    if ($this->entity->get('status')->value == 0 && $this->entity->get('uid')->target_id == \Drupal::currentUser()->id()) {
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

    if ($this->entity->get('status')->value == 7) {
      unset($actions['create_audit_submit']);
      unset($actions['audit_status_submit']);
    }
    return $actions;
  }

  /**
   * @description 取消工单动作
   */
  public function cancelSubmitForm(array $form, FormStateInterface $form_state) {
    $same_fnos = \Drupal::service('paypre.paypreservice')->getfnosbySamecnos($this->entity);
    if (count($same_fnos) == 1) {
      \Drupal::service('purchase.purchaseservice')->setPurchasefallbackfromPaypre($this->entity);
    }
    $this->entity->set('status', 7)
      ->set('iscancel', 1)
      ->save();
    drupal_set_message('当付款单为分批付款时，取消付款单只能逐条取消', 'warning');
    drupal_set_message('取消成功。');
  }

  /**
   * @description 发起审核动作
   *
   * 1. 首先保存该模型的审批流程用户列表到audit, 并把audit的id保存到该模型.
   * 2. 更改该付款单的审核状态为待审批
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
      drupal_set_message('当前付款单发起审批成功！！！');
    }
    else {
      drupal_set_message('当前单无审批人，无法发起审批动作', 'error');
    }

  }

  /**
   * @description 复用当前付款单动作
   */
  public function duplicatePaypreSubmitForm(array $form, FormStateInterface $form_state) {

    $duplicate_entity = $this->entity->createDuplicate();
    $duplicate_entity->set('status', 0);
    $duplicate_entity->set('audit', 0);
    $duplicate_entity->set('aids', []);
    // 金额.
    $duplicate_entity->set('amount', 0);
    $duplicate_entity->save();
    $form_state->setRedirectUrl(new Url("entity.paypre.collection"));
    drupal_set_message('该付款单成功复用，请进入个人模式重新编辑付款单!!!');

  }

}
