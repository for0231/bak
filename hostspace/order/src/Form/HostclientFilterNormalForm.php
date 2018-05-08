<?php

/**
 * @file
 * Contains \Drupal\order\Form\HostclientFilterNormalForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class HostclientFilterNormalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'hostclient_filter_normal_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter server messages'),
      '#open' => !empty($_SESSION['hostclient_filter_normal']),
    );

    $form['filters']['ipb'] = array(
      '#type' => 'textfield',
      '#title' => t('Business ip'),
      '#size' => 20
    );
    $form['filters']['ipm'] = array(
      '#type' => 'textfield',
      '#title' => t('Management ip'),
      '#size' => 20
    );

    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => t('Status'),
      '#options' => array('-1' => 'All') + hostClientStatus()
    );
    
    // 有正常在线使用服务器的客户
    $clients = \Drupal::service('hostclient.serverservice')->getServerUser(3);
    $client_arr = array('' => 'All');
    $company_arr = array('' => 'All');

    foreach($clients as $client) {
      if(empty($client->client_name)) {
        $client_arr[$client->uid] = $client->name;
      } else {
        $client_arr[$client->uid] = $client->client_name;
      }
      
      if(empty($client->corporate_name)) {
        $company_arr[$client->uid] = $client->name;
      } else {
        $company_arr[$client->uid] = $client->corporate_name;
      }
    }
    $form['filters']['client_uid'] = array(
      '#type' => 'select',
      '#title' => t('Client'),
      '#options' => $client_arr
    );
    $form['filters']['corporate_name'] = array(
      '#type' => 'select',
      '#title' => t('Company'),
      '#options' => $company_arr
    );
     
    $fields = array('ipb', 'ipm', 'status', 'client_uid', 'corporate_name');
    $allempty = true;
    foreach ($fields as $field) {
      if($field == 'status') {
        if(!isset($_SESSION['hostclient_filter_normal'][$field])) {
          $_SESSION['hostclient_filter_normal'][$field] =-1;
        }
        if($_SESSION['hostclient_filter_normal'][$field] != -1) {
          $form['filters'][$field]['#default_value'] = $_SESSION['hostclient_filter_normal'][$field];
          $allempty = false;
        }
      } else{
        if(!empty($_SESSION['hostclient_filter_normal'][$field])) {
          $form['filters'][$field]['#default_value'] = $_SESSION['hostclient_filter_normal'][$field];
          $allempty = false;
        }
      }
    }
    if($allempty) {
      $_SESSION['hostclient_filter_normal'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['hostclient_filter_normal'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['hostclient_filter_normal']['ipb'] = $form_state->getValue('ipb');
    $_SESSION['hostclient_filter_normal']['ipm'] = $form_state->getValue('ipm');
    $_SESSION['hostclient_filter_normal']['status'] = $form_state->getValue('status');
    $_SESSION['hostclient_filter_normal']['client_uid'] = $form_state->getValue('client_uid');
    $_SESSION['hostclient_filter_normal']['corporate_name'] = $form_state->getValue('corporate_name');    
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['hostclient_filter_normal'] = array();
  }


}
