<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\BusinessAddForm.
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * 线路表单
 */
class BusinessAddForm extends FormBase {

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
      '#options' => array('2'=>'客户共享产品','4'=>'客户专用产品'),
      '#default_value' => 2,
      '#required' => true,
    );
    $form['link_id'] = array(
      '#type' => 'textfield',
      '#title' => '业务链路ID(我司分配)',
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
      '#title' => '专线客户cacti',
    );
    $form['note'] = array(
      '#type' => 'textfield',
      '#title' => '备注',
      '#maxlength' => 50
    );
    $form['no'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    $form['vlan'] = array(
      '#type' => 'textfield',
      '#title' => 'Vlan',
      '#maxlength' => 50
    );
    $form['client_name'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#maxlength' => 50
    );
    $form['contract_no'] = array(
      '#type' => 'textfield',
      '#title' => '合同编号',
      '#maxlength' => 50
    );
    $form['rent_time'] = array(
      '#type' => 'textfield',
      '#title' => '租用时间',
      '#maxlength' => 50
    );
    
    $form['end_time'] = array(
      '#type' => 'textfield',
      '#title' => '到期时间',
      '#maxlength' => 50
    );
    $form['price'] = array(
      '#type' => 'textfield',
      '#title' => '价格',
      '#maxlength' => 50,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    $form['#attached'] = [
      'library' => ['resourcepool/drupal.respool-time'],
    ];
    if($no) {
      $dedicated_res = $this->db_service->loadEntityById('resource_dedicated_resources',$no);
      if(!empty($dedicated_res)){
        $form['type']['#default_value']= $dedicated_res->type;
        $form['link_id']['#default_value']= $dedicated_res->link_id;
        $form['commit_bandwidth']['#default_value']= $dedicated_res->commit_bandwidth;
        $form['brust_bandwidth']['#default_value']= $dedicated_res->brust_bandwidth;
        $form['A_end']['#default_value']=  $dedicated_res->A_end;
        $form['Z_end']['#default_value']= $dedicated_res->Z_end;
        $form['note']['#default_value']= $dedicated_res->note;
        $form['cacti1']['#default_value']= $dedicated_res->cacti1;
        $form['no']['#value'] = $no;
        $form['client_name']['#default_value']=  $dedicated_res->client_name;
        $form['rent_time']['#default_value']=  date("Y-m-d",$dedicated_res->rent_time);
        $form['end_time']['#default_value']=  date("Y-m-d",$dedicated_res->end_time);
        $form['price']['#default_value']=  $dedicated_res->price;
        $form['vlan']['#default_value']= $dedicated_res->vlan;
        $form['contract_no']['#default_value']= $dedicated_res->contract_no;
      }
    }
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
      'note' => $form_state->getValue('note'),
      'link_id' => $form_state->getValue('link_id'),
      'commit_bandwidth' => $form_state->getValue('commit_bandwidth'),
      'brust_bandwidth' => $form_state->getValue('brust_bandwidth'),
      'A_end' => $form_state->getValue('A_end'),
      'Z_end' => $form_state->getValue('Z_end'),
      'client_name' => empty($form_state->getValue('client_name'))?'':$form_state->getValue('client_name'),
      'rent_time' => empty($form_state->getValue('rent_time'))?null:strtotime($form_state->getValue('rent_time')),
      'end_time' => empty($form_state->getValue('end_time'))?null:strtotime($form_state->getValue('end_time')),
      'price' => empty($form_state->getValue('price'))?null:$form_state->getValue('price'),
      'vlan' => empty($form_state->getValue('vlan'))?null:$form_state->getValue('vlan'),
      'contract_no' => $form_state->getValue('contract_no'),
      'cacti1' => $form_state->getValue('cacti1'),
    );
    if($no){
      $this->db_service->update_entity_byno($values, $no,'resource_dedicated_resources');
    }else{
      $this->db_service->add_entity($values,'resource_dedicated_resources');
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.resourcepool.businesslist')); 
    
  }
}

