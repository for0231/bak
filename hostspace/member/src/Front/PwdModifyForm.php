<?php
/**
 * @file
 * Contains \Drupal\member\Front\PwdModifyForm.
 */

namespace Drupal\member\Front;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormBuilder;

class PwdModifyForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'modify_pwd';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['account']['pass'] = array(
      '#type' => 'password_confirm',
      '#size' => 25,
      '#description' => $this->t('To change the current user password, enter the new password in both fields.'),
    );  
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => 'Save'
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) { 
    $pass = $form_state->getValue('pass');
    if(!$pass) {
      $form_state->setErrorByName('pass',$this->t('Please fill out your new password.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $confirm = $form_state->getValue('pass');
    $user = entity_load('user', \Drupal::currentUser()->id());
    $user->set('pass', $confirm);
    $user->save();
    drupal_set_message($this->t('Password modification success。'), 'info');
  }
}
