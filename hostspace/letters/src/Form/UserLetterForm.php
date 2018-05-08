<?php

/**
 * @file
 * Contains \Drupal\letters\Form\UserLetterForm.
 */

namespace Drupal\letters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class UserLetterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'user_letter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_user = \Drupal::currentUser();
    $data = \Drupal::service('letters.letterservice')->getinboxData($current_user->id());
    $form['letter_list'] = array(
      '#theme' => 'member_letter_box',
      '#letters' => $data,
      '#notReadCount' =>  count(\Drupal::service('letters.letterservice')->getNotReadCountByUid($current_user->id()))
    );
    $form['pager']['#type'] = 'pager';
    $form['#attached']['library'] = array('letters/letters.user_letter');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }


}
