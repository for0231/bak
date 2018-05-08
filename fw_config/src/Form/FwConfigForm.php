<?php

/**
 * @file
 * Contains \Drupal\fw_config\Form\FwConfigForm.
 */

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 手动增加牵引表单类
 */
class FwConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_fw_cofnig_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('fw_config.settings');
    $form['write'] = array(
      '#type' => 'fieldset',
      '#title' => '写数据的防火墙',
    );
    $form['write']['write_fw'] = array(
      '#type' => 'textarea',
      '#placeholder' => '一行输入一个防火格式如：66.102.250.158:28099-用户名-密码-0/1',
      '#default_value' => $config->get('write_fws'),
    );
    $form['read'] = array(
      '#type' => 'fieldset',
      '#title' => '读数据的防火墙',
    );
    $form['read']['read_fw'] = array(
      '#type' => 'textfield',
      '#placeholder' => '格式如：66.102.250.158:28099-用户名-密码',
      '#default_value' => $config->get('read_fws'),
    );
    $form['readmore'] = array(
        '#type' => 'fieldset',
        '#title' => '读数据多个的防火墙',
    );
    $form['readmore']['readmore_fw'] = array(
        '#type' => 'textarea',
        '#placeholder' => '一行输入一个防火格式如：66.102.250.158:28099-用户名-密码',
        '#default_value' => $config->get('readmore_fws'),
    );
    $form['action'] = array(
      '#type' => 'actions',
      'submit' => array(
        '#type' => 'submit',
        '#value' => '保存'
      )
    );
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $write_fw = trim($form_state->getValue('write_fw'));
    $write_fws = explode("\r\n", $write_fw);
    foreach($write_fws as $item) {
      $arr = explode('-', $item);
      if(count($arr) != 4) {
        $form_state->setErrorByName('write_fw', '写数据防火墙格式错误');
      }
    }    
    $read_fw = trim($form_state->getValue('read_fw'));
    $read_fws = explode("-", $read_fw);
    if(count($read_fws) != 3) {
      $form_state->setErrorByName('read_fw', '读数据防火墙格式错误');
    }
    $readmore_fw = trim($form_state->getValue('readmore_fw'));
    $readmore_fws = explode("\r\n", $readmore_fw);
    foreach($readmore_fws as $item) {
      $arr = explode('-', $item);
      if(count($arr) != 3) {
        $form_state->setErrorByName('readmore_fw', '写数据防火墙格式错误');
      }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('fw_config.settings');
    $write_fw = trim($form_state->getValue('write_fw'));
    $read_fw = trim($form_state->getValue('read_fw'));
    $readmore_fw = trim($form_state->getValue('readmore_fw'));
    $config->set('read_fws', $read_fw);
    $config->set('write_fws', $write_fw);
    $config->set('readmore_fws', $readmore_fw);
    $config->save();
    drupal_set_message('保存成功');
  }
}
