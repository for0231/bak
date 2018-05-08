<?php

namespace Drupal\requirement\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class RequirementDetailForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['no'] = [
      '#markup' => $this->entity->get('no')->value,
      '#description' => '该编号结构为R+9位数字',
    ];
    $form['title'] = [
      '#markup' => $this->entity->get('title')->value,
    ];
    $form['num'] = [
      '#markup' => $this->entity->get('num')->value,
    ];
    $form['requiredate'] = [
      '#markup' => isset($this->entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($this->entity->get('requiredate')->value, 'html_date') : '-',
    ];

    $select_requiretype = getRequirementType();
    $form['requiretype'] = [
      '#markup' => $select_requiretype[$this->entity->get('requiretype')->value],
    ];

    $user = $this->entity->get('uid')->entity;
    $form['user_name'] = [
      '#markup' => empty($user->get('realname')->value) ? $user->get('name')->value : $user->get('realname')->value,
    ];
    $form['user_depart'] = [
      '#markup' => empty($user->get('depart')->value) ? '-' : taxonomy_term_load($user->get('depart')->value)->label(),
    ];
    $form['user_company'] = [
      '#markup' => empty($user->get('company')->value) ? '-' : taxonomy_term_load($user->get('company')->value)->label(),
    ];

    $member_info = \Drupal::service('member.memberservice')->getMemberInfo($this->entity->get('uid')->entity);
    $form['member_info'] = [
      '#markup' => $member_info,
    ];
    $form['#theme'] = 'requirement_detail_form';

    $form['#attached']['library'] = ['requirement/requirement-form'];
    $form['#attached']['drupalSettings']['requirement']['rid'] = $this->entity->id();

    $form['#attached']['drupalSettings']['audit'] = [
      'module' => $this->entity->getEntityTypeId(),
      'id' => $this->entity->id(),
    ];

    // If ($this->entity->get('requiretype')->value == 0) {.
    // @append 未审批状态下可显示审批流
    if ($this->entity->get('audit')->value == 0) {
      if (\Drupal::currentUser()->hasPermission('administer requirement edit')) {
        $form['audit_locale'] = \Drupal::service('audit_locale.audit_localeservice')->getAuditLocaleWorkflowStatus($this->entity);
      }

    }

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
    if ($this->entity->get('status')->value == 8) {
      drupal_set_message('该工单已经被取消，无法再进行其他操作。', 'error');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message('需求单: ' . $this->entity->label() . ' 保存成功');
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

    $pids = $this->entity->get('pids');
    foreach ($pids as $pid) {
      $part[] = $pid->entity->id();
    }

    if (!empty($part)) {
      // @append 非计划需求单参与审批动作。
      if ($this->entity->get('requiretype')->value != 1) {
        // @todo 下面这个是否应该删除
        $has_audit_spec = \Drupal::service('audit_locale.audit_localeservice')->getModuleAuditLocale($this->entity->getEntityTypeId(), $this->entity->id());
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
        /**
         * @description 暂时屏蔽掉
         * if (\Drupal::currentUser()->id() != $this->entity->get('uid')->target_id && $this->entity->get('audit')->value == 1) {
         * $actions['audit_check_submit'] = [
         * '#type' => 'submit',
         * '#value' => '审批',
         * '#id' => 'check_check_accept',
         * '#attributes' => [
         * 'class' => ['btn-danger'],
         * 'onclick' => 'return false;',
         * ],
         * ];
         * }
        */

      }
      else {
        $actions['trans_requirement_type_submit'] = [
          '#type' => 'submit',
          '#value' => '转立即执行',
          '#submit' => ['::transformPlanTypeSubmitForm'],
          '#attributes' => [
            'class' => ['btn-warning'],
          ],
        ];
      }

      // 需求单已完成.
      if (in_array($this->entity->get('status')->value, [16])) {
        $actions['clone_requirement_submit'] = [
          '#type' => 'submit',
          '#value' => '复用当前需求单',
          '#submit' => ['::cloneRequirementSubmitForm'],
          '#attributes' => [
            'class' => ['btn-danger'],
          ],
        ];
      }
      // 需求单已完成.
      if (\Drupal::service('part.partservice')->checkPartWuliuStatusfromRequirement($this->entity) && !in_array($this->entity->get('status')->value, [16])) {
        $actions['finish_requirement_submit'] = [
          '#type' => 'submit',
          '#value' => '完结采购需求',
          '#submit' => ['::finishRequirementSubmitForm'],
          '#attributes' => [
            'class' => ['btn-danger'],
          ],
        ];
      }

      // If ($this->entity->get('requiretype')->value == 2) {.
      if (($this->entity->get('uid')->target_id == \Drupal::currentUser()->id()) && ($this->entity->get('requiretype')->value == 2)) {
        $actions['new_circle_requirement_submit'] = [
          '#type' => 'submit',
          '#value' => '新的周期采购需求',
          '#submit' => ['::newCircleRequirementSubmitForm'],
          '#attributes' => [
            'class' => ['btn-primary'],
          ],
        ];
      }

      if (($this->entity->get('uid')->target_id == \Drupal::currentUser()->id()) && ($this->entity->get('requiretype')->value == 2)) {
        $actions['finish_circle_requirement_submit'] = [
          '#type' => 'submit',
          '#value' => '结束周期性需求采购',
          '#submit' => ['::finishCircleRequirementSubmitForm'],
          '#attributes' => [
            'class' => ['btn-danger'],
          ],
        ];
      }

      if ($this->entity->get('status')->value == 8) {
        unset($actions['create_audit_submit']);
        unset($actions['audit_status_submit']);
      }
    }

    if ($actions['submit']) {
      unset($actions['submit']);
    }
    return $actions;
  }

  /**
   * @description 发起审核动作
   *
   * 1. 首先保存该模型的审批流程用户列表到audit, 并把audit的id保存到该模型.
   * 2. 更改该需求单的审核状态为待审批
   * 3. 修改需求单的各配件状态
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
      // 3. 更新需求单包含的所有配件的状态.
      \Drupal::service('part.partservice')->updatePartStatus($this->entity);
      drupal_set_message('该单成功发起审批!!!');
    }
    else {
      drupal_set_message('当前单无审批人，无法发起审批动作', 'error');
    }
  }

  /**
   * @description 取消工单动作
   */
  public function cancelSubmitForm(array $form, FormStateInterface $form_state) {
    $this->entity->set('status', 8)
      ->save();
    drupal_set_message('取消成功。');
  }

  /**
   * @description 发起审核动作
   * 1. 可以查看该需求单的审批状态
   */
  public function auditStatusSubmitForm(array $form, FormStateInterface $form_state) {
    drupal_set_message('查看审核状态动作, 使用弹出层');
  }

  /**
   * @description 计划需求单转立即执行
   *              1. 需求单创建时间变更
   *              2. 需求单包含的配件的创建时间变更
   */
  public function transformPlanTypeSubmitForm(array $form, FormStateInterface $form_state) {
    // 计划转立即执行，修改创建时间后，无法追踪哪些是计划类的需求单了。.
    $this->entity
      ->set('requiretype', 0)
      ->save();

    drupal_set_message('该计划需求单成功转为立即执行');
  }

  /**
   * @description 复用当前需求单
   *  需要重置以下内容:
   *    1. 工单状态，审批状态
   *    2. 审批人清空
   *    2. 配件复用
   */
  public function cloneRequirementSubmitForm(array $form, FormStateInterface $form_state) {

    $pids = $this->entity->get('pids');
    foreach ($pids as $pid) {
      $part = $pid->entity;
      $duplicate_part = $part->createDuplicate();
      $duplicate_part->set('ship_supply_id', 0);
      $duplicate_part->set('ship_supply_no', NULL);
      $duplicate_part->set('supply_id', 0);
      $duplicate_part->set('rno', 0);
      $duplicate_part->set('cno', 0);
      $duplicate_part->set('fno', 0);
      $duplicate_part->set('pno', 0);
      $duplicate_part->set('re_status', 0);
      $duplicate_part->set('re_ship_status', 0);
      $duplicate_part->set('re_audit', 0);
      $duplicate_part->set('ch_status', 0);
      $duplicate_part->set('ch_audit', 0);
      $duplicate_part->set('plandate', 0);

      $duplicate_part->save();
      $new_pids[] = $duplicate_part->id();
    }

    $duplicate_entity = $this->entity->createDuplicate();
    $duplicate_entity->set('status', 0);
    $duplicate_entity->set('audit', 0);
    $duplicate_entity->set('aids', []);
    $duplicate_entity->set('pids', $new_pids);
    $duplicate_entity->save();
    $form_state->setRedirectUrl(new Url("entity.requirement.collection"));
    drupal_set_message('该需求单成功复用，请进入个人模式重新编辑需求单!!!');
  }

  /**
   * @description 完成需求采购
   * 1. 更新需求单状态
   * 2. 更新采购单状态
   */
  public function finishRequirementSubmitForm(array $form, FormStateInterface $form_state) {

    \Drupal::service('requirement.requirementservice')->updateRequirementStatusforComplete($this->entity);

    $form_state->setRedirectUrl(new Url("entity.requirement.collection"));
    drupal_set_message('该需求单采购完成!!!');
  }

  /**
   * @description 完成周期性需求采购
   * 1. 更新需求单状态为立即执行
   */
  public function finishCircleRequirementSubmitForm(array $form, FormStateInterface $form_state) {
    $this->entity->set('requiretype', 0)
      ->save();
    drupal_set_message('结束周期性需求采购。');

    $form_state->setRedirectUrl(new Url("entity.requirement.collection"));

    drupal_set_message('该需求单完成周期性采购!!!');
  }

  /**
   * @description 新的周期性需求采购
   * 1. 更改原需求单标题，格式为"原标题+ (1,2,3,...)
   * 2. 复制周期性需求单，并更改期望交付时间+1个月
   * 3. 新需求单的配件复制增加，并清空原有属性，保留需求单的状态和审批状态
   */
  public function newCircleRequirementSubmitForm(array $form, FormStateInterface $form_state) {
    $duplicate_entity = $this->entity->createDuplicate();
    $duplicate_entity->save();

    $origin_title = $this->entity->get('title')->value;

    preg_match("/-\d*$/", $origin_title, $origin_number);
    $split_number = reset($origin_number);
    if (!empty($split_number)) {
      $number = abs($split_number);
    }
    else {
      $number = 1;
    }

    $simple_title = str_replace($split_number, '', $origin_title);

    $pids = $this->entity->get('pids');
    foreach ($pids as $pid) {
      $part = $pid->entity;
      $duplicate_part = $part->createDuplicate();
      $duplicate_part->set('ship_supply_id', 0);
      $duplicate_part->set('ship_supply_no', NULL);
      $duplicate_part->set('supply_id', 0);
      $duplicate_part->set('rno', $duplicate_entity->id());
      $duplicate_part->set('cno', 0);
      $duplicate_part->set('fno', 0);
      $duplicate_part->set('pno', 0);
      // $duplicate_part->set('re_status', $this->entity->get('status')->value);
      // 新的周期单也需要审核通过后才能进入采购.
      $duplicate_part->set('re_status', 2);
      $duplicate_part->set('re_ship_status', 0);
      $duplicate_part->set('re_audit', 1);
      // $duplicate_part->set('re_audit', $this->entity->get('audit')->value);.
      $duplicate_part->set('ch_status', 0);
      $duplicate_part->set('ch_audit', 0);
      $duplicate_part->set('plandate', 0);

      $duplicate_part->save();
      $new_pids[] = $duplicate_part->id();
    }
    $aids = $this->entity->get('aids');
    foreach ($aids as $aid) {
      $duplicate_audit_entity = $aid->entity->createDuplicate();
      $duplicate_audit_entity
        ->set('status', 1)
        ->set('isaudit', 1)
        ->save();
      $aid_ids[] = $duplicate_audit_entity->id();
    }
    $new_number = $number + 1;
    $duplicate_entity->set('status', 2);
    // $duplicate_entity->set('status', $this->entity->get('status')->value);.
    $duplicate_entity->set('audit', 1);
    // $duplicate_entity->set('audit', $this->entity->get('audit')->value);.
    $duplicate_entity->set('aids', $aid_ids);
    $duplicate_entity->set('pids', $new_pids);
    $duplicate_entity->set('requiretype', 0);
    $duplicate_entity->set('title', $simple_title . '-' . $new_number);
    $duplicate_entity->save();

    $this->entity->set('title', $simple_title . '-' . $new_number)
      ->save();

    // $form_state->setRedirectUrl(new Url("entity.requirement.collection"));.
    drupal_set_message('成功添加周期性需求单采购!!!, 需求单编号: ' . $duplicate_entity->id() . ' 需求单名称: ' . $duplicate_entity->get('title')->value);
  }

}
