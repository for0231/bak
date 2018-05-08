<?php

namespace Drupal\server\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'image_image' widget.
 *
 * @FieldWidget(
 *   id = "part_select_status",
 *   label = @Translation("Single value select Widget"),
 *   field_types = {
 *     "multi_part",
 *   }
 * )
 */
class  PartSelectStatusWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $parent_element =  parent::formElement($items, $delta, $element, $form, $form_state);
    $elements['target_id'] = array(
      '#multiple' => false,
      '#weight' => '-1',
      '#default_value' => $items[$delta]->target_id,
      '#attached' => array(
        'library' => array('server/drupal.multi-part-widget')
      )
    ) + $parent_element;
    $elements['value'] = array(
      '#type' => 'checkbox',
      '#title' => t('Basic parts'),
      '#default_value' => $items[$delta]->value,
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach($values as &$item) {
      if(isset($item['target_id'][0]['target_id']) && !empty($item['target_id'][0]['target_id'])) {
        $target_id = $item['target_id'][0]['target_id'];
        $item['target_id'] = $target_id;
      } else {
        $item['target_id'] = NULL;
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmptyOption() {
    return NULL;
  }
}
