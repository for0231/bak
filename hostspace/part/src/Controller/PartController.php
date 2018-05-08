<?php

/**
 * @file
 * Contains \Drupal\part\Controller\PartController.
 */

namespace Drupal\part\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\part\PartPurchaseDetailList;

/**
 * Returns responses for part routes.
 */
class PartController extends ControllerBase {

  /**
   * Provides part page redirect single part edit.
   *
   */
  public function partEdit($part) {
    $part = entity_load('part', $part);
    switch ($part->get('type')->value) {
      case 'part_cpu':
        return $this->redirect('part.cpu.edit_form', array(
          'part_cpu' => $part->get('ppid')->value,
        ));
        break;
      case 'part_harddisc':
        return $this->redirect('part.harddisc.edit_form', array(
          'part_harddisc' => $part->get('ppid')->value,
        ));
        break;
      case 'part_memory':
        return $this->redirect('part.memory.edit_form', array(
          'part_memory' => $part->get('ppid')->value,
        ));
        break;
      case 'part_mainboard':
        return $this->redirect('part.mainboard.edit_form', array(
          'part_mainboard' => $part->get('ppid')->value,
        ));
        break;
      case 'part_chassis':
        return $this->redirect('part.chassis.edit_form', array(
          'part_chassis' => $part->get('ppid')->value,
        ));
        break;
      case 'part_raid':
        return $this->redirect('part.raid.edit_form', array(
          'part_raid' => $part->get('ppid')->value,
        ));
        break;
      case 'part_network':
        return $this->redirect('part.network.edit_form', array(
          'part_network' => $part->get('ppid')->value,
        ));
        break;
      case 'part_optical':
        return $this->redirect('part.optical.edit_form', array(
          'part_optical' => $part->get('ppid')->value,
        ));
        break;
      case 'part_switch':
        return $this->redirect('part.switch.edit_form', array(
          'part_switch' => $part->get('ppid')->value,
        ));
        break;
    }
  }

  /**
   * 采购列表
   */
  public function purchaseDetail() {
    $list = PartPurchaseDetailList::createInstance(\Drupal::getContainer());
    return $list->render();

  }

}
