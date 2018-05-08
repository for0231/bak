<?php

/**
 * @file
 * Contains \Drupal\product\Form\ProductForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\hostlog\HostLogFactory;


class ProductForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $options = array('' => '-- select --');
    $business_list = entity_load_multiple('product_business');
    foreach($business_list as $business) {
      if($business->getSimpleValue('resource_lib') != 'part_lib') {
        $options[$business->id()] = $business->label();
      }
    }
    $default_business_value = '';
    $default_business_text = '';
    if(!$entity->isNew()) {
      $default_business = \Drupal::service('product.default.business')->getListByProduct($entity->id());
      foreach($default_business as $item) {
        if(empty($default_business_value)) {
          $default_business_value = $item->businessId . '=' . $item->business_content;
        } else {
          $default_business_value .= ',' . $item->businessId . '=' . $item->business_content;
        }
        $business = $business_list[$item->businessId];
        $dataText = $business->label();
        $content_text = product_business_value_text($business, $item->business_content);
        $htmlContent = '<span>'. $dataText .'：'. $content_text .' </span><a class="remove-business" href="javascript:void(0)">Remove</a>';
        $default_business_text .= '<div business-id="'. $item->businessId .'">'. $htmlContent .'</div>';
      }
    }
    $form['default_business_value'] = array(
      '#type' => 'hidden',
      '#default_value' => $default_business_value
    );
    $form['business_group'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => 'container-inline'
      ),
      '#weight' => 30,
      '#suffix' => SafeMarkup::format('<div id="display_business_wrapper">'. $default_business_text .'</div>', array())
    );
    $form['business_group']['business_data'] = array(
      '#type' => 'select',
      '#title' => t('Default business'),
      '#options' => $options,
      '#ajax' => array(
        'callback' => '::loadBusinessContent',
        'wrapper' => 'business_content_wrapper',
        'method' => 'html'
      )
    );
    $form['business_group']['content_wrapper'] = array(
      '#type' => 'container',
      '#id' => 'business_content_wrapper'
    );
    $form['business_group']['content_wrapper']['business_content'] = array();
    $business_id = $form_state->getValue('business_data');
    if(!empty($business_id)) {
      $business = $business_list[$business_id];
      $ctl = product_business_control($business);
      $operate = $business->getSimpleValue('operate');
      if($operate == 'select_and_number') {
        $form['business_group']['content_wrapper']['business_content'] = array(
          '#type' => 'container'
        );
        $form['business_group']['content_wrapper']['business_content']['select_content'] = $ctl['select'];
        $form['business_group']['content_wrapper']['business_content']['number_content'] = $ctl['number'];
      } else {
         $form['business_group']['content_wrapper']['business_content'] = $ctl;
      }
    }
    $form['business_group']['business_submit'] = array(
      '#type' => 'button',
      '#value' => '+',
      '#pre_render' => array(
         array(get_class($this), 'preRenderButton'),
      ),
      '#attached' => array(
        'library' => array('product/drupal.product-default-business'),
      )
    );
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';

    $form['room_check'] = array(
      '#type' => 'checkboxes',
      '#title' => t('所属机房'),
      '#required' => true,
      '#options' => $this->checkRoomOptions(),
      '#weight' => 13,
    );
    if ($entity->get('rids')->value) {
      $form['room_check']['#default_value'] = (Array) json_decode($entity->get('rids')->value);
    }
    return $form;
  }

  public static function loadBusinessContent(array $form, FormStateInterface $form_state) {
    return $form['business_group']['content_wrapper']['business_content'];
  }

  public static function preRenderButton($element) {
    $element['#attributes']['type'] = 'button';
    Element::setAttributes($element, array('id', 'name', 'value'));

    $element['#attributes']['class'][] = 'button';
    if (!empty($element['#button_type'])) {
      $element['#attributes']['class'][] = 'button--' . $element['#button_type'];
    }
    // @todo Various JavaScript depends on this button class.
    $element['#attributes']['class'][] = 'form-submit';

    if (!empty($element['#attributes']['disabled'])) {
      $element['#attributes']['class'][] = 'is-disabled';
    }

    return $element;

  }
  public function checkRoomOptions() {
    $rooms = entity_load_multiple('room');
    foreach ($rooms as $row) {
      $option[$row->id()] = $row->label();
    }
    return $option;
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $options = array();
    $value = $form_state->getValue('default_business_value');
    if(!empty($value)) {
      $value_array = explode(',', $value);
      $error = false;
      foreach($value_array as $item) {
        $item_array = explode('=', $item);
        if(empty($item_array[0]) || empty($item_array[1])) {
          $error = true;
          continue;
        }
        $options[] = array(
          'businessId' => $item_array[0],
          'business_content' => $item_array[1]
        );
      }
      if($error) {
        drupal_set_message($this->t('The default business is wrong, do not save error value.'), 'warning');
      }
    }
    $entity->default_business = $options;
    $action = 'update';
    if($entity->isnew()) {
      $action = 'insert';
      $entity->add_business_price = true;
    }
    $rooms = $form_state->getValue('room_check');
    $jsonencode = json_encode($rooms);
    $entity->set('rids', $jsonencode);
    $entity->save();
    //-------保存日志 -----
    HostLogFactory::OperationLog('product')->log($entity, $action);
    //---------------------
    drupal_set_message($this->t('Product saved successfully'));
    $form_state->setRedirectUrl(new Url('admin.product.list'));
  }
}
