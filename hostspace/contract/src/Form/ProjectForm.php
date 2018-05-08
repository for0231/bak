<?php

/**
 * @file
 * Contains \Drupal\contract\Form\ProjectForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectForm extends ContentEntityForm {

	 /**
   * The custom buainsess ip storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;	

	/**
   * Constructs a businessIp object.
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
      $entity_manager->getStorage('host_project')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $users = entity_load_multiple('contract_user');
    if(!empty($users)) {
      foreach($users as $user) {
        $ops[$user->id()] = $user->label();
      }
    }    
    $form['client'] = array(
      '#type' => 'select',
      '#options' => isset($ops) ? ( array('' => '选择客户') +$ops) : array(),
      '#required' => TRUE,
      '#title' => '客户',
      '#weight' => 3,
      '#default_value' => $this->entity->get('client')->entity ? $this->entity->get('client')->entity->id() : '' 
    );
 
    $form['date'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
        
    // 时间
    $form['date']['start'] = array(
    	'#title' => '开始时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#default_value' => $this->entity->getProjectproperty('begin_time') ? format_date($this->entity->getProjectproperty('begin_time'), 'custom', 'Y-m-d') : '',
      '#required' =>TRUE,
    );
    $form['date']['expire'] = array(
    	'#title' => '结束时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#default_value' => $this->entity->getProjectproperty('end_time') ? format_date($this->entity->getProjectproperty('end_time'), 'custom', 'Y-m-d') : '',
      '#required' =>TRUE,
    );
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');
    $form['type'] = array(
    	'#title' => '项目类型',
    	'#type' => 'select',
      '#default_value' => $this->entity->getProjectproperty('type'),
      '#options' => ip_server_type(),
      '#required' =>TRUE,
      '#weight' => 3
    );

    if(!$this->entity->isNew()) {
      $form['status'] = array(
        '#type' => 'select',
        '#title' => '执行状态',
        '#default_value' => $this->entity->getProjectproperty('status'),
        '#options' => projectStatus(),
        '#weight' => 2
      ); 
    }

    return $form;
  }
  public function creatProjextID() {
    $num_id = 'HSP';
    $dingdanhao = date("i-s");
    $num_id .= str_replace("-","",$dingdanhao);
    $num_id .= rand(1,9999);
    return $num_id;
  }
 /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $start = $form_state->getValue('start');
    $expire = $form_state->getValue('expire');
    $status = $form_state->getValue('status');
    $client = $form_state->getValue('client');
    
    $entity->set('code', $this->creatProjextID());
    $entity->set('uid', \Drupal::currentUser()->id());
    $entity->set('begin_time', strtotime($start));
    $entity->set('end_time', strtotime($expire));
    $entity->set('status', $status ? $status : 1);
    $entity->set('client', $client);
    
    $entity->save();
 
    drupal_set_message('项目添加成功！');
    $form_state->setRedirectUrl(new Url('project.admin'));
    
  }

}
