<?php
namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RackPartFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rackpart_filter_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['rack_part']),
    );
    $form['filters']['manage_ip'] = array(
      '#type' => 'textfield',
      '#title' => '管理ip',
      '#default_value' => empty($_SESSION['rack_part']['manage_ip']) ? '' : $_SESSION['rack_part']['manage_ip']
    );
    $form['filters']['rack'] = array(
      '#type' => 'textfield',
      '#title' => '机柜',
      '#default_value' => empty($_SESSION['rack_part']['rack'])? '': $_SESSION['rack_part']['rack']
    );
    $form['filters']['ye_vlan'] = array(
      '#type' => 'textfield',
      '#title' => '业务vlan',
      '#default_value' => empty($_SESSION['rack_part']['ye_vlan'])? '': $_SESSION['rack_part']['ye_vlan']
    );
    $form['filters']['networkcard_vlan'] = array(
      '#type' => 'textfield',
      '#title' => '第二网卡vlan',
      '#default_value' => empty($_SESSION['rack_part']['networkcard_vlan'])? '': $_SESSION['rack_part']['networkcard_vlan']
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    $form['filters']['reset'] = array(
      '#type' => 'submit',
      '#value' => '清空',
      '#submit' => array('::resetForm'),
    );
    $form['filters']['export'] = array(
      '#type' => 'link',
      '#title' => '导出',
      '#url' => new Url('admin.rackpart.educe'),
      '#attributes' => array(
        'class' => array('button')
      )
    );
    $roles = \Drupal::currentUser()->getRoles();
    if(in_array('worksheet_manage',$roles) && !empty($_SESSION['rack_part']['rack'])) {
      $form['filters']['delete'] = array(
        '#type' => 'link',
        '#title' => '删除',
        '#url' => new Url('admin.rackpart.delete'),
        '#attributes' => array(
          'class' => array('button')
        )
      );
    }
    $form['filters']['notes_delete'] = array(
      '#type' => 'link',
      '#title' => '备注删除(选择机柜)',
      '#url' => new Url('admin.rackpart.notes.delete'),
      '#attributes' => array(
        'class' => array('button')
      )
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['rack_part']['manage_ip'] = $form_state->getValue('manage_ip');
    $_SESSION['rack_part']['rack'] = $form_state->getValue('rack');
    $_SESSION['rack_part']['ye_vlan'] = $form_state->getValue('ye_vlan');
    $_SESSION['rack_part']['networkcard_vlan'] = $form_state->getValue('networkcard_vlan');
  }
  
  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['rack_part'] = array();
  }
}
