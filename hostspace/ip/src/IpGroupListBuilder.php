<?php
/**
 * @file IP段入库申请列表
 * Contains \Drupal\ip\IpGroupListBuilder.
 */

namespace Drupal\ip;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IpGroupListBuilder {
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
    $condition = array();
    if(!empty($_SESSION['admin_ip_group_filter']['name'])) {
      $condition['name'] = array('value' => $_SESSION['admin_ip_group_filter']['name'], 'op' => 'like');
    }
    if(!empty($_SESSION['admin_ip_group_filter']['rid'])) {
      $condition['rid'] = $_SESSION['admin_ip_group_filter']['rid'];
    }
    $groups = \Drupal::service('ip.ipservice')->loadIpGroup($condition);
    return $groups;
  }

  private function getRow($group) {
    $row['gid'] = $group->gid;
    $row['name'] = $group->name;
    $row['room'] = entity_load('room', $group->rid)->label();
    $row['user'] = entity_load('user', $group->uid)->label();
    $row['create']  = date('Y-m-d H:i:s', $group->created);
    $row['op']['data'] = array(
      '#type' => 'operations',
      '#links' => array(
        'update' => array(
          'title' => '编辑',
          'url' => new Url('ip.group.edit', array('group_id' => $group->gid))
        ),
        'delete' => array(
          'title' => '删除',
          'url' => new Url('ip.group.delete', array('group_id' => $group->gid))
        )
      )
    );
    return $row;
  }

  /**
   * 表单渲染
   */
  public function render() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\ip\Form\IpGroupFilterForm');
    $groups = $this->load();
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('Id', '组名', '所属机房', '创建人', '创建时间','操作'),
      '#rows' => array(),
      '#empty' => t('no data')
    );
    foreach($groups as $group) {
      $build['table']['#rows'][$group->gid] =  $this->getRow($group);
    }
    return $build;
  }
}
