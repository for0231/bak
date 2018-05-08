<?php

/**
 * @file
 * Contains \Drupal\question\Form\QuestionDetailForm.
 */

namespace Drupal\question\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for question category add.
 */

class QuestionDetailForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    unset($form['content']);
    //返回列表页面
    $form['back'] = array(
      '#type' => 'link',
      '#title' => t('返回上一页面'),
      '#url' => new Url('question.admin')
    );
    // 接受问题的表单元素
    $this->drawAcceptQuestionForm($form, $form_state);

    // 负责处理该问题的用户实体
    $server_user = $this->entity->get('server_uid')->entity;

    //如果当前用户不是该问题的负责人 则不能转交问题  问题 已经处理完成也不能转交问题

    // 得到该问题的转交记录
    $record_is_accpet = false;
    $records = getQuestionService()->getQuestionTransferRecordByQuestionId($this->entity->id());
    if(!empty($records)) {
      foreach($records as $record) {
        if($record->to_uid != \Drupal::currentUser()->id() && !$record->to_stamp){ // 问题已经被转出  不绘出转交的控件
          $record_is_accpet = true;
          break;
        }
      }
    }
    if($server_user && $server_user->id() == \Drupal::currentUser()->id() && $this->entity->get('status')->value != 3 && !$record_is_accpet){
      // 变更问题的负责人 的表单元素
      $this->drawTransferQuestionForm($form, $form_state);
    }

    // 显示转接记录的表单元素
    $this->drawTransferRecordForm($form, $form_state);

    // 显示问题处理详情的表单元素
    $this->drawQuestionDealDetailForm($form, $form_state);

    //若该问题还未处理成功才加载用于回复的表单元素  && 不是当前用户接受的问题不能回复
    if($this->entity->get('status')->value != 3 && $server_user && $server_user->id() == \Drupal::currentUser()->id()) {
       //对故障问题作出回复的表单元素
      $this->drawReplyQuestionForm($form, $form_state);
    }
    return $form;
  }

  /**
   * 绘出 接受问题的表单元素
   *
   * @param $form
   * @param $form_state
   */
  private function drawAcceptQuestionForm(array &$form, FormStateInterface $form_state){
    //查询该问题是否有转交记录
    $records = getQuestionService()->getQuestionTransferRecordByQuestionId($this->entity->id());
    //得到最新一条的转交记录
    $newRecord = array_shift($records);

    //该故障还未被接受的原因 -》由别人转出
    if($newRecord && !$newRecord->to_stamp && $newRecord->to_uid == \Drupal::currentUser()->id()) {
      $form['transfer_record_id'] = array(
         '#type' => 'value',
         '#value' => $newRecord->id
      );
      $form['t_is_accept'] = array(
        '#type' => 'fieldset',
        '#title' => t('Whether to accept to deal with the problem.'),
      );
      $form['t_is_accept']['transfer_container'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('container-inline')
        )
      );
      //故障的负责专员
      $emp = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$newRecord->from_uid);
      $form['t_is_accept']['transfer_container']['t_accept'] = array(
        '#type' => 'label',
        '#title' => t('该故障由&nbsp;%from&nbsp;于%time转交给你，是否接受',array('%from'=> $emp ? $emp->employee_name : 'admin','%time'=> format_date($newRecord->from_stamp, 'custom', 'Y-m-d H:i:s')))
      );
      $form['t_is_accept']['transfer_container']['t_submit'] = array(
        '#type' => 'submit',
        '#value' => '接受',
        '#name' => 't_accept_deal',
        '#submit' => array('::acceptTransferQuestion'),
      );

    } elseif(!$newRecord && !$this->entity->get('server_uid')->entity) {  // 这个故障一次都还没被接受
      $form['is_accept'] = array(
        '#type' => 'fieldset',
        '#title' => t('Whether to accept to deal with the problem.'),
      );
      $form['is_accept']['container'] = array(
        '#type' => 'container',
        '#attributes' => array(
           'class' => array('container-inline')
         )
      );
      $form['is_accept']['container']['accept'] = array(
        '#type' => 'label',
        '#title' => '该故障还没有人处理，你是否要接受处理该故障'
      );
      $form['is_accept']['container']['submit'] = array(
        '#type' => 'submit',
        '#value' => '接受',
        '#name' => 'accept_deal',
        '#submit' => array('::acceptQuestion'),
      );
    }
    return $form;
  }

  /**
   * 绘出变更负责人的表单元素 并执行转交
   *
   * @param $form
   * @param $form_state
   */
  private function drawTransferQuestionForm(array &$form, FormStateInterface $form_state){
    //变更问题的负责人
    $form['transfered'] = array(
      '#type' => 'details',
      '#id' => 'do_transfer_wrapper',
      '#title' => t('Transfer the question out'),
    );

    $form['transfered']['question_num'] = array(
      '#type' => 'label',
      '#title' => '问题编号: &nbsp;&nbsp;&nbsp;'.$this->entity->id(),
    );
    $form['transfered']['client'] = array(
      '#type' => 'label',
      '#title' => '客户:&nbsp;&nbsp;&nbsp;&nbsp;'.$this->entity->get('uid')->entity->getUsername()
    );
    //部门、员工联动
    $dept = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('dept_employee',0,1);
    $dept_ops = array('' => 'Select department');
	  foreach ($dept as $v) {
	 	  $dept_ops[$v->tid] = $v->name;
	  }
    $form['transfered']['dept'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    $form['transfered']['dept']['department'] = array(
      '#type' => 'select',
      '#options' => $dept_ops,
      '#title' => '部门',
      '#ajax' => array(
        'callback' => '::loadPeople',
        'wrapper' => 'employee_wrapper',
        'effect' => 'none',
        'method' => 'html'
      )
    );
    $form['transfered']['dept']['dept_child'] = array(
      '#type' => 'container',
      '#id' => 'employee_wrapper',
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    //得到页面选择的部门的部门id
    $dept_id = $form_state->getValue('department');
    if(!empty($dept_id)){
      $employees = getQuestionService()->getEmployeeByDepartment($dept_id);
      $employee_arr = array('' => '-- Select employee --');
      foreach($employees as $key=>$emp) {
        $employee_arr[$emp->uid] = $emp->employee_name;
      } 
      $form['transfered']['dept']['dept_child']['employee'] = array(
        '#type' => 'select',
        '#options' => $employee_arr,
        '#title' => '员工'
      );
    }
    // ---------联动结束 ---------------
    $form['transfered']['reason'] = array(
      '#type' => 'textarea',
      '#title' => '转出原因/处理描述:'
    );
    $form['transfered']['submit'] = array(
      '#type' => 'submit',
      '#value' => '确定转交',
      '#name' => 'do_transfer',
      '#submit' => array('::transferQuestion'),
    );
    
    return $form;
  }

  /**
   * 绘出显示问题转交记录的表单元素
   *
   * @param $form
   * @parm $form_state
   *
   */
  private function drawTransferRecordForm(array &$form, FormStateInterface $form_state) {
    $header = $this->buildRecordHeader();
    $transfer_record = getQuestionService()->getQuestionTransferRecordByQuestionId($this->entity->id(),$header);
    if($transfer_record) {
      $form['transferRecord'] = array(
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Transfered record'),
      );
      $rows_arr = array();
      $form['transferRecord']['show'] = array(
        '#type' => 'table',
        '#header' => $this->buildRecordHeader(),
        '#rows' => array(),
      );
      foreach($transfer_record as $key=>$record) {
        $emp = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$record->from_uid);
        //给表格绑行
        $rows_arr[$key] = array(
          'Transferred out' => $emp ? $emp->employee_name : 'admin',
          'Transferred out of time' => date('Y-m-d H:i',$record->from_stamp),// $this->dateFormatter->format($record->from_stamp, 'short'),
          'Recipient' => $record->to_uid != 0 ? \Drupal::service('member.memberservice')->queryDataFromDB('employee',$record->to_uid)->employee_name : 'SOP转出',
          'Receiving time' => $record->to_stamp ? date('Y-m-d H:i',$record->to_stamp):'未接受' ,
          'Description' => $record->description
        );
      }
      $form['transferRecord']['show']['#rows'] = $rows_arr;
      $form['transferRecord']['record_pager'] = array('#type' => 'pager');
    }
    return $form;
  }

   /**
   * 创建显示转接记录的表头
   */
  public function buildRecordHeader() {
    $header['from_uid'] = array(
      'data' => $this->t('Transferred out'),
      'field' => 'from_uid',
      'specifier' => 'from_uid'
    );
    $header['from_stamp'] = array(
      'data' => $this->t('Transferred out of time'),
      'field' => 'from_stamp',
      'specifier' => 'from_stamp'
    );
    $header['to_uid'] = array(
      'data' => $this->t('Recipient'),
      'field' => 'to_uid',
      'specifier' => 'to_uid'
    );
    $header['to_stamp'] = array(
      'data' => $this->t('Receiving time'),
      'field' => 'to_stamp',
      'specifier' => 'to_stamp'
    );
    $header['description'] = array(
      'data' => $this->t('Description'),
    );
   return $header ;
  }

  /**
   * 绘出显示问题处理详情的表单元素
   *
   * @param $form
   * @parm $form_state
   *
   */
  private function drawQuestionDealDetailForm(array &$form, FormStateInterface $form_state) {
    $form['deal'] = array(
      '#type' => 'fieldset',
      '#title' => t('Processing details'),
    );

    $form['deal']['deal_detail'] = array(
      '#type' => 'table',
      '#header' => $this->getDetailFormHeader(),
    );
    //给表单指定CSS样式表
    $form['#attached']['library'][] = 'question/question.detail';

    //表格第一行 显示问题基本信息
    //申报该故障的客户
    $client = \Drupal::service('member.memberservice')->queryDataFromDB('client',$this->entity->get('uid')->entity->id());
    $client_name = $client ? $client->client_name : $this->entity->get('uid')->entity->getUsername();
    $row_arr = array(
     array(
       '#markup' => SafeMarkup::format($client_name ."<br/>" . t('POST time:') . date('Y-m-d H:i',$this->entity->get('created')->value), array()),
      ),
      array(
        '#markup' => $this->entity->get('content')->value,
        '#wrapper_attributes' => array(
          'colspan' => 2
        )
      ),
    );
    $form['deal']['deal_detail'][1] = $row_arr;

    //第二行开始 根据当前故障查询所有的回复详情
    $allReply = getQuestionService()->getAllReplyMessageByQuestionId($this->entity->id());
    // 从第二行开始追加
    $row_id = 1;
    foreach($allReply as $key=>$reply) {
      $row_id++;
      $form['deal']['deal_detail'][$row_id] = $this->appendRowsToTable($reply);
    }
    return $form;
  }
  /**
   * 给显示详情的table添加列
   *
   * @param $reply
   *  回复内容对象
   */
  private function appendRowsToTable($reply) {
    // 得到这条回复信息的标志
    $flag = $reply->flags;
    $user_obj = null;
    $dept_name = '';
    $user_name = '';
    if($flag == 1) {  // 标志这条信息是前台会员反馈
      $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client',$reply->uid);
      $dept_name = 'Client';
      $user_name =  $user_obj ? $user_obj->client_name: $this->entity->get('uid')->entity->getUsername();
    } else{  // 公司员工处理的回复
      $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$this->entity->get('server_uid')->entity->id());
      $dept_name = $user_obj ? entity_load('taxonomy_term', $user_obj->department)->label() : 'System';
      $user_name = $user_obj ? $user_obj->employee_name : $this->entity->get('uid')->entity->getUsername();
    }

    $row_arr = array(
     array(
       '#markup' => '('.$dept_name.') ' .$user_name." <br/>" . t('Processing time: ').date('Y-m-d H:i',$reply->creat),
      ),
      array(
        '#markup' => $reply->content,
        '#wrapper_attributes' => array(
          'colspan' => 2
        )
      ),
    );
    return $row_arr;
  }
  /**
   * 绘出显示问题处理详情表单的表头
   *
   */
  private function getDetailFormHeader() {
    $status = $this->entity->get('status')->value ? questionStatus()[$this->entity->get('status')->value] : '未处理';
    $finish_stamp = $this->entity->get('finish_stamp')->value ? date('Y-m-d H:i',$this->entity->get('finish_stamp')->value) : t('Unfinished');

    //得到当前问题类型处理完成所需要的时间
    $ecxept_time = $this->entity->get('parent_question_class')->entity->get('limited_stamp')->value;
    // 处理完成实际的消耗时间
    $real_time = ceil( ($this->entity->get('finish_stamp')->value - $this->entity->get('accept_stamp')->value)/60 );
    //判断是否超时  实际消耗的时间大于期望时间  则超时
    $status_str ='';
    if($ecxept_time < $real_time ) {
      $status_str .= ' / <span  style="color:red">'.t('Time out : ').($real_time - $ecxept_time).' min</span>';
    }

    $header = array(
      'deals_status' => array(
        'data' =>array(
          array(
            '#type' => 'container',
            '#markup' => t('Type of question:').$this->entity->get('parent_question_class')->entity->label(),
          ),
          array(
            '#type' => 'container',
            '#markup' => t('Status:') . $status.SafeMarkup::format($status_str, array()),
          )
        ),
      ),
      'type_question ' => array(
        'data' =>array(
          array(
            '#type' => 'container',
            '#markup' => ('IP of server:'). SafeMarkup::format("<br/>".str_replace("\r\n","<br/>",$this->entity->get('mipstring')->value), array()),
          )
        ),
      ),
      'stamp' => array(
        'data' => array(
           array(
            '#type' => 'container',
            '#markup' => t('Reception hours:').date('Y-m-d H:i',$this->entity->get('accept_stamp')->value),
            '#attributes' => array(
              'class' => array('time')
            )
          ),
          array(
            '#type' => 'container',
            '#markup' => t('Expected completion time:').date('Y-m-d H:i',$this->entity->get('pre_finish_stamp')->value),
            '#attributes' => array(
              'class' => array('time')
            )
          ),
          array(
            '#type' => 'container',
            '#markup' => t('Finish time:').$finish_stamp,
            '#attributes' => array(
              'class' => array('time')
            )
          ),
        )	
      )
    );
    return $header;
  }

  /**
   * 绘出回复问题的表单元素
   *
   * @param $form
   * @parm $form_state
   *
   */
  private function drawReplyQuestionForm(array &$form, FormStateInterface $form_state) {
    $form['reply'] = array(
      '#type' => 'fieldset',
      '#title' => t('Reply to question'),
    );

    $form['reply']['reply_content'] = array(
      '#type' => 'text_format',
      '#title' => '回应该故障问题'
    );
    $form['reply']['question_status'] = array(
      '#type' => 'select',
      '#title' => '处理状态',
      '#options' => array('0' => t('Select status')) + questionStatus()
    );

    return $form;
  }

  /**
   * 接受由别人转入的故障问题
   *
   * @param $form
   * @param $form_state
   */

  public function acceptTransferQuestion(array &$form, FormStateInterface $form_state){
    $transfer_record_id = $form_state->getValue('transfer_record_id');
    $field_arr = array('to_stamp' => REQUEST_TIME);
    //修改故障的负责专员
    $entity = $this->entity;
    $entity->server_uid = \Drupal::currentUser()->id();
    $entity->save();
    getQuestionService()->setTransferRecoreAccecpTime($transfer_record_id,$field_arr);
    // ======================= 写入接手别人转出故障 的日志 ============================
    // 得到这一条转交记录
    $record_obj = getQuestionService()->getTranslateRecordById($transfer_record_id);
    $record_obj->flag = 'receive';
    $entity->other_data = array('data_name' => 'server_question_convert', 'data' => (array)$record_obj, 'data_id' => $record_obj->id);

    HostLogFactory::OperationLog('question')->log($entity, 'update');
    // ======================= 日志写入结束 ==================================
    drupal_set_message($this->t('You have accepted the problem successfully.Please deal with it as soon as possible!'));
  }

  /**
   * @description 接受处理新的故障问题
   *
   * @param $form
   * @param $form_state
   */
  public function acceptQuestion(array &$form, FormStateInterface $form_state){
    $entity = $this->entity;
    $entity->server_uid = \Drupal::currentUser()->id();
    $entity->accept_stamp  = REQUEST_TIME;
    $entity->status  = 1;
    //得到当前问题类型处理完成所需要的时间
    $times = $entity->get('parent_question_class')->entity->get('limited_stamp')->value;
    //设置预计完成的时间
    $entity->pre_finish_stamp  = strtotime('+'.$times.' minutes',REQUEST_TIME);
    $entity->save();

    // ======================= 写入接受故障的日志 =============
    HostLogFactory::OperationLog('question')->log($entity, 'control');
    // ======================= 日志写入结束 ====================
    drupal_set_message('你于'. format_date(REQUEST_TIME, 'custom', 'Y-m-d H:i'). '接手处理该故障。预计' .$times. '分钟后处理完成。请留意时间');

  }

  /**
   * 问题转交
   *
   * @param form
   * @param $fome_state
   *
   */
  public function transferQuestion(array &$form, FormStateInterface $form_state){
    // 接收人
    $employee = $form_state->getValue('employee');
    // 转出理由
    $reason = $form_state->getValue('reason');

    if(!$reason) {
      drupal_set_message($this->t('请填写转出理由!.'),'error');
      return;
    }
    if(!$employee){
      drupal_set_message($this->t('请选择接受者 !'),'error');
      return;
    } elseif($employee == \Drupal::currentUser()->id()) {
      drupal_set_message($this->t('你疯了吧,自己转自己?!'),'error');
      return;
    } else {
      $record_arr = array(
        'to_uid' => $employee,
        'from_uid' => $this->entity->get('server_uid')->entity->id(),
        'from_stamp' => REQUEST_TIME,
        'question_id' => $this->entity->id(),
        'description' => $reason
        );

      $record_id = getQuestionService()->saveTransferRecord($record_arr);
      // ======================= 写入转出故障 的日志 ============================
      $entity = $this->entity;
      // 得到这一条转交记录
      $record_arr['flag'] = 'transfer_out';
      $entity->other_data = array('data_name' => 'server_question_convert', 'data' => $record_arr, 'data_id' => $record_id);
      HostLogFactory::OperationLog('question')->log($entity, 'question_convert');
      // ======================= 日志写入结束 ==================================
      drupal_set_message($this->t('The question has been transfer out . Please notify the recipient to reception !'));
    }
  }

  /**
   *根据选择的部门加载员工  ajax回调函数
   */
  public function loadPeople(array $form, FormStateInterface $form_state) {
    return $form['transfered']['dept']['dept_child']['employee'];
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = array();
    // 负责处理该问题的用户实体
    $server_user = $this->entity->get('server_uid')->entity;
    // 得到该问题的转交记录
    $record_is_accept = false;
    $records = getQuestionService()->getQuestionTransferRecordByQuestionId($this->entity->id());
    if(!empty($records)) {
      foreach($records as $record) {
        if($record->to_uid != \Drupal::currentUser()->id() && !$record->to_stamp){ // 问题已经被转出  不绘出回复按钮
          $record_is_accpet = true;
          break;
        }
      }
    }  
    if($this->entity->get('status')->value != 3 && $server_user && $server_user->id() == \Drupal::currentUser()->id() && !$record_is_accept) {
      $actions = parent::actions($form, $form_state);
      $actions['submit']['#value'] = $this->t('Submit');
    } else {
      $actions = array();
    }    
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   * 提交问题回复
   */
  public function save(array $form, FormStateInterface $form_state) {
    //得到当前实体
    $entity = $this->entity;
    //回复问题
    $content = $form_state->getValue('reply_content')['value'];
    $status = $form_state->getValue('question_status');
    if(!$status) {
      drupal_set_message($this->t('Please select status!'),'error');
      return '';
    }

    if(!$content){
      drupal_set_message($this->t('Please fill in your reply message !'),'error');
      return;
    } else {
      $reply_arr = array(
        'content' => $content,
        'creat' => REQUEST_TIME,
        'uid' => \Drupal::currentUser()->id(),
        'question_id' => $entity->id()
      );
      $reply_id = getQuestionService()->saveQuestionReply($reply_arr);
      //问题处理完成
      if($status == 3) {
        $entity->finish_stamp = REQUEST_TIME;
      }
      //设置故障的状态
      $entity->status = $status;
      $entity->save();

      // ======================= 写入回应故障 的日志 ============================
      $entity->other_data = array('data_name' => 'server_question_detail', 'data' => $reply_arr, 'data_id' => $reply_id);
      HostLogFactory::OperationLog('question')->log($entity, 'response');
      // ======================= 日志写入结束 ==================================
      drupal_set_message($this->t('The question has been transfer out . Please notify the recipient to reception !'));
      drupal_set_message($this->t('Your reply has been submitted successfully!'));
    }
  }
}
