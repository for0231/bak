<?php
/**
 * @file
 * Contains \Drupal\knowledge\Controller\KnowledgeController.
 */

namespace Drupal\knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;

class KnowledgeController extends ControllerBase {
  /**
   * 知识内容展示
   */
  public function knowledgeView() {
    $build['search'] = $this->formBuilder()->getForm('\Drupal\knowledge\Form\KnowledgeSearchForm');
    $contents = array();
    $storage = $this->entityManager()->getStorage('knowledge_content');
    $items = entity_load_multiple_by_properties('knowledge_type', array());
    foreach($items as $item) {
      if($parent_id = $item->get('parent_id')->value) {
        $contents[$parent_id]['subclass'][$item->id()]['title'] = $item->label();
        $contents[$parent_id]['subclass'][$item->id()]['number'] = $item->get('problem_quantity')->value;
        $contents[$parent_id]['subclass'][$item->id()]['knowledges'] = $item->getKnowledgeContent($storage, 6);
      } else {
        $contents[$item->id()]['title'] = $item->label();
        if(!isset($contents[$item->id()]['subclass'])) {
          $contents[$item->id()]['subclass'] = array();
        }
      }
    }
    $build['knowledge_content'] = array(
      '#theme' => 'knowledge_content',
      '#contents' => $contents
    );
    return $build;
  }

  /**
   * 知识内容详情
   */
  public function knowledgeInfo(EntityInterface $knowledge_content) {
    $number = $knowledge_content->get('browse_number')->value;
    $knowledge_content->set('browse_number', $number + 1);
    $knowledge_content->save();
    $build['search'] = $this->formBuilder()->getForm('\Drupal\knowledge\Form\KnowledgeSearchForm');
    $build['knowledge_content_info'] = array(
      '#theme' => 'knowledge_content_info',
      '#knowledge' => $knowledge_content
    );
    return $build;
  }

  /**
   * 知识内容搜索
   *
   * @param string $key
   *   要搜索的关键字
   */
  public function knowledgeSearch($key) {
    $build['search'] = $this->formBuilder()->getForm('\Drupal\knowledge\Form\KnowledgeSearchForm', $key);
    $storage = $this->entityManager()->getStorage('knowledge_content');
    $entitys = $storage->loadBySearch($key);
    $build['list'] = array(
      '#theme' => 'knowledge_search',
      '#knowledges' => $entitys
    );
    $build['pager'] = array(
      '#type' => 'pager',
    );
    return $build;
  }
}
