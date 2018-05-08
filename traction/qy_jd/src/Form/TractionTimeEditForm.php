<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\TractionTimeEditForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * 修改牵引时间表单类
 */
class TractionTimeEditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_traction_time_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $traction_id = null) {
    $request = $this->getRequest();
    if(!$request->query->has('destination')) {
      drupal_set_message('非法操作');
      return $form;
    }
    $db_service= \Drupal::service('qy_jd.db_service');
    $qy_list = $db_service->load_qy(array('id' => $traction_id));
    if(empty($qy_list)) {
      drupal_set_message('非法操作');
      $url = $request->query->get('destination');
      return new RedirectResponse($url);
    }
    $qy = reset($qy_list);
    $form['traction_id'] = array(
      '#type' => 'value',
      '#value' => $traction_id
    );
    $form['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'Ip',
      '#disabled' => true,
      '#default_value' => $qy->ip
    );
    $form['time'] = array(
      '#type' => 'number',
      '#title' => '解封时间',
      '#required' => true,
      '#default_value' => $qy->time,
      '#field_suffix' => '分钟'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '修改'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db_service= \Drupal::service('qy_jd.db_service');
    $traction_id = $form_state->getValue('traction_id');
    $db_service->update_qy(array('time' => $form_state->getValue('time')), $traction_id);
    drupal_set_message('修改成功');
  }
}
