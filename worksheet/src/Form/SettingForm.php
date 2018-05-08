<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\SettingForm.
 */
namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class SettingForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'worksheet_setting_form';
  }
  
  private function buildRow($type) {
    return array(
      array(
        '#markup' => $type->class_name,
      ),
      array(
        '#markup' => $type->operation_name,
      ),
      'complete_time' => array(
        '#type' => 'number',
        '#min' => 0,
        '#default_value' => $type->complete_time
      ),
      'workload' => array(
        '#type' => 'number',
        '#min' => 0,
        '#step' => 0.01,
        '#default_value' => $type->workload
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('worksheet.settings');
    $form['overall']  = array(
      '#type' => 'details',
      '#title' => '超时时间设置',
      '#open' => true
    );
    $form['overall']['accept_time'] = array(
      '#type' => 'number',
      '#title' => '接单时间',
      '#default_value' => $config->get('accept_time'),
      '#required' => true,
      '#min' => 1,
      '#description' => '新创建的工单，必须在此时间内接受订单，否则记录异常'
    );
    $form['overall']['transfer_time'] = array(
      '#type' => 'number',
      '#title' => '转接时间',
      '#default_value' => $config->get('transfer_time'),
      '#required' => true,
      '#min' => 1,
      '#description' => '运维转交出来的工单，必须在此时间内接受订单，否则记录异常'
    );
    $form['content_time'] = array(
      '#type' => 'details',
      '#title' => '机房工作内容时间设置',
      '#open' => true
    );
    $service = \Drupal::service('worksheet.option');
    $contents = $service->getJobContent();
    $form['content_value'] = array(
      '#type' => 'value',
      '#value' => $contents
    );
    $content_time = $config->get('room_content_time');
    foreach($contents as $key => $content) {
      $form['content_time']['content_' . $key] = array(
        '#type' => 'number',
        '#title' => $content,
        '#default_value' => $content_time[$key]
      );
    }
    $typeService = \Drupal::service('worksheet.type');
    $types = $typeService->getTypeDate();
    $form['list'] = array(
      '#type' => 'details',
      '#title' => '类型时间及工作量设置',
      '#open' => false
    );
    $form['list']['type_setting'] = array(
      '#type' => 'table',
      '#header' => array('类型', '操作类型', '所需时间', '工作量'),
      '#rows' => array()
    );
    foreach($types as $type) {
      $row = $this->buildRow($type);
      $tid = $type->tid;
      if($tid>=160 && $tid<=169) { //机房事务分类不需求设置时间
        $row['complete_time']['#disabled'] = true;
      }
      $form['list']['type_setting'][$type->tid] = $row;
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $accept_time = $form_state->getValue('accept_time');
    $transfer_time = $form_state->getValue('transfer_time');
    $config = \Drupal::configFactory()->getEditable('worksheet.settings');
    $config->set('accept_time', $accept_time);
    $config->set('transfer_time', $transfer_time);
    //设置机房
    $value = array();
    $contents = $form_state->getValue('content_value');
    foreach($contents as $key =>$contnet) {
      $content_time = $form_state->getValue('content_' . $key);
      if($content_time) {
        $value[$key] = $content_time;
      }
      else {
        $value[$key] = 0;
      }
    }
    $config->set('room_content_time', $value);
    $config->save();
    //保存列表
    $typeService = \Drupal::service('worksheet.type');
    $types = $form_state->getValue('type_setting');
    foreach($types as $tid => $type) {
      $typeService->update($type, $tid);
    }
    drupal_set_message('保存成功');
  }
}
