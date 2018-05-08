<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskIBandForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;


use Drupal\order\ServerDistribution;
/**
 * Provide for sop ip or bandwidth add.
 */
class SopTaskIBandForm extends ContentEntityForm {
  /**
   * 获取业务IP段类型.
   */
  function bip_get_bip_segment_type() {
    $type_arr = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('business_ip_segment_type', 0, 1);
    $options = array();
    foreach ($type_arr as $row) {
      $options[$row->tid] = $row->name;
    }
    return $options;
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $form['mips'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      // '#description' => $this->t('某客户的管理IP'),.
      '#required' => TRUE,
      // '#default_value' => $entity->isNew() ? '' : $entity->get('mips')->entity->label() . '(' . $entity->get('mips')->entity->id() . ')' ,.
      '#autocomplete_route_name' => 'sop.sop_task_server.room.autocomplete',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
        'js_room_mip' => 'autocomplete_task_room',
      ),
      '#attached' => array(
        'library' => array('sop/sop.sop_task_room.autocompletemip'),
      ),
    );
    if (!$entity->isNew()) {
      // user_load($entity->get('client_uid')->target_id);.
      $client_user = '';
    }    $form['client_uid'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#id' => 'sop_task_room_client',
      // '#description' => '格式:用户名|客户名|昵称|公司名',
      // '#default_value' => isset($client_user) ? $client_user->getUsername() : '',.
      '#required' => TRUE,
      '#autocomplete_route_name' => 'sop.sop_task_server.client.autocomplete',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['sop_op_type'] = array(
      '#type' => 'select',
      '#title' => t('操作类型'),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
      '#options' => array(
        0 => '添加IP',
        1 => '更改IP',
        2 => '停用IP',
        3 => '带宽变更',
      ),
      '#weight' => 10,
      // '#default_values' => !empty($this->entity) ? $this->entity->get('sop_op_type')->value : 0,.
    );
    $sop_task_server_types = $this->sop_task_iband_type();
    $form['sop_type'] = array(
      '#type' => 'select',
      '#title' => '类型',
      // '#description' => '工单类型',.
      '#required' => TRUE,
      '#options' => $sop_task_server_types,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    if (!empty($entity->get('mips')->target_id)) {
      $hostclients = entity_load_multiple_by_properties('hostclient', array('ipm_id' => $entity->get('mips')->entity->id()));
    }
    $bips_options = array();
    if (!empty($hostclients)) {
      $hostclients = reset($hostclients);
      $bips_values = $hostclients->get('ipb_id');
      foreach ($bips_values as $value) {
        $ipb_obj = $value->entity;
        if ($ipb_obj) {
          $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
          $bips_options[$ipb_obj->id()] = $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label();
        }
      }
    }

    $form['edit_info']['business_ip']['bips'] = array(
      '#type' => 'select',
      '#id' => 'edit-bips-id',
      '#multiple' => TRUE,
      '#title' => $this->t('已有业务IP'),
      '#size' => 6,
      '#validated' => TRUE,
      '#options' => $bips_options,
      '#attributes' => array(
        'class' => array('form-control'),
      ),
    );
    $aips_options = array();
    $aips_values = $entity->get('aips');
    foreach ($aips_values as $value) {
      $aip_obj = $value->entity;
      if ($aip_obj) {
        $entity_type = taxonomy_term_load($aip_obj->get('type')->value);
        $aips_options[$aip_obj->id()] = $aip_obj->label() . '-' . $aip_obj->get('type')->entity->label();
      }
    }
    $form['edit_info']['business_ip']['aips'] = array(
      '#type' => 'select',
      '#id' => 'edit-aips-id',
      '#multiple' => TRUE,
      '#title' => $this->t('添加IP'),
      '#size' => 6,
      '#validated' => TRUE,
      '#options' => $aips_options,
      '#attributes' => array(
        'class' => array('form-control'),
      ),
    );
    $sips_options = array();
    $sips_values = $entity->get('sips');
    foreach ($sips_values as $value) {
      $sip_obj = $value->entity;
      if ($sip_obj) {
        $entity_type = taxonomy_term_load($sip_obj->get('type')->value);
        $sips_options[$sip_obj->id()] = $sip_obj->label() . '-' . $sip_obj->get('type')->entity->label();
      }
    }
    $form['edit_info']['business_ip']['sips'] = array(
      '#type' => 'select',
      '#id' => 'edit-sips-id',
      '#multiple' => TRUE,
      '#title' => $this->t('停用IP'),
      '#size' => 6,
      '#validated' => TRUE,
      '#options' => $sips_options,
      '#attributes' => array(
        'class' => array('form-control'),
      ),
    );
    $option_bip_types = $this->bip_get_bip_segment_type();
    $form['bip_type'] = array(
      '#type' => 'select',
      '#title' => t('IP类型'),
      // '#description' => t('IP段的类型,默认为普通段IP'),.
      '#options' => $option_bip_types,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    /*
    $active = array(0 => t('未选'), 1 => t('添加'), 2 => t('停用'));
    $form['set_active'] = array(
    '#type' => 'radios',
    '#title' => t('操作状态'),
    '#options' => $active,
    '#id' => 'edit-set-active',
    '#attributes' => array(
    'class' => array(),
    ),
    );
     */

    // 选择业务IP.
    $ipb_options = array();

    $form['edit_info']['business_ip']['ipb_search'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('business-search'),
      ),
    );
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_text'] = array(
      '#type' => 'textfield',
      '#title' => '业务IP关键词',
      '#size' => 12,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#id' => 'ipb_search_submit',
      '#submit' => array(array(get_class($this), 'ipbSearchSubmit')),
      '#limit_validation_errors' => array(
        array('ipb_search_text'),
        array('bip_type'),
        array('client_uid'),
      ),
      '#attributes' => array(
        'class' => array('btn btn-primary form-control input-sm'),
      ),
      '#ajax' => array(
        'callback' => array(get_class($this), 'ipbSearchAjax'),
        'wrapper' => 'ipb_search_wrapper',
        'method' => 'html',
      ),
    );
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper'] = array(
      '#type' => 'container',
      '#id' => 'ipb_search_wrapper',
    );
    $options = array();
    $dis = ServerDistribution::createInstance();
    $search_text = $form_state->getValue('ipb_search_text');
    $search_text_ip_type = $form_state->getValue('bip_type');
    $search_text_user = $form_state->getValue('client_uid');

    $options = $dis->getMatchIpb($search_text, $search_text_user, $search_text_ip_type);
    $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper']['ipb_search_content'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 5,
      '#options' => $options,
      '#attributes' => array(
        'class' => array('form-control'),
      ),
    );

    $form['#attached']['library'] = array('sop/sop.sop_task_iband.sop-task-iband-form');
    return $form;
  }
  /**
   *
   */
  public static function ipbSearchAjax(array $form, FormStateInterface $form_state) {
    return $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper']['ipb_search_content'];
  }
  /**
   *
   */
  public static function ipbSearchSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }
  /**
   * SOP IP宽带工单类型.
   */
  function sop_task_iband_type() {
    return array(
      'i4' => 'I4',
    );
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('IP带宽工单保存成功!'));
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }

}
