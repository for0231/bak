<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskIBandDetailForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\order\ServerDistribution;
use Drupal\hostlog\HostLogFactory;

/**
 * 工单服务器上下架类详情表单.
 */
class SopTaskIBandDetailForm extends FormBase {
  protected $hostclient_service;

  /**
   *
   */
  public function __construct() {
    $this->hostclient_service = \Drupal::service('hostclient.serverservice');
  }
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
  public function getFormId() {
    return 'sop_task_iband_detail_form';
  }
  /**
   * Check current user's permission.
   */
  private function checkCurrentDisabledPermission($sop, $hostclient, $handle_info) {
    $has_bus_permission = \Drupal::currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = \Drupal::currentUser()->hasPermission('administrator technology sop permission');
    // 工单已完成时全部禁用.
    if ($sop->get('sop_status')->value == 4) {
      $disabled_all = TRUE;
    }
    else {
      $disabled_all = FALSE;
    }
    // 工单已接受.
    if ($sop->get('solving_uid')->target_id == \Drupal::currentUser()->id()) {
      $disabled_current_user = FALSE;
    }
    else {
      $disabled_current_user = TRUE;
    }
    // 当前用户不是当前工单的接受者，则禁用所有.
    if ($has_bus_permission) {
      if ($handle_info->busi_uid != \Drupal::currentUser()->id() || $sop->get('sop_complete')->value == 2) {
        $disabled_current_accept_all = TRUE;
      }
      else {
        $disabled_current_accept_all = FALSE;
      }
    }
    if ($has_tech_permission) {
      if ($handle_info->tech_uid != \Drupal::currentUser()->id() || $sop->get('sop_complete')->value == 2) {
        $disabled_current_accept_all = TRUE;
      }
      else {
        $disabled_current_accept_all = FALSE;
      }
    }
    return $disabled_all || $disabled_current_user || $disabled_current_accept_all;
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sop = NULL) {
    $entity = $this->entity = $sop;
    $hostclient = $entity->get('hid')->entity;
    $handle_info = \Drupal::service('hostclient.handleservice')->loadHandleInfo4SopByHandleId($entity->get('handle_id')->value);
    $has_bus_permission = \Drupal::currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = \Drupal::currentUser()->hasPermission('administrator technology sop permission');
    $disabled_bool = $this->checkCurrentDisabledPermission($entity, $hostclient, $handle_info);

    // 附加业务列表.
    $form['additonal_bussiness'] = array(
      '#theme' => 'admin_handle_task_server_hostclient_info',
      '#handle_info' => $handle_info,
      '#description' => $this->t('该服务器的所有附加业务信息!'),
    );

    /***********************************************
     * 添加机房属性
     * Start
     ***********************************************
     */
    $room_label = '';
    if ($hostclient->get('rid')->target_id) {
      $room_label = $hostclient->get('rid')->entity->label();
    }
    else {
      $room_label = '-';
    }
    $form['markup_sop_room'] = array(
      '#markup' => '机房: ' . $room_label,
    );

    if ($hostclient->get('rid')->target_id) {
      $room_id = $hostclient->get('rid')->target_id;
      $autocomplete_route_parameters['room_id'] = $room_id;
    }
    else {
      $room_id = '';
      $autocomplete_route_parameters['room_id'] = 0;
    }

    $form['room'] = array(
      '#type' => 'hidden',
      '#value' => $room_id,
      '#attributes' => array(
        'id' => 'room',
      ),
    );
    $is_room_check = \Drupal::config('common.global')->get('is_district_room_id');
    $form['room_check'] = array(
      '#type' => 'hidden',
      '#value' => $is_room_check,
      '#attributes' => array(
        'id' => 'room_check',
      ),
    );
    /***********************************************
     * 添加机房属性
     * End
     ***********************************************
     */
    // 管理IP.
    $product = $hostclient->getObject('product_id');
    $autocomplete_route_parameters['server_type'] = $product->getObjectId('server_type');
    $ipm_default = '';
    if ($ipm_obj = $hostclient->getObject('ipm_id')) {
      $ipm_default = $ipm_obj->label() . '(' . $hostclient->getObjectId('cabinet_server_id') . ')';
      $autocomplete_route_parameters['current_ipm'] = $ipm_obj->id();
    }
    $form['markup_sop_status'] = array(
      '#markup' => '当前状态: ' . sop_task_status()[$entity->get('sop_status')->value],
    );
    $current_server_status = array(
      0 => '正常服务器',
      1 => '试用服务器',
    );
    $form['markup_server_status'] = array(
      '#markup' => '当前状态: ' . $current_server_status[$hostclient->get('trial')->value],
    );
    $form['mips'] = array(
      '#type' => 'textfield',
      '#title' => '管理IP',
      '#required' => TRUE,
    // 只有业务有权限更改.
      '#disabled' => $disabled_bool || !$has_bus_permission,
      // '#disabled' => true, //有技术、业务权限都不能编辑.
      '#description' => $this->t('结构:管理IP-服务器ID,选定服务器后只能选择该管理IP组下业务IP!'),
      '#default_value' => $ipm_default,
      '#autocomplete_route_name' => 'distribution.server.autocomplete',
      '#autocomplete_route_parameters' => $autocomplete_route_parameters,
      '#element_validate' => array('Drupal\order\ServerDistribution::matchValueValidate'),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['client_uid'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#required' => TRUE,
      // '#disabled' => $disabled_bool,.
      '#description' => $this->t('客户的用户名'),
    // 有技术、业务权限都不能编辑,.
      '#disabled' => TRUE,
      '#default_value' => $hostclient->get('client_uid')->entity->getUsername(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['client_description'] = array(
      '#markup' => '客户备注:' . $handle_info->client_description,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['sop_type'] = array(
      '#type' => 'select',
      '#title' => '类型',
      '#required' => TRUE,
    // $disabled_bool,.
      '#disabled' => TRUE,
      '#options' => sop_type_levels(),
      '#default_value' => $entity->get('sop_type')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    // 业务可编辑.
    $form['sop_op_type'] = array(
      '#type' => 'select',
      '#title' => '操作类型',
      '#required' => TRUE,
    // $disabled_bool,.
      '#disabled' => TRUE,
      '#options' => sop_task_op_status(),
      '#default_value' => $entity->get('sop_op_type')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['pre_solving_uid'] = array(
      '#type' => 'textfield',
      '#title' => '上次操作人',
      '#required' => TRUE,
    // 有技术、业务权限都不能编辑.
      '#disabled' => TRUE,
      '#default_value' => !empty($entity->get('presolving_uid')->target_id) ? getSopClientName(\Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('presolving_uid')->target_id)) : '-',
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $bips_options = array();
    $bips_values = $hostclient->get('ipb_id');
    foreach ($bips_values as $value) {
      $ipb_obj = $value->entity;
      if ($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $bips_options[$ipb_obj->id()] = $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label();
      }
    }
    // @todo 另有一处代码和此行相同，
    // 目的:
    // @todo 未上架业务IP的使用状态还未标示已用的，这里需要将该类IP放到添加IP栏里。
    // array(0,1,2)表示待处理，处事中，待上架三种状态
    if (in_array($hostclient->get('status')->value, array(0, 1, 2))) {
      $bips = array();
    }
    else {
      $bips = $bips_options;
    }
    // 如果有业务权限可编辑.
    $sop_had_bips = count($bips);
    $form['edit_info']['business_ip']['bips'] = array(
      '#type' => 'select',
      '#id' => 'edit-bips-id',
      '#multiple' => TRUE,
      '#title' => $this->t('已有业务IP'),
      '#description' => $this->t('新服务器时:如果已自动分配业务IP,这里还是需要放置到添加IP栏里,表示一个正式流程。'),
      '#size' => 6,
      '#validated' => TRUE,
      // '#disabled' => $disabled_bool || !$has_bus_permission,.
      '#disabled' => $disabled_bool,
      '#options' => $bips,
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

    // 如果用业务操作是否已完成的标志来判断会更好
    // 或者两个条件一起判断也行?
    if (in_array($hostclient->get('status')->value, array(0, 1, 2))) {
      // @annotation 使用array_merge会删掉key值
      $aips_options = $bips_options + $aips_options;
    }
    $form['edit_info']['business_ip']['aips'] = array(
      '#type' => 'select',
      '#id' => 'edit-aips-id',
      '#multiple' => TRUE,
      '#title' => $this->t('添加IP'),
      '#description' => $this->t('业务和技术待绑定的业务IP'),
      '#size' => 6,
      '#validated' => TRUE,
      // '#disabled' => $disabled_bool || !$has_bus_permission,.
      '#disabled' => $disabled_bool,
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
      '#description' => $this->t('和已有业务IP栏相关联，可互相交互!'),
      '#size' => 6,
      // '#disabled' => $disabled_bool || !$has_bus_permission,.
      '#disabled' => $disabled_bool,
      '#validated' => TRUE,
      '#options' => $sips_options,
      '#attributes' => array(
        'class' => array('form-control'),
      ),
    );
    // 如果有业务权限，则显示.
    if ($has_bus_permission) {
      $dis = ServerDistribution::createInstance();
      if ($is_room_check && $room_id) {
        $segoptions = $dis->getMatchIpbSegment($room_id);
        $form['edit_info']['business_ip']['bip_segment'] = array(
          '#title' => t('该机房下业务IP段'),
          '#type' => 'select',
          '#multiple' => TRUE,
          '#size' => 5,
          '#id' => 'edit-bips-segment-id',
          '#options' => $segoptions,
          '#disabled' => $disabled_bool,
          '#attributes' => array(
            'class' => array('form-control'),
          ),
        );
      }
      $option_bip_types = $this->bip_get_bip_segment_type();
      $form['bip_type'] = array(
        '#type' => 'select',
        '#title' => t('IP类型'),
        '#description' => t('IP段的类型,默认为普通段IP'),
        '#options' => $option_bip_types,
        '#disabled' => $disabled_bool,
        '#attributes' => array(
          'class' => array('form-control input-sm'),
        ),
      );
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
        '#disabled' => $disabled_bool,
        '#attributes' => array(
          'class' => array('form-control input-sm'),
        ),
      );
      $form['edit_info']['business_ip']['ipb_search']['ipb_search_submit'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Search'),
        '#id' => 'ipb_search_submit',
        '#disabled' => $disabled_bool,
        '#submit' => array(array(get_class($this), 'ipbSearchSubmit')),
        '#limit_validation_errors' => array(
          array('ipb_search_text'),
          array('bip_type'),
          array('client_uid'),
          array('mips'),
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

      /*
      if ($is_room_check && $room) {
      $options = $dis->getMatchIpbSegment($search_text, $search_text_user, $search_text_ip_type);
      } else {
      $options = $dis->getMatchIpb($search_text, $search_text_user, $search_text_ip_type);
      } */
      $search_text = $form_state->getValue('ipb_search_text');
      $search_text_ip_type = $form_state->getValue('bip_type');
      $search_text_user = $form_state->getValue('client_uid');
      // 业务人员选中/自动分配的管理IP
      // @start 查找当前登记的管理IP的分组ID -开始
      $ipm_value = $form_state->getValue('mips');
      $ipm_group_id = $this->getIpGroupId($ipm_value);
      // @end 查找当前登记的管理IP的分组ID -结束
      if ($is_room_check && $room_id) {
        $search_text_ip_segment = $form_state->getValue('bip_segment');

        $form['edit_info']['business_ip']['ipb_search']['ipb_search_submit']['#limit_validation_errors'][] = array('bip_segment');
        $options = $dis->getMatchIpb($search_text, $search_text_user, $search_text_ip_type, $ipm_group_id, $search_text_ip_segment);
      }
      else {
        $options = $dis->getMatchIpb($search_text, $search_text_user, $search_text_ip_type, $ipm_group_id);
      }
      $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper']['ipb_search_content'] = array(
        '#type' => 'select',
        '#multiple' => TRUE,
        '#size' => 5,
        '#description' => $this->t('默认加载所有可用业务IP,也可根据IP类型和关键词搜索目标业务IP'),
        '#options' => $options,
        '#disabled' => $disabled_bool,
        '#attributes' => array(
          'class' => array('form-control'),
        ),
      );
    }
    $form['created'] = array(
      '#type' => 'textfield',
      '#title' => '建单时间',
      '#required' => TRUE,
    // 有技术、业务权限都不能编辑,.
      '#disabled' => TRUE,
      '#default_value' => date('Y-m-d H:i:s', $hostclient->get('created')->value),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );

    $form['pid'] = array(
      '#type' => 'textfield',
      '#title' => '产品名称',
      '#required' => TRUE,
      // '#disabled' => $disabled_bool || !$has_tech_permission, //有技术权限时启用编辑
    // 有技术、业务权限都不能编辑.
      '#disabled' => TRUE,
      '#default_value' => $hostclient->get('product_id')->entity->label(),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    $form['equipment_date'] = array(
      '#type' => 'textfield',
      '#title' => '上架时间',
      '#required' => TRUE,
    // 有技术、业务权限都不能编辑.
      '#disabled' => TRUE,
      '#default_value' => empty($hostclient->get('equipment_date')->value) ? '-' : date('Y-m-d H:i:s', $hostclient->get('equipment_date')->value),
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );

    $form['description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('需求'),
      '#default_value' => $sop->get('description')->value,
      '#disabled' => $disabled_bool,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );

    $form['result_description'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('处理过程、结果'),
      '#disabled' => $disabled_bool,
      '#default_value' => $entity->get('result_description')->value,
      '#attributes' => array(
        'class' => array('form-control input-sm'),
      ),
    );
    // 如果有技术权限则显示.
    if ($has_tech_permission || $sop->get('sop_status')->value == '22') {

      $form['tech_iband_edit_info']['isbind'] = array(
        '#type' => 'checkbox',
        '#title' => '已绑定',
        '#disabled' => $disabled_bool,
        '#default_value' => $sop->get('isbind')->value,
      );
      $form['tech_iband_edit_info']['online_op'] = array(
        '#type' => 'checkbox',
        '#title' => '上机操作',
        '#disabled' => $disabled_bool,
        '#default_value' => $sop->get('online_op')->value,
      );
      $form['tech_iband_edit_info']['management_card'] = array(
        '#type' => 'checkbox',
        '#title' => '已添加管理卡',
        '#disabled' => $disabled_bool,
        '#default_value' => $sop->get('management_card')->value,
      );

      $form['tech_iband_check_info']['check_handle'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Check server'),
        '#required' => TRUE,
        '#disabled' => $disabled_bool,
        '#options' => $this->checkOptions(),
      );
      if ($handle_info->tech_check_item) {
        $form['tech_iband_check_info']['check_handle']['#default_value'] = (Array) json_decode($handle_info->tech_check_item);
      }
      $form['tech_edit_info']['init_pwd'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Server initial password'),
        '#required' => TRUE,
        '#disabled' => $disabled_bool,
        '#default_value' => $hostclient->getSimpleValue('init_pwd'),
      );
      $form['tech_edit_info']['server_mask'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Subnet mask'),
        '#disabled' => $disabled_bool,
        '#default_value' => $hostclient->getSimpleValue('server_mask'),
      );

      $form['tech_edit_info']['server_gateway'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Gateway'),
        '#disabled' => $disabled_bool,
        '#default_value' => $hostclient->getSimpleValue('server_gateway'),
      );

      $form['tech_edit_info']['server_dns'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('DNS'),
        '#disabled' => $disabled_bool,
        '#default_value' => $hostclient->getSimpleValue('server_dns'),
      );
    }

    if ($has_bus_permission) {

      // 该工单不是当前处理用户或者该工单正处于业务转接状态时,需其他业务能接受该工单，启用接受工单按钮.
      if ((\Drupal::currentUser()->id() != 1) && in_array($sop->get('sop_status')->value, array(0, 8, 22))) {
        $form['actions']['busi_accept_submit'] = array(
          '#type' => 'submit',
          '#value' => '接受工单',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::busiAcceptSubmitForm'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
      if ($entity->get('sop_complete')->value == 2 && in_array($entity->get('sop_status')->value, array(22))) {
        // 技术已交付业务，服务器状态已上架.
        $form['actions']['bus_commit_client'] = array(
          '#type' => 'submit',
          '#value' => '交付客户',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::busiCommitClientSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
      if (in_array($entity->get('sop_status')->value, array(3, 6))) {
        // 业务已交付给客户.
        $form['actions']['bus_finish_sop'] = array(
          '#type' => 'submit',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#value' => '完成工单',
          '#submit' => array('::busiFinishSopSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }

      if (empty($entity->get('sop_complete')->value)) {
        // 业务处理已完成时，不可保存.
        $form['actions']['bus_submit'] = array(
          '#type' => 'submit',
          '#value' => '保存|业务',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#id' => 'edit-busi-save-submit',
          '#disabled' => $disabled_bool,
          '#validate' => array('::busiValidateSubmit'),
          '#submit' => array('::busiSaveSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        /**
         * $form['actions']['busi_create_room_submit'] = array(
         * '#type' => 'submit',
         * '#value' => '生成机房事务',
         * '#prefix' => '<div class="col-xs-2">',
         * '#suffix' => '</div>',
         * '#submit' => array(),
         * '#attributes' => array(
         * 'class' => array('btn btn-primary form-control input-sm'),
         * ),
         * );
         */
        $form['actions']['bus_other_submit'] = array(
          '#type' => 'submit',
          '#value' => '交其他人|业务',
          '#disabled' => $disabled_bool,
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::busiOtherSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['to_tech_submit'] = array(
          '#type' => 'submit',
          '#value' => '交付技术|业务',
          '#disabled' => $disabled_bool,
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#validate' => array('::busiToTechValidateSubmit'),
          '#submit' => array('::busiToTechSaveSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        // 如果确认点击运维返工，则当前工单默认为状态9.
        if (in_array($sop->get('sop_status')->value, array(22))) {
          $form['actions']['busi_return_to_tech_submit'] = array(
            '#type' => 'submit',
            '#value' => '运维返工',
            '#prefix' => '<div class="col-xs-2">',
            '#suffix' => '</div>',
            '#submit' => array('::busiRejectTechSubmit'),
            '#attributes' => array(
              'class' => array('btn btn-primary form-control input-sm'),
            ),
          );
        }
      }

    }
    if ($has_tech_permission) {
      if ((\Drupal::currentUser()->id() != 1) && in_array($sop->get('sop_status')->value, array(5, 11))) {
        // If ($handle_info->tech_uid != \Drupal::currentUser()->id() || $sop->get('sop_status')->value == 5) {.
        $form['actions']['tech_accept_submit'] = array(
          '#type' => 'submit',
          '#value' => '接受工单',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::techAcceptSubmitForm'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
      elseif (in_array($entity->get('sop_complete')->value, array(2, 1))) {
        // 技术未完成工单,不可保存.
        $form['actions']['tech_submit'] = array(
          '#type' => 'submit',
          '#value' => '保存|技术',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::techSaveSubmit'),
          '#disabled' => $disabled_bool,
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['to_busi_submit'] = array(
          '#type' => 'submit',
          '#value' => '交付业务',
          '#disabled' => $disabled_bool,
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#validate' => array('::techToBusiValidateSubmit'),
          '#submit' => array('::techToBusiSaveSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['tech_other_submit'] = array(
          '#type' => 'submit',
          '#value' => '交其他人|技术',
          '#disabled' => $disabled_bool,
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::techOtherSubmit'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
        $form['actions']['tech_to_client_submit'] = array(
          '#type' => 'submit',
          '#value' => '交客户',
          '#disabled' => $disabled_bool,
          '#submit' => array('::tech2ClientSubmit'),
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
      // 接受运维返工.
      if (in_array($sop->get('sop_status')->value, array(9))) {
        $form['actions']['tech_accept_fan_submit'] = array(
          '#type' => 'submit',
          '#value' => '接受返工',
          '#prefix' => '<div class="col-xs-2">',
          '#suffix' => '</div>',
          '#submit' => array('::techAcceptFanSubmitForm'),
          '#attributes' => array(
            'class' => array('btn btn-primary form-control input-sm'),
          ),
        );
      }
    }
    if ($has_bus_permission) {
      $form['#attached']['library'] = array('sop/sop.sop_task_iband.sop-task-iband-form');
    }
    $form['#theme'] = 'sop_task_iband_detail';
    return $form;
  }
  /**
   * @description 技术接受运维返工
   */
  public function techAcceptFanSubmitForm(array &$form, FormStateInterface $form_state) {
    $hostclient = $this->entity->get('hid')->entity;
    $handle_id = $this->entity->get('handle_id')->value;
    // 接受工单仅保存当前处理人.
    $this->entity->set('solving_uid', $this->currentUser()->id());
    $this->entity->set('sop_status', 92);
    $this->entity->set('sop_complete', 1);
    $this->entity->save();
    // Handle info 处理.
    $handle_info['tech_uid'] = $this->currentUser()->id();
    $handle_info['tech_accept_data'] = REQUEST_TIME;
    $this->hostclient_service->updateHandleInfo($handle_info, $handle_id);
    // ----------写日志---------.
    $handle_info_log = $this->hostclient_service->loadHandleInfo($handle_id);
    $entity = entity_load('hostclient', $handle_info_log->hostclient_id);
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info_log);
    $entity->other_status = 'tech_dept_accept';
    HostLogFactory::OperationLog('order')->log($entity, 'update');
    drupal_set_message('运维返工单接受成功!');
  }
  /**
   * @description 实现业务操作--运维返工
   */
  public function busiRejectTechSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    // 运维已交付.
    $sop->set('sop_status', 9);
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->set('solving_uid', NULL);
    $sop->save();
    drupal_set_message('运维返工交付完成!');
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * @description 技术直接交付客户
   */
  public function tech2ClientSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    if ($sop->get('sop_complete')->value == 2) {
      // 运维已交付.
      $sop->set('sop_status', 6);
      $sop->set('solving_uid', $this->currentUser()->id());
      $sop->save();
      $hostclient = $sop->get('hid')->entity;
      \Drupal::service('letters.letterservice')->sendCustomer($hostclient);
      drupal_set_message('交付客户完成!');
      $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
    }
    else {
      drupal_set_message('请完成该工单!');
    }
  }
  /**
   * @description 交付其他技术处理
   */
  public function techOtherSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    // 运维转接工单.
    $sop->set('sop_status', 5);
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->set('solving_uid', NULL);
    $sop->save();
    drupal_set_message('技术交付完成!');
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * @description 交付其他业务处理
   */
  public function busiOtherSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    // 业务转接.
    $sop->set('sop_status', 8);
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->set('solving_uid', NULL);
    $sop->save();
    drupal_set_message('业务交付完成!');
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * @description 完成工单
   */
  public function busiFinishSopSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    $handle_info = \Drupal::service('hostclient.handleservice')->loadHandleInfo4SopByHandleId($sop->get('handle_id')->value);
    $hostclient = $sop->get('hid')->entity;

    // 判断订单是否处理完成，并修改状态
    // 忽略是否试用.
    $order_id = $handle_info->handle_order_id;
    $all_complete = $this->hostclient_service->checkHandleStatus($order_id);
    if ($all_complete) {
      // 如果该服务器不是试用时，则关闭对应订单的状态.
      if (!$hostclient->getSimplevalue('trial')) {
        $order = entity_load('order', $order_id);
        $order->set('status', 5);
        $order->save();
      }
      $sop->set('aips', array());
      $sop->set('sips', array());
      // 业务已交付.
      $sop->set('sop_status', 4);
      $sop->set('presolving_uid', $this->currentUser()->id());
      $sop->save();
      drupal_set_message('工单完成!');
      $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
    }
    elseif (\Drupal::currentUser()->id() == 1) {
      $sop->set('aips', array());
      $sop->set('sips', array());
      // 业务已交付.
      $sop->set('sop_status', 4);
      $sop->set('presolving_uid', $this->currentUser()->id());
      $sop->save();
      drupal_set_message('强制完成工单成功!');
    }
    else {
      drupal_set_message('工单完成失败!', 'error');
    }

  }
  /**
   * @description 业务交付客户
   * @todo 这个待处理，其动作是给客户发送一条信息,
   *       这个动作其实应该可以考虑放到运维处理完成后
   */
  public function busiCommitClientSubmit(array &$form, FormStateInterface $form_state) {
    $sop = $this->entity;
    // 业务已交付.
    $sop->set('sop_status', 3);
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->save();
    $hostclient = $sop->get('hid')->entity;
    \Drupal::service('letters.letterservice')->sendCustomer($hostclient);
    // drupal_set_message('给客户送该服务器已上架的短信息!');
    // $form_state->setRedirectUrl(new Url('admin.sop_task.list'));.
    drupal_set_message('交付客户成功，请及时完成该工单!');
  }
  /**
   * @description 需要操作的流程
   * 1. hostspace状态变更
   * 2. handleinfo 状态变更
   * 3. sop 状态保存
   */
  public function techToBusiSaveSubmit(array &$form, FormStateInterface $form_state) {
    $entity = $hostclient = $this->entity->get('hid')->entity;
    $sop = $this->entity;
    $handle_id = $this->entity->get('handle_id')->value;
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $check_handle = $form_state->getValue('check_handle');
    $jsonencode = json_encode($check_handle);
    $description = $form_state->getValue('description');
    $new_handle_info['tech_check_item'] = $jsonencode;
    $new_handle_info['tech_description'] = $description['value'];
    $time = REQUEST_TIME;
    $entity->set('equipment_date', $time);
    if ($entity->getSimplevalue('trial')) {
      $config = \Drupal::config('common.global');
      $trial_time = $config->get('server_trial_time');
      if (empty($trial_time)) {
        $trial_time = 24;
      }
      $entity->set('status', 3);
      $entity->set('service_expired_date', strtotime('+' . $trial_time . ' hour', $time));
    }
    else {
      $product_service = \Drupal::service('order.product');
      if ($handle_info->handle_action == 1) {
        $entity->set('status', 3);
        // 计算结束时间.
        $order_product = $product_service->getProductById($handle_info->handle_order_product_id);
        $entity->set('service_expired_date', strtotime('+' . $order_product->product_limit . ' month', $time));
        // 保存配件到server.
        $product_business_list = $product_service->getOrderBusiness($handle_info->handle_order_product_id);
        $this->hostclient_service->saveServerPartHire($entity, $product_business_list);
      }
      elseif ($handle_info->handle_action == 3) {
        // 保存配件到server.
        $product_business_list = $product_service->getOrderBusiness($handle_info->handle_order_product_id);
        $this->hostclient_service->saveServerPartUpgrade($entity, $product_business_list);
      }
      $entity->set('unpaid_order', 0);
    }
    $new_handle_info['tech_complete_data'] = $time;
    $new_handle_info['tech_status'] = 1;
    // ------日志handle信息--------.
    $handle_info->tech_complete_data = $new_handle_info['tech_complete_data'];
    $handle_info->tech_status = $new_handle_info['tech_status'];
    $entity->save();

    // 保存处理信息.
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
    // 保存处理信息.
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
    // 判断订单是否处理完成，并修改状态.
    if (!$entity->getSimplevalue('trial')) {
      $order_id = $handle_info->handle_order_id;
      $all_complete = $this->hostclient_service->checkHandleStatus($order_id);
      if ($all_complete) {
        $order = entity_load('order', $order_id);
        $order->set('status', 5);
        $order->save();
      }
    }

    // ----------写日志---------.
    $handle_info->tech_check_item = $new_handle_info['tech_check_item'];
    $handle_info->tech_description = $new_handle_info['tech_description'];
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info);
    $entity->other_status = 'tech_dept_handle';
    HostLogFactory::OperationLog('order')->log($entity, 'update');

    // 业务待处理.
    $sop->set('sop_status', 22);
    // 运维完成.
    $sop->set('sop_complete', 2);
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->save();
    drupal_set_message($this->t('成功交付业务!'));
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * 技术接受工单.
   *
   * @description 1. sop 工单处理
   *              2. handle处理
   */
  public function techAcceptSubmitForm(array &$form, FormStateInterface $form_state) {
    $hostclient = $this->entity->get('hid')->entity;
    $handle_id = $this->entity->get('handle_id')->value;
    // 接受工单仅保存当前处理人.
    $this->entity->set('solving_uid', $this->currentUser()->id());
    $this->entity->set('sop_status', 1);
    $this->entity->save();
    // Handle info 处理.
    $handle_info['tech_uid'] = $this->currentUser()->id();
    $handle_info['tech_accept_data'] = REQUEST_TIME;
    $this->hostclient_service->updateHandleInfo($handle_info, $handle_id);
    // ----------写日志---------.
    $handle_info_log = $this->hostclient_service->loadHandleInfo($handle_id);
    $entity = entity_load('hostclient', $handle_info_log->hostclient_id);
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info_log);
    $entity->other_status = 'tech_dept_accept';
    HostLogFactory::OperationLog('order')->log($entity, 'update');
  }
  /**
   * @description 技术完成操作时
   * 这里更新服务器的业务IP
   */
  public function techToBusiValidateSubmit(array &$form, FormStateInterface $form_state) {
    // $complete = $form_state->getValue('complete');.
    $check_handle = $form_state->getValue('check_handle');
    foreach ($check_handle as $key => $value) {
      if (!$value) {
        $form_state->setErrorByName('check_handle[' . $key . ']', $this->t('%item no checked', array('%item' => $this->checkOptions()[$key])));
      }
    }
    // 业务验证结束.
  }
  /**
   * @description 1. sop保存
   *              2. handle保存
   *              3. hostclient保存
   */
  public function techSaveSubmit(array &$form, FormStateInterface $form_state) {
    $entity = $hostclient = $this->entity->get('hid')->entity;
    $hostclient_id = $hostclient->id();
    $handle_id = $this->entity->get('handle_id')->value;
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $sop = $this->entity;
    $has_tech_permission = \Drupal::currentUser()->hasPermission('administrator technology sop permission');
    // 有业务权限并且工单状态为0.
    // @todo description待处理
    if ($has_tech_permission) {
      $check_handle = $form_state->getValue('check_handle');
      $jsonencode = json_encode($check_handle);
      $description = $form_state->getValue('description');
      $new_handle_info['tech_check_item'] = $jsonencode;
      $new_handle_info['tech_description'] = $description['value'];

      $entity->set('init_pwd', $form_state->getValue('init_pwd'));
      $entity->set('server_mask', $form_state->getValue('server_mask'));
      $entity->set('server_gateway', $form_state->getValue('server_gateway'));
      $entity->set('server_dns', $form_state->getValue('server_dns'));
      $entity->set('server_manage_card', $form_state->getValue('management_card'));
      $entity->set('isbind', $form_state->getValue('isbind'));
      $entity->set('online_op', $form_state->getValue('online_op'));

      $entity->save();
      // 保存处理信息.
      $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
      // ----------写日志---------.
      $handle_info->tech_check_item = $new_handle_info['tech_check_item'];
      $handle_info->tech_description = $new_handle_info['tech_description'];
      $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info);
      $entity->other_status = 'tech_dept_handle';

      HostLogFactory::OperationLog('order')->log($entity, 'update');
      $sop->set('sop_op_type', $form_state->getValue('sop_op_type'));
      $sop->set('sop_type', $form_state->getValue('sop_type'));
      $sop->set('description', $form_state->getValue('description'));
      $sop->set('result_description', $form_state->getValue('result_description'));
      $sop->set('management_card', $form_state->getValue('management_card'));
      $sop->set('isbind', $form_state->getValue('isbind'));
      $sop->set('online_op', $form_state->getValue('online_op'));
      // Sop.
      $sop->save();
      drupal_set_message('技术工单保存成功');
    }
  }
  /**
   * 检查IP分配是否正确.
   *
   * @param $entity hostclient的实体
   * @param $ipbs 业务IP数组
   */
  private function checkIPdistribution($entity, $ipbs) {
    // 得到分配的业务IP
    // 得到正分配和已分配的业务IP
    // 等于所有已购买的业务IP.
    $business_ips = array();
    foreach ($ipbs as $ipb) {
      $ipb_obj = entity_load('ipb', $ipb);
      $ipb_type = $ipb_obj->get('type')->target_id;
      $business_ips[$ipb_type][] = $ipb;
    }

    // 得到购买的业务IP
    $buy_ips = array();
    $business_list = $this->hostclient_service->loadHostclientBusiness($entity->id());

    foreach ($business_list as $business) {
      $business_obj = entity_load('product_business', $business->business_id);
      $lib = $business_obj->getSimpleValue('resource_lib');
      if ($lib != 'ipb_lib') {
        continue;
      }
      $operate = $business_obj->getSimpleValue('operate');
      if ($operate == 'edit_number') {
        $contents = entity_load_multiple_by_properties('product_business_entity_content', array(
          'businessId' => $business->business_id,
        ));
        $content = reset($contents);
        $type_id = $content->getSimpleValue('target_id');
        if (isset($buy_ips[$type_id])) {
          $buy_ips[$type_id] = $buy_ips[$type_id] + $business->business_content;
        }
        else {
          $buy_ips[$type_id] = $business->business_content;
        }
      }
      elseif ($operate == 'select_content') {
        $def_value = $business->business_content;
        $def_value_arr = explode(',', $def_value);
        foreach ($def_value_arr as $value) {
          $content = entity_load('product_business_entity_content', $value);
          $type_id = $content->getSimpleValue('target_id');
          if (isset($buy_ips[$type_id])) {
            $buy_ips[$type_id] = $buy_ips[$type_id] + 1;
          }
          else {
            $buy_ips[$type_id] = 1;
          }
        }
      }
      elseif ($operate == 'select_and_number') {
        $def_value = $business->business_content;
        $def_value_arr = explode(',', $def_value);
        foreach ($def_value_arr as $item) {
          $item_arr = explode(':', $item);
          $content = entity_load('product_business_entity_content', $item_arr[0]);
          $type_id = $content->getSimpleValue('target_id');
          if (isset($buy_ips[$type_id])) {
            $buy_ips[$type_id] = $buy_ips[$type_id] + $item_arr[1];
          }
          else {
            $buy_ips[$type_id] = $item_arr[1];
          }
        }
      }
    }

    // 判断.
    foreach ($buy_ips as $key => $num) {
      if (!array_key_exists($key, $business_ips)) {
        return FALSE;
      }

      $dis_num = count($business_ips[$key]);
      if ($dis_num != $num) {
        return FALSE;
      }
      unset($business_ips[$key]);
    }
    if (!empty($business_ips)) {
      return FALSE;
    }
    return TRUE;
  }
  /**
   * @description 业务如果处理完成后可以交付技术部
   * 1. sop工单状态的变更（1.工单状态 2.工单完成状态)
   * 2. hostclient的完成状态变更
   * 3. handleinfo的业务状态变更
   */
  public function busiToTechSaveSubmit(array &$form, FormStateInterface $form_state) {
    $entity = $hostclient = $this->entity->get('hid')->entity;
    $sop = $this->entity;
    $handle_id = $this->entity->get('handle_id')->value;
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $bips_options = array();
    $bips_values = $hostclient->get('ipb_id');
    foreach ($bips_values as $value) {
      $ipb_obj = $value->entity;
      if ($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $bips_options[$ipb_obj->id()] = $ipb_obj->id();
      }
    }

    $aips_options = array();
    $aips_values = $sop->get('aips');
    foreach ($aips_values as $value) {
      $aip_obj = $value->entity;
      if ($aip_obj) {
        $aips_options[$aip_obj->id()] = $aip_obj->id();
      }
    }
    $sips_options = array();
    $sips_values = $sop->get('sips');
    foreach ($sips_values as $value) {
      $sip_obj = $value->entity;
      if ($sip_obj) {
        $sips_options[$sip_obj->id()] = $sip_obj->id();
      }
    }
    // hostclients保存
    // 业务IP相关设值.
    $ipb_values = $aips_options;
    $ipb_origin_values = $bips_options;
    $ipb_values = $ipb_values + $ipb_origin_values;
    $entity->set('ipb_id', array_diff($ipb_values, $sips_options));
    // Handle info 状态保存.
    if ($handle_info->handle_action == 1) {
      $entity->set('status', 2);
    }
    $new_handle_info['busi_status'] = 1;
    $new_handle_info['busi_complete_data'] = REQUEST_TIME;
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
    // ----日志保存handel_info----.
    $handle_info->busi_status = $new_handle_info['busi_status'];
    $handle_info->busi_complete_data = $new_handle_info['busi_complete_data'];
    // Hostclient 保存.
    $entity->save();
    // ----------写日志---------.
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info);
    $entity->other_status = 'business_dept_handle';
    HostLogFactory::OperationLog('order')->log($entity, 'update');

    // 运维处理.
    $sop->set('sop_status', 11);
    // 业务完成.
    $sop->set('sop_complete', 1);
    $sop->set('presolving_uid', $this->currentUser()->id());
    $sop->save();
    drupal_set_message($this->t('成功交付技术部!'));
    $form_state->setRedirectUrl(new Url('admin.sop_task.list'));
  }
  /**
   * 业务保存提交表单.
   */
  public function busiSaveSubmit(array &$form, FormStateInterface $form_state) {
    $entity = $hostclient = $this->entity->get('hid')->entity;
    $hostclient_id = $hostclient->id();
    $handle_id = $this->entity->get('handle_id')->value;
    $sop = $this->entity;
    $has_bus_permission = \Drupal::currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = \Drupal::currentUser()->hasPermission('administrator technology sop permission');

    // 有业务权限并且工单状态为0.
    if ($has_bus_permission && ($sop->get('sop_complete')->value == 0 || empty($sop->get('sop_complete')->value))) {
      // 管理iP相关设值.
      $old_ipm_value = $entity->getObjectId('ipm_id');
      $ipm_value = trim($form_state->getValue('mips'));
      $cabinet_server = entity_load('cabinet_server', $ipm_value);
      $entity->set('ipm_id', $cabinet_server->getObjectId('ipm_id'));
      $entity->set('server_id', $cabinet_server->getObjectId('server_id'));
      $entity->set('cabinet_server_id', $ipm_value);
      if (empty($old_ipm_value)) {
        $entity->brfore_save_ipm = array();
      }
      else {
        $entity->brfore_save_ipm = $old_ipm_value;
      }
      // 如果SOP里没有该服务器的添加IP数据并且该服务器为0,1,2，则清空该服务器的业务IP数据。.
      // @todo
      // 这里可能会出现问题,服务器升级时的状态是否会是0，1，2中的一个待进一步查证
      // 如果仅是新服务器上架是有这几个状态，则下面的代码是正确的。
      // 下面的代码解决的问题是系统自动分配后的业务IP自动添加到了服务器的业务IP数据里面了。理论上应该删除这些默认的业务IP。.
      if (empty($sop->get('aips')->value) && in_array($entity->get('status')->value, array(0, 1, 2))) {
        // 取消清空操作
        // 2016-01-27
        // $ipb_values = array();
        // $entity->set('ipb_id', $ipb_values);.
      }
      // 执行保存.
      $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);

      // 增加异步业务IP保存属性.
      $entity->save_ipb_change = $form_state->save_ipb_change;
      // hostclient保存.
      $entity->save();
      // ----------针对Hostclient写日志---------.
      $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info);
      $entity->other_status = 'business_dept_handle';
      HostLogFactory::OperationLog('order')->log($entity, 'update');

      // SOP工单保存.
      $sop->set('aips', $form_state->getValue('aips'));
      $sop->set('sips', $form_state->getValue('sips'));
      $sop->set('sop_op_type', $form_state->getValue('sop_op_type'));
      $sop->set('sop_type', $form_state->getValue('sop_type'));
      $sop->set('description', $form_state->getValue('description'));
      $sop->set('result_description', $form_state->getValue('result_description'));

      // Sop.
      $sop->save();
      drupal_set_message('业务工单保存成功');
    }
    // 业务处理完成.
  }

  /**
   * 业务交付技术验证表单.
   */
  public function busiToTechValidateSubmit(array &$form, FormStateInterface $form_state) {
    $hostclient = $this->entity->get('hid')->entity;
    if (empty($form_state->getValue('aips')) && in_array($hostclient->get('status')->value, array(0, 1, 2))) {
      // error_log(print_r($hostclient->get('status')->value));
      //  $form_state->setErrorByName('aips',$this->t('业务IP不能为空!'));.
    }
  }
  /**
   * 业务保存验证表单.
   */
  public function busiValidateSubmit(array &$form, FormStateInterface $form_state) {
    $handle_id = $this->entity->get('handle_id')->value;
    $hostclient = $this->entity->get('hid')->entity;
    $sop = $this->entity;
    $has_bus_permission = \Drupal::currentUser()->hasPermission('administrator bussiness sop permission');
    $has_tech_permission = \Drupal::currentUser()->hasPermission('administrator technology sop permission');
    // 有业务权限并且工单状态为0
    // if ($has_bus_permission && ($sop->get('sop_complete')->value == 0 || empty($sop->get('sop_complete')->value))) {.
    if ($has_bus_permission) {
      // 验证业务IP
      // 业务验证开始
      // 判断管理IP.
      $ipm_value = trim($form_state->getValue('mips'));
      if (empty($ipm_value)) {
        $form_state->setErrorByName('mips', $this->t('管理IP错误!'));
      }
      else {
        $cabinet_server = entity_load('cabinet_server', $ipm_value);
        if (empty($cabinet_server)) {
          $form_state->setErrorByName('mips', $this->t('管理IP不存在!'));
        }
        else {
          $d_rid = $cabinet_server->getObject('cabinet_id')->getObjectId('rid');
          $rid = $form_state->getValue('room');
          if (!empty($rid) && $rid != $d_rid) {
            $form_state->setErrorByName('mips', $this->t('管理IP所属机房错误!'));
          }
          $product = $hostclient->getObject('product_id');
          $product_type = $product->getObjectId('server_type');
          $ipm_type = $cabinet_server->getObject('server_id')->get('type')->target_id;
          if ($product_type != $ipm_type) {
            $form_state->setErrorByName('mips', $this->t('管理IP分配错误!'));
          }
          else {
            $ipm_obj = $cabinet_server->getObject('ipm_id');
            $ipm_status = $ipm_obj->get('status')->value;
            if ($ipm_status != 1 && $hostclient->getObjectId('ipm_id') != $ipm_obj->id()) {
              $form_state->setErrorByName('mips', $this->t('管理IP的状态错误!'));
            }
          }
        }
      }
      // 标识业务处理是否已完成.
      // @todo 这个字段待处理
      // @todo 停用业务IP的下架处理
      $ipb_aips_values = $form_state->getValue('aips');
      $ipb_sips_values = $form_state->getValue('sips');
      // if(empty($ipb_values) || !empty($ipb_sips_values)) {.
      if ($hostclient->getSimplevalue('trial')) {
        if (count($ipb_aips_values) != 1) {
          $form_state->setErrorByName('aips', $this->t('试用服务器仅能分配一个业务IP!'));
        }
      }
      else {
        // 需要添加的业务IP.
        $ipb_bips_values = $form_state->getValue('bips');
        $b = $this->checkIPdistribution($hostclient, $ipb_aips_values + $ipb_bips_values);
        if (!$b) {
          $form_state->setErrorByName('aips', $this->t('业务IP分配错误,仔细检查IP个数!'));
        }
      }
      $aip_verifies = $this->busiVerifyAipOrSip($sop, $ipb_aips_values, 'aip');
      // 验证管理IP和业务IP是否在一个分组内
      $ipbs = $ipb_aips_values + $ipb_sips_values + $ipb_bips_values;
      $checkIpGroup = $this->checkoutIpGroup($ipm_obj, $ipbs);
      if (!empty($checkIpGroup)) {
        foreach ($checkIpGroup as $key => $row) {
          drupal_set_message($row, 'error');
        }
      }
      $form_state->save_aipb_change = $this->busiVerifyAipOrSip($sop, $ipb_aips_values, 'aip');
      $form_state->save_sipb_change = $ipb_sips_values + $form_state->save_aipb_change['rm'];
      $form_state->save_ipb_change = array(
        'add' => $form_state->save_aipb_change['add'],
        'rm' => $form_state->save_sipb_change,
      );
    }
    // 业务验证结束.
  }
  /**
   * 验证待添加或删除的业务IP.
   *
   * @param $sop
   * @param $ipb_values
   * @param $tag 值是aip, sip
   */
  private function busiVerifyAipOrSip($sop, $ipb_values, $tag) {
    $ipb_add = array();
    $ipb_rm = array();
    if (empty($tag)) {
      return array();
    }
    if ($tag == 'aip') {
      $old_ipb_values = $sop->get('aips')->getValue();
    }// Elseif ($tag == 'sip') {
    // $old_ipb_values = $sop->get('sips')->getValue();
    // }
    foreach ($ipb_values as $key => $value) {
      $b = FALSE;
      foreach ($old_ipb_values as $old_value) {
        if (!empty($old_value) && $value == $old_value['target_id']) {
          $b = TRUE;
          break;
        }
      }
      if (!$b) {
        $ipb_add[] = $value;
      }
    }
    foreach ($old_ipb_values as $old_value) {
      if (empty($old_value['target_id'])) {
        break;
      }
      $b = FALSE;
      foreach ($ipb_values as $key => $value) {
        if ($old_value['target_id'] == $value) {
          $b = TRUE;
          break;
        }
      }
      if (!$b) {
        $ipb_rm[] = $old_value['target_id'];
      }
    }
    $error_ip = array();
    if ($tag == 'aip') {
      foreach ($ipb_add as $ip) {
        $ipb_obj = entity_load('ipb', $ip);
        if ($ipb_obj->get('status')->value != 1) {
          $error_ip = array(
            '%ip' => $ipb_obj->label(),
          );
          break;
        }
      }
    }
    return array(
      'add' => $ipb_add,
      'rm'  => $ipb_rm,
      'error' => $error_ip,
    );
  }
  /**
   * 业务接受工单.
   */
  public function busiAcceptSubmitForm(array $form, FormStateInterface $form_state) {
    $hostclient = $this->entity->get('hid')->entity;
    $handle_id = $this->entity->get('handle_id')->value;

    // 接受工单仅保存当前处理人.
    $this->entity->set('solving_uid', \Drupal::currentUser()->id());
    $this->entity->set('sop_status', 2);
    $this->entity->set('sop_complete', 0);
    $this->entity->save();

    // 处理handle.
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $new_handle_info['busi_uid'] = $this->currentUser()->id();
    $new_handle_info['busi_accept_data'] = REQUEST_TIME;
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);

    // 处理hostclient
    // $hostclient = entity_load('hostclient', $hid);.
    if ($handle_info->handle_action == 1) {
      $hostclient->set('status', 1);
      $hostclient->save();
    }

    // ----------写日志---------.
    $handle_info->busi_uid = $new_handle_info['busi_uid'];
    $handle_info->busi_accept_data = $new_handle_info['busi_accept_data'];
    $hostclient->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array) $handle_info);
    $hostclient->other_status = 'business_dept_accept';
    HostLogFactory::OperationLog('order')->log($hostclient, 'update');

    drupal_set_message('成功接受工单');
  }
  /**
   * 技术检验项.
   */
  private function checkOptions() {
    return array(
      'remote' => t('whether it can remote'),
      'pwd' => t('The password is correct or not'),
      'ip' => t('whether IP is normal'),
      'config' => t('Configuration verification'),
      'port' => t('Port verification'),
    );
  }

  /**
   *
   */
  public static function ipbSearchSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   *
   */
  public static function ipbSearchAjax(array $form, FormStateInterface $form_state) {
    return $form['edit_info']['business_ip']['ipb_search']['ipb_search_wrapper']['ipb_search_content'];
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   * nothing to do.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  private function getIpGroupId($ipm_value) {
    // 管理IP
    $cabinet_server = entity_load('cabinet_server', $ipm_value);
    if (!empty($cabinet_server)) {
      $entity_ipm = $cabinet_server->getObject('ipm_id');
      // group id=1
      $ipm_group_id = $entity_ipm->get('group_id')->value;
    }
  }

  private function checkoutIpGroup($ipm_obj, $ipbs) {
    $business_ips = array();
    $ipm_group_id = $ipm_obj->get('group_id')->value;
    $check = array();
    foreach ($ipbs as $ipb) {
      $ipb_obj = entity_load('ipb', $ipb);
      $ipb_group_id = $ipb_obj->get('group_id')->value;
      if ($ipm_group_id == $ipb_group_id) {
        continue;
      } else {
        $check[$ipb] = $ipb_obj->label();
      }
    }
    return $check;
  }
}
