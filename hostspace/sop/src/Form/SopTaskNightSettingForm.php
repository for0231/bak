<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskNightSettingForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for configurable actions.
 */
class SopTaskNightSettingForm extends ConfigFormBase {
  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sop_technight_setting_form';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sop.settings'];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sop.settings');
    $form['technight_settings'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('技术夜班时间设置'),
      '#description' => t('备注: 时间以24小时整点为准'),
    );
    $form['technight_settings']['starttime'] = array(
      '#type' => 'number',
      '#title' => t('夜晚开始时间'),
      '#default_value' => empty($config->get('technight.starttime')) ? 0 : $config->get('technight.starttime'),
    );
    $form['technight_settings']['expiretime'] = array(
      '#type' => 'number',
      '#title' => t('夜晚结束时间'),
      '#default_value' => empty($config->get('technight.expiretime')) ? 0 : $config->get('technight.expiretime'),
    );
    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sop.settings')
      ->set('technight.starttime', $form_state->getValue('starttime'))
      ->set('technight.expiretime', $form_state->getValue('expiretime'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
