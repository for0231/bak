<?php

/**
 * @file
 * Contains \Drupal\ip\Form\AddBusinessIpForm.
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

class AddBusinessIpForm extends ContentEntityForm {

   /**
   * The custom buainsess ip storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $businessIpStorage;

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
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $business_ip_storage) {
    parent::__construct($entity_manager);
    $this->businessIpStorage = $business_ip_storage;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('ipb')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $form['ip'] = array(
      '#type' => 'textfield',
      '#required' =>TRUE,
      '#title' => 'ip',
      '#size' => 30,
      '#description' => '业务IP。',
      '#default_value' => $this->entity->label()
    );
    $disable = FALSE;  // IP状态控件是否可用的标识
    // 如果该IP正在使用中，则不能修改IP的状态
    if($status == 5) {
      drupal_set_message('This IP is in use, can not be modified.', 'warning');
      $form['#disabled'] = TRUE;
    }
    // 添加IP的时候 不能添加状态为Used的IP
    $ipb_status = ipbStatus();  // 得到业务IP状态数组
    if($entity->isNew()) {
      unset($ipb_status[5]);  //移除状态数组中为Used的项
    }
    $form['status'] = array(
      '#type' => 'select',
      '#title' => t('Status'),
      '#required' =>TRUE,
      '#description' => '业务IP状态。',
      '#default_value' => $status != 0 ? $status : 1,
      '#weight' => 4,
      '#options' => ipbStatus()
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
      '#options' => $room_options,
      '#weight' => 7,
      '#required' => true,
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
    $ip_obj = $this->businessIpStorage->loadByProperties(array('ip' => $ip));
    //判断该实体对象是否是一个新的实体对象
    if($this->entity->isNew() && !empty($ip_obj)){
      $form_state->setErrorByName('ip',$this->t('The ip: %ip has been exists.',array('%ip' => $ip)));
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'insert';
    $message = '添加业务IP成功！';
    if(!$entity->isNew()) {
      $action = 'update';
      $message = '修改业务IP信息成功！';
    }
    $entity->save();
    /** ======================  写入添加业务IP的操作日志 ============*/
    HostLogFactory::OperationLog('ip')->log($entity, $action);
    /**================================================== */
    drupal_set_message($message);
    $form_state->setRedirectUrl(new Url('ip.ipb.admin'));
  }

}
