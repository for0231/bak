<?php
/**
 * @file
 */
namespace Drupal\kaoqin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KaoqinFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kaoqin_filter_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['filter'] = [
      '#type' => 'details',
      '#title' => '搜索条件',
      '#open' => FALSE,
    ];
    $form['filter']['name'] = [
      '#type' => 'textfield',
      '#title' => '姓名',
      '#size' => 60,
    ];
    $form['filter']['date'] = [
      '#type' => 'textfield',
      '#title' => '月份',
      '#description' => '如2017-12',
    ];

    $form['filter']['actions'] = ['#type' => 'actions'];
    $form['filter']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('确认搜索'),
      '#attributes' => [
        'class' => ['btn btn-default'],
      ],
    ];
    $form['filter']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#limit_validation_errors' => array(),
      '#submit' => array('::resetForm'),
      '#attributes' => [
        'class' => ['btn btn-danger'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');

    if (!empty($name)) {
      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['realname' => $name]);
      if (empty($users)) {
        drupal_set_message('该员工不存在', 'error');
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['user_employ_filter']['name'] = $form_state->getValue('name');
    $_SESSION['user_employ_filter']['date'] = $form_state->getValue('date');
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['user_employ_filter']['name'] = [];
    $_SESSION['user_employ_filter']['date'] = [];
  }
}
