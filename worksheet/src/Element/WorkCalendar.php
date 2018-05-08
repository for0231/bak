<?php

/**
 * @file
 * Contains \Drupal\worksheet\Element\WorkCalendar.
 */

namespace Drupal\worksheet\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;

/**
 * 设置工作日历
 *
 * @FormElement("work_calendar")
 */
class WorkCalendar extends FormElement {
  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#process' => array(
        array($class, 'processWorkCalendar'),
      ),
      '#theme' => array('work_calendar'),
    );
  }
  
  public static function processWorkCalendar(&$element, FormStateInterface $form_state, &$form) {
    $element['#attributes']['class'][] = 'form-calendar';
    $element['#attached']['library'][] = 'worksheet/drupal.work-calendar';
    return $element;
  }
}