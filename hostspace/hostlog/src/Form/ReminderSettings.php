<?php

/**
 * @file 网站提醒设置
 * Contains Drupal\hostlog\Form\ReminderSettings
 */

namespace Drupal\hostlog\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReminderSettings extends ConfigFormBase {
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
    return 'hostlog_reminder_settings';
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hostlog.settings'];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hostlog.settings');
    $form['reminder_settings'] = array(
      '#type' => 'details',
      '#open' => true,
      '#title' => t('Reminder Settings(提醒设置)'),
    );
    $form['reminder_settings']['retips'] = array(
      '#type' => 'select',
      '#title' => t('Reminder Times(提醒次数)'),
      '#options' => array('只提醒一次', '重复提醒'),
      '#default_value' => empty($config->get('retips')) ? 0 : $config->get('retips'),
    );
    $form['reminder_settings']['isaudio'] = array(
      '#type' => 'select',
      '#title' => t('Audio Reminder(声音提醒)'), //声音提醒
      '#options' => array('不需要', '需要'),
      '#default_value' => empty($config->get('retips')) ? 0 : $config->get('retips'),
    );
    $form['reminder_settings']['interval'] = array(
      '#type' => 'number',
      '#title' => 'Interval(间隔时间-分钟)', //过期时间延迟分钟
      '#default_value' => empty($config->get('interval')) ? 5 : $config->get('interval'),
    );
    $form['reminder_settings']['expiration_delay'] = array(
      '#type' => 'number',
      '#title' => 'Expiration(延迟过期时间-分钟)', //过期时间延迟分钟
      '#default_value' => empty($config->get('expiration_delay')) ? 20 : $config->get('expiration_delay'),
    );

    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('hostlog.settings')
      ->set('retips', $form_state->getValue('retips'))
      ->set('expiration_delay', $form_state->getValue('expiration_delay'))
      ->set('isaudio', $form_state->getValue('isaudio'))
      ->set('interval', $form_state->getValue('interval'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
