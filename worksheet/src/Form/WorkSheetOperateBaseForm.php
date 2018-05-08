<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetOperateBaseForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetOperateBaseForm extends ContentEntityForm {
  /**
   * 跳转Url
   */
  protected function redirectUrl() {
    return new Url('admin.worksheet.sop');
  }
  //状态对应的字段
  protected function statusField() {
    return array();
  }
  //状态对应的按扭
  protected function statusBution() {
    return array(
      1 => array(
        'worksheet_business' => array('submit'),
        'worksheet_operation' => array('accept', 'abnormal_audit')
      ),
      5 => array(
        'worksheet_business' => array('submit', 'edit_to_op'),
      ),
      10 => array(
        'worksheet_operation' => array('accept', 'abnormal_audit')
      ),
      15 => array(
        'worksheet_operation' => array('submit','abnormal_audit','transfer', 'tobusiness', 'toclient', 'opwait')
      ),
      16 => array(
        'worksheet_operation' => array('ophandle')
      ),
      20 => array(
        'worksheet_operation' => array('accept', 'abnormal_accept')
      ),
      25 => array(
        'worksheet_operation' => array('accept')
      ),
      30 => array(
        'worksheet_business' => array('toclient', 'abnormal_quality', 'buswait', 'deliver_info'),
      ),
      31 => array(
        'worksheet_business' => array('bushandle'),
      ),
      35 => array(
        'worksheet_business' => array('complete', 'abnormal_quality', 'deliver_info'),
        'worksheet_operation' => array('deliver_info')
      ),
      40 => array(
        'worksheet_business' => array('complete', 'abnormal_quality', 'deliver_info'),
      )
    );
  }
  
  /**
   * 获取当前用户的bution
   */
  protected function currentUserBution($status) {
    $butions = $this->statusBution();
    if(!isset($butions[$status])) {
      return array();
    }
    $status_butions = $butions[$status];
    $account = \Drupal::currentUser();
    $roles = $account->getRoles();
    $user_bution = array();
    foreach($roles as $role) {
      if(isset($status_butions[$role])) {
        $user_bution = array_merge($user_bution, $status_butions[$role]);
      }
    }
    return array_unique($user_bution);
  }

  /**
   * 获取当前用户的字段
   */
  protected function currentUserField($status) {
    $fields = $this->statusField();
    if(!isset($fields[$status])) {
      return array();
    }
    $status_fields = $fields[$status];
    $account = \Drupal::currentUser();
    $roles = $account->getRoles();
    $user_fields = array();
    foreach($roles as $role) {
      if(isset($status_fields[$role])) {
        $user_fields = array_merge($user_fields, $status_fields[$role]);
      }
    }
    return array_unique($user_fields);
  }
  /**
   * 设置交付信息
   */
  protected function deliverInfo() {
    return '';
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $form['show_created'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#default_value' => date('Y-m-d H:i:s', $entity->get('created')->value)
    );
    $form['show_user'] = array(
      '#type' => 'textfield',
      '#title' => '建单人',
      '#default_value' => $entity->createUser()
    );
    $form['show_handleuser'] = array(
      '#type' => 'textfield',
      '#title' => '处理人',
      '#default_value' => $entity->handleUser()
    );
    $form['show_lastuser'] = array(
      '#type' => 'textfield',
      '#title' => '交接人',
      '#default_value' => $entity->lastUser()
    );
    $form = $this->operateForm($form, $form_state);
    $status = $entity->get('status')->value;
    //设置禁止操作的字段
    $user_fields = $this->currentUserField($status);
    foreach($form as $name => $item) {
      if(stripos($name, '#') !== false) {
        continue;
      }
      if(empty($user_fields)) {
        $form[$name]['#disabled'] = true;
      } else {
        if(!in_array($name, $user_fields)) {
          $form[$name]['#disabled'] = true;
        }
      }
    }
    //如果处理人不是自己就不能编辑
    $account = \Drupal::currentUser();
    $roles = $account->getRoles();
    $uid = $account->id();
    $handle_uid = $entity->get('handle_uid')->target_id;
    if(!empty($handle_uid) && $handle_uid != $uid && in_array('worksheet_operation', $roles)) {
      foreach($user_fields as $field) {
        $form[$field]['#disabled'] = true;
      }
    }
    $form['other'] = array(
      '#type' => 'hidden',
      '#value' => '',
      '#attributes' => array('id' => 'other_info')
    );
    if(in_array($status, array(15,16,30,35,40))) {
      $form['deliver_info_show'] = array(
        '#markup' => SafeMarkup::format('<div id="deliver-info-show" style="display:none;">' . $this->deliverInfo() . '</div>', array())
      );
    }
    $form['js']['#attached'] = array(
      'library' => array('worksheet/drupal.work-sheet-operate')
    );
    return $form;
  }
  /**
   * 扩展操作表单
   */
  protected function operateForm(array $form, FormStateInterface $form_state) {
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $actions['submit']['#value'] = '保存修改';
    $actions['accept'] = array(
      '#type' => 'submit',
      '#value' => '接受工单',
      '#submit' => array('::submitAccept')
    );
    $actions['abnormal_audit'] = array(
      '#type' => 'submit',
      '#value' => '异常审核',
      '#submit' => array('::submitAbnormalAudit')
    );
    $actions['abnormal_accept'] = array(
      '#type' => 'submit',
      '#value' => '异常接受工单',
      '#submit' => array('::submitAbnormalAccept')
    );
    $actions['transfer'] = array(
      '#type' => 'submit',
      '#value' => '转交他人',
      '#submit' => array('::submitForm', '::submitTransfer')
    );
    $actions['tobusiness'] = array(
      '#type' => 'submit',
      '#value' => '交业务',
      '#submit' => array('::submitForm', '::submitToBusiness'),
      '#validate' => array('::validateForm', '::validateToBusiness')
    );
    $actions['toclient'] = array(
      '#type' => 'submit',
      '#value' => '交客户',
      '#submit' => array('::submitForm', '::submitToClient')
    );
    $actions['opwait'] = array(
      '#type' => 'submit',
      '#value' => '设置运维等待',
      '#submit' => array('::submitForm', '::submitOpWait')
    );
    $actions['ophandle'] = array(
      '#type' => 'submit',
      '#value' => '设置运维处理',
      '#submit' => array('::submitOpHandle')
    );
    $actions['edit_to_op'] = array(
      '#type' => 'submit',
      '#value' => '修正并转交运维',
      '#submit' => array('::submitForm', '::submitEditToOp')
    );
    $actions['buswait'] = array(
      '#type' => 'submit',
      '#value' => '设置业务等待',
      '#submit' => array('::submitForm', '::submitBusWait')
    );
    $actions['bushandle'] = array(
      '#type' => 'submit',
      '#value' => '设置业务处理',
      '#submit' => array('::submitBusHandle')
    );
    $actions['complete'] = array(
      '#type' => 'submit',
      '#value' => '完成',
      '#submit' => array('::submitComplete'),
      '#validate' => array('::validateForm', '::validateComplete')
    );
    $actions['abnormal_quality'] = array(
      '#type' => 'submit',
      '#value' => '质量异常',
      '#submit' => array('::submitAbnormalQuality')
    );
    $actions['deliver_info'] = array(
      '#type' => 'submit',
      '#value' => '提取交付信息',
      '#submit' => array('::submitDeliverInfo')
    );
    //是否可以审核异常
    $last_uid = $entity->get('last_uid')->target_id;
    if($status == 15 && $last_uid != 0) {
      unset($actions['abnormal_audit']);
    }
    //显示当前用户的buttion
    $user_butions = $this->currentUserBution($status);
    foreach($actions as $name => $item) {
      if(empty($user_butions)) {
        unset($actions[$name]);
      } else {
        if(!in_array($name, $user_butions)) {
          unset($actions[$name]);
        }
      }
    }
    //当前处理人为自己，其它人只能转出
    if($status==15) {
      $account = \Drupal::currentUser();
      $uid = \Drupal::currentUser()->id();
      $handle_uid = $entity->get('handle_uid')->target_id;
      if($uid != $handle_uid) {
        foreach($user_butions as $bution){
          if($bution != 'transfer') {
            unset($actions[$bution]);
          }
        }
      }
    }
    return $actions;
  }
  /**
   * 接受工单
   */
  public function submitAccept(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    //新工单、业务转接、运维转接、运维返工，四种情况需求接单
    if($input['op'] == '接受工单' && ($status == 1 || $status == 10 || $status==20 || $status==25)) {
      $uid = \Drupal::currentUser()->id();
      if($status != 20 && $entity->getEntityTypeId() != 'work_sheet_room') {
        $entity->set('begin_time', REQUEST_TIME);
      }
      $entity->set('handle_uid', $uid);
      $entity->set('status', 15);
      //判断是否超时
      $is_cs = false;
      if($status == 1) {
        $config = \Drupal::config('worksheet.settings');
        $accept_time = $config->get('accept_time');
        $create_time = $entity->get('created')->value;
        if($accept_time > 0 && REQUEST_TIME - $create_time > $accept_time * 60) {
          $is_cs = true;
          $entity->handle_record_info = array(
            'uid' => $uid,
            'time' => REQUEST_TIME,
            'operation_id' => 27,
            'is_abnormal' => 1,
            'person_liable' => $config->get('task_assigner'),
            'reason' => '超时'
          );
          $entity->set('abnormal_exist', 1);
        }
      }
      if($status == 20) {
        $config = \Drupal::config('worksheet.settings');
        $transfer_time = $config->get('transfer_time');
        if($transfer_time > 0) {
          $entity_type = $entity->getEntityTypeId();
          $storage = \Drupal::entityManager()->getStorage('work_sheet_handle');
          $handle = $storage->getLastHandle($entity->id(), $entity_type);
          if($handle){
            $hanle_time = $handle->get('time')->value;
            if(REQUEST_TIME - $hanle_time > $transfer_time * 60) {
              $is_cs = true;
              $entity->handle_record_info = array(
                'uid' => $uid,
                'time' => REQUEST_TIME,
                'operation_id' => 28,
                'is_abnormal' => 1,
                'person_liable' => $config->get('task_assigner'),
                'reason' => '超时'
              );
              $entity->statistic_record = array(
                array('uid' => $uid, 'event' => 7, 'user_dept' => 'worksheet_operation')
              );
              $entity->set('abnormal_exist', 1);
            }
          }
        }
      }
      if(!$is_cs) {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 2,
          'is_abnormal' => 0,
        );
      }
      $entity->save();
    }
  }
  /**
   * 异常审核
   */
  public function submitAbnormalAudit(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op'] == '异常审核' && ($status==1 || $status==15)) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('last_uid', $uid);
      $entity->set('status', 5);
      $entity->set('abnormal_exist', 1);
      $other = $form_state->getUserInput()['other'];
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 20,
        'is_abnormal' => 1,
        'person_liable' => $entity->get('uid')->target_id,
        'reason' => $other
      );
      $entity->statistic_record = array(
        array('uid' => $entity->get('uid')->target_id, 'event' => 4)
      );
      $entity->save();
      //设置声音
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_business');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个异常审核工单', $uids);
      drupal_set_message('异常审核操作成功');
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   * 异常接受工单
   */
  public function submitAbnormalAccept(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op'] == '异常接受工单' && $status==20) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('handle_uid', $uid);
      $entity->set('status', 15);
      //判断超时
      $is_cs = false;
      $config = \Drupal::config('worksheet.settings');
      $transfer_time = $config->get('transfer_time');
      if($transfer_time > 0) {
        $entity_type = $entity->getEntityTypeId();
        $storage = \Drupal::entityManager()->getStorage('work_sheet_handle');
        $handle = $storage->getLastHandle($entity->id(), $entity_type);
        if($handle){
          $hanle_time = $handle->get('time')->value;
          if(REQUEST_TIME - $hanle_time > $transfer_time * 60) {
            $is_cs = true;
          }
        }
      }
      $entity->set('abnormal_exist', 1);
      $other = $form_state->getUserInput()['other'];
      $person_liable = $entity->get('last_uid')->target_id;
      if($is_cs) {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 29,
          'is_abnormal' => 1,
          'person_liable' => $person_liable,
          'reason' => $other
        );
        $entity->statistic_record = array(
          array('uid' => $person_liable, 'event' => 5, 'user_dept' => 'worksheet_operation'),
          array('uid' => $uid, 'event' => 7, 'user_dept' => 'worksheet_operation')
        );
      } else {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 21,
          'is_abnormal' => 1,
          'person_liable' => $person_liable,
          'reason' => $other
        );
        $entity->statistic_record = array(
          array('uid' => $person_liable, 'event' => 5, 'user_dept' => 'worksheet_operation')
        );
      }
      $entity->save();
    }
  }
  /**
   * 转交他人
   */
  public function submitTransfer(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op'] == '转交他人' && $status == 15) {
      $uid = \Drupal::currentUser()->id();
      $last_uid = $entity->get('handle_uid')->target_id;
      $entity->set('last_uid', $last_uid);
      $entity->set('handle_uid', 0);
      $entity->set('status', 20);
      //判断是否超时
      $is_cs = false;
      $completed = $entity->get('completed')->value;
      if($completed > 0) {
        $begin_time = $entity->get('begin_time')->value;
        if(REQUEST_TIME - $begin_time > $completed * 60) {
          $is_cs = true;
        }
      }
      if($is_cs) {
        $entity->set('abnormal_exist', 1);
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 30,
          'is_abnormal' => 1,
          'person_liable' => $uid,
          'reason' => '超时'
        );
        $entity->statistic_record = array(
          array('uid' => $uid, 'event' => 2, 'user_dept' => 'worksheet_operation'),
          array('uid' => $uid, 'event' => 7, 'user_dept' => 'worksheet_operation')
        );
      } else {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 3,
          'is_abnormal' => 0
        );
        $entity->statistic_record = array(
          array('uid' => $uid,'event' => 2, 'user_dept' => 'worksheet_operation')
        );
      }
      $entity->save();
      //设置声音
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个转交工单', $uids);
      drupal_set_message('转交他人操作成功');
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   * 交业务
   */
  public function submitToBusiness(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='交业务' && $status==15) {
      $uid = \Drupal::currentUser()->id();
      $last_uid = $entity->get('handle_uid')->target_id;
      $entity->set('last_uid', $last_uid);
      $entity->set('handle_uid', 0);
      $entity->set('status', 30);
      
      //判断是否超时
      $is_cs = false;
      $completed = $entity->get('completed')->value;
      if($completed > 0) {
        $begin_time = $entity->get('begin_time')->value;
        if(REQUEST_TIME - $begin_time > $completed * 60) {
          $storage = \Drupal::entityManager()->getStorage('work_sheet_handle');
          $entity_query = $storage->getBaseQuery();
          $entity_query->condition('wid', $entity->id());
          $entity_query->condition('entity_name', $entity->getEntityTypeId());
          $entity_query->condition('operation_id', 30);
          $result = $entity_query->execute()->fetchCol();
          if(empty($result)) {
            $is_cs = true;
          }
        }
      }
      $entity->set('end_time', REQUEST_TIME);
      if($is_cs) {
        $entity->set('abnormal_exist', 1);
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 26,
          'is_abnormal' => 1,
          'person_liable' => $uid,
          'reason' => '超时'
        );
        $entity->statistic_record = array(
          array('uid' => $uid, 'event' => 3, 'user_dept' => 'worksheet_operation'),
          array('uid' => $uid, 'event' => 7, 'user_dept' => 'worksheet_operation')
        );
      } else {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 5,
          'is_abnormal' => 0
        );
        $entity->statistic_record = array(
          array('uid' => $uid, 'event' => 3, 'user_dept' => 'worksheet_operation')
        );
      }
      $entity->save();
      //状态修改为30,运维已完成,修改工单状态表数据
      //待修改
      
      $wid = $entity->get('wid')->value;
      $entity_name = $entity->getEntityTypeId();
      $type = substr($entity_name,11,2);
      $group_wid = $type.$wid;
      //通过组合工单编号修改结束状态和结束时间
      $list = array(
        'estatus'=>$entity->get('status')->value,
        'etime'=>time()
      );
      $rows = \Drupal::service('worksheet.dbservice')->update_status($list,$group_wid);
      
      //设置声音
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_business');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个运维转交工单', $uids);
      drupal_set_message('交业务操作成功');
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   * 交业务验证
   */
  public function validateToBusiness(array &$form, FormStateInterface $form_state) {
  }
  /**
   * 交客户
   */   
  public function submitToClient(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='交客户' && ($status==15 || $status==30)) {
      $op_id = 11;
      $uid = \Drupal::currentUser()->id();
      $entity->set('handle_uid', $uid);
      $is_cs = false;
      if($status==15) {
        $entity->set('last_uid', $uid);
        $entity->set('status', 35);
        //状态修改为35,运维已完成,运维交付客户,
        //通过组合工单编号修改工单的结束状态和结束时间
        $dbserver = \Drupal::service('worksheet.dbservice');
        $wid = $entity->get('wid')->value;
        $entity_name = $entity->getEntityTypeId();
        $type = substr($entity_name,11,2);
        $group_wid = $type.$wid;
        $list = array(
          'estatus'=>$entity->get('status')->value,
          'etime'=>time()
        );
        $rows = $dbserver->update_status($list,$group_wid);
        //判断超时
        $completed = $entity->get('completed')->value;
        if($completed > 0) {
          $begin_time = $entity->get('begin_time')->value;
          if(REQUEST_TIME - $begin_time > $completed * 60) {
            $storage = \Drupal::entityManager()->getStorage('work_sheet_handle');
            $entity_query = $storage->getBaseQuery();
            $entity_query->condition('wid', $entity->id());
            $entity_query->condition('entity_name', $entity->getEntityTypeId());
            $entity_query->condition('operation_id', 30);
            $result = $entity_query->execute()->fetchCol();
            if(empty($result)) {
              $is_cs = true;
            }
          }
        }
        $entity->set('end_time', REQUEST_TIME);
        if($is_cs) {
          $entity->set('abnormal_exist', 1);
          $entity->statistic_record = array(
            array('uid' => $uid, 'event' => 3, 'user_dept' => 'worksheet_operation'),
            array('uid' => $uid, 'event' => 7, 'user_dept' => 'worksheet_operation')
          );
        } else {
          $entity->statistic_record = array(
            array('uid' => $uid, 'event' => 3,'user_dept' => 'worksheet_operation')
          );
        }
        $op_id = 6;
      } else {
        $entity->set('status', 40);
      }
      $entity->set('com_time', REQUEST_TIME);
      if($is_cs) {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => 25,
          'is_abnormal' => 1,
          'person_liable' => $uid,
          'reason' => '超时'
        );
      } else {
        $entity->handle_record_info = array(
          'uid' => $uid,
          'time' => REQUEST_TIME,
          'operation_id' => $op_id,
          'is_abnormal' => 0
        );
      }
      $entity->save();
      drupal_set_message('交客户操作成功');
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   * 设置运维等待
   */
  public function submitOpWait(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='设置运维等待' && $status == 15) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('status', 16);
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 9,
        'is_abnormal' => 0
      );
      $entity->save();
    }
  }
  /**
   * 设置运维处理
   */
  public function submitOpHandle(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='设置运维处理' && $status == 16) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('status', 15);
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 10,
        'is_abnormal' => 0
      );
      $entity->save();
    }
  }
  /**
   * 设置业务等待
   */
  public function submitBusWait(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='设置业务等待' && $status == 30) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('status', 31);
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 7,
        'is_abnormal' => 0
      );
      $entity->save();
    }
  }
  /**
   * 设置业务处理
   */
  public function submitBusHandle(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='设置业务处理' && $status == 31) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('status', 30);
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 8,
        'is_abnormal' => 0
      );
      $entity->save();
    }
  }
  /**
   * 质量异常
   */
  public function submitAbnormalQuality(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='质量异常' && ($status==30 || $status==35 || $status == 40)) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('status', 45);
      if(!$entity->get('com_time')->value) {
        $entity->set('com_time', REQUEST_TIME);
      }
      $entity->set('abnormal_exist', 1);
      $entity->abnormal_quality_clone = 1;
      $other = $form_state->getUserInput()['other'];
      $person_liable = $entity->get('last_uid')->target_id;
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 22,
        'is_abnormal' => 1,
        'person_liable' => $person_liable,
        'reason' => $other
      );
      $entity->statistic_record = array(
        array('uid' => $person_liable, 'event' => 6, 'user_dept' => 'worksheet_operation')
      );
      $entity->save();
      //设置声音
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个质量异常工单', $uids);
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   *完成
   */
  public function submitComplete(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='完成' && ($status==35 || $status ==40)) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('handle_uid', $uid);
      $entity->set('status', 45);
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 12,
        'is_abnormal' => 0
      );
      $entity->save();
      drupal_set_message('完成操作成功');
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   * 完成时验证
   */
  public function validateComplete(array &$form, FormStateInterface $form_state) {
  }
  /**
   * 提取交付信息
   */
  public function submitDeliverInfo(array $form, FormStateInterface $form_state) { 
  }
  /**
   * 修正并转交运维
   */
  public function submitEditToOp(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    if($input['op']=='修正并转交运维' && $status==5) {
      $uid = \Drupal::currentUser()->id();
      $entity->set('last_uid', 0);
      $handle_uid = $entity->get('handle_uid')->target_id;
      if($handle_uid > 0) {
        $entity->set('status', 15);
      } else {
        $entity->set('status', 10);
      }
      $entity->handle_record_info = array(
        'uid' => $uid,
        'time' => REQUEST_TIME,
        'operation_id' => 4,
        'is_abnormal' => 0
      );
      $entity->save();
      //设置声音
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个业务修正工单', $uids);
      drupal_set_message('改正并转交运维操作成功');
    }
    $form_state->setRedirectUrl($this->redirectUrl());
  }
  /**
   * 保存修改
   */
  public function save(array $form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    if($input['op'] == '保存修改') {
      $entity = $this->entity;
      $entity->save();
      drupal_set_message('保存成功！');
    }
  }
}
