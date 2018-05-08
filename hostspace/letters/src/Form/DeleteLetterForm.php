<?php

/**
 * @file
 * Contains \Drupal\letters\Form\DeleteLetterForm.
 */

namespace Drupal\letters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class DeleteLetterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'delete_letter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $flag=null, $letter_id=null) {
    $letter = \Drupal::service('letters.letterservice')->getLetter($letter_id, $flag);
    if(!$letter) {
      return array('#markup' => '无法完成的请求: -> 编号'. $letter_id . '对应的信件不存在！');
    }

    $form['letter_id'] = array(
      '#type' => 'value',
      '#value' => $letter->id
    );
    $form['flag'] = array(
      '#type' => 'value',
      '#value' => $flag
    );    
    $form['question'] = array( 
      '#type' => 'container',
      '#markup' => $this->t('是否要删除信件： %letter', array('%letter' => $letter->title))
    );
    $form['yes'] = array(
      '#type' => 'submit',
      '#value' => 'Delete',        
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => 'Cancel',
      '#url' => ($flag == 'outbox') ? new Url('letter.outbox') : new Url('letter.inbox')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $letter_id = $form_state->getValue('letter_id');
    $flag = $form_state->getValue('flag');
    // 重定向链接UTL
    $redirect_url = ($flag == 'outbox') ? new Url('letter.outbox') : new Url('letter.inbox'); 

    $effect_rows = \Drupal::service('letters.letterservice')->deleteLetter($letter_id, $flag);
    if($effect_rows) {
      drupal_set_message('删除成功！');
    } else {
      drupal_set_message('删除失败！');
    }
    $form_state->setRedirectUrl($redirect_url);
  }
}
