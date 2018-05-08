<?php
/**
 * @file
 * Contains \Drupal\hc_alipay\Form\AlipayConfigForm
 */
namespace Drupal\hc_alipay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class AlipayConfigForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'alipay_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $alipay_config = \Drupal::config('hc_alipay.settings');
    $partner = $alipay_config->get('alipay.partner');
    $key = $alipay_config->get('alipay.key');
    $transport = $alipay_config->get('alipay.transport');

    $form['partner'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Partner'),
      '#default_value' => !empty($partner) ? $partner : '',
    );
    $form['key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => !empty($key) ? $key : '',
    );
    $form['transport'] = array(
      '#type' => 'select',
      '#title' => $this->t('Transport'),
      '#options' => array(
        'http' => 'HTTP',
        'https' => 'HTTPS',
       ),
      '#default_value' => !empty($transport) ? $transport : '',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('hc_alipay.settings');
    $config->set('alipay.partner', $form_state->getValue('partner'));
    $config->set('alipay.key', $form_state->getValue('key'));
    $config->set('alipay.transport', $form_state->getValue('transport'));
    $config->save();
    drupal_set_message('Alipay configuration success');
    $form_state->setRedirectUrl(new Url('alipay.config'));
  }
}
