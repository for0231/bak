<?php

/**
 * @file
 * Contains \Drupal\contract\Form\ContractUserForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
class ContractUserForm extends ContentEntityForm {

	 /**
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;	

	/**
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The custom block storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $entity_storage) {
    parent::__construct($entity_manager);
    $this->entityStorage = $entity_storage;
  }
 	
	 /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('host_contract')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {   
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $form['type'] = array(
      '#type' => 'select',
      '#options' => contractUserStatus(),
      '#title' => '客户类型',
      '#required' => true,
      '#weight' => 2,
      '#default_value' => $entity ? $entity->get('type')->value : ''
    );

    $form['contact'] = array(
      '#type' => 'fieldset',
      '#title' => '主要联系人',
      '#weight' => 20
    );
    $form['contact']['contract_user'] = array(
      '#type' => 'textfield',
      '#title' => '联系人',
      '#default_value' => $entity ? $entity->get('contact')->value : '',
    );
    $form['contact']['mobile'] = array(
      '#type' => 'textfield',
      '#title' => '联系电话',
      '#default_value' => $entity ? $entity->get('mobile')->value : '',
    );
    $form['contact']['contract_email'] = array(
      '#type' => 'email',
      '#title' => 'E-mail',
      '#default_value' => $entity ? $entity->get('email')->value : '',
    );
    return $form;
  }


 /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $type = $form_state->getValue('type');
    $contact = $form_state->getValue('contract_user');
    $mobile = $form_state->getValue('mobile');
    $email = $form_state->getValue('contract_email');
    
    $entity->set('type', $type);
    $entity->set('contact', $contact);
    $entity->set('mobile', $mobile);
    $entity->set('email', $email);
    $entity->set('uid', \Drupal::currentUser()->id());
    
    $entity->save();
 
    drupal_set_message('客户创建成功！');
    $form_state->setRedirectUrl(new Url('contract.user.admin'));
    
  }

}
