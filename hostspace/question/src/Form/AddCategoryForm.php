<?php

/**
 * @file
 * Contains \Drupal\question\Form\AddCategoryForm.
 */

namespace Drupal\question\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provide a form controller for question category add.
 */

class AddCategoryForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    return $form;
  }
 
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->save();
    drupal_set_message($this->t('The category %category has been created.',array('%category' => $entity->label())));
    $form_state->setRedirectUrl(new Url('question.category.admin'));
  }
}
