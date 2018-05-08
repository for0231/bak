<?php

namespace Drupal\requirement\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 *
 */
class RequirementForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['no'] = [
      '#markup' => $this->entity->get('no')->value,
      '#title' => '需求单编号',
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('title')->value,
    ];

    $form['num'] = [
      '#type' => 'textfield',
      '#default_value' => $this->entity->get('num')->value,
    ];
    $select_requiretype = getRequirementType();
    $form['requiretype'] = [
      '#type' => 'select',
      '#options' => $select_requiretype,
      '#default_value' => $this->entity->get('requiretype')->value,
      '#description' => '需求单类型, 立即执行或计划执行或周期执行',
    ];

    $form['requiredate'] = [
      '#type' => 'date',
      '#required' => TRUE,
      '#default_value' => isset($this->entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($this->entity->get('requiredate')->value, 'html_date') : '-',
    ];

    if (!$this->entity->isNew()) {
      $user = $this->entity->get('uid')->entity;
      $form['user_name'] = [
        '#markup' => empty($user->get('realname')->value) ? $user->get('name')->value : $user->get('realname')->value,
      ];

      $form['user_depart'] = [
        '#markup' => empty($user->get('depart')->value) ? '-' : taxonomy_term_load($user->get('depart')->value)->label(),
      ];
      $form['user_company'] = [
        '#markup' => empty($user->get('depart')->value) ? '-' : taxonomy_term_load($user->get('company')->value)->label(),
      ];
    }
    $form['#attached']['library'] = ['requirement/requirement-form'];
    $form['#attached']['drupalSettings']['requirement']['rid'] = $this->entity->id();

    $form['actions'] = [
      '#type' => 'actions',
    ];
    // @todo 此处无效，待进一步处理。
    if ($this->entity->get('audit')->value != 0) {
      $form['actions'] = [
        '#id' => '#edit-submit',
        '#attributes' => [
          'disabled' => 'disabled',
        ],
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => 'EEEEE',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->entity->get('audit')->value != 0) {
      drupal_set_message('该需求单已发起审批，不再支持需求单编辑', 'error');
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // @todo unitprice在采购或付款单详情里面去添加
    $requiredate = $form_state->getValue('requiredate');
    if ($requiredate == '-') {
      $requiredate = date('Y-m-d', time());
    }
    $this->entity
      ->set('requiredate', strtotime($requiredate))
      ->set('requiretype', $form_state->getValue('requiretype'));

    $this->entity->save();

    // 更改save_status.
    \Drupal::service('part.partservice')->setSaveStatus($this->entity);
    $form_state->setRedirectUrl(new Url("entity.requirement.collection"));
    drupal_set_message('需求单: ID-' . $this->entity->id() . ' ,编号: ' . $this->entity->label() . ' 保存成功');
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
