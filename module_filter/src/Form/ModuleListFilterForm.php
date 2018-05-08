<?php

/**
 * @file
 * 重写模块列表页
 *
 * Contains \Drupal\module_filter\Form\ModuleListFilterForm.
 */
namespace Drupal\module_filter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form\ModulesListForm;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\SafeMarkup;

class ModuleListFilterForm extends ModulesListForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = \Drupal::config('module_filter.settings');
    unset($form['filters']);
    $form['module_filter'] = array(
      '#type' => 'module_filter',
      '#attached' => array(
        'library' => array('module_filter/drupal.module')
      )
    );
    $checkbox_defaults = array(
      ((isset($_GET['enabled'])) ? $_GET['enabled'] : 1) ? 'enabled' : '',
      ((isset($_GET['disabled'])) ? $_GET['disabled'] : 1) ? 'disabled' : '',
      ((isset($_GET['required'])) ? $_GET['required'] : 1) ? 'required' : '',
      ((isset($_GET['unavailable'])) ? $_GET['unavailable'] : 1) ? 'unavailable' : ''
    );
    $form['module_filter']['show'] = array(
      '#type' => 'checkboxes',
      '#default_value' => array_filter($checkbox_defaults),
      '#options' => array('enabled' => t('Enabled'), 'disabled' => t('Disabled'), 'required' => t('Required'), 'unavailable' => t('Unavailable')),
      '#prefix' => '<div id="module-filter-show-wrapper">',
      '#suffix' => '</div>'
    );
    
    if($config->get('module_filter_tabs')) {
      $form['module_filter']['#attached']['library'][] = 'module_filter/drupal.module_filter_tab';
      $module_handler = \Drupal::moduleHandler();
      if(!$module_handler->moduleExists('page_actions') && $config->get('module_filter_dynamic_save_position')) {
        $form['module_filter']['#attached']['library'][] = 'module_filter/drupal.dynamic_position';
      }
    }

    if(!$this->moduleHandler->moduleExists('page_actions')) {
      $form['actions']['#prefix'] = '<div id="module-filter-submit">';
      $form['actions']['#suffix'] = '</div>';
    }
 
    $header = array(
      array('data' => '', 'class' => array('checkbox')),
      array('data' => t('Name'), 'class' => array('name')),
      array('data' => t('Description'), 'class' => array('description'))
    );
    $package_ids = array('all');
    $enabled['all'] = array();
    
    if ($config->get('module_filter_track_recent_modules', 1)) {
      $recent_modules = $config->get('module_filter_recent_modules');
      if(empty($recent_modules)) {
        $$recent_modules = array();
      }
      $recent_modules = array_filter($recent_modules, 'module_filter_recent_filter');
      // Save the filtered results.
      $config = \Drupal::configFactory()->getEditable('module_filter.settings');
      $config->set('module_filter_recent_modules', $recent_modules);
      $config->save();
      
      $package_ids[] = 'recent';
      $enabled['recent'] = array();
      $form['actions']['submit']['#submit'] = array('::module_filter_system_modules_submit_recent', '::submitForm');
    }
    
    // Determine what modules are new (within a week).
    $new_modules = module_filter_new_modules();
    $package_ids[] = 'new';
    $enabled['new'] = array();

    $rows = array();
    $flip = array('even' => 'odd', 'odd' => 'even');
    $packages = Element::children($form['modules']);
    foreach($packages as $package) {
      $package_id = module_filter_get_id($package);
      $package_ids[] = $package_id;
      $rows[] = array(
        '#attributes' => array(
          'id' => $package_id .'-package',
          'class' => array('admin-package-title')
        ),
        array(
          '#wrapper_attributes' => array(
            'colspan' => 3
          ),
          '#markup' => $form['modules'][$package]['#title'],
          '#prefix' => '<h3>',
          '#suffix' => '</h3>'
        )
      );
      $rows[] = array(
        '#attributes' => array(
          'class' => array('admin-package-header')
        ),
        array(
          '#wrapper_attributes' => array('class' => array('checkbox')),
          '#markup' => ''
        ),
        array(
          '#wrapper_attributes' => array('class' => array('name')),
          '#markup' => t('Name')
        ),
        array(
          '#wrapper_attributes' => array('class' => array('description')),
          '#markup' => t('Description')
        )
      );

      $stripe = 'odd';
      $enabled[$package_id] = array();
      
      $items = Element::children($form['modules'][$package]);
      foreach($items as $key) {
        $module = &$form['modules'][$package][$key];
        $is_enabled = isset($module['enable']['#default_value']) ? $module['enable']['#default_value'] : '';
        $enabled['all'][] = $enabled[$package_id][] = $is_enabled;
        if (isset($recent_modules[$key])) {
          $enabled['recent'][] = $is_enabled;
        }
        if (isset($new_modules[$key])) {
          $enabled['new'][] = $is_enabled;
        }

        $row = array();
        
        $version = !empty($module['version']['#markup']);
        $requires = !empty($module['#requires']);
        $required_by = !empty($module['#required_by']);
        
        $toggle_enable = '';
        if (isset($module['enable']['#type']) && $module['enable']['#type'] == 'checkbox') {
          unset($module['enable']['#title']);
          $class = ($is_enabled ? 'enabled' : 'off');
          if (!empty($module['enable']['#disabled'])) {
            $class .= ' disabled';
          }
          $toggle_enable = '<div class="js-hide toggle-enable ' . $class . '"><div>&nbsp;</div></div>';
        }
        $row[] = array(
          '#parents' => array('modules', $package_id, $key, 'enable'),
          '#prefix' => $toggle_enable,
          '#wrapper_attributes' => array('class' => array('checkbox'))
        ) + $module['enable'];
        
        $row[] = array(
          '#prefix' => '<strong>',
          '#suffix' => '</strong><span class="module-machine-name">(' . $key . ')</span>',
          '#wrapper_attributes' => array('class' => array('name')),
        ) + $module['name'];
        
        $description = '<span class="details"><span class="text">' . drupal_render($module['description']) . '</span></span>';
        if ($version || $requires || $required_by) {
          $description .= '<div class="requirements">';
          if ($version) {
            $description .= '<div class="admin-requirements">' . t('Version: !module-version', array('!module-version' => drupal_render($module['version']))) . '</div>';
          }
          if ($requires) {
            $description .= '<div class="admin-requirements">' . t('Requires: !module-list', array('!module-list' => implode(', ', $module['#requires']))) . '</div>';
          }
          if ($required_by) {
            $description .= '<div class="admin-requirements">' . t('Required by: !module-list', array('!module-list' => implode(', ', $module['#required_by']))) . '</div>';
          }
          $description .= '</div>';
        }
        $help = isset($module['links']['help']);
        $permissions = isset($module['links']['permissions']);
        $configure = isset($module['links']['configure']);
        if($help || $permissions || $configure) {
          $description .= '<div class="links">';
          if($help) {
            $description .= drupal_render($module['links']['help']);
          }
          if($permissions) {
            $description .= drupal_render($module['links']['permissions']);
          }
          if($configure) {
            $description .= drupal_render($module['links']['configure']);
          }
          $description .'</div>';
        }
        $row[] = array(
          '#prefix' => '<div class="inner expand" role="button">',
          '#suffix' => '</div>',
          '#wrapper_attributes' => array('class' => array('description')),
          '#markup' => $description
        );
        
        $class = array(module_filter_get_id($package) . '-tab', 'module', $stripe);
        if (isset($recent_modules[$key])) {
          $class[] = 'recent-module';
        }
        if (isset($new_modules[$key])) {
          $class[] = 'new-module';
        }
        $rows[] = array(
          '#attributes' => array(
            'class' => $class
          ),
        ) + $row;

        $stripe = $flip[$stripe];
      }
      
      $form['modules'][$package]['#printed'] = TRUE;
    }
    
    if($config->get('module_filter_count_enabled')) {
      $enabled_counts = array();
      foreach ($enabled as $package_id => $value) {
        $enabled_counts[$package_id] = array(
          'enabled' => count(array_filter($value)),
          'total' => count($value),
        );
      }
      $form['module_filter']['#attached']['drupalSettings']['moduleFilter'] = array(
        'packageIDs' => $package_ids,
        'enabledCounts' => $enabled_counts
      );
    }
    unset($form['modules']);
    $form['modules'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => array()
    );
    foreach($rows as $key=>$row) {
      $form['modules'][$key] = $row;
    }
    $form['#theme'] = 'module_filter_system_modules_tabs';
    return $form;
  }
  
  public function module_filter_system_modules_submit_recent(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('module_filter.settings');
    $recent_modules = array();
    if($recent = $config->get('module_filter_recent_modules')) {
      $recent_modules = $recent;
    }
    $modules = $this->buildModuleList($form_state);
    foreach($modules as $types) {
      foreach($types as $key=>$value) {
        $recent_modules[$key] = REQUEST_TIME;
      }
    }
    $config->set('module_filter_recent_modules', $recent_modules);
    $config->save();
  }
}
