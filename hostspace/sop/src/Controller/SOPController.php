<?php

/**
 * @file
 * Contains \Drupal\sop\Controller\SOPController.
 */

namespace Drupal\sop\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
/**
 * Returns responses for sop routes.
 */
class SOPController extends ControllerBase {

  /**
   * Provides sop page redirect single sop task edit.
   */
  public function sopEdit($sop) {
    $sop_entity = entity_load('sop', $sop);
    switch ($sop_entity->get('module')->value) {
      case 'sop_task_server':
        return $this->redirect('admin.sop.sop_task.sop_task_server.edit', array(
          'sop_task_server' => $sop_entity->get('sid')->value,
        )
        );

      break;
      case 'sop_task_room':
        return $this->redirect('admin.sop.sop_task.sop_task_room.edit', array(
          'sop_task_room' => $sop_entity->get('sid')->value,
        )
        );

      break;
      case 'sop_task_machine':
        return $this->redirect('admin.sop.sop_task.sop_task_machine.edit', array(
          'sop_task_machine' => $sop_entity->get('sid')->value,
        )
        );

      break;
      case 'sop_task_iband':
        return $this->redirect('admin.sop.sop_task.sop_task_iband.edit', array(
          'sop_task_iband' => $sop_entity->get('sid')->value,
        )
        );

      break;
      case 'sop_task_failure':
        return $this->redirect('admin.sop.sop_task.sop_task_failure.edit', array(
          'sop_task_failure' => $sop_entity->get('sid')->value,
        )
        );

      break;
    }
  }
  /**
   * SOP 详细.
   */
  public function sopDetail(EntityInterface $sop) {
    $module = $sop->get('module')->value;
    $id = $sop->get('sid')->value;
    switch ($sop->get('module')->value) {
      /*
      case 'sop_task_server':
      $sop = entity_load($module, $id);
      $build['sop_task_server_detail'] = \Drupal::service('form_builder')->getForm('Drupal\sop\Form\SopTaskServerDetailForm', $sop);

      break;
       */
      case 'sop_task_failure':
        $sop = entity_load($module, $id);
        $build['sop_task_server_detail'] = \Drupal::service('form_builder')->getForm('Drupal\sop\Form\SopTaskFailureDetailForm', $sop);
        $build['sop_task_server_detail']['sop_task_question_detail'] = \Drupal::service('form_builder')->getForm('Drupal\sop\Form\SopTaskFailureQuestionDetail', $sop->get('qid')->entity);

        break;

      case 'sop_task_iband':
        $sop = entity_load($module, $id);
        $build['sop_task_server_detail'] = \Drupal::service('form_builder')->getForm('Drupal\sop\Form\SopTaskIBandDetailForm', $sop);

        break;

      /*
      case 'sop_task_machine':
      $sop = entity_load($module, $id);
      $build['sop_task_server_detail'] = \Drupal::service('form_builder')->getForm('Drupal\sop\Form\SopTaskMachineDetailForm', $sop);

      break;
       */
      case 'sop_task_room':
        $sop = entity_load($module, $id);
        $build['sop_task_server_detail'] = \Drupal::service('form_builder')->getForm('Drupal\sop\Form\SopTaskRoomDetailForm', $sop);
        break;
    }

    return $build;
  }

  /**
   * 获取SOP类型名称.
   */
  public function getTitle(EntityInterface $sop) {
    $module = $sop->get('module')->value;
    $en = \Drupal::entityManager()->getStorage($module);
    return SafeMarkup::checkPlain($en->getEntityType()->getLabel());
  }

  /**
   *
   */
  public function handleClientAutocomplete(Request $request) {
    $matches = array();
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = Unicode::strtolower(array_pop($typed_string));
      $matches = \Drupal::service('member.memberservice')->getMatchClients($typed_string);
    }
    return new JsonResponse($matches);
  }

}
