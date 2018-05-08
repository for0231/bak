<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskRoomDetailForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 工单服务器上下架类详情表单.
 */
class SopTaskRoomDetailForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sop_task_room_detail_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sop = NULL) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    $entity = $this->entity = $sop;
    $disabled_bool = TRUE;
    $cabinet_server = entity_load_multiple_by_properties('cabinet_server', array('ipm_id' => $entity->get('mip')->target_id));
    $cabinet_server = reset($cabinet_server);
    $form['mips'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $cabinet_server->get('ipm_id')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['client_uid'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $cabinet_server->get('uid')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $sop_type_levels = sop_type_levels();
    $form['sop_type'] = array(
      '#type' => 'textfield',
      '#title' => '类型',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $sop_type_levels[$entity->get('sop_type')->value],
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['pid'] = array(
      '#type' => 'textfield',
      '#title' => '配置',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $cabinet_server->get('server_id')->entity->get('type')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['cabinet'] = array(
      '#type' => 'textfield',
      '#title' => '机柜',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $cabinet_server->get('cabinet_id')->entity->get('rid')->entity->label() . '-' . $cabinet_server->get('cabinet_id')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['port'] = array(
      '#type' => 'textfield',
      '#title' => '端口',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $cabinet_server->get('start_seat')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );

    $form['created'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => !empty($entity->get('created')->value) ? date('Y-m-d H:i:s', $entity->get('created')->value) : '-',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['uid'] = array(
      '#type' => 'textfield',
      '#title' => '建单人',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => $entity->get('uid')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['solving_uid'] = array(
      '#type' => 'textfield',
      '#title' => '操作人',
      '#required' => TRUE,
      '#disabled' => $disabled_bool,
      '#default_value' => !empty($entity->get('solving_uid')->target_id) ? $entity->get('solving_uid')->entity->label() : '-',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('需求、故障现象'),
      '#default_value' => $entity->get('description')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['base_description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('下一步操作'),
      '#default_value' => $entity->get('base_description')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['result_description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('处理过程、结果'),
      '#default_value' => $entity->get('result_description')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    /*
    $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => '保存',
    '#attributes' => array(
    'class' => array('btn btn-primary form-control input-sm'),
    ),
    );
     */
    if ($has_tech_permission || $has_bus_permission) {
      if ($sop->get('solving_uid')->target_id != $this->currentUser()->id()) {
        $form['actions']['sop_accept_submit'] = array(
          '#type' => 'submit',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#value' => '接受工单',
          '#submit' => array('::AcceptSopTaskFailureNewAction'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
      else {
        $form['actions']['bus_submit'] = array(
          '#type' => 'submit',
          '#value' => '保存',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#disabled' => $disabled_bool,
          '#submit' => array('::SaveSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['tech_other_submit'] = array(
          '#type' => 'submit',
          '#value' => '交其他人',
          '#disabled' => $disabled_bool,
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::OtherSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['bus_finish_sop'] = array(
          '#type' => 'submit',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#value' => '完成工单',
          '#submit' => array('::FinishSopSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
    }
    $form['#theme'] = array('sop_task_room_detail');
    return $form;
  }
  /**
   * @description 完成工单
   */
  public function FinishSopSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    // 业务已交付.
    $sop->set('sop_status', 4);
    $sop->save();
    drupal_set_message('工单完成!');
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * @description 交其他人处理
   */
  public function OtherSubmit(array &$form, FormStateInterface $form_state) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    $sop = $this->entity;
    if ($has_tech_permission) {
      // 运维转接工单.
      $sop->set('sop_status', 5);
    }
    elseif ($has_bus_permission) {
      // 业务转接.
      $sop->set('sop_status', 8);
    }
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->set('solving_uid', NULL);
    $sop->save();
    drupal_set_message('交付完成!');
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * @description 保存故障工单
   */
  public function SaveSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    $sop->set('base_description', $form_state->getValue('base_description'));
    $sop->set('result_description', $form_state->getValue('result_description'));
    $sop->set('level', $form_state->getValue('level'));
    // Sop.
    $sop->save();
    drupal_set_message('技术工单保存成功');
  }
  /**
   * @description 工单接受
   */
  public function AcceptSopTaskFailureNewAction(array &$form, FormStateInterface $form_state) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    // 接受工单仅保存当前处理人.
    $this->entity->set('solving_uid', $this->currentUser()->id());
    if ($has_tech_permission) {
      $this->entity->set('sop_status', 1);
    }
    elseif ($has_bus_permission) {
      $this->entity->set('sop_status', 2);
    }
    $this->entity->save();
    drupal_set_message('成功接受工单');
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
    $description = $form_state->getValue('description');
    $base_description = $form_state->getValue('base_description');
    $result_description = $form_state->getValue('result_description');
    $this->entity->set('description', $description);
    $this->entity->set('base_description', $base_description);
    $this->entity->set('result_description', $result_description);
    $this->entity->save();
    drupal_set_message($this->t('机房事务工单保存成功!'));
     */
  }

}
