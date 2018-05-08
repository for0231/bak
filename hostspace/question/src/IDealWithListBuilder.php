<?php

/**
 * @file 
 * Contains \Drupal\question\IDealWithListBuilder.
 */

namespace Drupal\question;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IDealWithListBuilder {
   /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
			$container->get('form_builder')
    );
  }

  /**
   * 创建表头
   */
  private function buildMyQuestionHeader() {
    $header['id'] = array(
      'data' => t('ID'),
      'field' => 'id',
      'specifier' => 'id'
    );
    $header['uid'] = array(
      'data' => t('Client'),
      'field' => 'uid',
      'specifier' => 'uid'
    );
    $header['parent_question_class'] = array(
      'data' => t('Category'),
      'field' => 'parent_question_class',
      'specifier' => 'parent_question_class'
    );
    $header['created'] = array(
      'data' => t('Created'),
      'field' => 'created',
      'specifier' => 'created'
    );
    $header['status'] = array(
      'data' => t('Status'),
      'field' => 'status',
      'specifier' => 'status'
    );
  	$header['accept_stamp'] = array(
      'data' => t('Accept time'),
      'field' => 'accept_stamp',
      'specifier' => 'accept_stamp'
    );
		$header['pre_finish_stamp'] = array(
      'data' => t('Estimated time'),
      'field' => 'pre_finish_stamp',
      'specifier' => 'pre_finish_stamp'
    );
		$header['finish_stamp'] = array(
      'data' => t('Finish time'),
      'field' => 'finish_stamp',
      'specifier' => 'finish_stamp'
    );
		$header['time_consuming'] = array(
      'data' => t('Time consuming'),
    );
    $header['operations'] = array(
      'data' => t('Operations'),
    );

   return $header ;
  }

  /** 
   * 表单筛选条件
   */
  private function filterForm() {
    $condition = array();
      if(!empty($_SESSION['my_question_filter'])) {
		    if(!empty($_SESSION['my_question_filter']['uid'])){
          $str = $_SESSION['my_question_filter']['uid'];
          $user = \Drupal::service('member.memberservice')->queryUserByName('client',$str);
          $client_uid = $user ? $user->uid : '25';
			  	$condition['uid'] = $client_uid;
		    }
		  	if(!empty($_SESSION['my_question_filter']['category'])){
         $condition['parent_question_class'] =  $_SESSION['my_question_filter']['category'];
		  	}	
        if(!empty($_SESSION['my_question_filter']['status'])){
				  $condition['status'] = $_SESSION['my_question_filter']['status'];
		  	}		
		  	if(!empty($_SESSION['my_question_filter']['content'])){
			    $condition['content__value'] = $_SESSION['my_question_filter']['content'];
			  }		
    }

    return $condition;

  }
  
  /**
   * 构建行数据
   */
  private function createRow($header) {
    $condition = $this->filterForm();
    $row_arr = array();
    $all_question = getQuestionService()->getQuestionByEmployee(\Drupal::currentUser()->id(), $condition,$header);
       
    foreach($all_question as $key=>$question) {
      //申报客户
      $client = \Drupal::service('member.memberservice')->queryDataFromDB('client',$question->uid);
      //得到当前问题类型处理完成所需要的时间       
      $ecxept_time = entity_load('question_class',$question->parent_question_class)->get('limited_stamp')->value;
      // 处理完成实际的消耗时间
      $real_time = ceil(($question->finish_stamp - $question->accept_stamp)/60 );
      // 状态显示处理 
      $status_str = $question->status ? questionStatus()[$question->status] : t('Unfinished');
      //判断是否超时  实际消耗的时间大于期望时间  则超时
      if($ecxept_time < $real_time ) {
        $status_str .= ' / <label style="color:red">'.t('Time out').'</label>';
      }
      $row_arr[$question->id] = array(
        'ID' => $question->id,
        'Client' =>$client ? $client->name : entity_load('user',$question->uid)->getUsername(),
        'Category' => entity_load('question_class',$question->parent_question_class)->label(), 
        'creat' => date('Y-m-d H:i',$question->created),
        'Status' => SafeMarkup::format($status_str, array()) ,
        'Accept time' => date('Y-m-d H:i',$question->accept_stamp),
        'Estimated time' => date('Y-m-d H:i',$question->pre_finish_stamp),
        'Finish time' => $question->finish_stamp ? date('Y-m-d H:i',$question->finish_stamp) : t('Unfinished'),
        'Time consuming' => $question->finish_stamp ? $real_time.' min' : '--'
      ); 
      $row_arr[$question->id]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $this->getOpArr($question->id) 
     );
    }
    return $row_arr;
  }

  /**
   * 构建操作的链接数组
   *
   * @param $question_id
   *   问题编号
   *
   * @return 组装好的Operations数组
   */
  private function getOpArr($question_id) {
    $op = array();
    $op['Edit'] = array(
      'title' => 'Edit',
      'url' => new Url('question.detail_form', array('question' => $question_id))
    );
    return $op;
  }


  /**
   * 渲染表格
   */
  public function render() {
    $build['admin_my_question_filter'] = $this->formBuilder->getForm('Drupal\question\Form\MyQuestionFilterForm');
    
    $header = $this->buildMyQuestionHeader();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $this->createRow($header),
      '#empty' => t('There have no question data to show.')
    );
    $build['my_question_pager'] = array('#type' => 'pager');

    return $build;
  }
}

