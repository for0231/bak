<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\TaskAssignerForm.
 */
namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class TaskAssignerForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'worksheet_task_assigner_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = \Drupal::entityManager()->getStorage('user');
    $config = \Drupal::config('worksheet.settings');
    $name = '无';
    if($assigner = $config->get('task_assigner')) {
      $user = $storage->load($assigner);
      if($user) {
        $name = $user->label();
      }
    }
    $form['distribution_person'] = array(
      '#type' => 'details',
      '#title' => '工单分配人申请',
      '#open' => true
    );
    $form['distribution_person']['info'] = array(
      '#markup' => '当前工单的分配人为['. $name .'],你确定要替换他成为分配人？'
    );
    $form['distribution_person']['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => '确定替换'
      )
    );
    //值班人员设置
    $form['person_on_duty'] = array(
      '#type' => 'details',
      '#title' => '值班人员设置',
      '#open' => true,
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $form['person_on_duty']['add_person'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#selection_handler' => 'username',
      '#maxlength' => 1024,
    );
    $form['person_on_duty']['duty_submit'] = array(
      '#type' => 'submit',
      '#value' => '增加值班人员',
      '#submit' => array('::personDutySubmit')
    );
    $form['person_on_duty']['list'] = array(
      '#type' => 'table',
      '#header' => array('姓名', '操作'),
      '#rows' => array(),
    );
    $person_duty = $config->get('person_on_duty');
    if(!empty($person_duty)){
      foreach($person_duty as $uid) {
        $user = $storage->load($uid);
        $form['person_on_duty']['list']['#rows'][] = array(
          'user' => $user->label(),
          'op' => array(
            'data' => array(
              '#type' => 'link',
              '#title' => '删除',
              '#url' => new Url('admin.worksheet.sop.person.duty', array('uid' => $uid, 'op' => 'delete', 'back' => 1))
            )
          )
        );
      }
    }
    //工单分配时间段设置
    $form['allot_time'] = array(
      '#type' => 'details',
      '#title' => '分配时间段设置',
      '#open' => true,
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $form['allot_time']['time'] = array(
      '#type' => 'textfield',
      '#title' => '时间段',
      '#default_value' =>'',
      '#min' => 1,
      '#placeholder' => '分配时间段设置格式为：09:00:00-10:00:00',
    );
    $form['allot_time']['time_coefficient'] = array(
      '#type' => 'number',
      '#title' => '时间段系数',
      '#default_value' =>'',
      '#step'=> 0.1
    );
    $form['allot_time']['submit'] = array(
      '#type' => 'submit',
      '#value' => '添加设置',
      '#submit' => array('::allotTimeSubmit')
    );
    $form['allot_time']['list'] = array(
      '#type' => 'table',
      '#header' => array('时间段','时间系数', '操作'),
      '#rows' => array(),
    );
    $allocate_time = $config->get('allocate_time');
    if(!empty($allocate_time)){
      foreach($allocate_time as $item) {
        $form['allot_time']['list']['#rows'][] = array(
          'time' =>$item[0],
          'time_coefficient' =>$item[1],
          'op' => array(
            'data' => array(
              '#type' => 'link',
              '#title' => '删除',
              '#url' => new Url('admin.worksheet.sop.allo', array('time' => $item[0],'back' => 1))
            )
          )
        );
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $op = $form_state->getValue('op');
    if($op=='添加设置'){
      $time = $form_state->getValue('time');
      if(strstr($time,"-")){
        $strarray = explode('-',$time);
        $first =  strtotime($strarray[0]);
        $sencod = strtotime($strarray[1]);
        if(!$first && !$sencod){
          $form_state->setErrorByName('time','时间格式设置不正确');
        }
      }else{
        $form_state->setErrorByName('time','时间格式设置不正确');
      }
    }
    
    
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('worksheet.settings');
    $config->set('task_assigner', \Drupal::currentUser()->id());
    $config->save();
    $nowuid = \Drupal::currentUser()->id();
    $fields= array(
      'allot_uid'=> \Drupal::currentUser()->id(),
      'created'=>strtotime(date("Y-m-d H:i:s",intval(time()))),
    );
    $updaterow = \Drupal::service('worksheet.dbservice')->update_allot();
    $addrow = \Drupal::service('worksheet.dbservice')->add_allot($fields);
    if($addrow && $updaterow){
      drupal_set_message('替换成功');
    }
  }
  /**
   * 值班人员设置提交
   */
  public function personDutySubmit(array &$form, FormStateInterface $form_state) {
    $add_person = $form_state->getValue('add_person');
    if($add_person) {
      $config = \Drupal::configFactory()->getEditable('worksheet.settings');
      $person_on_duty = $config->get('person_on_duty');
      $person_on_duty[] = $form_state->getValue('add_person');
      $return = array_unique($person_on_duty);
      $config->set('person_on_duty', $return);
      $config->save();
    }
  }
   /**
    * 工单分配时间段设置提交
    */
  public function allotTimeSubmit(array &$form, FormStateInterface $form_state) {
    $time = $form_state->getValue('time');
    $time_coefficient = $form_state->getValue('time_coefficient');
    if($time && $time_coefficient) {
      $config = \Drupal::configFactory()->getEditable('worksheet.settings');
      $allocate_time = $config->get('allocate_time');
      $allocate_time[] = array($time,$time_coefficient);
      $config->set('allocate_time', $allocate_time);
      $config->save();
    }
  }
}
