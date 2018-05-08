<?php

/**
 * @file
 * Contains \Drupal\question\user\ClientQuestionDetailForm.
 */

namespace Drupal\question\user;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
/**
 * Provide a form controller for question category add.
 */

class ClientQuestionDetailForm extends ContentEntityForm {
    
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // 判断要访问的数据是否术语访问者
    if($this->entity->get('uid')->entity->id() != \Drupal::currentUser()->id()) {
      drupal_set_message('您访问的数据不存在！', 'warning');
      return array();
    }    
    $form = parent::form($form, $form_state);
    unset($form['content']);
    //问题处理详情
    $this->drawQuestionDealDetailForm($form, $form_state);
    //对问题有疑虑 继续反馈  --当问题还未处理完成时才绘制表单
    if($this->entity->get('status')->value != 3) {
      $this->drawReplyQuestionForm($form, $form_state);
    }
    return $form;
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

    $client_name = '';
    if($client) {
      $name = $client->client_name ? $client->client_name : $this->entity->get('uid')->entity->getUsername();
      $client_name = '['. $client->corporate_name . '] -> '. $name;
    } else {
      $client_name =   $this->entity->get('uid')->entity->getUsername();
    }
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
    $flag = $reply->flags;
    $user_obj = null;
    $dept_name = '';
    $user_name = '';
    if($flag == 1) {
      $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client',$reply->uid);
      $dept_name = $user_obj ? $user_obj->corporate_name :'Client';
      if($user_obj) {
        $user_name =  $user_obj->client_name ? $user_obj->client_name: $this->entity->get('uid')->entity->getUsername();
      } else {
        $user_name =  $this->entity->get('uid')->entity->getUsername();
      }
    } else{
      $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$this->entity->get('server_uid')->entity->id());
      $dept_name = $user_obj ? entity_load('taxonomy_term', $user_obj->department)->label() : 'System';
      if($user_obj) {
        $user_name = $user_obj->employee_name ? $user_obj->employee_name : $this->entity->get('uid')->entity->getUsername();
      } else {
        $user_name =  $this->entity->get('uid')->entity->getUsername();
      }
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
  /*  //判断是否超时  实际消耗的时间大于期望时间  则超时
    $status_str ='';
    if($ecxept_time < $real_time ) {
      $status_str .= ' / <a  style="color:red">'.t('Time out : '). ($real_time - $ecxept_time) .' min</a>';
    }*/
    
    $header = array(
      'deals_status' => array(
        'data' =>array(
          array(
            '#type' => 'container',
            '#markup' => t('Type of question:').$this->entity->get('parent_question_class')->entity->label(),
          ),
          array(
            '#type' => 'container',
            '#markup' => t('Status:') . $status,
          )
        ),
      ),
      'type_question ' => array(
        'data' =>array(
          array(
            '#type' => 'container',
            '#markup' => ('IP of server:'). SafeMarkup::format("<br/>".str_replace("\r\n","<br/>",$this->entity->get('ipstring')->value), array()),
          )
        ),
      ),
       
      'stamp' => array(
        'data' => array(
           array(
            '#type' => 'container',
            '#markup' => t('Declare time：').date('Y-m-d H:i',$this->entity->get('created')->value), 
            '#attributes' => array(
              'class' => array('time')
            )
          ),
        /*  array(
            '#type' => 'container',
            '#markup' => $this->entity->get('pre_finish_stamp')->value ? t('Expected completion time:').date('Y-m-d H:i',$this->entity->get('pre_finish_stamp')->value) : '',
            '#attributes' => array(
              'class' => array('time')
            )
          ),*/
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
      '#title' => '仍有疑虑 ? 继续反馈'
    );
      
    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = array();
    if($this->entity->get('status')->value != 3 && $this->entity->get('uid')->entity->id() == \Drupal::currentUser()->id()) {
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
    
    if(!$content){
      drupal_set_message($this->t('Please fill in your reply!'),'error');
    } else {
      getQuestionService()->saveQuestionReply(array(
          'content' => $content,
          'creat' => REQUEST_TIME,
          'uid' => \Drupal::currentUser()->id(),
          'question_id' => $entity->id(),
          'flags' => 1
        )
      );
      drupal_set_message($this->t('Your Feedback has been submitted successfully.We will deal with as soon as possible!'));
    }
  }
}
