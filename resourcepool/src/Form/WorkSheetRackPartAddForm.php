<?php
namespace Drupal\resourcepool\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityTypeInterface;

class WorkSheetRackPartAddForm extends ContentEntityForm
{
  public function getFormId(){
    return 'workwheet_rackpartadd_form';
  }  
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['room'] = array(
      '#type' => 'select',
      '#title' => '机房',
      '#options' => getRoom(),
    );
    if(!$this->entity->isNew()){
      $form['manage_ip']['#disabled'] = true;
    }
    return $form;
  }
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $port = $form_state->getValue('port')[0]['value'];
    $ye_network_card = $form_state->getValue('ye_network_card')[0]['value'];
    $network_card = $form_state->getValue('network_card')[0]['value'];
    if(!empty($port)){
      if(empty(substr($port,0,4)==='Ethe')){
        $form_state->setErrorByName('port','管理端口格式错误');
      }
    }
    if(!empty($ye_network_card)){
      $num1 = substr($ye_network_card,0,2)=='Gi';
      $num2 = substr($ye_network_card,0,4)=='Ethe';
      $num3=$num1+$num2;
      if($num3<1){
        $form_state->setErrorByName('ye_network_card','业务网卡格式错误');
      }
    }
    if(!empty($network_card)){
      $numg = substr($network_card,0,2)=='Gi';
      $nume = substr($network_card,0,4)=='Ethe';
      $num4=$numg+$nume;
      if($num4<1){
        $form_state->setErrorByName('network_card','第二网卡格式错误');
      }
    }
  }
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->save();
    drupal_set_message($this->t('添加成功'));
    $form_state->setRedirectUrl(new Url('admin.resourcepool.rackpart.list'));
  }
}