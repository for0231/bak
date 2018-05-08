<?php
/**
 * @file
 * Contains \Drupal\idc\Form\ProductPriceDeleteForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class TechnologyRollbackForm extends FormBase {

  protected $hostclient_service;

  public function __construct() {
    $this->hostclient_service = \Drupal::service('hostclient.serverservice');
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'technology_rollback_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $handle_id = null) {
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    if($handle_info->tech_status != 0 || $handle_info->tech_uid != $this->currentUser()->id()) {
      return $this->redirect('admin.hostclient.technology.list');
    }
    $entity = entity_load('hostclient', $handle_info->hostclient_id);
    $form['handle_id'] = array(
      '#type' => 'value',
      '#value' => $handle_id
    );
    $form['#title'] = $this->t('Are you sure you want to bring the server(%server) back to the business department', array(
      '%server' => $entity->getObject('ipm_id')->label()
    ));

    $form['description'] = array('#markup' => t('Rollback will remove all your data'));
    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Confirm')
    );
    $form['actions']['cancel'] = array(
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => new Url('admin.hostclient.technology.dept', array('handle_id' => $handle_id))
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $handle_id = $form_state->getValue('handle_id');
    $handle_info = $this->hostclient_service->loadHandleInfo($handle_id);
    $entity = entity_load('hostclient', $handle_info->hostclient_id);

    $new_handle_info['busi_status'] = 0;
    $new_handle_info['tech_uid'] = 0;
    $new_handle_info['tech_accept_data'] = 0;
    $new_handle_info['tech_check_item'] = '';
    $new_handle_info['tech_description'] = '';
    $this->hostclient_service->updateHandleInfo($new_handle_info, $handle_id);
    $entity->set('status', 1);
    $entity->save();
    //-----写日志------
    $handle_info->busi_status = $new_handle_info['busi_status'];
    $handle_info->tech_uid = $new_handle_info['tech_uid'];
    $handle_info->tech_accept_data = $new_handle_info['tech_accept_data'];
    $handle_info->tech_check_item = $new_handle_info['tech_check_item'];
    $handle_info->tech_description = $new_handle_info['tech_description'];
    $entity->other_data = array('data_id' => $handle_id, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info);
    $entity->other_status = 'tech_dept_rollback';
    HostLogFactory::OperationLog('order')->log($entity, 'update');

    drupal_set_message($this->t('Rollback was successful'));
    $form_state->setRedirectUrl(new Url('admin.hostclient.technology.list'));
  }
}
