<?php

namespace Drupal\purchase\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class PurchaseForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['title'] = [
      '#markup' => $this->entity->get('title')->value,
    ];
    $form['no'] = [
      '#markup' => $this->entity->get('no')->value,
    ];
    $form['created'] = [
      '#markup' => isset($this->entity->get('created')->value) ? \Drupal::service('date.formatter')->format($this->entity->get('created')->value, 'short') : '-',
    ];

    // 币种.
    $ftype = '';
    $storage = \Drupal::entityTypeManager()->getStorage('currency')->loadMultiple();
    foreach ($storage as $currency) {
      $ftype .= $currency->id() . ":" . $currency->label() . ";";
    }
    $ftype = substr($ftype, 0, -1);
    // 供应商.
    $entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    $taxonomy_supplies = $entity_manager->loadTree('supply', 0, NULL, TRUE);
    $str = '';
    foreach ($taxonomy_supplies as $term) {
      $str .= $term->id() . ":" . $term->label() . ";";
    }
    $str = substr($str, 0, -1);
    $form['#attached']['library'] = ['purchase/purchase-form'];
    $form['#attached']['drupalSettings']['purchase']['id'] = $this->entity->id();
    $form['#attached']['drupalSettings']['purchase']['sid'] = $str;
    $form['#attached']['drupalSettings']['purchase']['ftype'] = $ftype;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->entity->get('audit')->value != 0) {
      drupal_set_message('该采购单已发起审批，不再支持采购单编辑', 'error');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // 采购单的新增，使用服务进行添加
    // $this->entity->set('no', $form_state->getValue('no'))->save();
    // @todo 一个采购单只能包含一个币种
    $this->entity->save();
    $form_state->setRedirectUrl(new Url("entity.purchase.collection"));
    drupal_set_message('采购单: ID-' . $this->entity->id() . ' ,编号: ' . $this->entity->label() . ' 保存成功');
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if ($actions['submit'] && $this->entity->get('status')->value != 0) {
      unset($actions['submit']);
    }
    return $actions;
  }

}
