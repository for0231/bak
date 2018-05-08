<?php

/**
 * @file
 * Contains \Drupal\qy\Form\SyncDataForm.
 */

namespace Drupal\qy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\Client;

/**
 * 同步数据
 */
class SyncDataForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_sync_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = \Drupal::service('settings');
    $data_to = $settings->get('sync_data_to');
    if(!empty($data_to)) {
      $form['tip'] = array(
        '#markup' => '你确定要把数据更新到[' . $data_to . ']上去。'
      );
      $form['sync_user'] = array(
        '#type' => 'textfield',
        '#title' => '用户名',
        '#description' => '请输入'. $data_to .'网站的用户名',
      );
      $form['sync_password'] = array(
        '#type' => 'password',
        '#title' => '密码',
        '#description' => '请输入'. $data_to .'网站的密码',
      );
      $form['actions'] = array(
        '#type' => 'actions',
        'submit' => array(
          '#type' => 'submit',
          '#value' => '确定更新',
          '#attributes' => array(
            'sync-url' => 'http://' . $data_to . '/admin/qy/sync/data/exec'
          )
        )
      );
      $form['#attached'] = array(
        'library' => array('qy/drupal.sync-data')
      );
    } else {
      $form['tip'] = array(
        '#markup' => '没有配置同步的更新地址。'
      );
    }
    return $form;
  }

 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}

