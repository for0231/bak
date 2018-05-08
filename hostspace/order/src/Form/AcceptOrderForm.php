<?php

/**
 * @file
 * Contains \Drupal\order\Form\AcceptOrderForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\order\ServerDistribution;

/**
 * Provides the article delete confirmation form.
 */
class AcceptOrderForm extends ContentEntityConfirmFormBase {
	/**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to order %order Distribution server', array(
      '%order' => $this->entity->getSimpleValue('code'),
    ));
  }

	 /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this part.
    return new Url('admin.order.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Distribution');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $dis = ServerDistribution::createInstance();
    $dis->orderDistributionServer($entity);
    drupal_set_message($this->t('Accept Distribution Success!'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
