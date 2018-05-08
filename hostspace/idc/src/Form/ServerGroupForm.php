<?php
/**
 * @file
 * Contains \Drupal\idc\Form\ServerGroupForm.
 */
namespace Drupal\idc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ServerGroupForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'server_group_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $groupId = null) {
    $child_datas = entity_load_multiple_by_properties('cabinet_server', array('parent_id' => $groupId));
    $options = array();
    foreach($child_datas as $item) {
      $server_entity = $item->getObject('server_id');
      $c_ipm = $item->getObject('ipm_id');

      $options[] = array(
        'server_code' => $server_entity->label() .'|'. $server_entity->get('type')->entity->label(),
        'mange_ip' => $c_ipm->label(),
        'switch' => 'P:' . $item->getObject('switch_p')->label() . '('. $item->getSimpleValue('switch_p') .')M:' . $item->getObject('switch_m')->label() . '('. $item->getSimpleValue('switch_m') . ')',
        'node' => $item->getSimpleValue('group_name'),
        'status' => ipmStatus()[$c_ipm->get('status')->value],
        'op' => array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(
              'remove' => array(
                'title' => t('Remove'),
                'url' => new Url('admin.idc.seat.server.delete', array('cabinet_server' => $item->id()))
              )
            )
          )
        )
      );
    }
    $form['list'] = array(
      '#type' => 'table',
      '#header' => array(
        'server_code' => '服务器编码',
        'mange_ip' => '管理IP',
        'switch' => '交换机IP',
        'node' => '节点',
        'status' => '状态',
        'op' => '操作'
      ),
      '#rows' => $options,
      '#empty' => t('No content available.'),
    );
    $entity = entity_load('cabinet_server', $groupId);
    $form['back'] = array(
      '#type' => 'link',
      '#title' => t('Return'),
      '#attributes' => array('class' => array('button')),
      '#url' => new Url('admin.idc.cabinet.seat', array('room_cabinet' => $entity->getObjectId('cabinet_id')))
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
