<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\ServercommAddForm.
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * 线路表单
 */
class DedicatedresAddForm extends FormBase {

  protected $db_service;

  public function __construct($db_service) {
    $this->db_service = $db_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
       \Drupal::service('resourcepool.dbservice')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dedicated_res_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$no = 0) {
    $form['type'] = array(
      '#type' => 'select',
      '#title' => '类型',
      '#options' => array('1'=>'共享专线资源','3'=>'专用专线资源'),
      '#default_value' => 1,
      '#required' => true,
    );
    $form['link_id'] = array(
      '#type' => 'textfield',
      '#title' => '链路ID(我司分配)',
      '#required' => true,
      '#maxlength' => 50
    );
    $form['link_code'] = array(
      '#type' => 'textfield',
      '#title' => '链路代号(供应商分配)',
      '#required' => true,
      '#maxlength' => 50
    );
    $form['commit_bandwidth'] = array(
      '#type' => 'number',
      '#title' => 'commit带宽',
      '#maxlength' => 50,
      '#field_suffix' => 'M'
    );
    $form['brust_bandwidth'] = array(
      '#type' => 'number',
      '#title' => 'brust带宽',
      '#maxlength' => 50,
      '#field_suffix' => 'M'
    );
    $form['reality_bw_percent'] = array(
      '#type' => 'number',
      '#title' => '月度实际流量',
      '#maxlength' => 50,
      '#field_suffix' => 'M'
    );
    $form['A_end'] = array(
      '#type' => 'textfield',
      '#title' => 'A-end',
      '#maxlength' => 50
    );
    $form['Z_end'] = array(
      '#type' => 'textfield',
      '#title' => 'Z-end',
      '#maxlength' => 50
    );
    $form['cacti1'] = array(
      '#type' => 'textfield',
      '#title' => '基础专线cacti',
    );
    $form['note'] = array(
      '#type' => 'textfield',
      '#title' => '备注',
      '#maxlength' => 50
    );
    $form['supplier'] = array(
      '#type' => 'textfield',
      '#title' => '供应商',
      '#maxlength' => 50
    );
    $form['no'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    if($no) {
      $dedicated_res = $this->db_service->loadEntityById('resource_dedicated_resources',$no);
      if(!empty($dedicated_res)){
        $form['type']['#default_value']= $dedicated_res->type;
        $form['supplier']['#default_value']= $dedicated_res->supplier;
        $form['link_id']['#default_value']= $dedicated_res->link_id;
        $form['link_code']['#default_value']= $dedicated_res->link_code;
        $form['commit_bandwidth']['#default_value']= $dedicated_res->commit_bandwidth;
        $form['brust_bandwidth']['#default_value']= $dedicated_res->brust_bandwidth;
        $form['reality_bw_percent']['#default_value']= $dedicated_res->reality_bw_percent;
        $form['A_end']['#default_value']=  $dedicated_res->A_end;
        $form['Z_end']['#default_value']= $dedicated_res->Z_end;
        $form['note']['#default_value']= $dedicated_res->note;
        $form['cacti1']['#default_value']= $dedicated_res->cacti1;
        $form['no']['#value'] = $no;
      }
    }
    $form['#attached'] = [
      'library' => ['resourcepool/drupal.respool-time'],
    ];
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
    $no = $form_state->getValue('no');
    $valuelist=array();
    $values = array(
      'type' => $form_state->getValue('type'),
      'supplier' => $form_state->getValue('supplier'),
      'note' => $form_state->getValue('note'),
      'link_id' => $form_state->getValue('link_id'),
      'link_code' => $form_state->getValue('link_code'),
      'commit_bandwidth' => $form_state->getValue('commit_bandwidth'),
      'brust_bandwidth' => $form_state->getValue('brust_bandwidth'),
      'reality_bw_percent' => $form_state->getValue('reality_bw_percent'),
      'cacti1' => $form_state->getValue('cacti1'),
      'A_end' => $form_state->getValue('A_end'),
      'Z_end' => $form_state->getValue('Z_end'),
    );
    if($no){
      $this->db_service->update_entity_byno($values, $no,'resource_dedicated_resources');
    }else{
      $this->db_service->add_entity($values,'resource_dedicated_resources');
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.resourcepool.dedicatedres')); 
    
  }
}

