<?php

/**
 * @file
 * Contains \Drupal\order\Form\BusinessDeptForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\order\ServerDistribution;
use Drupal\hostlog\HostLogFactory;

class BusinessDeptForm extends FormBase {

  protected $hostclient_service;

  public function __construct() {
    $this->hostclient_service = \Drupal::service('hostclient.serverservice');
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hostclient_business_dept_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $handle_id = null) {
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $entity = entity_load('hostclient', $handle_info->hostclient_id);
    $busi_uid = $handle_info->busi_uid;
    $handle_status = $handle_info->busi_status;

    $form['handle_id'] = array(
      '#type' => 'value',
      '#value' => $handle_id
    );
    $form['hostclient_id'] = array(
      '#type' => 'value',
      '#value' => $handle_info->hostclient_id
    );
    //接受块
    $form['accept_group'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#weight' => 1,
    );
    if($busi_uid) {
      $form['accept_group']['#title'] = $this->t('The Department for processing specialist'); //该部门处理专员
      $busi_user = entity_load('user', $busi_uid);
      $form['accept_group']['confirm_msg'] = array(
        '#type' => 'label',
        '#title' => $this->t('%name in the %time took over the handling of the server.', array(
          '%name' => $busi_user->label(),
          '%time' => format_date($handle_info->busi_accept_data, 'custom' ,'Y-m-d H:i:s')
        ))
      );
      if($handle_status == 0 && ($this->currentUser()->id() == 1 || $this->currentUser()->id() == $busi_uid)) {
        $form['accept_group']['change'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('container-inline')
          )
        );
        $all_employee = \Drupal::service('member.memberservice')->getAllEmployee();
        $options = array('' => t('Select'));
        foreach($all_employee as $employee) {
          if($employee->uid != $busi_uid) {
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
      $form['accept_group']['#title'] = $this->t('The Department did not handle'); //该部门未处理
      $form['accept_group']['confirm_msg'] = array(
        '#type' => 'label',
        '#title' => t('You have to accept the server responsible for handling it?') //您要接受负责该服务器的处理吗
      );
      $form['accept_group']['accept_submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Accept'),
        '#submit' => array('::acceptSubmitForm')
      );
    }
    //基础信息块
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
      '#handle_info' => $handle_info,
      '#is_distribution' => false
    );

    //部门编辑块
    $disabled = true;
    if($handle_status == 0 && $this->currentUser()->id() == $busi_uid) {
      $disabled = false;
    }
    $form['edit_info'] = array(
      '#type' => 'details',
      '#title' => t('Business department'),
      '#open' => true,
      '#weight' => 10,
      '#disabled' => $disabled
    );

    //管理IP
    $product = $entity->getObject('product_id');
    $autocomplete_route_parameters['server_type'] = $product->getObjectId('server_type');
    $ipm_default = '';
    if($ipm_obj = $entity->getObject('ipm_id')) {
      $ipm_default = $ipm_obj->label().'('. $entity->getObjectId('cabinet_server_id') .')';
      $autocomplete_route_parameters['current_ipm'] = $ipm_obj->id();
    }
    $form['edit_info']['ipm_value'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Management ip'),
      '#required' => true,
      '#autocomplete_route_name' => 'distribution.server.autocomplete',
      '#autocomplete_route_parameters' => $autocomplete_route_parameters,
      '#element_validate' => array('Drupal\order\ServerDistribution::matchValueValidate'),
      '#default_value' => $ipm_default
    );
    $form['edit_info']['business_ip'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('business-ip')
      )
    );
    //选择业务IP
    $ipb_options = array();
    $ipb_values = $entity->get('ipb_id');
    foreach($ipb_values as $value) {
      $ipb_obj = $value->entity;
      if($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $ipb_options[$ipb_obj->id()] = $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label();
      }
    }
    $form['edit_info']['business_ip']['ipb_values'] = array(
      '#type' => 'select',
      '#id' => 'edit-ipb-id',
      '#multiple' => true,
      '#title' => $this->t('Business ip'),
      '#size'=> 10,
      '#validated' => true,
      '#options' => $ipb_options,
    );
    $form['edit_info']['business_ip']['ipb_search'] = array(
    );
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_text'] = array(
      '#type' => 'textfield',
      '#size' => 12
    );
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#id' => 'ipb_search_submit',
      '#submit' => array(array(get_class($this), 'ipbSearchSubmit')),
      '#limit_validation_errors' => array(array('ipb_search_text')),
      '#ajax' => array(
        'callback' => array(get_class($this), 'ipbSearchAjax'),
        'wrapper' => 'ipb_search_wrapper',
        'method' => 'html'
      )
    );
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper'] = array(
      '#type' => 'container',
      '#id' => 'ipb_search_wrapper'
    );
    $options = array();
    $dis = ServerDistribution::createInstance();
    $search_text = $form_state->getValue('ipb_search_text');
    $options = $dis->getMatchIpb($search_text, $entity->getObjectId('client_uid'));
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper']['ipb_search_content'] = array(
      '#type' => 'select',
      '#multiple' => true,
      '#size' => 9,
      '#options' => $options
    );

    $form['edit_info']['complete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Processing is complete'), //处理完成
      '#default_value' => $handle_status
    );

    $form['edit_info']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#id' => 'business-save',
      '#validate' => array('::submitValidateForm')
    );

    $form['#attached']['library'] = array('order/drupal.business-dept-form');
    return $form;
  }

  public static function ipbSearchSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  public static function ipbSearchAjax(array $form, FormStateInterface $form_state) {
    return $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper']['ipb_search_content'];
  }

  /**
   * 接受提交
   */
  public function acceptSubmitForm(array $form, FormStateInterface $form_state) {
    $handle_id = $form_state->getValue('handle_id');
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);

    $new_handle_info['busi_uid'] = $this->currentUser()->id();
    $new_handle_info['busi_accept_data'] = REQUEST_TIME;
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);

    $entity = entity_load('hostclient', $handle_info->hostclient_id);
    if($handle_info->handle_action == 1) {
      $entity->set('status', 1);
      $entity->save();
    }
    //----------写日志---------
    $handle_info->busi_uid = $new_handle_info['busi_uid'];
    $handle_info->busi_accept_data = $new_handle_info['busi_accept_data'];
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info);
    $entity->other_status = 'business_dept_accept';
    HostLogFactory::OperationLog('order')->log($entity, 'update');
  }

  /**
   * 变更负责人提交
   */
  public function changeSubmitForm(array $form, FormStateInterface $form_state) {
    $change_People = $form_state->getValue('change_People');
    if($change_People) {
      $handle_id = $form_state->getValue('handle_id');
      $handle_info['busi_uid'] = $change_People;
      //$handle_info['busi_accept_data'] = REQUEST_TIME;
      $this->hostclient_service->updateHandleInfo($handle_info, $handle_id);
      //-------写日志-------
      $handle_info_log = $this->hostclient_service->loadHandleInfo($handle_id);
      $entity = entity_load('hostclient', $handle_info_log->hostclient_id);
      $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
      $entity->other_status = 'business_dept_move';
      HostLogFactory::OperationLog('order')->log($entity, 'update');
    } else {
      drupal_set_message('请选择要负责人。');
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitValidateForm(array &$form, FormStateInterface $form_state) {
    $handle_id = $form_state->getValue('handle_id');
    $hostclient_id = $form_state->getValue('hostclient_id');
    $entity = entity_load('hostclient', $hostclient_id);
    //判断管理IP
    $ipm_value = trim($form_state->getValue('ipm_value'));
    if(empty($ipm_value)) {
      $form_state->setErrorByName('ipm_value',$this->t('Management ip error'));
    } else {
      $cabinet_server = entity_load('cabinet_server', $ipm_value);
      if(empty($cabinet_server)) {
        $form_state->setErrorByName('ipm_value',$this->t('Management ip does not exist'));
      } else {
        $product = $entity->getObject('product_id');
        $product_type = $product->getObjectId('server_type');
        $ipm_type = $cabinet_server->getObject('server_id')->get('type')->target_id;
        if($product_type != $ipm_type) {
          $form_state->setErrorByName('ipm_value',$this->t('Management ip distribution error'));
        } else {
          $ipm_obj = $cabinet_server->getObject('ipm_id');
          $ipm_status = $ipm_obj->get('status')->value;
          if($ipm_status != 1 && $entity->getObjectId('ipm_id') != $ipm_obj->id()) {
            $form_state->setErrorByName('ipm_value',$this->t('Management ip status error'));
          }
        }
      }
    }
    //判断业务IP
    $complete = $form_state->getValue('complete');
    $ipb_values = $form_state->getValue('ipb_values');
    if($complete) {
      if(empty($ipb_values)) {
        $form_state->setErrorByName('ipb_values',$this->t('Business ip no distribution'));
      } else {
        if($entity->getSimplevalue('trial')) {
          if(count($ipb_values) != 1) {
            $form_state->setErrorByName('ipb_values',$this->t('Trial server can only be assigned a business IP'));
          }
        } else {
          $b = $this->checkIPdistribution($entity, $ipb_values);
          if(!$b) {
            $form_state->setErrorByName('ipb_values',$this->t('Business ip distribution error'));
          }
        }
      }
    }
    $ipb_add = array();
    $ipb_rm = array();
    $old_ipb_values = $entity->get('ipb_id')->getValue();
    foreach($ipb_values as $key=>$value) {
      $b = false;
      foreach($old_ipb_values as $old_value) {
        if(!empty($old_value) && $value == $old_value['target_id']) {
          $b = true;
          break;
        }
      }
      if(!$b) {
        $ipb_add[] = $value;
      }
    }
    foreach($old_ipb_values as $old_value) {
      if(empty($old_value['target_id'])) {
        break;
      }
      $b = false;
      foreach($ipb_values as $key =>$value) {
        if($old_value['target_id'] == $value) {
          $b = true;
          break;
        }
      }
      if(!$b) {
        $ipb_rm[] = $old_value['target_id'];
      }
    }
    foreach($ipb_add as $ip) {
      $ipb_obj = entity_load('ipb', $ip);
      if($ipb_obj->get('status')->value != 1) {
        $form_state->setErrorByName('ipb_values',$this->t('Business ip: %ip status error', array(
          '%ip' => $ipb_obj->label()
        )));
        break;
      }
    }

    $form_state->save_ipb_change = array(
      'add' => $ipb_add,
      'rm' => $ipb_rm
    );
  }
  /**
   * 检查IP分配是否正确
   */
  private function checkIPdistribution($entity, $ipbs) {
    //得到分配的业务IP
    $business_ips = array();
    foreach($ipbs as $ipb) {
      $ipb_obj = entity_load('ipb', $ipb);
      $ipb_type = $ipb_obj->get('type')->target_id;
      $business_ips[$ipb_type][] = $ipb;
    }
    //得到购买的业务IP
    $buy_ips = array();
    $business_list = $this->hostclient_service->loadHostclientBusiness($entity->id());
    foreach($business_list as $business) {
      $business_obj = entity_load('product_business', $business->business_id);
      $lib = $business_obj->getSimpleValue('resource_lib');
      if($lib != 'ipb_lib') {
        continue;
      }
      $operate = $business_obj->getSimpleValue('operate');
      if($operate == 'edit_number') {
        $contents = entity_load_multiple_by_properties('product_business_entity_content', array(
          'businessId' => $business->business_id
        ));
        $content = reset($contents);
        $type_id = $content->getSimpleValue('target_id');
        if(isset($buy_ips[$type_id])) {
          $buy_ips[$type_id] = $buy_ips[$type_id] + $business->business_content;
        } else {
          $buy_ips[$type_id] = $business->business_content;
        }
      } else if ($operate == 'select_content') {
        $def_value = $business->business_content;
        $def_value_arr = explode(',', $def_value);
        foreach($def_value_arr as $value) {
          $content = entity_load('product_business_entity_content', $value);
          $type_id = $content->getSimpleValue('target_id');
          if(isset($buy_ips[$type_id])) {
            $buy_ips[$type_id] = $buy_ips[$type_id] + 1;
          } else {
            $buy_ips[$type_id] = 1;
          }
        }
      } else if ($operate == 'select_and_number') {
        $def_value = $business->business_content;
        $def_value_arr = explode(',', $def_value);
        foreach($def_value_arr as $item) {
          $item_arr = explode(':', $item);
          $content = entity_load('product_business_entity_content', $item_arr[0]);
          $type_id = $content->getSimpleValue('target_id');
          if(isset($buy_ips[$type_id])) {
            $buy_ips[$type_id] = $buy_ips[$type_id] + $item_arr[1];
          } else {
            $buy_ips[$type_id] = $item_arr[1];
          }
        }
      }
    }
    //判断
    foreach($buy_ips as $key => $num) {
      if(!array_key_exists($key, $business_ips)) {
        return false;
      }
      $dis_num = count($business_ips[$key]);
      if($dis_num != $num) {
        return false;
      }
      unset($business_ips[$key]);
    }
    if(!empty($business_ips)) {
      return false;
    }
    return true;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $handle_id = $form_state->getValue('handle_id');
    $hostclient_id = $form_state->getValue('hostclient_id');
    $entity = entity_load('hostclient', $hostclient_id);

    //管理iP相关设值
    $old_ipm_value = $entity->getObjectId('ipm_id');
    $ipm_value = trim($form_state->getValue('ipm_value'));
    $cabinet_server = entity_load('cabinet_server', $ipm_value);
    $entity->set('ipm_id', $cabinet_server->getObjectId('ipm_id'));
    $entity->set('server_id', $cabinet_server->getObjectId('server_id'));
    $entity->set('cabinet_server_id', $ipm_value);
    $entity->brfore_save_ipm = $old_ipm_value;
    //业务IP相关设值
    $ipb_values = $form_state->getValue('ipb_values');
    $entity->set('ipb_id', $ipb_values);
    $entity->save_ipb_change = $form_state->save_ipb_change;

    //执行保存
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $complete = $form_state->getValue('complete');
    if($complete) {
      if($handle_info->handle_action == 1) {
        $entity->set('status', 2);
      }
      $new_handle_info['busi_status'] = 1;
      $new_handle_info['busi_complete_data'] = REQUEST_TIME;
      $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
      //----日志保存handel_info----
      $handle_info->busi_status = $new_handle_info['busi_status'];
      $handle_info->busi_complete_data = $new_handle_info['busi_complete_data'];

      drupal_set_message($this->t('Finished processing the business department'));
      $form_state->setRedirectUrl(new Url('admin.hostclient.business.list'));
    }
    $entity->save();
    //----------写日志---------
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info);
    $entity->other_status = 'business_dept_handle';
    HostLogFactory::OperationLog('order')->log($entity, 'update');
  }
}
