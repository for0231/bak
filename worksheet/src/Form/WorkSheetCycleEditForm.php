<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetCycleAdd.php
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WorkSheetCycleEditForm extends FormBase {

  protected $cycle_key = 'work_sheet_cycle_exec';

  public function getFormId() {
    return 'worksheet_cycleedit_form';
  }

  public function getHours(){
    $hours = array();
    for($i=0;$i<24;++$i){
      $hours[$i] = $i;
    }
    return $hours;
  }

  public function getMinOrSecond(){
    $ms = array();
    for($i=1;$i<60;++$i){
      $ms[$i] = $i;
    }
    return $ms;
  }


  public function buildForm(array $form, FormStateInterface $form_state, $work_sheet_cycle_key = 0) {
    $state = \Drupal::state();
    $data = $state->get($this->cycle_key)[$work_sheet_cycle_key];
    $form['type'] = array(
      '#type' => 'select',
      '#title' => '所属分类',
      '#options' => \Drupal::service('worksheet.type')->getCycleType(),
      '#default_value' => $data['type']
    );
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => '名称',
      '#default_value' => $data['name'],
    );
    $form['switch'] = array(
      '#type' => 'select',
      '#title' => '开／关',
      '#options' => array(1=>'开',2=>'关'),
      '#default_value' => $data['switch'] ? 1 : 2
    );
    $form['content'] = array(
      '#type' => 'textarea',
      '#title' => '内容',
      '#default_value' => $data['content']
    );
    $form['exec_hours'] = array(
      '#type' => 'select',
      '#title' => '执行小时',
      '#options' => $this->getHours(),
      '#default_value' => $data['hours']
    );
    $form['exec_min'] =  array(
      '#type' => 'select',
      '#title' => '执行分钟',
      '#options' => $this->getMinOrSecond(),
      '#default_value' => $data['min']
    );
    $form['client'] =  array(
      '#type' => 'textfield',
      '#title' => '公司名称',
      '#default_value' => $data['client']
    );
    $form['key'] =  array(
      '#type' => 'value',
      '#value' => $work_sheet_cycle_key,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存修改'
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $key = $form_state->getValue('key');
    $data = $state->get($this->cycle_key);
    $hours =  intval($form_state->getValue('exec_hours'));
    $min = intval($form_state->getValue('exec_min'));
    $content = $form_state->getValue('content');
    $type = $form_state->getValue('type');
    $name = $form_state->getValue('name');
    $client = $form_state->getValue('client');
    $switch = $form_state->getValue('switch');
    $insert = array(
      'hours' => $hours,
      'min' => $min,
      'content' => $content,
      'type' => $type,
      'name' => $name,
      'key' => intval($key),
      'client' => $client,
      'switch' => ($switch == 1 ) ? true : false
    );
    $data[$key] = $insert;
    $state->set($this->cycle_key,$data);
    drupal_set_message('修改成功');
    $form_state->setRedirectUrl(new Url('admin.worksheet.cycle.add'));
  }

}
