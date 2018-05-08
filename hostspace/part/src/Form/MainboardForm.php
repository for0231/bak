<?php

/**
 * @file
 * Contains \Drupal\part\Form\MainboardForm.
 */

namespace Drupal\part\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for Mainboard edit.
 */
class MainboardForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $config = \Drupal::config('part.settings');
    $term_memory_slots = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren($config->get('part.mainboard.memory_slot'));
    $terms['_none'] = 'None';
    foreach($term_memory_slots as $key => $value) {
      $terms[$key] = $value->getName();
    }
    $entity = $this->entity;
    $memory_solt_value = $entity->get("memory_slot")->value;
    $memory_slot_number_value = '_none';
    $memory_slot_trem_value = '_none';
    if(!empty($memory_solt_value)) {
      $solt_array = explode('*',$memory_solt_value);
      $memory_slot_number_value = $solt_array[0];
      $memory_slot_trem_value = $solt_array[1];
    }
    $form['memory_slot_group'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
      '#weight' => 46,
    );
    $form['memory_slot_group']['memory_slot_number'] = array(
      '#type' => 'select',
      '#title' => t('Memory Slot'),
      '#required' => true,
      '#options'=> array('_none' => 'None',2=>2,3=>4,4=>8,5=>16),
      '#default_value' => $memory_slot_number_value
    );
    $form['memory_slot_group']['memory_slot_trem'] = array(
      '#type' => 'select',
      '#options' => $terms,
      '#default_value' => $memory_slot_trem_value
    );
    $form['stock'] = array(
      '#type' => 'number',
      '#title' => t('Storage Number'),
      '#weight' => 95,
      '#default_value' => 0,
    );
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $memory_slot_number = $form_state->getValue('memory_slot_number');
    $memory_slot_trem = $form_state->getValue('memory_slot_trem');
    if($memory_slot_number== '_none' && $memory_slot_trem == '_none') {
      $entity->set('memory_slot','');
    } else {
      $entity->set('memory_slot', $memory_slot_number. '*' . $memory_slot_trem);
    }
    $action = 'update';
    if($entity->isNew()) {
      $action = 'insert';
    }
    $entity->save();
    HostLogFactory::OperationLog('part')->log($entity, $action);
    drupal_set_message($this->t('Mainboard saved successfully'));
  }
}
