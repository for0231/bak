<?php
namespace Drupal\idc\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "entity_reference_autocomplete_server",
 *   label = @Translation("Autocomplete server"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AutocompleteServerWidget extends EntityReferenceAutocompleteWidget {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $title = $element['#title'];
    $discription = $element['#description'];
    unset($element['#title']);
    unset($element['#description']);
    $auto_element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = array();
    $terms = entity_load_multiple('server_type');
    foreach($terms as $key=>$term) {
      $options[$key] = $term->label();
    }
    $elements['server_type'] = array(
      '#type' => 'select',
      '#options' => $options,
      '#weight' => '-1',
      '#title' => $title
    )+ $element;

    $elements['target_id'] = array(
      '#attributes' => array(
         'js_name' => 'autocomplete_server'
      ),
      '#description' => $discription,
      '#attached' => array(
        'library' => array('idc/drupal.autocompleteServerWidget')
      )
    ) + $auto_element['target_id'];

    return $elements;
  }
}
