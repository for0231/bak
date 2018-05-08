<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\SplitProductAddForm
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * 线路表单
 */
class SplitProductAddForm extends FormBase {

  protected $db_service;
  protected $client_no;

  public function __construct($db_service) {
    $this->db_service = $db_service;
    $this->client_no = empty($_GET['client_no'])?'':$_GET['client_no'];
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
    return 'business_res_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$no = 0) {
    //获取可选的所属资源
    $res_list = $this->db_service->load_dedicated_linkid(1);
    $res_lists = array();
    foreach($res_list as $item){
      $res_lists[$item->no]=$item->link_id;
    }
    $form['affiliation_res'] = array(
      '#type' => 'select',
      '#title' => '归属资源',
      '#options' => $res_lists,
      '#required' => true,
    );
    if(!empty($this->client_no)){
      $dedicated = $this->db_service->loadEntityById('resource_dedicated_resources',$this->client_no);
      $split_res = $this->db_service->get_split_res($dedicated->link_id);
      $aff_list= array();
      foreach($split_res as $item){
        $affiliation_res = $this->db_service->loadEntityById('resource_dedicated_resources',$item->affiliation_res);
        if(!empty($affiliation_res)){
          $aff_list[$affiliation_res->no] = empty($affiliation_res->link_id)?'':$affiliation_res->link_id;
        }
      }
      $form['split_res'] = array(
        '#theme' => 'split_res_list',
        '#split_res' =>$split_res,
        '#aff_list' =>$aff_list,
        '#dedicated'=>$dedicated,
        '#client_no'=>$this->client_no,
      );
    }
    $form['no'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '添加',
    );
    $form['finish'] = array(
      '#type' => 'link',
      '#title' => '结束添加',
      '#url' => new url('admin.resourcepool.businesslist'),
      '#attributes' => array(
        'class'=>array('button js-form-submit form-submit'),
      )
    );
    if($no) {
      //设置表单元素默认值
      $business_res = $this->db_service->loadEntityById('resource_table3',$no);
      if(!empty($business_res)){
        $form['affiliation_res']['#default_value']= $business_res->affiliation_res;
        $form['no']['#value'] = $no;
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
    $resid = $form_state->getValue('affiliation_res');
    $subsection_res =$this->db_service->loadEntityById('resource_dedicated_resources',$resid); 
    $dedicated = $this->db_service->loadEntityById('resource_dedicated_resources',empty($this->client_no)?$no:$this->client_no);
    $values = array(
      'affiliation_res' => $form_state->getValue('affiliation_res'),
      'affiliation_pro' => empty($dedicated->link_id)?'':$dedicated->link_id,
      'subsection_res' => $subsection_res->link_id.'-'.'资源用户'.time(),
    );
    if($no) {
      $this->db_service->update_entity_byno($values, $no,'resource_table3');
    } else {
      $this->db_service->add_entity($values,'resource_table3');
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.resourcepool.splitproduct.add',array('client_no'=>$this->client_no)));
  }
}

