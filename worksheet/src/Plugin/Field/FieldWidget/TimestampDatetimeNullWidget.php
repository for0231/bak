<?php

namespace Drupal\worksheet\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\Plugin\Field\FieldWidget\TimestampDatetimeWidget;
/**
 * Plugin implementation of the 'datetime timestamp' widget.
 *
 * @FieldWidget(
 *   id = "datetime_timestamp_null",
 *   label = @Translation("Datetime Timestamp Null"),
 *   field_types = {
 *     "timestamp"
 *   }
 * )
 */
class TimestampDatetimeNullWidget extends TimestampDatetimeWidget {
  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      // @todo The structure is different whether access is denied or not, to
      //   be fixed in https://www.drupal.org/node/2326533.
      if (isset($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $date = $item['value'];
        $item['value'] = $date->getTimestamp();
      }
      elseif (isset($item['value']['object']) && $item['value']['object'] instanceof DrupalDateTime) {
        $date = $item['value']['object'];
        $item['value'] = $date->getTimestamp();
      }
      else {
        $item['value'] = null;
      }
    }
    return $values;
  }

}
