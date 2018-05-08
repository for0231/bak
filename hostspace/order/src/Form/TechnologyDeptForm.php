<?php

/**
 * @file
 * Contains \Drupal\order\Form\TechnologyDeptForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;


class TechnologyDeptForm extends FormBase {

  protected $hostclient_service;

  public function __construct() {
    $this->hostclient_service = \Drupal::service('hostclient.serverservice');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hostclient_technology_dept_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $handle_id = null) {
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    if($handle_info->busi_status == 0) { //判断业务部是否处理了。
      return $this->redirect('admin.hostclient.untreated');
    }
    $entity = entity_load('hostclient', $handle_info->hostclient_id);
    $tech_uid = $handle_info->tech_uid;
    $handle_status = $handle_info->tech_status;

    $form['handle_id'] = array(
      '#type' => 'value',
      '#value' => $handle_id
    );
    $form['hostclient_id'] = array(
      '#type' => 'value',
      '#value' => $handle_info->hostclient_id
    );
    //受理块
    $form['accept_group'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#weight' => 1,
    );
    if($tech_uid) {
      $form['accept_group']['#title'] = $this->t('The Department for processing specialist'); //该部门处理专员
      $tech_user = entity_load('user', $tech_uid);
      $form['accept_group']['confirm_msg'] = array(
        '#type' => 'label',
        '#title' => $this->t('%name in the %time took over the handling of the server.', array(
          '%name' => $tech_user->label(), 
          '%time' => format_date($handle_info->tech_accept_data, 'custom' ,'Y-m-d H:i:s')
        ))
      );
      if($handle_status == 0 && ($this->currentUser()->id() == 1 || $this->currentUser()->id() == $tech_uid)) {
        $form['accept_group']['change'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('container-inline')
          )
        );
        $all_employee = \Drupal::service('member.memberservice')->getAllEmployee();
        $options = array('' => t('select'));
        foreach($all_employee as $employee) {
          if($employee->uid != $tech_uid) {
            $options[$employee->uid] = $employee->employee_name;
          }
        }
        $form['accept_group']['change']['change_People'] = array(
          '#type' => 'select',
          '#title' => $this->t('Change the responsible personnel'),
          '#options' => $options
        );
        $form['accept_group']['change']['change_confirm'] = array(
          '#type' => 'submit',
          '#value' => $this->t('Confirm'),
          '#limit_validation_errors' => array(array('change_People'), array('handle_id')),
          '#submit' => array('::changeSubmitForm'),
        );
      }
    } else {
      $form['accept_group']['#title'] = $this->t('The department has not yet processed!'); //该部门未处理
      $form['accept_group']['confirm_msg'] = array(
        '#type' => 'label',
        '#title' => t('Are you accept to handle the server ?') //您要接受负责该服务器的处理吗
      );
      $form['accept_group']['accept_submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Accept'),
        '#submit' => array('::acceptSubmitForm')
      );
    }
    //基本信息
    $form['base_info'] = array(
      '#type' => 'details',
      '#title' => $this->t('Base info'),
      '#open' => true,
      '#weight' => 5
    );
    $form['base_info']['client'] = array(
      '#theme' => 'admin_order_client',
      '#client_obj' => $entity->getObject('client_uid'),
    );
    $form['base_info']['hostclient_info'] = array(
      '#theme' => 'admin_hostclient_info',
      '#handle_info' => $handle_info
    );

    //部门编辑辑
    $disabled = true;
    if($handle_status == 0 && $this->currentUser()->id() == $tech_uid) {
      $disabled = false;
    }
    $form['edit_info'] = array(
      '#type' => 'details',
      '#title' => t('Technical Department'),
      '#open' => true,
      '#weight' => 10,
      '#disabled' => $disabled
    );

    $form['edit_info']['init_pwd'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server initial password'),
      '#required' => true,
      '#default_value' => $entity->getSimpleValue('init_pwd')
    );

    $sys_options = array('' => t('Select'));
    $sys_terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('server_system');
    foreach($sys_terms as $sys_term) {
      $sys_options[$sys_term->tid] = $sys_term->name;
    }
    $form['edit_info']['server_system'] = array(
      '#type' => 'select',
      '#title' => $this->t('System'),
      '#required' => true,
      '#options' => $sys_options,
      '#default_value' => $entity->getObjectId('server_system')
    );

    $form['edit_info']['server_mask'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subnet mask'),
      '#default_value' => $entity->getSimpleValue('server_mask')
    );

    $form['edit_info']['server_gateway'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Gateway'),
      '#default_value' => $entity->getSimpleValue('server_gateway')
    );

    $form['edit_info']['server_dns'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('DNS'),
      '#default_value' => $entity->getSimpleValue('server_dns')
    );

    $form['edit_info']['server_manage_card'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Management Card'),
      '#default_value' => $entity->getSimpleValue('server_manage_card')
    );
    $form['edit_info']['handle_description'] = array(
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $handle_info->tech_description
    );

    //是否处理完成
    $form['edit_info']['complete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Processing is complete'),
      '#default_value' => $handle_status
    );

    $form['edit_info']['check_handle'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Check server'),
      '#options' => $this->checkOptions()
    );
    if($handle_info->tech_check_item) {
      $form['edit_info']['check_handle']['#default_value'] = (Array)json_decode($handle_info->tech_check_item);
    }
    $form['edit_info']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#validate' => array('::submitValidateForm')
    );
    if(!$disabled) {
      $form['edit_info']['rollback'] = array(
        '#type' => 'link',
        '#title' => $this->t('Rollback'),
        '#url' => new Url('admin.hostclient.technology.rollback', array('handle_id' => $handle_info->hid)),
        '#attributes' => array(
          'class' => array('button', 'button--danger')
        )
      );
    }
    return $form;
  }

  /**
   * 检验项
   */
  private function checkOptions() {
    return array(
      'remote' => t('whether it can remote'),
      'pwd' => t('The password is correct or not'),
      'ip' => t('whether IP is normal'),
      'config' => t('Configuration verification'),
      'port' =>  t('Port verification')
    );
  }

  /**
   * 技术部员工接受处理服务器的表单提交
   *
   */
  public function acceptSubmitForm(array $form, FormStateInterface $form_state) {
    $handle_id = $form_state->getValue('handle_id');
    $handle_info['tech_uid'] = $this->currentUser()->id();
    $handle_info['tech_accept_data'] = REQUEST_TIME;
    $this->hostclient_service->updateHandleInfo($handle_info, $handle_id);
    //----------写日志---------
    $handle_info_log = $this->hostclient_service->loadHandleInfo($handle_id);
    $entity = entity_load('hostclient', $handle_info_log->hostclient_id);
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
    $entity->other_status = 'tech_dept_accept';
    HostLogFactory::OperationLog('order')->log($entity, 'update');
  }

  /**
   * 变更负责人提交
   */
  public function changeSubmitForm(array $form, FormStateInterface $form_state) {
    $change_People = $form_state->getValue('change_People');
    if($change_People) {
      $handle_id = $form_state->getValue('handle_id');
      $handle_info['tech_uid'] = $change_People;
      //$handle_info['tech_accept_data'] = REQUEST_TIME;
      $this->hostclient_service->updateHandleInfo($handle_info, $handle_id);
      //-------写日志-------
      $handle_info_log = $this->hostclient_service->loadHandleInfo($handle_id);
      $entity = entity_load('hostclient', $handle_info_log->hostclient_id);
      $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
      $entity->other_status = 'tech_dept_move';
      HostLogFactory::OperationLog('order')->log($entity, 'update');
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitValidateForm(array &$form, FormStateInterface $form_state) {
    $complete = $form_state->getValue('complete');
    $check_handle = $form_state->getValue('check_handle');
     if($complete) {
      foreach($check_handle as $key=>$value) {
        if(!$value) {
          $form_state->setErrorByName('check_handle['. $key .']', $this->t('%item no checked', array('%item' => $this->checkOptions()[$key])));
        }
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $handle_id = $form_state->getValue('handle_id');
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $hostclient_id = $form_state->getValue('hostclient_id');
    $entity = entity_load('hostclient', $hostclient_id);

    $check_handle = $form_state->getValue('check_handle');
    $jsonencode = json_encode($check_handle);
    $description = $form_state->getValue('handle_description');
    $new_handle_info['tech_check_item'] = $jsonencode;
    $new_handle_info['tech_description'] = $description;
    //保存hostclient
    $complete = $form_state->getValue('complete');
    if($complete) {
      $time = REQUEST_TIME;
      $entity->set('equipment_date', $time);
      if($entity->getSimplevalue('trial')) {
        $config = \Drupal::config('common.global');
        $trial_time = $config->get('server_trial_time');
        if(empty($trial_time)) {
          $trial_time = 24;
        }
        $entity->set('status', 3);
        $entity->set('service_expired_date', strtotime('+'. $trial_time .' hour', $time));
      } else {
        $product_service = \Drupal::service('order.product');
        if($handle_info->handle_action == 1) {
          $entity->set('status', 3);
          //计算结束时间
          $order_product = $product_service->getProductById($handle_info->handle_order_product_id);
          $entity->set('service_expired_date', strtotime('+'. $order_product->product_limit .' month', $time));
          //保存配件到server
          $product_business_list = $product_service->getOrderBusiness($handle_info->handle_order_product_id);
          $this->hostclient_service->saveServerPartHire($entity, $product_business_list);
        } else if ($handle_info->handle_action == 3) {
          //保存配件到server
          $product_business_list = $product_service->getOrderBusiness($handle_info->handle_order_product_id);
          $this->hostclient_service->saveServerPartUpgrade($entity, $product_business_list);
        }
        $entity->set('unpaid_order', 0);
      }
      $new_handle_info['tech_complete_data'] = $time;
      $new_handle_info['tech_status'] = 1;
      //------日志handle信息--------
      $handle_info->tech_complete_data = $new_handle_info['tech_complete_data'];
      $handle_info->tech_status = $new_handle_info['tech_status'];

      drupal_set_message($this->t('Finished processing the technology department'));
      $form_state->setRedirectUrl(new Url('admin.hostclient.technology.list'));
    }

    $entity->set('init_pwd', $form_state->getValue('init_pwd'));
    $entity->set('server_system', $form_state->getValue('server_system'));
    $entity->set('server_mask', $form_state->getValue('server_mask'));
    $entity->set('server_gateway', $form_state->getValue('server_gateway'));
    $entity->set('server_dns', $form_state->getValue('server_dns'));
    $entity->set('server_manage_card', $form_state->getValue('server_manage_card'));
    $entity->save();

    //保存处理信息
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
    //判断订单是否处理完成，并修改状态
    if($complete && !$entity->getSimplevalue('trial')) {
      $order_id = $handle_info->handle_order_id;
      $all_complete = $this->hostclient_service->checkHandleStatus($order_id);
      if($all_complete) {
        $order = entity_load('order', $order_id);
        $order->set('status', 5);
        $order->save();
      }
    }

    //----------写日志---------
    $handle_info->tech_check_item = $new_handle_info['tech_check_item'];
    $handle_info->tech_description = $new_handle_info['tech_description'];
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info);
    $entity->other_status = 'tech_dept_handle';
    HostLogFactory::OperationLog('order')->log($entity, 'update');
  }
}
