<?php
/**
 * @file   凭证管理form
 * Contains \Drupal\member\Form\CertificateManageForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CertificateManageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'certificate_manage_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['page_certificate'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('Order_title')
      )
    );
    $form['page_certificate']['title'] = array(
      '#markup' => '<b>'. t('Certificate management') .'</b>'
    );
    $form['proof'] = array(
      '#type' => 'tableselect',
      '#header' => array(t('Order code'), t('Amount'), t('Time'), t('Status')),
      '#options' => array(),
      '#attributes' => array('class' => array('user-table')),
      '#empty' => t('No data.'),
      '#process' => array(
        array(get_class($this), 'processTableselect'),
      ),
    );
    $condtion['uid'] =  \Drupal::currentuser()->id();
    $condtion['status'] = array('field' => 'status', 'value'=> array(3,4,5), 'op' => 'IN');
    $oids = \Drupal::service('order.orderservice')->userOrderList($condtion);
    $orders = entity_load_multiple('order', $oids);
    $member_service = \Drupal::service('member.memberservice');
    foreach($orders as $order) {
      $amount = $order->getSimpleValue('order_price') - $order->getSimpleValue('discount_price');
      $date = date('Y-m-d H:i:s',$order->getSimpleValue('payment_date'));
      $oid = $order->id();
      $proofs = $member_service->loadProof(array('order_ids' => array('value' => $oid, 'op' => 'like')));
      if(empty($proofs)) {
        $form['proof']['#options'][$order->id()] = array(
          $order->label(),
          $amount, 
          $date , 
          $this->t('Not generated'),
        );
      } else {
        $proof = reset($proofs);
        $form['proof']['#options'][$order->id()] = array(
          $order->label(),
          $amount, 
          $date , 
          \Drupal::l('Re browse', new Url('member.my.certificate.build', array('key' => $proof->id))),
          '#attributes' => array('disabled' => 'disabled'),
        );
      }
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Generate certificate')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $oids = $form_state->getValue('proof');
    $select_oids = array();
    foreach($oids as $key => $val) {
      if($val) {
        $select_oids[] = $key;
      }
    }
    if(empty($select_oids)) {
      $form_state->setErrorByName('op', $this->t('Please select the item to create a certificate'));
    } else {
      $form_state->select_oids = $select_oids;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $oids = $form_state->select_oids;
    $str = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz');
    $key = \Drupal::currentUser()->id(). substr($str, 1,10);
    $_SESSION['build_pdf'] = array('key' => $key, 'oids' => $oids, 'save' => false);
    $form_state->setRedirectUrl(new Url('member.my.certificate.build', array('key' => $key)));
  }
  
  /**
   * Creates checkbox or radio elements to populate a tableselect table.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   tableselect element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function processTableselect(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#multiple']) {
      $value = is_array($element['#value']) ? $element['#value'] : array();
    }
    else {
      // Advanced selection behavior makes no sense for radios.
      $element['#js_select'] = FALSE;
    }

    $element['#tree'] = TRUE;

    if (count($element['#options']) > 0) {
      if (!isset($element['#default_value']) || $element['#default_value'] === 0) {
        $element['#default_value'] = array();
      }

      // Create a checkbox or radio for each item in #options in such a way that
      // the value of the tableselect element behaves as if it had been of type
      // checkboxes or radios.
      foreach ($element['#options'] as $key => $choice) {
        // Do not overwrite manually created children.
        if (!isset($element[$key])) {
          if ($element['#multiple']) {
            $title = '';
            if (isset($element['#options'][$key]['title']) && is_array($element['#options'][$key]['title'])) {
              if (!empty($element['#options'][$key]['title']['data']['#title'])) {
                $title = new TranslatableMarkup('Update @title', array(
                  '@title' => $element['#options'][$key]['title']['data']['#title'],
                ));
              }
            }
            $element[$key] = array(
              '#type' => 'checkbox',
              '#title' => $title,
              '#title_display' => 'invisible',
              '#return_value' => $key,
              '#default_value' => isset($value[$key]) ? $key : NULL,
              '#attributes' => $element['#attributes'],
            );
           
            if(isset($element['#options'][$key]['#attributes']['disabled'])) {
              $element[$key]['#disabled'] = true;
            }
          }
          else {
            // Generate the parents as the autogenerator does, so we will have a
            // unique id for each radio button.
            $parents_for_id = array_merge($element['#parents'], array($key));
            $element[$key] = array(
              '#type' => 'radio',
              '#title' => '',
              '#return_value' => $key,
              '#default_value' => ($element['#default_value'] == $key) ? $key : NULL,
              '#attributes' => $element['#attributes'],
              '#parents' => $element['#parents'],
              '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
              '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
            );
          }
          if (isset($element['#options'][$key]['#weight'])) {
            $element[$key]['#weight'] = $element['#options'][$key]['#weight'];
          }
        }
      }
    }
    else {
      $element['#value'] = array();
    }
    return $element;
  }
}
