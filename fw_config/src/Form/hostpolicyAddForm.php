<?php

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class hostpolicyAddForm extends FormBase{
  
  public function getFormId() {
    return 'hostpolicy_add_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build['ip'] = array(
      '#type' => 'textfield',
      '#title' => '主机IP',
      '#required'=> true,
    );
    $build['policy'] = array(
      '#type' => 'select',
      '#title' => '类型',
      '#options' => array(
        '2'=>'忽略',
        '1'=>'屏蔽',
        '4096'=>'宽松',
      )
    );
    $build['submit'] = array(
      '#type' => 'submit',
      '#value' => '提交',
    );
    return $build;
  }

  public function submitForm(array &$form,FormStateInterface $form_state) {
    $conn = new \Drupal\fw_config\WdConnect();
    $wdservice = \Drupal::service('fw_config.wdfirewall');
    $url = 'http://162.212.181.3:10000/setting/hostpolicy/add';
    $conn->init();
    $conn->login();
    $ip =  $form_state->getValue('ip');
    $policy = $form_state->getValue('policy');
    if($policy== 1){
      $policy2='屏蔽';
    }elseif($policy== 2){
      $policy2='忽略';
    }elseif($policy== 4096){
      $policy2='宽松(4096)';
    }
    $list =  array(
      'ip' => $ip,
      'policy' =>$policy,
    );
    $html = $conn->commonFunction($url,$list,'POST');
    if(empty($html)){
      $addresult = $wdservice->add($list);
          $url2 = 'http://162.212.181.3:10000/setting/hostpolicy/list';
      $conn->init();
      $conn->login();
      $datalist = $conn->getRead($url2);
      unset($datalist[0]);
      $valuelist=array();
      foreach ($datalist as $item){
        if(trim($item[1])== $ip){
          $valuelist= $item;
        }
      }
      if(!empty($valuelist)){
        $fields =array('value'=> substr($valuelist[5],2,strlen($valuelist[5])));
        $updateresult = $wdservice->update($fields,$ip);
      }
    }

    if (empty($html)&& $updateresult){
      drupal_set_message('添加成功');
    }else{
      drupal_set_message('添加失败','error');
    }
    $form_state->setRedirectUrl(new Url('admin.fw.hostpolicy.list'));
  }
}

?>