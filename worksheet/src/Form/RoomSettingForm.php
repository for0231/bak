<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\RoomSettingForm.
 */
namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RoomSettingForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'worksheet_room_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $year = $form_state->getValue('year');
    $month = $form_state->getValue('month');
    if(empty($year)) {
      $year = date('Y', REQUEST_TIME);
      $month = date('m', REQUEST_TIME);
    }
    $form['group'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    $form['group']['year'] = array(
      '#type' => 'select',
      '#default_value' => $year,
      '#options' => array(
        2016=>'2016年',
        2017=>'2017年',
        2018=>'2018年',
        2019=>'2019年',
        2020=>'2020年'        
      ),
      '#ajax' => array(
        'callback' => array(get_class($this), 'ajaxCallback'),
        'method' => 'html',
        'wrapper' => 'calendar-wrappers'
      )
    );
    $form['group']['month'] = array(
      '#type' => 'select',
      '#default_value' => $month,
      '#options' => array(
        '01' => '一月',
        '02' => '二月',
        '03' => '三月',
        '04' => '四月',
        '05' => '五月',
        '06' => '六月',
        '07' => '七月',
        '08' => '八月',
        '09' => '九月',
        '10' => '十月',
        '11' => '十一月',
        '12' => '十二月'
      ),
      '#ajax' => array(
        'callback' => array(get_class($this), 'ajaxCallback'),
        'method' => 'html',
        'wrapper' => 'calendar-wrappers'
      )
    );
    $form['calendar'] = array(
      '#type' => 'container',
      '#weight' => '15',
      '#id' => 'calendar-wrappers'
    );
    $form['calendar']['work_calendar'] = array(
      '#type' => 'work_calendar',
      '#year' => $year,
      '#month' => $month,
      '#attributes' => array(
        'change-url' => \Drupal::url('admin.worksheet.room.time.save')
      )
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

  }
  
  /**
   * 回调函数
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['calendar']['work_calendar'];
  }
}
