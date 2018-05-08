<?php
/**
 * @file
 * Contains \Drupal\member\EmployeeListBuilder.
 */

namespace Drupal\member;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmployeeListBuilder {

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

  private function load() {
    $condition = $this->filterEmployee();
    // 得到所有的员工数据
    $employee_arr = \Drupal::service('member.memberservice')->getAllMember('user_employee_data',$condition,$this->builEmployeeHeader());
    return $employee_arr;
  }

  /**
   * 创建员工表头
   */
  private function builEmployeeHeader() {
    $header['Username'] = array(
      'data' => t('User name'),
      'field' => 'name',
      'specifier' => 'name'
    );
    $header['employee_name'] = array(
      'data' => t('Real name'),
      'field' => 'employee_name',
      'specifier' => 'employee_name'
    );
    $header['department'] = array(
      'data' => t('Department'),
      'field' => 'department',
      'specifier' => 'department'
    );
    $header['position'] = array(
      'data' => t('Position'),
      'field' => 'position',
      'specifier' => 'position'
    );
    $header['email'] = array(
      'data' => t('邮件'),
    );
    $header['phone'] = array(
      'data' => t('Phone'),
    );
    $header['tencent'] = array(
      'data' => t('QQ'),
    );
    $header['operations'] = array(
      'data' =>t('Operations'),
    );

   return $header ;
  }

  /**
   * 构建筛选条件
   * @return $condition array
   *   构建好的条件数组
   */
  private function filterEmployee() {
    $condition = array();
    if(!empty($_SESSION['admin_employee_filter'])) {
		  if(!empty($_SESSION['admin_employee_filter']['name'])){
        $condition['ufd.name'] = array('field' => 'ufd.name', 'op' => 'LIKE', 'value' => '%'. $_SESSION['admin_employee_filter']['name']. '%');
			}
			if(!empty($_SESSION['admin_employee_filter']['employee_name'])){
				 $condition['mem.employee_name']  = array('field' =>'mem.employee_name', 'op' => 'LIKE', 'value' => '%'. $_SESSION['admin_employee_filter']['employee_name'] . '%');	
  		}		
			if(!empty($_SESSION['admin_employee_filter']['department'])){
				 $condition['mem.department']  = array('field' => 'mem.department', 'op' => '=', 'value' => $_SESSION['admin_employee_filter']['department']);
			}		
		}
    return $condition;
  }

  /**
   * 构建行数据
   *
   * @param $employee_arr array()
   *
   * @return $rows_arr
   *   构建好的行数据
   */
  private function buildRow($employee_arr) {
    $rows_arr = array();
    if($employee_arr) {
      foreach($employee_arr as $employee) {
        $rows_arr[$employee->uid] = array(
          'Username' => $employee->name,
          'Real name' => $employee->employee_name,
          'Department' => $employee->department ? entity_load('taxonomy_term', $employee->department)->label() : '',
          'Position' => $employee->position ? entity_load('taxonomy_term', $employee->position)->label() : '',
          'email' => $employee->mail,
          'phone' => $employee->telephone,
          'tencent' => $employee->qq,
        );
        $rows_arr[$employee->uid]['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $this->getOp($employee->uid)
        );
     }
   } else {
     drupal_set_message(t('There have no data to show.'), 'warning');
   }
   return $rows_arr;
  }

  /**
   * 构建操作的链接数组
   *
   * @param $editUrl
   *   编辑用户所指向的routing_name
   *
   * @param $deleteUal
   *   删除用户所指向的routing_name
   *
   * @return 组装好的Operations数组
   */
  private function getOp($uid) {
    $op = array();
    $op['Edit'] = array(
      'title' => t('Edit'),
      'url' => new Url('entity.user.edit_form', array('user' => $uid))
    );
    $op['Delete'] = array(
      'title' => t('Delete'),
      'url' => new Url('entity.user.cancel_form',array('user' => $uid))
    );
    return $op;
  }

  /**
   * 渲染
   */
  public function render() {
    
    //得到所有的员工数据
    $employee_arr = $this->load();

    //加载显示筛选条件的表单
    $build['filter'] = $this->formBuilder->getForm('Drupal\member\Form\EmployeeFilterForm');

    //构建表结构
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->builEmployeeHeader(),
      '#rows' => $this->buildRow($employee_arr)
    );
    $build['employee_pager']['#type'] = 'pager';
    return $build;
  }


}
