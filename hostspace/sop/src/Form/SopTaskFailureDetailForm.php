<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskFailureDetailForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\hostlog\HostLogFactory;

/**
 * 工单服务器上下架类详情表单.
 */
class SopTaskFailureDetailForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sop_task_failure_detail_form';
  }
  /**
   * Check current user's permission.
   */
  private function checkCurrentDisabledPermission($sop, $hostclient) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    // 工单已完成时全部禁用.
    if ($sop->get('sop_status')->value == 4) {
      $disabled_all = TRUE;
    }
    else {
      $disabled_all = FALSE;
    }
    // 工单已接受.
    if ($sop->get('solving_uid')->target_id == \Drupal::currentUser()->id()) {
      $disabled_current_user = FALSE;
    }
    else {
      $disabled_current_user = TRUE;
    }

    return $disabled_all || $disabled_current_user;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sop = NULL) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    $entity = $this->entity = $sop;
    $hostclient = $sop->get('hid')->entity;
    $question = $sop->get('qid')->entity;
    $disabled_bool = $this->checkCurrentDisabledPermission($entity, $hostclient);
    $disable_all = TRUE;
    $form['markup_sop_status'] = array(
      '#markup' => '当前状态: ' . sop_task_status()[$entity->get('sop_status')->value],
      '#attributes' => array(
        'class' => array('form-control text-primary'),
      ),
    );
    $form['mips'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#required' => TRUE,
      '#disabled' => $disabled_bool || $disable_all,
      '#default_value' => $hostclient->get('ipm_id')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['created'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#required' => TRUE,
      '#disabled' => $disabled_bool || $disable_all,
      '#default_value' => date('Y-m-d H:i:s', $question->get('created')->value),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $sop_type_levels = sop_type_levels();
    $form['sop_type'] = array(
      '#type' => 'textfield',
      '#title' => '类型',
      '#required' => TRUE,
      '#disabled' => $disabled_bool || $disable_all,
      '#default_value' => $sop_type_levels[$entity->get('sop_type')->value],
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $options_level = sop_task_failure_question_level();
    $form['level'] = array(
      '#type' => 'select',
      '#title' => '问题等级',
      '#default_value' => $entity->get('level')->value,
      '#disabled' => $disabled_bool,
      '#options' => $options_level,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $diff_time = timediff($question->get('finish_stamp')->value, $question->get('accept_stamp')->value);
    $diff = !empty($diff_time['allmin']) ? $diff_time['allmin'] . '分钟' : '-';
    $form['diff_time'] = array(
      '#type' => 'textfield',
      '#title' => '耗时',
      '#disabled' => $disabled_bool || $disable_all,
      '#default_value' => $diff,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['uid'] = array(
      '#type' => 'textfield',
      '#title' => '建单人',
      '#required' => TRUE,
      '#disabled' => $disabled_bool || $disable_all,
      '#default_value' => $question->get('uid')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['solving_uid'] = array(
      '#type' => 'textfield',
      '#title' => '操作人',
      '#required' => TRUE,
      '#disabled' => $disabled_bool || $disable_all,
      '#default_value' => !empty($entity->get('solving_uid')->target_id) ? $entity->get('solving_uid')->entity->label() : '-',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    // @todo 需要做safe markup 处理
    /*
    $form['description'] = array(
    '#theme' => 'admin_handle_task_failure_info',
    //'#markup' => $question->get('content')->getValue()[0]['value'],
    '#question' => $question,
    '#attributes' => array(
    'class' => array('form-control input-sm'),
    ),
    );
     */
    $form['base_description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('故障原因(选填)'),
      '#default_value' => $entity->get('base_description')->value,
      '#disabled' => $disabled_bool,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['result_description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('处理过程、结果(选填)'),
      '#default_value' => $entity->get('result_description')->value,
      '#disabled' => $disabled_bool,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['actions'] = array(
      '#type' => 'actions',
    );
    // @todo 这个参数待确定?
    $disable_submit = FALSE;

    if ($has_tech_permission) {
      if (in_array($sop->get('sop_status')->value, array(0, 5, 11))) {
        $form['actions']['sop_accept_submit'] = array(
          '#type' => 'submit',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#value' => '接受工单',
          '#submit' => array('::techAcceptSopTaskFailureNewAction'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
      if ($sop->get('solving_uid')->target_id == $this->currentUser()->id()) {
        $form['actions']['bus_submit'] = array(
          '#type' => 'submit',
          '#value' => '保存',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#disabled' => $disabled_bool,
          '#submit' => array('::techSaveSubmit'),
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
          '#submit' => array('::techOtherSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['bus_finish_sop'] = array(
          '#type' => 'submit',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#value' => '完成工单',
          '#submit' => array('::techFinishSopSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
    }

    $form['#theme'] = 'sop_task_failure_detail';
    // 故障问答详情.
    $this->drawContactQuestionForm($form, $form_state);
    return $form;
  }
  /**
   * 绘出显示问题处理详情表单的表头.
   */
  private function getDetailFormHeader() {
    $question = $this->entity->get('qid')->entity;
    $status = $question->get('status')->value ? questionStatus()[$question->get('status')->value] : '未处理';
    $finish_stamp = $question->get('finish_stamp')->value ? date('Y-m-d H:i', $question->get('finish_stamp')->value) : t('Unfinished');

    // 得到当前问题类型处理完成所需要的时间.
    $ecxept_time = $question->get('parent_question_class')->entity->get('limited_stamp')->value;
    // 处理完成实际的消耗时间.
    $real_time = ceil(($question->get('finish_stamp')->value - $question->get('accept_stamp')->value) / 60);
    // 判断是否超时  实际消耗的时间大于期望时间  则超时.
    $status_str = '';
    if ($ecxept_time < $real_time) {
      $status_str .= ' / <span  style="color:red">' . t('Time out : ') . ($real_time - $ecxept_time) . ' min</span>';
    }

    $header = array(
      'deals_status' => array(
        'data' => array(
          array(
            '#type' => 'container',
            '#markup' => t('Type of question:') . $question->get('parent_question_class')->entity->label(),
          ),
          array(
            '#type' => 'container',
            '#markup' => t('Status:') . $status . SafeMarkup::format($status_str, array()),
          ),
        ),
      ),
      'type_question ' => array(
        'data' => array(
          array(
            '#type' => 'container',
            '#markup' => ('IP of server:') . SafeMarkup::format("<br/>" . str_replace("\r\n", "<br/>", $question->get('mipstring')->value), array()),
          ),
        ),
      ),
      'stamp' => array(
        'data' => array(
           array(
             '#type' => 'container',
             '#markup' => t('Reception hours:') . date('Y-m-d H:i', $question->get('accept_stamp')->value),
             '#attributes' => array(
               'class' => array('time'),
             ),
           ),
          array(
            '#type' => 'container',
            '#markup' => t('Expected completion time:') . date('Y-m-d H:i', $question->get('pre_finish_stamp')->value),
            '#attributes' => array(
              'class' => array('time'),
            ),
          ),
          array(
            '#type' => 'container',
            '#markup' => t('Finish time:') . $finish_stamp,
            '#attributes' => array(
              'class' => array('time'),
            ),
          ),
        ),
      ),
    );
    return $header;
  }
  /**
   * 给显示详情的table添加列.
   *
   * @param $reply
   *   回复内容对象
   */
  private function appendRowsToTable($reply) {
    $question = $this->entity->get('qid')->entity;
    // 得到这条回复信息的标志.
    $flag = $reply->flags;
    $user_obj = NULL;
    $dept_name = '';
    $user_name = '';
    if ($flag == 1) {
      // 标志这条信息是前台会员反馈.
      $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client', $reply->uid);
      $dept_name = 'Client';
      $user_name = $user_obj ? $user_obj->client_name : $question->get('uid')->entity->getUsername();
    }
    else {
      // 公司员工处理的回复.
      $user_target = $question->get('server_uid')->entity;
      if (!empty($user_entity)) {
        $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $question->get('server_uid')->entity->id());
      }
      else {
        $user_obj = '';
      }
      $dept_name = $user_obj ? entity_load('taxonomy_term', $user_obj->department)->label() : 'System';
      $user_name = $user_obj ? $user_obj->employee_name : $question->get('uid')->entity->getUsername();
    }

    $row_arr = array(
     array(
       '#markup' => '(' . $dept_name . ') ' . $user_name . " <br/>" . t('Processing time: ') . date('Y-m-d H:i', $reply->creat),
     ),
      array(
        '#markup' => $reply->content,
        '#wrapper_attributes' => array(
          'colspan' => 2,
        ),
      ),
    );
    return $row_arr;
  }
  /**
   *
   */
  public function drawContactQuestionForm(array &$form, FormStateInterface $form_state) {
    $form['deal'] = array(
      '#type' => 'fieldset',
      '#title' => t('Processing details'),
    );

    $form['deal']['deal_detail'] = array(
      '#type' => 'table',
      '#header' => $this->getDetailFormHeader(),
      '#attributes' => array(
        'class' => array('table-hover table-bordered'),
      ),
    );
    $question = $this->entity->get('qid')->entity;

    // 表格第一行 显示问题基本信息
    // 申报该故障的客户.
    $client = \Drupal::service('member.memberservice')->queryDataFromDB('client', $question->get('uid')->entity->id());
    $client_name = $client ? $client->client_name : $question->get('uid')->entity->getUsername();
    $row_arr = array(
     array(
       '#markup' => SafeMarkup::format($client_name . "<br/>" . t('POST time:') . date('Y-m-d H:i', $question->get('created')->value), array()),
     ),
      array(
        '#markup' => $question->get('content')->value,
        '#wrapper_attributes' => array(
          'colspan' => 2,
        ),
      ),
    );
    $form['deal']['deal_detail'][1] = $row_arr;

    // 第二行开始 根据当前故障查询所有的回复详情.
    $allReply = getQuestionService()->getAllReplyMessageByQuestionId($question->id());
    // 从第二行开始追加.
    $row_id = 1;
    foreach ($allReply as $key => $reply) {
      $row_id++;
      $form['deal']['deal_detail'][$row_id] = $this->appendRowsToTable($reply);
    }
    if ($question->get('status')->value != 3) {
      $form['question']['questioncontent'] = array(
        '#type' => 'textfield',
        '#attributes' => array(
          'placefolder' => 'Please fill',
          'class' => array('form-control input-sm'),
        ),
      );
      $form['question']['contact_submit'] = array(
        '#type' => 'submit',
        '#value' => 'Send',
        '#submit' => array('::sendQuestionContent'),
        '#attributes' => array(
          'class' => array('btn btn-primary form-control input-sm'),
        ),
      );
    }
    return $form;
  }
  /**
   *
   */
  public function sendQuestionContent(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    $question = $sop->get('qid')->entity;

    // 回复问题.
    $content = $form_state->getValue('questioncontent');
    if (!$content) {
      drupal_set_message($this->t('Please fill in your reply message !'), 'error');
      return;
    }
    else {
      $reply_arr = array(
        'content' => $content,
        'creat' => REQUEST_TIME,
        'uid' => \Drupal::currentUser()->id(),
        'question_id' => $question->id(),
      );
      $reply_id = getQuestionService()->saveQuestionReply($reply_arr);
      // 设置故障的状态.
      $question->status = 1;
      $question->save();
      // ======================= 写入回应故障 的日志 ============================.
      $question->other_data = array('data_name' => 'server_question_detail', 'data' => $reply_arr, 'data_id' => $reply_id);
      HostLogFactory::OperationLog('question')->log($question, 'insert');
    }

  }
  /**
   * @description 完成工单
   */
  public function techFinishSopSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    $question = $sop->get('qid')->entity;

    $question->finish_stamp = REQUEST_TIME;
    // 设置故障的状态.
    $question->status = 3;
    $question->save();

    // 工单完成.
    $sop->set('sop_status', 4);
    $sop->save();
    drupal_set_message('工单完成!');
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * @description 工单接受
   */
  public function techAcceptSopTaskFailureNewAction(array &$form, FormStateInterface $form_state) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    // 问题接受处理.
    $question = $this->entity->get('qid')->entity;
    $question->server_uid = \Drupal::currentUser()->id();
    $question->accept_stamp  = REQUEST_TIME;
    $question->status  = 1;
    // 得到当前问题类型处理完成所需要的时间.
    $times = $question->get('parent_question_class')->entity->get('limited_stamp')->value;
    // 设置预计完成的时间.
    $question->pre_finish_stamp  = strtotime('+' . $times . ' minutes', REQUEST_TIME);
    $question->save();

    // ======================= 写入接受故障的日志 =============.
    HostLogFactory::OperationLog('question')->log($question, 'control');
    // ======================= 日志写入结束 ====================
    //
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
    $level = $form_state->getValue('level');
    $description = $form_state->getValue('description');
    $base_description = $form_state->getValue('base_description');
    $result_description = $form_state->getValue('result_description');
    $this->entity->set('level', $level);
    $this->entity->set('description', $description);
    $this->entity->set('base_description', $base_description);
    $this->entity->set('result_description', $result_description);
    $this->entity->save();
    drupal_set_message($this->t('故障处理工单保存成功!'));
     */
  }


  /**
   * 为新工单时，显示审核异常按钮.
   */
  public function acceptSopTaskFailureExceptionAction(array &$form, FormStateInterface $form_state) {
    // @todo 暂时留空，待处理
    // 这个动作未理清思路，如果按工单由客户提出，异常审核，则会返回给客户？
    // 是否需要给后台人员手动建立故障工单机会?
    drupal_set_message($this->t('异常故障工单处理成功! 暂时留空处理!'));
  }

  /**
   * @description 技术交其他人处理
   */
  public function techOtherSubmit(array &$form, FormStateInterface $form_state) {
    $has_bus_permission = $this->currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = $this->currentUser()->hasPermission('administrator technology sop permission');
    $sop = $this->entity;
    $question = $this->entity->get('qid')->entity;
    $base_description = $form_state->getValue('base_description');
    $result_description = $form_state->getValue('result_description');
    $record_arr = array(
      'to_uid' => 0,
      'from_uid' => $question->get('server_uid')->entity->id(),
      'from_stamp' => REQUEST_TIME,
      'question_id' => $question->id(),
      'description' => $base_description['value'],
    );
    $record_id = getQuestionService()->saveTransferRecord($record_arr);
    // ======================= 写入转出故障 的日志 ============================
    // 得到这一条转交记录.
    $record_arr['flag'] = 'transfer_out';
    $question->other_data = array('data_name' => 'server_question_convert', 'data' => $record_arr, 'data_id' => $record_id);
    HostLogFactory::OperationLog('question')->log($question, 'insert');
    // ======================= 日志写入结束 ==================================.
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
   * @description 技术保存故障工单
   */
  public function techSaveSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    $sop->set('base_description', $form_state->getValue('base_description'));
    $sop->set('result_description', $form_state->getValue('result_description'));
    $sop->set('level', $form_state->getValue('level'));
    // Sop.
    $sop->save();
    drupal_set_message('技术工单保存成功');
  }
  /**
   * 工单的动作按钮定义组.
   */
  private function drawSopActions(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form['actions'] = array(
      '#type' => 'actions',
    );
    $disable_submit = FALSE;
    /**
     * @todo 该工单未接受工单保存按钮
     */
    if ($entity->get('solving_uid')->target_id != \Drupal::currentUser()->id()) {
      $disable_submit = TRUE;
    }
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#prefix' => '<div class="col-xs-2">',
      '#suffix' => '</div>',
      '#value' => '保存',
      '#disabled' => $disable_submit,
      '#attributes' => array(
        'class' => array('btn btn-primary form-control input-sm'),
      ),
    );
    /**
     * Actions for sop.
     * 0. 新工单
     */
    if ($entity->get('sop_status')->value == 0) {
      $form['actions']['sop_accept'] = array(
        '#type' => 'submit',
        '#prefix' => '<div class="col-xs-2">',
        '#suffix' => '</div>',
        '#value' => '接受工单',
        '#submit' => array('::acceptSopTaskFailureNewAction'),
        '#attributes' => array(
          'class' => array('btn btn-primary form-control input-sm'),
        ),
      );
      // 同为新工单时，显示该按钮.
      $form['actions']['sop_unusual'] = array(
        '#type' => 'submit',
        '#prefix' => '<div class="col-xs-2">',
        '#suffix' => '</div>',
        '#value' => '审核异常',
        '#submit' => array('::acceptSopTaskFailureExceptionAction'),
        '#attributes' => array(
          'class' => array('btn btn-primary form-control input-sm'),
        ),
      );
    }
  }

}
