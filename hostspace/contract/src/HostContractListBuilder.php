<?php

/**
 * @file 
 * Contains \Drupal\contract\HostContractListBuilder.
 */

namespace Drupal\contract;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

class HostContractListBuilder extends EntityListBuilder {
  	/**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory.
   */
   protected $queryFactory;

   /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
   protected $formBuilder;

  /**
   * Constructs a new BusinessIpListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *  The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *  The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  The entity query factory.
   */
	  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage,  QueryFactory $query_factory, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->formBuilder = $form_builder;
  }

	/**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
			$container->get('form_builder')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('host_contract');
    $entity_query->pager(20);	
    // 筛选合同
    if(!empty($_SESSION['admin_contract_filter'])) {
      if(!empty($_SESSION['admin_contract_filter']['status'])) {
        $entity_query->condition('status',$_SESSION['admin_contract_filter']['status'],'=');
      }
      if(!empty($_SESSION['admin_contract_filter']['name'])) {
        $entity_query->condition('name',$_SESSION['admin_contract_filter']['name'],'CONTAINS');
      }
    }
		$header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }

 /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
       'data' => '合同ID',
       'field' => 'id',
       'specifier' => 'id'
    );
    $header['name'] = array(
       'data' => '合同名称',
       'field' => 'name',
       'specifier' => 'name'
    );
    $header['code'] = array(
       'data' => '合同编号',
       'field' => 'code',
       'specifier' => 'code'
    );
    $header['amount'] = array(
       'data' => '合同金额',
       'field' => 'amount',
       'specifier' => 'amount'
    );
    $header['client'] = array(
       'data' => '客户名称',
       'field' => 'client',
       'specifier' => 'client'
    );
    $header['project'] = array(
       'data' => '所属项目',
       'field' => 'project',
       'specifier' => 'project'
    );
    
    $header['type'] = array(
       'data' => '合同类别',
       'field' => 'type',
       'specifier' => 'type'
    );
   	$header['creator'] = array(
       'data' => '建立人',
       'field' => 'uid',
       'specifier' => 'uid'
     );
		$header['created'] = array(
       'data' => '建立时间',
       'field' => 'created',
       'specifier' => 'created'
    );
		$header['status'] = array(
       'data' => '合同状态',
       'field' => 'status',
       'specifier' => 'status'
    );

   return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name']['data'] = array(
      '#type' => 'link',
      '#title' => $entity->getproperty('name'),
      '#url' => new Url('entity.host_contract.edit_form', array('host_contract'=>$entity->id())),
    ); 
    $row['code'] = $entity->label();
    $row['amount'] = $entity->getproperty('amount');
    $row['client'] = $entity->getPropertyObject('client')->label(); 
    $row['project'] = $entity->getPropertyObject('project')->getProjectproperty('name'); 
    $row['type'] = $entity->getPropertyObject('type')->label(); 
    $row['uid'] = getEmployeeName($entity->getPropertyObject('uid')->id());
		$row['created'] = format_date($entity->getproperty('created'), 'custom', 'Y-m-d');
    $status = $entity->getproperty('status') ? $entity->getproperty('status') : 1;
    $row['status'] = contractStatus()[$status];

		return $row + parent::buildRow($entity);
  }

	/**
   * {@inheritdoc}
   */
  public function render() {
    $build['admin_contract_filter'] = $this->formBuilder->getForm('Drupal\contract\Form\ContractFilterForm');
    $build += parent::render();   
    $build['table']['#tableselect'] = TRUE;
    $build['table']['#empty'] = $this->t('No data to show.');
    return $build;
  }
  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $status = $entity->getProperty('status');
    if($entity->access('execute') && $entity->hasLinkTemplate('execute-form') && $status == 1) {
      $operations['execute'] = array(
        'title' => '执行合同',
        'weight' => -5,
        'url' => $entity->urlInfo('execute-form'),
      );
    }
    if($entity->access('stop') && $entity->hasLinkTemplate('stop-form') && in_array($status, array(1, 2))) {
      $operations['stop'] = array(
        'title' => '终止合同',
        'weight' => -4,
        'url' => $entity->urlInfo('stop-form'),
      );
    }
    if($entity->access('complete') && $entity->hasLinkTemplate('complete-form') && in_array($status, array(1, 2))) {
      $operations['complete'] = array(
        'title' => '结束合同',
        'weight' => -4,
        'url' => $entity->urlInfo('complete-form'),
      );
    }

    return $operations;
  }
  

}

         
