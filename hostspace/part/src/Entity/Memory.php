<?php

/**
 * @file
 * Contains \Drupal\part\Entity\Memory.
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * Defines the memory entity class.
 *
 * @ContentEntityType(
 *   id = "part_memory",
 *   label = @Translation("Memory"),
 *   handlers = {
 *     "form" = {
 *        "default" = "Drupal\part\Form\MemoryForm",
 *     }
 *   },
 *   base_table = "part_memory",
 *   data_table = "part_memory_field_data",
 *   revision_table = "part_memory_revision",
 *   revision_data_table = "part_memory_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "mid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Memory extends PartEntityBase  {
  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $this->set('uid', \Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    \Drupal::service('part.partservice')->save($this, $update);
  }
  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $config = \Drupal::config('part.settings');
    $fields['mid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Memory ID'))
      ->setDescription(t('The memory\'s ID'));

    $fields['suitable'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Suitable type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.memory.suitable_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 1
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['capacity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Memory capacity'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.memory.capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 10
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['capacity_descripition'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Memory capacity desscription'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['memory_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Memory type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.memory.type_base_parameter')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 4
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['memory_clocked'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Memory clocked'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.memory.clocked_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);



    $fields['practicle_config'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('practicle configuration'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.memory.practicle_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 6
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);



    $fields['cl_delay'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CL delay'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => 7
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


    $fields['production_process'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Memory production process'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.memory.process_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 8
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);
    return $fields;
  }
}
