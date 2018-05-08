<?php
/**
 * @file 
 * Contains \Drupal\ip\Form\BusinessIpTypeForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BusinessIpTypeForm {

   /**
   * 渲染模板
   */
  public function render() {
    $build = array();

    $build['list'] = array(
      '#type' => 'table',
      '#header' => array('ID', 'Name', 'Operations'),
      '#rows' => $this->buildRow()
    );
    return $build;
  }

  /**
   * 创建行数据
   *
   * @return $rows array
   *   创建好的行数据数组
   */
  private function buildRow() {
    $type_arr = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('business_ip_type',0,1);
    $rows = array();
    foreach ($type_arr as $v) {
      $rows[$v->tid] = array(
        'ID' => $v->tid,
        'Name' => $v->name
      );
      $rows[$v->tid]['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $this->getOpArr($v->tid, $v->name)
       );
    }
    return $rows;
  }

  /**
   * 构建操作的链接数组
   *
   * @param $question_id
   *   问题编号
   *
   * @return 组装好的Operations数组
   */
  private function getOpArr($tid,$name) {
    $op = array();
    $op['Edit'] = array(
      'title' => 'Edit',
      'url' => new Url('entity.taxonomy_term.edit_form', array('taxonomy_term' => $tid))
    );
    $op['Delete'] = array(
      'title' => 'Delete',
      'url' => new Url('entity.taxonomy_term.delete_form', array('taxonomy_term' => $tid))
    );
    return $op;
  } 

}
