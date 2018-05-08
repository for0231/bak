<?php

/**
 * @file
 * Contains \Drupal\kaoqin\Form\KaoqinUponDeleteForm.
 */

namespace Drupal\kaoqin\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the part delete confirmation form.
 */
class KaoqinUponDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('确认删除这条排班记录?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.kaoqin.upon.listbuilder');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('姓名: %name, 时间: %date。', array(
      '%name' => $this->entity->get('user')->entity->get('realname')->value,
      '%date' => date('Y-m-d', $this->entity->get('datetime')->value),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    drupal_set_message($this->t('成功删除排班记录'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}

