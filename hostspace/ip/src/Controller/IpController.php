<?php

/**
 * @file
 * Contains \Drupal\ip\Controller\IpController.
 */

namespace Drupal\ip\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ip\Form\BusinessIpTypeForm;
use Drupal\ip\Form\BusinessIPCalssifyForm;
use Drupal\ip\BipApplyListBuilder;
use Drupal\ip\BipCancleApplyListBuilder;
use Drupal\ip\IpGroupListBuilder;

class IpController extends ControllerBase {

  /**
   * 管理业务IP的防御类型
   */
  public function adminBusinessIpType() {
    $type = new BusinessIpTypeForm();
    return $type->render();
  }
  /**
   * IP段入库申请 的申请列表
   */
  public function bipApplyList() {
    $list = BipApplyListBuilder::createInstance(\Drupal::getContainer());
    return $list->render();
  }
  /**
   * 业务IP分类信息管理
   */
  public function adminBusinessIpClassify() {
   $classify = new BusinessIPCalssifyForm ();
   return $classify->render();
  }
  /**
   * 业务ip下架申请列表
   */
  public function bipCancleApplyList() {
    $list = BipCancleApplyListBuilder::createInstance(\Drupal::getContainer());
    return $list->render();
  }

  /**
    *view the business ip segment
    */
  public function viewIpSegment(){
     $build['list'] = array(
      '#theme' => 'ip_segment_list',
     );
    return $build;
  }

  public function groupList() {
    $ipgroup = IpGroupListBuilder::createInstance(\Drupal::getContainer());
    return $ipgroup->render();
  }

  public function groupDelete($group_id) {
    \Drupal::service('ip.ipservice')->deleteIpGroup($group_id);
    drupal_set_message('删除成功');
    return new RedirectResponse('/admin/ip/group');
  }
 //@todo 加载IP类型列表
}

