<?php

/**
 * @file
 * Contains \Drupal\ip\Form\BusinesspDeleteForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\String;
use Drupal\hostlog\HostLogFactory;


/**
 * Provides the article delete confirmation form.
 */

class BusinesspDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this busienss ip ? IP : %ip', array(
            '%ip' => $this->entity->get('ip')->value,
      )
    );
  }
  
   /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this part.
    return new Url('ip.ipb.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This business IP  will be delete.');
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

    if($this->entity->get('status')->value == 5) {
      drupal_set_message($this->t('The IP is in use, can not be deleted.'),'error');
    }else{
      // Delete the business ip
      $this->entity->delete();

      drupal_set_message($this->t('The business IP has been deleted.'));
      /** ======================  写入删除业务IP的操作日志 ============= */       
      HostLogFactory::OperationLog('ip')->log($this->entity, 'delete');
      /**================================================== */ 
    }
    

    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
