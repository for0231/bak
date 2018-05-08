<?php

namespace Drupal\purchase\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PurchaseDetailForm extends ContentEntityForm {

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
    ];
    $form['created'] = [
      '#markup' => isset($this->entity->get('created')->value) ? \Drupal::service('date.formatter')->format($this->entity->get('created')->value, 'short') : '-',
    ];

    $form['#attached']['library'] = ['purchase/purchase-form'];
    $form['#attached']['drupalSettings']['purchase']['id'] = $this->entity->id();

    $form['#attached']['drupalSettings']['audit'] = [
      'module' => $this->entity->getEntityTypeId(),
      'id' => $this->entity->id(),
    ];
    $form['audit_locale'] = \Drupal::service('audit_locale.audit_localeservice')->getAuditLocaleWorkflowStatus($this->entity);

    $member_info = \Drupal::service('member.memberservice')->getMemberInfo($this->entity->get('uid')->entity);
    $form['member_info'] = [
      '#markup' => $member_info,
    ];

    // 物流商.
    $entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    $taxonomy_ships = $entity_manager->loadTree('ships', 0, NULL, TRUE);
    $ships = '';
    foreach ($taxonomy_ships as $term) {
      $ships .= $term->id() . ":" . $term->label() . ";";
    }
    $ships = substr($ships, 0, -1);
    $form['#attached']['drupalSettings']['purchase']['ships'] = $ships;

    $form['description'] = [
      '#markup' => $this->entity->get('description')->value,
    ];
    $form['purpose'] = [
      '#markup' => $this->entity->get('purpose')->value,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->entity->get('status')->value == 9) {
      drupal_set_message('该工单已经被取消，无法再进行其他操作。', 'error');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->set('no', $form_state->getValue('no'))
      ->save();
    drupal_set_message('采购单保存成功');
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
    // @todo 下面这个是否应该删除
    $has_audit_spec = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($this->entity->getEntityTypeId(), $this->entity->id());
    // 审批状态需要一直可见.
    if (count($audits) == 0 && \Drupal::currentUser()->id() == $this->entity->get('uid')->target_id) {
      $actions['create_audit_submit'] = [
        '#type' => 'submit',
        '#value' => '发起审核',
        '#submit' => ['::createAuditSubmitForm'],
        '#validate' => ['::validateCreateAuditSubmitForm'],
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
    if ($this->entity->get('status')->value == 5 && $this->entity->get('audit')->value == 3) {
      $actions['force_cancel_submit'] = [
        '#type' => 'submit',
        '#value' => '强制取消工单',
        '#submit' => ['::forceCancelSubmitForm'],
        '#attributes' => [
          'class' => ['btn-danger'],
        ],
      ];
    }
    // 需求单已完成.
    if (\Drupal::service('part.partservice')->checkPartWuliuStatusfromRequirement($this->entity) && $this->entity->get('audit')->value == 3 && $this->entity->get('status')->value != 14) {
      $actions['finish_purchase_submit'] = [
        '#type' => 'submit',
        '#value' => '完成采购',
        '#submit' => ['::finishPurchaseSubmitForm'],
        '#attributes' => [
          'class' => ['btn-danger'],
        ],
      ];
    }

    if ($actions['submit']) {
      unset($actions['submit']);
    }

    if ($this->entity->get('status')->value == 9) {
      unset($actions['create_audit_submit']);
      unset($actions['audit_status_submit']);
    }

    return $actions;
  }

  /**
   * @description 发起审核动作
   *
   * 1. 首先保存该模型的审批流程用户列表到audit, 并把audit的id保存到该模型.
   * 2. 更改该采购单的审核状态为待审批
   * 3. 修改采购单的各配件状态
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
      // 3. 更新采购单包含的所有配件的状态.
      \Drupal::service('part.partservice')->updatePartStatus($this->entity);
      drupal_set_message('当前采购单成功发起审批！！');
    }
    else {
      drupal_set_message('当前单无审批人，无法发起审批动作', 'error');
    }
  }

  /**
   *
   */
  private function auditStatusSubmitForm(array $form, FormStateInterface $form_state) {
    // @todo 处理采购单详情页的审核流程
    drupal_set_message('查看审核状态动作, 使用弹出层');
  }

  /**
   * @description 取消工单动作
   */
  public function cancelSubmitForm(array $form, FormStateInterface $form_state) {
    \Drupal::service('part.partservice')->setPartsfallbackfromPurchase($this->entity);
    $this->entity->set('status', 9)
      ->set('iscancel', 1)
      ->save();
    drupal_set_message('取消成功。');
  }

  /**
   * @description 审批过后的采购单才发现包含多个供应商，导致无法继续跟进。
   *              这时可以使用强制取消功能.
   *
   *  强制取消处理逻辑:
   *  1. 取消当前采购单继续操作，设置失效采购单--以后或许可以用作统计审批错误的单。
   *  2. 复制新的数据，状态设置为新采购单状态,等待采购单审批后续流程.
   */
  public function forceCancelSubmitForm(array $form, FormStateInterface $form_state) {
    $duplicate_entity = $this->entity->createDuplicate();
    $this->entity->set('status', 15)->save();

    $duplicate_entity->set('status', 0);
    $duplicate_entity->set('audit', 0);
    $duplicate_entity->set('aids', []);
    $duplicate_entity->save();
    drupal_set_message('强制取消工单成功。');
  }

  /**
   * @description 验证采购单发起审批时需经验证。
   */
  public function validateCreateAuditSubmitForm(array $form, FormStateInterface $form_state) {
    $pids = $this->entity->get('pids');
    foreach ($pids as $pid) {
      $status = \Drupal::service('part.partservice')->checkPurchasePartStatus($pid->entity);
      if ($status) {
        drupal_set_message('发起失败，请确认采购配件内容是否正确!', 'error');
      }
    }
  }

  /**
   * @description 完成采购单动作
   */
  public function finishPurchaseSubmitForm(array $form, FormStateInterface $form_state) {
    \Drupal::service('purchase.purchaseservice')->updatePurchaseStatusforComplete($this->entity);

    $form_state->setRedirectUrl(new Url("entity.purchase.collection"));
    drupal_set_message('完成采购。');
  }

}
