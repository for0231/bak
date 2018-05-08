<?php

namespace Drupal\kaoqin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;

use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Utility\SafeMarkup;
/**
 *
 */
class KaoqinSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kaoqin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['kaoqin.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('kaoqin.settings');

    $form['description'] = [
      '#markup' => SafeMarkup::format("<font color=red>班次排班时，请勿重复排班。该考勤部门不包括总监级考勤.</font>",[]),
    ];

    $departs_tids = [18, 19, 20, 21, 22, 40, 41, 83];
    $term_departs = Term::loadMultiple($departs_tids);

    $options_depart = [];
    foreach ($term_departs as $key => $term_depart) {
      $options_depart[$key] = taxonomy_term_title($term_depart);
    }

    $form['departs_type'] = [
      '#type' => 'details',
      '#title' => '排班方式',
      '#open' => TRUE,
    ];
    $form['departs_type']['filters_normal'] = [
      '#type' => 'details',
      '#title' => '正常班次',
      '#open' => FALSE,
    ];
    $form['departs_type']['filters_normal']['departs_normal'] = [
      '#type' => 'select',
      '#title' => '正常排班部门',
      '#options' => $options_depart,
      '#multiple' => 1,
      '#default_value' => explode(',', $config->get('kaoqin_type_normal')),
      '#size' => min(12, count($options_depart)),
      '#description' => '正常排班的部门有哪些，请指定。',
    ];
    $form['departs_type']['filters_normal']['description'] = [
      '#markup' => '正常班次考勤时间在9:05~18:00之间',
    ];
    $form['departs_type']['filters_tanxing'] = array(
      '#type' => 'details',
      '#title' => '弹性工作时间',
      '#open' => FALSE,
    );

    $form['departs_type']['filters_tanxing']['departs_tanxing'] = [
      '#type' => 'select',
      '#title' => '弹性工作部门',
      '#options' => $options_depart,
      '#multiple' => 1,
      '#default_value' => explode(',', $config->get('kaoqin_type_tanxing')),
      '#size' => min(12, count($options_depart)),
      '#description' => '弹性工作的部门有哪些，请指定。',
    ];

    $form['departs_type']['filters_tanxing']['description'] = [
      '#markup' => '弹性工作时间考勤时间必须在10:00~17:00,上班总时间不小于9小时',
    ];

    $form['departs_type']['filters_paiban'] = array(
      '#type' => 'details',
      '#title' => '排班班次',
      '#open' => FALSE,
    );

    $form['departs_type']['filters_paiban']['departs_paiban'] = [
      '#type' => 'select',
      '#title' => '排班工作部门',
      '#options' => $options_depart,
      '#multiple' => 1,
      '#default_value' => explode(',', $config->get('kaoqin_type_paiban')),
      '#size' => min(12, count($options_depart)),
      '#description' => '排班工作的部门有哪些，请指定。',
    ];

    $form['departs_type']['filters_paiban']['description'] = [
      '#markup' => '上班时间不固定，纯手工排班',
    ];

    $form['simple_kaoqin_type'] = [
      '#type' => 'radios',
      '#title' => '启用简单模式',
      '#description' => '即是使用统一的朝9晚6点模式',
      '#default_value' => $config->get('simple_kaoqin_type'),
      '#options' => [0=>'不启用', 1=>'启用'],
    ];
    $form['simple_kaoqin_description'] = [
      '#markup' => '简单考勤模式仅支持9:00-18:00这段时间.',
    ];

    $form['frendly_setting'] = [
      '#type' => 'textfield',
      '#title' => '人性化考勤时间',
      '#default_value' => $config->get('frendly_setting'),
      '#description' => '默认为分钟数',
    ];

    $form['beonduty'] = [
      '#type' => 'textfield',
      '#title' => '上班时间段',
      '#default_value' => $config->get('beonduty'),
      '#description' => '上班时间段设置',
    ];


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];

    $form['actions']['auto_normal'] = array(
      '#type' => 'submit',
      '#value' => '正常排班',
      '#submit' => array('::autoNormalSubmitForm'),
      '#attributes' => array(
        'class' => array('btn btn-warning input-sm'),
      ),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}cornsilk
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $departs_normal = $departs_tanxing = $departs_paiban = '';
    if (!empty($form_state->getValue('departs_normal'))) {
      $departs_normal = implode(',', $form_state->getValue('departs_normal'));
    }
    if (!empty($form_state->getValue('departs_tanxing'))) {
      $departs_tanxing = implode(',', $form_state->getValue('departs_tanxing'));
    }
    if (!empty($form_state->getValue('departs_paiban'))) {
      $departs_paiban = implode(',', $form_state->getValue('departs_paiban'));
    }

    $this->config('kaoqin.settings')
      ->set('kaoqin_type_normal', $departs_normal)
      ->set('kaoqin_type_tanxing', $departs_tanxing)
      ->set('kaoqin_type_paiban', $departs_paiban)
      ->set('simple_kaoqin_type', $form_state->getValue('simple_kaoqin_type'))
      ->set('frendly_setting', $form_state->getValue('frendly_setting'))
      ->set('beonduty', $form_state->getValue('beonduty'))
      ->save();

    drupal_set_message('各部门的考勤类型保存成功');
  }

  /**
   * @description 正常排班时，自动排班处理.
   */
  public function autoNormalSubmitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('自动排班保存成功');
  }
}
