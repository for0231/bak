<?php

namespace Drupal\part\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 *
 */
class PartForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $route_match = \Drupal::routeMatch();
    $this->requirement = $route_match->getParameter('requirement');

    $form['num'] = [
      '#type' => 'textfield',
      '#title' => '数量',
      '#weight' => 30,
      '#required' => TRUE,
      '#default_value' => $this->entity->get('num')->value,
    ];
    $field_storage = FieldStorageConfig::loadByName('taxonomy_term', 'field_description_two');
    $field = FieldConfig::loadByName('taxonomy_term', 'parts', 'field_caiwubianhao');
    $exist_parts = Vocabulary::load('parts');
    $taxonomy_term = taxonomy_term_load(140);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $num = $form_state->getValue('num');
    if ($num <= 0) {
      $form_state->setErrorByName('num', '请重新输入数量');
    }
    $form_state->requirement = $this->requirement;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $taxono = taxonomy_term_load($this->entity->get('nid')->target_id);
    $name = $taxono->label();
    $entity_manager = \Drupal::service('entity.manager')->getStorage('taxonomy_term');
    $parents = $entity_manager->loadAllParents($taxono->id());
    $pp = array_reverse($parents);
    unset($pp[count($pp) - 1]);
    $type = '';
    $caiwubianhao = [];
    foreach ($pp as $parent) {
      $type .= $parent->label();
      $type .= '>';
      if (!is_null($parent->get('field_caiwubianhao')->value)) {
        $caiwubianhao[] = $parent->get('field_caiwubianhao')->value;
      }
    }

    if (empty($type)) {
      $type = $name;
    }
    if (!is_null($taxono->get('field_caiwubianhao')->value)) {
      $caiwubianhao[] = $taxono->get('field_caiwubianhao')->value;
    }

    $string_caiwubianhao = implode('-', $caiwubianhao);

    $type = substr($type, 0, -1);
    $this->entity->set('name', $name)
      ->set('parttype', $type)
      ->set('caiwunos', $string_caiwubianhao);
    $this->entity->save();

    if ($form_state->requirement) {
      $part_status = \Drupal::service('part.partservice')->savePartsrno($this->entity, $form_state->requirement);
      $requirement_status = \Drupal::service('requirement.requirementservice')->save($this->entity);

      if (!$part_status) {
        drupal_set_message('需求配件保存失败', 'error');
      }
    }
    // 跳转到requirement的编辑页面.
    $form_state->setRedirectUrl(new Url('entity.requirement.edit_form', ['requirement' => $form_state->requirement]));
    drupal_set_message('需求配件保存成功');
  }

}
