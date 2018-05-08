<?php

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class FwLinkForm extends FormBase{
  
  public function getFormId() {
    return 'fw_link_from';
  }
  public function buildForm(array $form, FormStateInterface $form_state, $rows = array()) {
    $form['table'] = array(
      '#type' => 'tableselect',
      '#header' => array('防火墙地址','本地地址','远程地址','当前状态','屏蔽原因'),
      '#options' => $rows,
      '#empty' => '无数据',
      '#multiple'=>true,
      '#id' => 'fw-link-content'
    );
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}

?>