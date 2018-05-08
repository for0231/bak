<?php

/**
 * @file
 * Contains \Drupal\member\Controller\MemberController.
 */

namespace Drupal\member\Controller;

use Drupal\member\EmployeeListBuilder;
use Drupal\member\ClientListBuilder;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\member\Form\CreditAdminForm;
use Drupal\member\MemberRechargeListBuilder;
use Drupal\member\Form\RechargeRecordForm;

/**
 * Returns responses for member routes.
 */
class MemberController extends ControllerBase {

  /**
   * 构建员工管理列表
   *
   * @return 员工列表
   */
  public function employeeList() {
    $employee_list = EmployeeListBuilder::createInstance(\Drupal::getContainer());
    return  $employee_list->render();
  }


  /**
   *构建会员管理列表
   *
   * @return 列表
   */
  public function clientList(){
    $client_list = ClientListBuilder::createInstance(\Drupal::getContainer());
    return  $client_list->render();
  }

  public function creditAdmin() {
    $list = CreditAdminForm::createInstance(\Drupal::getContainer());
    return  $list->render();
  }

  /**
   * 管理切换到客户登录
   */
  public function sudoClientLogin($user) {
    $from = \Drupal::currentUser();
    $to = user_load($user);
    //$to =  entity_load('user', $user);
    if($to->get('user_type')->value != 'client') {
      // 返回一个错误
      return array('#markup' => '目标客户类型错误');
    }
    else {
      $session = \Drupal::service('session');
      $from_uid = $session->get('su_from_id');
      if (!empty($from_uid)) {
        $session->set('su_from_id', '');
      }
      $session->set('su_from_id', $from_uid);
      user_login_finalize($to);
      return $this->redirect('<front>');
    }
  }

  //=============前台请求===========

  /**
   * 会员中心首页
   */
  public function member_center() {
    $build['member_center'] = array(
      '#theme' => 'member_center',
      '#user' => \Drupal::currentUser()
    );
   $build['#attached']['library'] = array('member/member.center');
   return $build;
  }

  /**
   * 我的账户信息
   */
  public function myAccount() {
    $build['my_account'] = array(
      '#theme' => 'my_account',
      '#user_id' => \Drupal::currentUser()->id()
    );
    $build['#attached']['library'] = array('member/member.alarm');
    return $build;
  }
  /**
   * 安全信息
   */
  public function safeInfo() {
    $build['safe_info'] = array(
      '#theme' => 'my_safe_info',
      '#user_id' => \Drupal::currentUser()->id()
    );
    return $build;
  }

  /**
   * 我的基本信息
   */
  public function basinInfo() {
    $account =  entity_load('user', \Drupal::currentUser()->id());
    if($account->get('user_type')->value == 'client') {
      $build = array(
        '#theme' => 'member_information',
        '#member' => $account,
      );
    }else {
      $build['member_infor'] = array(
        '#theme' => 'employee_information',
        '#employee' => $account,
      );
    }

    return $build;
  }
  /**
   * 修改个人资料
   */
 public function editInfo() {
   return array('#theme' => 'user_form');
  }

  public function childAccount() {
    return array(
      '#theme' => 'child_account_admin',
      'child_list' => array()
    );
  }
  /**
   * 客户的消费记录
   */
  public function consumerRecord() {
    $build['list'] = array(
      '#theme' => 'member_consumer_record',
      'records' => null,
      'comsumer' => array()
    );
    $build['list_pager']['#type'] = 'pager';
    return $build;
  }

  /**
   * 修改密保问题
   *
   */
  public function safe_question() {
    $build['safe_question'] = array(
      '#theme' => 'security_question'
    );
    return $build;
  }
  /**
   * 后台管理充值记录
   */
  public function rechargeRecord($type) {
    $record = RechargeRecordForm::createInstance(\Drupal::getContainer());
    return $record->render($type);
  }

  /**
   * 设置余额预警开关
   */
  public function balanceAlarm(Request $request) {
    $uid = $request->request->get('uid');
    $flag = $request->request->get('flag');
    // 根据用户id查询到当前用户是否设置过信用额度
    $funds = \Drupal::service('member.memberservice')->getClientCredit($uid);
    // 该用户未设置过信用额度  无记录  为该用户添加一条记录
    if(empty($funds)) {
      // 操作记录
      $op_record = array(
        'amount' => 0,
        'message' => '用户打开余额预警开关。初始化额度0',
        'op_uid' => 0,
        'client_uid' => $uid,
        'created' => REQUEST_TIME
      );
      $rs = \Drupal::service('member.memberservice')->setClientCredit(array('alarm' => $flag, 'uid' => $uid) , $op_record);
      if($rs)
        return new JsonResponse('预警开关切换成功');
    } else {  // 存在记录  修改预警开关
      $rs = \Drupal::service('member.memberservice')->setBalanceAlarm($uid, $flag);
      if($rs)
        return new JsonResponse('预警开关切换成功');
    }
  }

}

