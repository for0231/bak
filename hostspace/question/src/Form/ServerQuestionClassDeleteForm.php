<?php

/**
 * @file
 * Contains \Drupal\question\Form\ServerQuestionClassDeleteForm.
 */

namespace Drupal\question\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\String;

/**
 * Provides the question category delete confirmation form.
 */

class ServerQuestionClassDeleteForm extends ContentEntityConfirmFormBase {
	/**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this category ? : %category', array(
        '%category' => $this->entity->get('class_name')->value,
      )
    );
  }
	
	 /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('question.category.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This category will be delete.');
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // 在删除故障分类的时候，判断该分类下是否存在故障信息。若存在，则提示错误信息。
    $question_arr = entity_load_multiple_by_properties('question',array('parent_question_class' => $this->entity->id()));

    if(!empty($question_arr)){
      drupal_set_message(t('There are some question under this category.'), 'error');
      $form_state->setRedirectUrl(new Url('question.category.admin'));
      return;
    }
    // 执行故障分类删除。
    $this->entity->delete();
    drupal_set_message($this->t('The category: %category has been deleted.', array('%category' => $this->entity->get('class_name')->value)));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
