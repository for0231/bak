<?php

/**
 * @file
 * Contains \Drupal\common\Form\CommonAreaDataImportForm.
 */

namespace Drupal\common\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Provides the part logging filter form.
 */
class CommonAreaDataImportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  	return 'common_area_data_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
///    $s = get_config_types_by_specified_path('common.settings', 'server.server_equipment_status');
    $form['#attributes']['enctype'] = 'multipartform-data';
  	$form['file_upload'] = array(
      '#type' => 'file',
      '#title' => $this->t('Please choose area data file'),
      '#description' => $this->t('Note: The file must be *.csv suffix file.'),
  	);

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
  	return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (($handle = @fopen($_FILES['files']['tmp_name']['file_upload'], "r")) !== FALSE) {
      $rows = array();
      $i = 1;
      while (($row = fgets($handle)) !== FALSE) {
        $i++;
        //exit();
      }
    }
    else {
      $form_state->setErrorByName('file_upload', '请上传合法的CSV格式文件');
      return ;
    }
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('submitted');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {

  }
}
