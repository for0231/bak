<?php
namespace Drupal\idc\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "radios_number",
 *   label = @Translation("Radios number"),
 *   description = @Translation("Radios number field."),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class RadiosNumberWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'options' => array(),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $discription = $element['#description'];
    unset($element['#description']);
    $elements['value'] = array(
      '#type' => 'radios',
      '#options' => $this->getSetting('options') + array('-1' => t('Other')),
      '#default_value' => 1,
      '#attached' => array(
        'library' => array('idc/drupal.RadiosNumberWidget')
      )
    ) + $element;
    $elements['custom_value'] = array(
      '#type' => 'number',
      '#size' => 3,
      '#min' => 1,
      '#description' => $discription
    );
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach($values as $key => $value) {
      if($value['value'] == -1) {
        $values[$key]['value'] = $value['custom_value'];
      }
    }
    return $values;
  }
}
