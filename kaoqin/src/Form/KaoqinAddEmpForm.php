<?php
/**
 * @file
 * Contains \Drupal\kaoqin\Form\KaoqinAddEmpForm.
 * @description 使用的是upon实体.
 */
namespace Drupal\kaoqin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KaoqinAddEmpForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kaoqin_addemp_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => '名称',
      '#default_value' => '段伟',
      '#size' => 60,
      '#required' => TRUE,
      '#prefix' => '<div class="form-group">',
      '#suffix' => '</div>',
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => '描述',
      '#default_value' => isset(  $context['keywords']) ? drupal_implode_tags($context['keywords']) : '',
      '#prefix' => '<div class="form-group">',
      '#suffix' => '</div>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('添加事件'),
      '#id' => 'add-event',
      '#attributes' => [
        'class' => ['btn btn-default'],
      ],
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

  }
}
