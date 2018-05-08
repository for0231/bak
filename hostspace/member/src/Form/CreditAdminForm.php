<?php
/**
 * @file 
 * Contains \Drupal\member\Form\CreditAdminForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CreditAdminForm {
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
   * 构建数据
   */
  private function load() {
    $condition = $this->filterForm();
    $all = \Drupal::service('member.memberservice')->getAllClientCredit($condition);
    $rows = array();
    foreach($all as $value) {
      $rows[$value->id] = array(
        'id' => $value->id,
        'client' => $value->client_name ? $value->client_name : entity_load('user', $value->uid)->label(),
        'company' => $value->corporate_name,
        'credit' => '￥' . $value->credit,
        'client_type' => clientType()[$value->client_type]
      );
      //得到用户所属的角色集合
      $client_role = entity_load('user', $value->uid)->getRoles();
      $role_arr = array();
      foreach($client_role as $role) {
        if($role != 'authenticated') {
          $role_arr[] = entity_load('user_role', $role)->label();
        }
      }
      //绑定用户角色列
      $rows[$value->id]['agent']['data'] = array(
        '#theme' => 'item_list',     
        '#items' => $role_arr
      );
      //给每一行绑定操作按钮
      $rows[$value->id]['operations']['data'] = array(
        '#type' => 'operations',     
        '#links' => array(
          'Upgrade' => array(
            'title' => t('Upgrade amount'),
            'url' => new Url('member.founds.credit.up', array('user' => $value->uid))
          ),
          'Reduce' => array(
            'title' => t('Reduce amount'),
            'url' => new Url('member.founds.credit.low', array('user' => $value->uid))
          ),
        ) 
      );
    }
    return $rows;
  }

  /**
   * 表单筛选
   */
  private function filterForm() {
    $condition = array();
    if(!empty($_SESSION['admin_credit_filter'])) {
      if(!empty($_SESSION['admin_credit_filter']['client'])) {
        $condition['uid'] = array('field' => 'client.client_name' , 'value' => '%' . $_SESSION['admin_credit_filter']['client'] . '%' , 'op' => 'LIKE');
      }
      if(!empty($_SESSION['admin_credit_filter']['company'])) {
        $condition['company'] = array('field' => 'client.corporate_name' , 'value' => '%' . $_SESSION['admin_credit_filter']['company'] . '%' , 'op' => 'LIKE');
      }
      if(!empty($_SESSION['admin_credit_filter']['client_type'])) {
        $condition['type'] = array('field' => 'client.client_type' , 'value' => $_SESSION['admin_credit_filter']['client_type'] , 'op' => '=');

      }

    }
    return $condition;
  }

  public function render() {
 
    //加载显示筛选条件的表单
    $build['filter'] = $this->formBuilder->getForm('Drupal\member\Form\CreditFilterForm');
    $rows = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array(t('ID'), t('Client'), t('Company'), t('Credit'), t('Client Type') , t('Agent'), t('Operations')),
      '#rows' => !empty($rows) ? $rows : array('1' =>array('#markup' => t('There have no data to show.'),'#wrapper_attributes' => array('colspan' => 7)))
    );
    $build['list_pager']['#type'] =  'pager';
    return $build;
  }

}
