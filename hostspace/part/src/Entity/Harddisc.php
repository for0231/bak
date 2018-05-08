<?php

/**
 * @file
 * Contains \Drupal\part\Entity\Hraddisc.
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * Defines the Hraddisc entity class.
 *
 * @ContentEntityType(
 *   id = "part_harddisc",
 *   label = @Translation("Hard disk"),
 *   handlers = {
 *     "form" = {
 *        "default" = "Drupal\part\Form\HardDiscEdit",
 *     }
 *   },
 *   base_table = "part_hard_disc",
 *   data_table = "part_hard_disc_field_data",
 *   revision_table = "part_hard_disc_revision",
 *   revision_data_table = "part_hard_disc_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "hid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Harddisc extends PartEntityBase  {
 
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
    
    $fields['hid'] = BaseFieldDefinition::create('integer') 
      ->setLabel(t('Hard disc ID'))
      ->setDescription(t('The disc\'s ID'));
    
    $fields['suitable'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Suitable type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.suitable_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' =>1
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);
    
    $fields['harddisk_type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Hard disk type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    $fields['size'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk size'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.size_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);
  
    $fields['hard_disc_capacity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk capacity'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 10
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

  
    $fields['discs_number'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk number'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.harddisc.disc_number_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 15
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);



    $fields['per_disc_capacity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk per capacity'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.per_disc_capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 20
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


    $fields['head_number'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk head number'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.head_number_capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 25
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

		$fields['cache'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk cache'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	 'part' => array('parent_id' => $config->get('part.harddisc.cache_capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 30
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

			$fields['speed'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk Rmp speed'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	 'part' => array('parent_id' => $config->get('part.harddisc.speed_capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 35
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

		$fields['interface_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk interface type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.interface_type_capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 40
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


		$fields['interface_speed'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Hard disk interface speed'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.harddisc.interface_speed_capacity_base_parameter')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 45
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);


    return $fields;
  }
}
