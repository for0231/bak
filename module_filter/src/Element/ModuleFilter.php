<?php

/**
 * @file
 * Contains \Drupal\module_filter\Element\ModuleFilter.
 */

namespace Drupal\module_filter\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides module filter.
 *
 * @FormElement("module_filter")
 */
class ModuleFilter extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#tree' => TRUE,
      '#weight' => -1,
      '#process' => array(
        array($class, 'form_process_module_filter'),
        array($class, 'processAjaxForm')
      ),
      '#theme' => 'module_filter'
    );
  }


  public static function form_process_module_filter(&$element, FormStateInterface $form_state, &$form) {
    $config = \Drupal::config('module_filter.settings');
    $module_handler = \Drupal::moduleHandler();
    $element['name'] = array(
      '#type' => 'textfield',
      '#title' => (isset($element['#title'])) ? $element['#title'] : t('Filter list'),
      '#default_value' => (isset($element['#default_value'])) ? $element['#default_value'] : ((isset($_GET['filter'])) ? $_GET['filter'] : ''),
      '#size' => (isset($element['#size'])) ? $element['#size'] : 45,
      '#weight' => (isset($element['#weight'])) ? $element['#weight'] : -10,
      '#attributes' => ((isset($element['#attributes'])) ? $element['#attributes'] : array()) + array('autocomplete' => 'off'),
      '#attached' => array(
        'library' => array('module_filter/drupal.module_filter'),
        'drupalSettings' => array(
          'moduleFilter' => array(
            'setFocus' => $config->get('module_filter_set_focus'),
            'tabs' => $config->get('module_filter_tabs'),
            'countEnabled' => $config->get('module_filter_count_enabled'),
            'visualAid' => $config->get('module_filter_visual_aid'),
            'hideEmptyTabs' => $config->get('module_filter_hide_empty_tabs'),
            'dynamicPosition' => (!$module_handler->moduleExists('page_actions')) ? $config->get('module_filter_dynamic_save_position') : FALSE,
            'useURLFragment' => $config->get('module_filter_use_url_fragment'),
            'useSwitch' => $config->get('module_filter_use_switch'),
            'trackRecent' => $config->get('module_filter_track_recent_modules'),
            'rememberActiveTab' => $config->get('module_filter_remember_active_tab'),
            'rememberUpdateState' => $config->get('module_filter_remember_update_state')
          )
        )
      ),
    );
    if (isset($element['#description'])) {
      $element['name']['#description'] = $element['#description'];
    }
    return $element;
  }
}
