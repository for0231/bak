<?php

/**
 * @file 
 * Contains \Drupal\question\Controller\QuestionStatisticsController.
 */

namespace Drupal\question\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Returns responses for member routes.
 */
class QuestionStatisticsController extends ControllerBase {

  // @todo 故障处理情况统计  reason: 还没弄明白具体怎么在页面展示。页面表单结构不能准确绘出

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a new SystemController.
   *
   * @param \Drupal\system\SystemManager $systemManager
   *   System manager service.
   *
   * @param \Drupal\Core\Database\Connection
   *   Database service
   */
  public function __construct(SystemManager $systemManager,FormBuilderInterface $form_builder)   {
    $this->systemManager = $systemManager;
    $this->formBuilder   = $form_builder;
  }	

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager'),
      $container->get('form_builder')
    );
  }
  
  /**
   * 对为经手处理的故障进行统计
   *
   */
  public function statistical() {
    
    $build = array();
    $row_arr = array();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildStatisticsHeader(),
    );
    $build['list']['#rows'] = $this->buildMyStatisticsRows(date('Y',REQUEST_TIME),date('m',REQUEST_TIME));
    return $build;
  }

  
  /**
   * 创建表头
   *
   * @return $header array() 
   *   表头
   */
  public function buildStatisticsHeader() {
    $header['id'] = array(
      'data' => $this->t('ID'),
    );
    $header['employee'] = array(
      'data' => $this->t('Employee'),
    );
    $header['category'] = array(
      'data' => $this->t('Category'),
    );
    $header['transferred_out'] = array(
      'data' => $this->t('Transferred out'),
    );
    $header['income'] = array(
      'data' => $this->t('Income'),
    );
     $header['on_time'] = array(
      'data' => $this->t('On time'),
    );
    $header['time_out'] = array(
      'data' => $this->t('Time out'),
    );
    $header['total'] = array(
      'data' => $this->t('Total'),
    );   

    return $header;
  }

  /**
   * 创建行
   *
   * @return $rows  array()
   *  行数据
   */
  public function buildMyStatisticsRows($year,$month) {
    
    $rows = array();
    //所有的员工数组
    $all_employee = \Drupal::service('member.memberservice')->getAllEmployee();
    $i = 0;
    foreach($all_employee as $key=>$employee) {
      $i++;
      $rows[$i] = array(
        'id' => $i,//$category->get('parent')->entity->label(),
        'employee' => $employee->employee_name,
        //'category' => $category->label(),
        /*'transferred_out' => $treansfer_out,
        'income' => $income,
        'on_time' => $on_time,
        'time_out' => $time_out,
        'total' => $total*/
      );
    }
    /*$out=0;
    $in = 0;
    $on = 0;
    $out_time = 0;
    $all = 0;
    // 得到所有的故障分类
    $categorys = entity_load_Multiple('question_class'); 
    $i = 0;
    foreach($categorys as $key=>$category) {
      $i++;
      $treansfer_out = getQuestionService()->getTransferRecordCountByCondition(\Drupal::currentUser()->id(),'out',$category->id(),$year,$month);
      $income = getQuestionService()->getTransferRecordCountByCondition(\Drupal::currentUser()->id(),'in',$category->id(),$year,$month);
      $on_time = getQuestionService()->getTimeOutQuestionCount(\Drupal::currentUser()->id(),'on_time',$category->id(),$year,$month);
      $time_out = getQuestionService()->getTimeOutQuestionCount(\Drupal::currentUser()->id(),'out_time',$category->id(),$year,$month);
      $total = $treansfer_out+$income+$on_time+ $time_out;
      $rows[$i] = array(
        'id' => $i,//$category->get('parent')->entity->label(),
        'type' => $category->label(),
        'transferred_out' => $treansfer_out,
        'income' => $income,
        'on_time' => $on_time,
        'time_out' => $time_out,
        'total' => $total
      );
      $out +=$treansfer_out;
      $in +=$income;
      $on +=$on_time;
      $out_time +=$time_out;
      $all += $total;

    }
    $rows[$i+1] = array(
      'id' => '',
      'type' => t('Total'),
      'transferred_out' => $out,
      'income' => $in,
      'on_time' => $on,
      'time_out' => $out_time,
      'total' => $all
    );*/
    return $rows;
  }
}

