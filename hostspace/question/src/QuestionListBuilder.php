<?php

/**
 * @file
 * Contains \Drupal\question\QuestionListBuilder.
 */

namespace Drupal\question;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Component\Utility\SafeMarkup;


/**
 * Defines a class to build a listing of business ip entities.
 *
 * @see \Drupal\ip\Entity\IPM.php
 */
class  QuestionListBuilder extends EntityListBuilder {

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
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new ListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *  The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *  The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  The entity query factory.
   */
	  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage,  QueryFactory $query_factory, FormBuilderInterface $form_builder, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->formBuilder = $form_builder;
	  $this->dateFormatter = $date_formatter;
}
	 /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
			$container->get('form_builder'),
			$container->get('date.formatter')
    );
  }

	/**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('question');
    $entity_query->pager(20);

		if(!empty($_SESSION['admin_question_filter'])) {
        //  根据客户 或者 处理人员筛选， 应该先对用户输入字段进行处理
				if(!empty($_SESSION['admin_question_filter']['uid'])){
          $user = \Drupal::service('member.memberservice')->queryUserByName('client',$_SESSION['admin_question_filter']['uid']);
          $client_uid = $user ? $user->uid : $_SESSION['admin_question_filter']['uid'];
					$entity_query->condition('uid',$client_uid,'CONTAINS');
				}
				if(!empty($_SESSION['admin_question_filter']['server_uid'])){
          $user = \Drupal::service('member.memberservice')->queryUserByName('employee',$_SESSION['admin_question_filter']['server_uid']);
          $serverUid = $user ? $user->uid : $_SESSION['admin_question_filter']['server_uid'];
					$entity_query->condition('server_uid',$serverUid,'CONTAINS');
				}
				if(!empty($_SESSION['admin_question_filter']['category'])){
					$entity_query->condition('parent_question_class',$_SESSION['admin_question_filter']['category'],'=');
				}
				if(!empty($_SESSION['admin_question_filter']['status'])){
					$entity_query->condition('status',$_SESSION['admin_question_filter']['status'],'=');
				}
				if(!empty($_SESSION['admin_question_filter']['content'])){
					$entity_query->condition('content__value',$_SESSION['admin_question_filter']['content'],'CONTAINS');
				}
		}

    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $question_id = $entity_query->execute();
    return array_reverse($this->storage->loadMultiple($question_id));
  }


 /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
       'data' => $this->t('ID'),
       'field' => 'id',
       'specifier' => 'id'
    );
    $header['uid'] = array(
       'data' => $this->t('Client'),
       'field' => 'uid',
       'specifier' => 'uid'
    );
    $header['parent_question_class'] = array(
       'data' => $this->t('Category'),
       'field' => 'parent_question_class',
       'specifier' => 'parent_question_class'
    );
    $header['created'] = array(
       'data' => $this->t('Created'),
       'field' => 'created',
       'specifier' => 'created'
    );
    $header['status'] = array(
       'data' => $this->t('Status'),
       'field' => 'status',
       'specifier' => 'status'
    );
    $header['server_uid'] = array(
       'data' => $this->t('Commissioner'),
       'field' => 'server_uid',
       'specifier' => 'server_uid'
    );
  	$header['accept_stamp'] = array(
       'data' => $this->t('Accept time'),
       'field' => 'accept_stamp',
       'specifier' => 'accept_stamp'
     );
		$header['pre_finish_stamp'] = array(
       'data' => $this->t('Estimated time'),
       'field' => 'pre_finish_stamp',
       'specifier' => 'pre_finish_stamp'
     );
		$header['finish_stamp'] = array(
       'data' => $this->t('Finish time'),
       'field' => 'finish_stamp',
       'specifier' => 'finish_stamp'
    );
		$header['time_consuming'] = array(
       'data' => $this->t('Time consuming'),
    );

   return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    //得到当前问题类型处理完成所需要的时间
    $ecxept_time = $entity->get('parent_question_class')->entity->get('limited_stamp')->value;
    // 处理完成实际的消耗时间
    $real_time = ceil( ($entity->get('finish_stamp')->value-$entity->get('accept_stamp')->value)/60 );
    $status_str = $entity->get('status')->value ? questionStatus()[$entity->get('status')->value] : t('Unfinished');
    //判断是否超时  实际消耗的时间大于期望时间  则超时
    if($ecxept_time < $real_time ) {
      $status_str .= ' / <label  style="color:red">'.t('Time out').'</label>';
    }
    $row['id'] = $entity->get('id')->value;

    //申报客户
    $client = \Drupal::service('member.memberservice')->queryDataFromDB('client',$entity->get('uid')->entity->id());
    $row['uid'] = $client ? $client->client_name ? $client->client_name : $entity->get('uid')->entity->getUsername() : $entity->get('uid')->entity->getUsername();

    $row['parent_question_class'] = $entity->get('parent_question_class')->entity->label();
    $row['created'] =  format_date($entity->get('created')->value, 'custom', 'Y-m-d H:i:s');
    $row['status'] = SafeMarkup::format($status_str, array());

    //故障的负责专员
    if($user = $entity->get('server_uid')->entity) {
      $emp = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$user->id());
      $server_user = $emp ? $emp->employee_name : $user->getUsername();
    }
    $row['server_uid'] = isset($server_user) ? $server_user : '--';

    $row['accept_stamp'] = $entity->get('accept_stamp')->value ? date('Y-m-d H:i',$entity->get('accept_stamp')->value) : t('It was not yet accepted');
		$row['pre_finish_stamp'] = $entity->get('pre_finish_stamp')->value ? date('Y-m-d H:i',$entity->get('pre_finish_stamp')->value) : t('It was not yet accepted');
    $row['finish_stamp'] = $entity->get('finish_stamp')->value ? format_date($entity->get('finish_stamp')->value, 'custom', 'Y-m-d H:i:s') : t('Unfinished');
		$row['time_consuming'] = $entity->get('finish_stamp')->value ? $real_time.' min' : '--';
		return $row + parent::buildRow($entity);
  }

	/**
   * {@inheritdoc}
   */
  public function render() {
    $build['admin_ip_filter'] = $this->formBuilder->getForm('Drupal\question\Form\QuestionFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No question data to show.');
    return $build;
  }
}

