<?php

/**
 * @file
 * Contains \Drupal\common\Element\B_E_DateTimePicker.
 */

namespace Drupal\common\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for double-input of passwords.
 *
 * Formats as a pair of password fields, which do not validate unless the two
 * entered passwords match.
 *
 * @FormElement("B_E_dateTime_picker")
 */
class B_E_DateTimePicker extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processSelectDate'),
      ),
      '#theme_wrappers' => array('form_element'),
    );
  }

    /**
   * Expand a password_confirm field into two text boxes.
   */
  public static function processSelectDate(&$element, FormStateInterface $form_state, &$complete_form) {
    // 时间
    $element['date'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
    $element['date']['start'] = array(
    	'#title' => '起始时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#required' =>TRUE,
    );
    $element['date']['expire'] = array(
    	'#title' => '结束时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#required' =>TRUE,
    );
    $element['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');

    $element['#element_validate'] = array(array(get_called_class(), 'validateDate'));


    return $element;
  } 

  public static function validateDate(&$element, FormStateInterface $form_state, &$complete_form) {
    $begin = $element['date']['start']['#value'];
    $end = $element['date']['expire']['#value']; 
    if(strtotime($begin) > strtotime($end)) {
      $form_state->setError($element, t('Start time can not be greater than the end time.'));
    }
  


  }


}
