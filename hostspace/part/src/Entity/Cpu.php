<?php

/**
 * @file
 * Contains \Drupal\part\Entity\Cpu.
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * Defines the cpu entity class.
 *
 * @ContentEntityType(
 *   id = "part_cpu",
 *   label = @Translation("CPU"),
 *   handlers = {
 *     "form" = {
 *        "default" = "Drupal\part\Form\CpuForm",
 *     }
 *   },
 *   base_table = "part_cpu",
 *   data_table = "part_cpu_field_data",
 *   revision_table = "part_cpu_revision",
 *   revision_data_table = "part_cpu_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Cpu extends PartEntityBase  {
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

    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('CPU ID'))
      ->setDescription(t('The cpu ID.'));

    $fields['suitable'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Suitable type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',        
        'handler_settings' => array(
          'target_bundles' => array(
             'part' =>array('parent_id' =>$config->get('part.cpu.base_parameter_type')) 
          ),
        ),
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 3
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['family'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Family'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.base_parameter_family')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['frequency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Frequency')) //主频
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => 7
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['max_frequency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Max Frequency'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => 9
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['out_frequency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Out Frequency'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' => 11
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['slot_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Slot Type'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	 'part' =>array('parent_id' => $config->get('part.cpu.cpu_slug_type')),
      	  )
        )
      ))  
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 13
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['kernel_code'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Kernel Code'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_kernel_code')),
      	  )
        )
      ))    
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 15
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['kernel_num'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Kernel Num'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_kernel_num')),
      	  )
        )
      ))      
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 17
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['thread_num'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Thread Num'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_kernel_threads')),
      	  )
        )
      ))     
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 19
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['cache_level_1'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cache Level 1'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_cache_level_1')),
      	  )
        )
      ))    
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 21
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['cache_level_2'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cache Level 2'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_cache_level_2')),
      	  )
        )
      ))     
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 23
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['cache_level_3'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cache Level 3'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_cache_level_3')),
      	  )
        )
      ))     
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 25
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['memory_controller'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Memory Controller')) //内存控制器
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)       
      ->setDisplayOptions('form', array(
          'type' => 'string',
          'weight' =>27,
      )) 
      //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    $fields['processor_64bit'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Processor 64bit'))
			->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.cpu.cpu_is_64')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 29
      )) //设置在表单里显示的控件
      ->setDisplayConfigurable('form', True);

    return $fields;
  }
}
