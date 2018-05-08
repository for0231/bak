<?php
namespace Drupal\online\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


/**
 *客服提交了答案后显示提问者的回复
 */

class HomeRealyForm extends FormBase {
  
  public function getFormId() {
    return 'online_test_form';
  }
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ask_content'] = array(
      '#type' => 'textarea',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '发送'
    );
    return $form;
  }
  
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    error_log('abc', 3, 'D:\log.txt');
  }
}
