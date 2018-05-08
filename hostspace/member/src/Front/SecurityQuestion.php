<?php
/**
 * @file
 * Contains \Drupal\member\Front\SecurityQuestion.
 */

namespace Drupal\member\Front;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormBuilder;

class SecurityQuestion extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'security_question';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $uid = \Drupal::currentUser()->id();
    $obj = \Drupal::service('member.memberservice')->queryDataFromDB(entity_load('user',$uid)->get('user_type')->value, $uid);
    if(!isset($obj->safe_question)) {
      return $form;
   }
    $form['question_1'] = array(
      '#type' => 'select',
      '#title' => t('Question 1'),
      '#options' => array('' => 'SELECt') + securityQuestion_1() , 
      '#default_value' => $obj->safe_question,
      '#required' => TRUE
    );
    $form['ans_1'] = array(
      '#type' => 'textfield',
      '#title' => t('Answer'),
      '#default_value' => $obj->safe_answer,
      '#required' => TRUE
   
    );
    $form['question_2'] = array(
      '#type' => 'select',
      '#title' => t('Question 2'),
      '#options' => array('' => 'SELECt') + securityQuestion_2() , 
      '#default_value' => $obj->safe_question_1,
      '#required' => TRUE
    );
    $form['ans_2'] = array(
      '#type' => 'textfield',
      '#title' => t('Answer'),
      '#default_value' => $obj->safe_answer_1,
      '#required' => TRUE
    );
    $form['question_3'] = array(
      '#type' => 'select',
      '#title' => t('Question 3'),
      '#options' => array('' => 'SELECt') + securityQuestion_3() , 
      '#default_value' => $obj->safe_question_2,
      '#required' => TRUE
    );
    $form['ans_3'] = array(
      '#type' => 'textfield',
      '#title' => t('Answer'),
      '#default_value' => $obj->safe_answer_2,
      '#required' => TRUE
    );
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => 'Save'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $q1 = $form_state->getValue('question_1');
    $q2 = $form_state->getValue('question_2');
    $q3 = $form_state->getValue('question_3');
    $ans_1 = $form_state->getValue('ans_1');
    $ans_2 = $form_state->getValue('ans_2');
    $ans_3 = $form_state->getValue('ans_3');

    $field_arr = array(
      'safe_question' => $q1,
      'safe_answer' => $ans_1,
      'safe_question_1' => $q2,
      'safe_answer_1' => $ans_2,
      'safe_question_2' => $q3,
      'safe_answer_2' => $ans_3,
    );
    $uid = \Drupal::currentUser()->id();
    //调用server类中的方法，存储会员信息到user_client_data 表中 
    $effect = \Drupal::service('member.memberservice')->updateUserInfo($uid,$field_arr,'client');
    if($effect) {
      drupal_set_message(t('Security question successfully set!'));
    } else {
      drupal_set_message('设置失败，请联系管理员！');
    }
    $form_state->setRedirectUrl(new Url('member.my.info'));
  }

}
