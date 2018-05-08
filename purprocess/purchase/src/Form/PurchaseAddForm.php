<?php

namespace Drupal\purchase\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PurchaseAddForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'purchase_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $choices = [];
    $request = \Drupal::request();
    $destination = $request->request->get('data');

    $choices = $destination['choices'][0];
    if (!empty($choices)) {
      $_SESSION['requirement_pool_checked'][\Drupal::currentUser()->id()] = $choices;
    }
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('名称'),
    ];

    $form['choices'] = [
      '#type' => 'value',
      '#value' => $_SESSION['requirement_pool_checked'][\Drupal::currentUser()->id()],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
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
    $choices = $form_state->getValue('choices');
    $title = $form_state->getValue('title');
    if (empty($choices)) {
      drupal_set_message('采购单创建失败，请重新创建采购单', 'error');
    }
    else {
      $check = \Drupal::service('part.partservice')->checkPartStatusById($choices);
      if ($check) {
        // 创建采购单数据
        // \Drupal::service('purchase.purchaseservice')->save(null, FALSE, $choices);.
        \Drupal::service('purchase.purchaseservice')->create($title, $choices);
        drupal_set_message('采购单创建成功!!!');
      }
      else {
        drupal_set_message('采购单创建失败，请检查配件状态!!!', 'error');
      }
    }

    $form_state->setRedirectUrl(new Url("entity.part.collection"));
  }

}
