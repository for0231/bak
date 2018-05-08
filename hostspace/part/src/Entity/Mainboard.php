<?php
/*
 * @file
 * \Drupal\part\Entity\Mainboard
 */

namespace Drupal\part\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\part\PartEntityBase;

/**
 * defines the mainboard entity class
 *
 * @ContentEntityType(
 *   id = "part_mainboard",
 *   label = @Translation("Mainboard"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\part\Form\MainboardForm"
 *     }
 *   },
 *   base_table = "part_mainboard",
 *   data_table = "part_mainboard_field_data",
 *   revision_table = "part_mainboard_revision",
 *   revision_data_table = "part_mainboard_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "mid",
 *     "revision" = "vid",
 *     "label" = "standard",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 */
class Mainboard extends PartEntityBase {

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

  /*
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $config = \Drupal::config('part.settings');

    $fields['mid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the mainboard entity.'))
      ->setReadOnly(TRUE);

    $fields['chipmaker'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Chipmaker'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.chipmaker')),
      	  )
        )
      ))      
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 1
      ))
      ->setDisplayConfigurable('form', True);
    $fields['chipset'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Chipset'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.chipset')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 5
      ))
      ->setDisplayConfigurable('form', True);
    $fields['chipset_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Chipset Description'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 10
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['chipset_display'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Chipset Display'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.chipset_display')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 15
      ))
      ->setDisplayConfigurable('fo  rm', True);
    $fields['chipset_audio'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Chipset Audio'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.chipset_audio')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 20
      ))
      ->setDisplayConfigurable('form', True);
    $fields['chipset_network'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Chipset Network'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.chipset_network')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 25
      ))
      ->setDisplayConfigurable('form', True);

    $fields['cpu_platform'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('CPU Platform'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.cpu_platform')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 30
      ))
      ->setDisplayConfigurable('form', True);

    $fields['cpu_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('CPU Type'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.cpu_type')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 35
      ))
      ->setDisplayConfigurable('form', True);

    $fields['cpu_slot'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('CPU Slot'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.mainboard.cpu_slot')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 40
      ))
      ->setDisplayConfigurable('form', True);

    $fields['cpu_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CPU Description'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 42
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['memory_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Memory Type'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' =>array(
        	  'part' =>array('parent_id' => $config->get('part.memory.type_base_parameter')),
      	  )
        )
      ))
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' =>45
      ))
      ->setDisplayConfigurable('form', True);

    $fields['memory_slot'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Memory Slot'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);
    $fields['memory_max'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Memory Max(unit:G)'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 50
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['memory_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Memory Description'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 51
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['pcie_slot'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PCI-E Slot'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 52
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['pci_slot'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PCI Slot'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 53
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['sata_interface'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SATA Interface'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 54
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['usb_interface'] = BaseFieldDefinition::create('string')
      ->setLabel(t('USB Interface'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 60
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['hdmi_interface'] = BaseFieldDefinition::create('string')
      ->setLabel(t('HDMI Interface'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 61
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['outer_port'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Outer Port'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 62
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['ps2_interface'] = BaseFieldDefinition::create('string')
      ->setLabel(t('PS/2 Interface'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 70
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['other_interface'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Other Interface'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 71
      ))
      ->setDisplayConfigurable('form', TRUE);
    $fields['mainboard_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Mainboard Type'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.mainboard_type')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
          'type' => 'options_select',
          'weight' => 75
      ))
      ->setDisplayConfigurable('form', True);

    $fields['support_ipmi'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Support IPMI'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array('display_label' => 'true'),
        'weight' => 77
      ))
      ->setDisplayConfigurable('form', True);

    $fields['system_not'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Not System'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings(array(
        'target_type' => 'taxonomy_term',
        'handler' => 'sub_term',
      	'handler_settings' => array(
      	  'target_bundles' => array(
        	  'part' => array('parent_id' => $config->get('part.mainboard.system_not')),
      	  )
        )
      )) 
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 80
      ))
      ->setDisplayConfigurable('form', True);

    return $fields;
  }
}
