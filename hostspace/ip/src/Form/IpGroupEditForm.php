<?php
/**
 * @file  IP段入库申请
 * Contains \Drupal\ip\Form\IpGroupEditForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class IpGroupEditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ip_group_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $group_id = 0) {
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => '分组名',
      '#required' => true
    );
    $entity_rooms = entity_load_multiple('room');
    $room_options = array('' => '- 请选择 -' );
    foreach ($entity_rooms as $row) {
      $room_options[$row->id()] = $row->label();
    }
    $form['rid'] = array(
      '#type' => 'select',
      '#title' => '所属机房',
      '#required' => true,
      '#options' => $room_options
    );
    $form['group_id'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    if(!empty($group_id)) {
      $groups = \Drupal::service('ip.ipservice')->loadIpGroup(array('gid' => $group_id));
      if(!empty($groups)) {
        $group = reset($groups);
        $form['name']['#default_value'] = $group->name;
        $form['rid']['#default_value'] = $group->rid;
        $form['group_id']['#value'] = $group_id;
        $ipm_query = \Drupal::service('entity.query')->get('ipm');
        $ipm_query->condition('group_id', $group_id);
        $ipms = $ipm_query->execute();

        $ipb_query = \Drupal::service('entity.query')->get('ipb');
        $ipb_query->condition('group_id', $group_id);
        $ipbs = $ipb_query->execute();
        if(!empty($ipms) || !empty($ipbs )) {
          $form['rid']['#disabled'] = TRUE;
        }
      }
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    $rid = $form_state->getValue('rid');
    $group_id = $form_state->getValue('group_id');
    $value = array(
      'name' => $name,
      'rid' => $rid,
      'changed' => time()
    );
    if($group_id) {
      \Drupal::service('ip.ipservice')->updateIpGroup($value, $group_id);
    } else {
      \Drupal::service('ip.ipservice')->addIpGroup($value + array(
        'created' => time(),
        'uid' => \Drupal::currentUser()->id()
      ));
    }
    $form_state->setRedirectUrl(new Url('ip.group.list'));
  }
}
