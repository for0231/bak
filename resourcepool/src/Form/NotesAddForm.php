<?php

/**
 * @file
 * Contains \Drupal\resourcepool\Form\NotesAddForm
 */

namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
/**
 * 批量增加机柜备注表单
 */
class NotesAddForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notes_add_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['rack'] = array(
      '#type' => 'textfield',
      '#title' => '机柜',
      '#maxlength' => 50,
      '#description'=>'指定需要批量修改的机柜',
    );
    $form['notes'] = array(
      '#type' => 'textarea',
      '#title' => '备注',
      '#description'=>'填写需要追加的备注',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '批量修改',
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
    //批量追加机柜实体的备注:
    $rack = $form_state->getValue('rack');
    $entitys = entity_load_multiple_by_properties('work_sheet_rackpart',array('rack'=>$rack));
    $form_notes = $form_state->getValue('notes');
    foreach($entitys as $entity){
      $notes = $entity->get('notes')->value;
      $entity->set('notes',$notes.$form_notes);
      $entity->save();
    }
    drupal_set_message('修改成功'); 
    $form_state->setRedirectUrl(new Url('admin.resourcepool.rackpart.list'));
  }
  /*
  public function submitDelete(array &$form, FormStateInterface $form_state) {
    //批量删除机柜实体的备注:
    $rack = $form_state->getValue('rack');
    $entitys = entity_load_multiple_by_properties('work_sheet_rackpart',array('rack'=>$rack));
    foreach($entitys as $entity){
      $entity->set('notes','');
      $entity->save();
    }
    drupal_set_message('修改成功'); 
    $form_state->setRedirectUrl(new Url('admin.resourcepool.rackpart.list'));
  }
  */
}

