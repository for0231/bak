<?php
/**
 * @file
 * Contains \Drupal\knowledge\Form\KnowledgeTypeDeleteForm.
 */

namespace Drupal\knowledge\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the part delete confirmation form.
 */
class KnowledgeTypeDeleteForm  extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return '确定要删除知识分类';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.knowledge_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '删除后将无法恢复';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    if($entity->get('parent_id')->value) {
      $storage = $this->entityManager->getStorage('knowledge_content');
      $query = $storage->getQuery();
      $query->condition('type_id', $entity->id());
      $query->range(0, 1);
      $items = $query->execute();
      if(!empty($items)) {
        $form_state->setErrorByName('op', '此分类已有知识数据，不能删除！');
      }
    } else {
      $items = entity_load_multiple_by_properties('knowledge_type', array('parent_id' => $entity->id()));
      if(!empty($items)) {
        $form_state->setErrorByName('op', '已经存在子分类数据，不能删除此分类！');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    drupal_set_message('删除成功');
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
