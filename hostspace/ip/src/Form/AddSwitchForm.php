<?php

/**
 * @file
 * Contains \Drupal\ip\Form\AddSwitchForm.
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
 * Provide a form controller for switch add.
 */

class AddSwitchForm extends ContentEntityForm {

 /**
   * The custom switch ip storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $switchIpStorage;

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
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $switch_ip_storage) {
    parent::__construct($entity_manager);
    $this->switchIpStorage = $switch_ip_storage;
  }

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('ips')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if($this->entity->get('status_equipment')->value == 'on') {
      drupal_set_message('This IP is in use, can not be modified.', 'warning');
      $form['#disabled'] = true;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  //得到输入的IP
  $ip =trim($form_state->getValue('ip')[0]['value']);

  //判断用户是否输入类IP
  if(empty($ip)){
      $form_state->setErrorByName('ip',$this->t('Please fill out ip.'));
    }

    //判断IP格式是否正确
  if(strcmp(long2ip(sprintf("%u",ip2long($ip))),$ip)) {
    $form_state->setErrorByName('ip',$this->t('Please comfire the IP:%ip.', array('%ip' => $ip)));
  }
  
  //根据输入的IP尝试加载业务IP实体，若存在则提示错误信息
  $ip_obj = $this->switchIpStorage->loadByProperties(array('ip' => $ip));
    
  //判断该实体对象是否是一个新的实体对象
  if($this->entity->isNew() && !empty($ip_obj)){
    $form_state->setErrorByName('ip',$this->t('The switch ip: %ip has been exists.',array('%ip' => $ip)));
  }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'insert';
    $message = '添加交换机IP成功！';
    if(!$entity->isNew()){ 
      $action = 'update';
      $message = '修改交换机IP信息成功！';
    }
    $entity->save();
    /** ======================  写入添加交换机IP的操作日志 ============= */  
    HostLogFactory::OperationLog('ip')->log($entity, $action);
    /**================================================== */
    drupal_set_message($message);
    $form_state->setRedirectUrl(new Url('ip.ips.admin'));

  }

}
