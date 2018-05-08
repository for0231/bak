<?php
/**
 * @file
 * Contains \Drupal\contract\Plugin\Field\FieldWidget\AttachmentWidget.
 */

namespace Drupal\contract\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Url;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'attachment_generic' widget.
 *
 * @FieldWidget(
 *   id = "attachment_generic",
 *   label = @Translation("Attachment"),
 *   field_types = {
 *     "attachment_file"
 *   }
 * )
 */

class AttachmentWidget extends FileWidget {

  public static function process($element, FormStateInterface $form_state, $form) {
    $element = parent::process($element, $form_state, $form);
    $item = $element['#value'];
     if($item['fids']) {
       $element['file_type'] = array(
        '#type' => 'select',
        '#title' => '附件类型',
        '#options' => array('' => '请选择', '1' => '资料', '2' => '其他'),
        '#default_value' => isset($item['file_type']) ? $item['file_type'] : '',
        '#weight' => '100',
      );
    }
    return $element; 
  }
}
