<?php
/**
 * @file
 * Contains \Drupal\member\ClientListBuilder.
 */

namespace Drupal\member;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ClientListBuilder {
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
   * 加载要显示的数据
   */
  private function load() {
    $condition = $this->filterClient();
    // 得到选择的角色条件
    $role = isset($condition['role']) ? $condition['role'] : '';
    // 移除条件数组中的角色条件
    unset($condition['role']);
    // 得到所有的客户数据
    $client_arr =\Drupal::service('member.memberservice')->getAllClient($condition,$this->builClientHeader(), $role);
    return $client_arr;
  }

  /**
   * 创建客户表头
   */
  private function builClientHeader() {
    $header['Username'] = array(
      'data' => t('Username'),
      'field' => 'name',
      'specifier' => 'name'
    );
    $header['client_name'] = array(
      'data' => t('Real name / Nick'),
      'field' => 'client_name',
      'specifier' => 'cleint_name'
    );
    $header['email'] = array(
      'data' => t('Email'),
      'field' => 'mail',
      'specifier' => 'mail'
    );
    $header['corporate_name'] = array(
      'data' =>t('Company\'s name'),
      'field' => 'corporate_name',
      'specifier' => 'corporate_name'
    );
    $header['client_type'] = array(
      'data' => t('Type'),
      'field' => 'client_type',
      'specifier' => 'client_type'
    );
    $header['reg_date'] = array(
      'data' => t('Created'),
      'field' => 'created',
      'specifier' => 'created'
    );
    $header['Agent'] = array(
      'data' => t('Agent'),
    );
    $header['operations'] = array(
      'data' => t('Operations'),
    );

   return $header ;
  }

  /**
   * 构建筛选条件
   * @return $condition array
   *   构建好的条件数组
   */
  private function filterClient() {
    $condition = array();
    if(!empty($_SESSION['admin_client_filter'])) {
			if(!empty($_SESSION['admin_client_filter']['name'])){
        $condition['ufd.name']= array('field' => 'ufd.name', 'op' => 'LIKE', 'value' => '%'.$_SESSION['admin_client_filter']['name'].'%');
			}
			if(!empty($_SESSION['admin_client_filter']['client_name'])){
				$condition['mem.client_name']= array('field' => 'mem.client_name', 'op' => 'LIKE', 'value' => '%'.$_SESSION['admin_client_filter']['client_name'].'%');
		  }
			if(!empty($_SESSION['admin_client_filter']['mail'])){
				$condition['ufd.mail']= array('field' => 'ufd.mail', 'op' => 'LIKE', 'value' => '%'.$_SESSION['admin_client_filter']['mail'].'%');
			}
      if(!empty($_SESSION['admin_client_filter']['role'])){
				 $condition['role']= $_SESSION['admin_client_filter']['role'];
			}
      if(!empty($_SESSION['admin_client_filter']['corporate_name'])){
			  $condition['mem.corporate_name']= array('field' => 'mem.corporate_name', 'op' => 'LIKE', 'value' => '%'.$_SESSION['admin_client_filter']['corporate_name'].'%');
			}
			if(!empty($_SESSION['admin_client_filter']['client_type'])){
			  $condition['mem.client_type']= array('field' => 'mem.client_type', 'op' => '=', 'value' => $_SESSION['admin_client_filter']['client_type']);
			}
		}
    return $condition;
  }

  /**
   * 构建行‘
   *
   * @param $client_arr array
   *   所有的客户数据
   *
   * @return $rows_arr array
   *   构建好的行数据
   */
  private function buildRow($client_arr) {
    $rows_arr = array();
    // build row
    if($client_arr) {
      foreach($client_arr as $client) {
        //得到用户所属的角色集合
        $client_role = entity_load('user', $client->uid)->getRoles();
        $role_arr = array();
        foreach($client_role as $role) {
          if($role != 'authenticated') {
            $role_entity = entity_load('user_role', $role);
            $role_arr[] = $role_entity ? $role_entity->label() : '';
          }
        }
        //给表格绑行
        $rows_arr[$client->uid] = array(
           'Username' => $client->name,
           'Real name' => $client->client_name,
           'Email' => $client->mail,
           'Company\'s name' => $client->corporate_name ,
           'type' => clientType()[$client->client_type] ,
           'reg_date' => format_date( $client->created , 'custom', 'Y-m-d H:i')
        );
        //绑定用户角色列
        $rows_arr[$client->uid]['Agent']['data'] = array(
           '#theme' => 'item_list',
           '#items' => $role_arr
        );
        //给每一行绑定操作按钮
        $rows_arr[$client->uid]['operations']['data'] = array(
           '#type' => 'operations',     
           '#links' => $this->getOp($client->uid) 
        );
        //清空角色数组搜索
        $role_arr = array();
      }
    }else {
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
      'title' => 'Edit',
      'url' => new Url('entity.user.edit_form', array('user' => $uid))
    );
    $op['Delete'] = array(
      'title' => 'Delete',
      'url' => new Url('entity.user.cancel_form',array('user' => $uid))
    );
    // 原有的额度  若该用户还未设置信用额度 并且当前操作用户有相应的权限 则显示设置信用额度的菜单按钮
    $credit = \Drupal::service('member.memberservice')->getClientCredit($uid);
    if(!$credit &&  \Drupal::currentUser()->hasPermission('administer credit adjust')) {
      $op['set'] = array(
        'title' => t('Set credit'),
        'url' => new Url('member.founds.credit.set',array('user' => $uid))
      );
    }
    if (\Drupal::currentUser()->hasPermission('administer sudo users')) {
      $op['sudo'] = array(
        'title' => t('Sudoer'),
        'url' => new Url('member.sudoer.login', array('user' => $uid)),
      );
    }
    return $op;
  }

  /**
   * 表单渲染
   */
  public function render() {

    //得到所有的员工数据
    $client_arr = $this->load();
    //加载显示筛选条件的表单
    $build['filter'] = $this->formBuilder->getForm('Drupal\member\Form\ClientFilterForm');
    //构建表结构
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->builClientHeader(),
      '#rows' => $this->buildRow($client_arr),
      '#empty' => t('There have no data to show.')
    );
     $build['list_pager']['#type'] = 'pager';
    return $build;
  }

}
