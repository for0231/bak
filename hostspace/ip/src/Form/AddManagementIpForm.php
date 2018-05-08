<?php

/**
 * @file
 * Contains \Drupal\ip\Form\AddManagementIpForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for management ip add.
 */

class AddManagementIpForm extends ContentEntityForm {

   /**
   * The custom management ip storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $managementIpStorage;

  /**
   * Constructs a businessIp object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $business_ip_storage
   *   The custom block storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $management_ip_storage) {
    parent::__construct($entity_manager);
    $this->managementIpStorage = $management_ip_storage;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('ipm')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['ip'] = array(
      '#type' => 'textfield',
      '#required' =>TRUE,
      '#title' => 'ip',
      '#size' => 30,
      '#description' => '管理IP。',
      '#default_value' => $this->entity->label()
    );

    $status= $this->entity->get('status')->value;

    $disable = FALSE;  // IP状态控件是否可用的标识
    // 如果该IP正在使用中，则不能修改IP的状态
    if($status == 5) {
      drupal_set_message('This IP is in use, can not be modified.', 'warning');
      $form['#disabled'] = TRUE;
    }
    $form['status'] = array(
      '#type' => 'select',
      '#title' => t('Status'),
      '#required' =>TRUE,
      '#default_value' => $status != 0 ? $status : 1,
      '#weight' => 4,
      '#description' => '管理IP状态。',
      '#options' =>ipmStatus()
    );

    $form['server_type'] = array(
      '#type' => 'select',
      '#title' => t('Server type'),
      '#required' =>TRUE,
      '#description' => '管理IP类型。',
      '#default_value' => $this->entity->get('server_type')->value,
      '#weight' => 5,
      '#options' => ip_server_type()
    );

    $entity_rooms = entity_load_multiple('room');
    if($this->entity->isNew()) {
      $room_options = array('' => '-选择-');
    } else {
      $room_options = array();
    }
    foreach ($entity_rooms as $row) {
      $room_options[$row->id()] = $row->label();
    }
    $form['rid'] = array(
      '#type' => 'select',
      '#title' => '所属机房',
      '#required' => true,
      '#options' => $room_options,
      '#weight' => 7,
      '#ajax' => array(
        'callback' => array(get_class($this), 'groupItem'),
        'wrapper' => 'group_item_wrapper',
        'method' => 'html'
      ),
      '#default_value' => $this->entity->get('rid')->value
    );
    $form['content'] = array(
      '#type' => 'container',
      '#weight' => '8',
      '#id' => 'group_item_wrapper'
    );
    $rid = empty($form_state->getValue('rid')) ? $this->entity->get('rid')->value : $form_state->getValue('rid');
    if(!empty($rid)) {
      $group_options = array('' => '-选择-');
      $groups = \Drupal::service('ip.ipservice')->loadIpGroup(array('rid' => $rid));
      foreach($groups as $group) {
        $group_options[$group->gid] = $group->name;
      }
      $form['content']['group_id'] = array(
        '#type' => 'select',
        '#title' => '所属分组',
        '#required' => true,
        '#options' => $group_options,
        '#default_value' => $this->entity->get('group_id')->value
      );
    } else {
      $form['content']['group_id'] = array();
    }
    return $form;
  }

  /**
   * 业务操作回调函数
   */
  public static function groupItem(array $form, FormStateInterface $form_state) {
    return $form['content']['group_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    //得到输入的IP
    $ip =trim($form_state->getValue('ip'));

     //判断用户是否输入类IP
    if(empty($ip)){
      $form_state->setErrorByName('ip',$this->t('Please fill out ip.'));
    }

    //判断IP格式是否正确
    if(strcmp(long2ip(sprintf("%u",ip2long($ip))),$ip)) {
      $form_state->setErrorByName('ip',$this->t('Please comfire the IP:%ip.', array('%ip' => $ip)));
    }

    //根据输入的IP尝试加载业务IP实体，若存在则提示错误信息
    $ip_obj = $this->managementIpStorage->loadByProperties(array('ip' => $ip));

    //判断该实体对象是否是一个新的实体对象
    if($this->entity->isNew() && !empty($ip_obj)){
      $form_state->setErrorByName('ip',$this->t('The management ip: %ip has been exists.',array('%ip' => $ip)), array('%ip' => $ip));
    }
  }



  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'insert';
    $message = '添加管理IP成功！';

    if(!$entity->isNew()){
      $action = 'update';
      $message = '修改管理IP信息成功！';
    }
    $entity->save();
    /** ======================  写入添加管理IP的操作日志 ============*/
    HostLogFactory::OperationLog('ip')->log($entity, $action);
    /**================================================== */
    drupal_set_message($message);
    $form_state->setRedirectUrl(new Url('ip.ipm.admin'));
  }

}
