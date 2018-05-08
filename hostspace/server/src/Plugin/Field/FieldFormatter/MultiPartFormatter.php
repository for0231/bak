<?php
namespace Drupal\server\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'multi_part_default' formatter.
 *
 * @FieldFormatter(
 *   id = "multi_part_default",
 *   label = @Translation("Multi Part formatter"),
 *   field_types = {
 *     "multi_part"
 *   }
 * )
 */
class MultiPartFormatter extends FormatterBase {
 /**
  * {@inheritdoc}
  */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $output = $item->value;
      $elements[$delta] = array('#markup' => $output);
    }
    return $elements;
  }
}
