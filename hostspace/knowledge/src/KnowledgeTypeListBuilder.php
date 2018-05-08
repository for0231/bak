<?php

/**
 * @file
 * Contains \Drupal\knowledge\KnowledgeTypeListBuilder
 */

namespace Drupal\knowledge;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of part entities.
 *
 */
class KnowledgeTypeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = '分类名称';
    return $header + parent::buildHeader();
  }


  /**
   * {@inheritdoc}
   *
   * Builds the entity listing as renderable array for table.html.twig.
   *
   * @todo Add a link to add a new item to the #empty text.
   */
  public function render() {
    $form['terms'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
    );
    $items = $this->getStorage()->loadByProperties(array('parent_id' => 0));
    foreach($items as $key => $item) {
      $form['terms'][$key] = array(
        'name' => array(
          '#markup' => $item->label()
        ),
        'operations' => array(
          '#type' => 'operations',
          '#links' => $this->getOperations($item),
        )
      );
      $c_items = $this->getStorage()->loadByProperties(array('parent_id' => $item->id()));
      foreach($c_items as $c_key => $c_item) {
        $indentation = array(
          '#theme' => 'indentation',
          '#size' => 1,
        );
        $form['terms'][$c_key] = array(
          'name' => array(
            '#prefix' => !empty($indentation) ? drupal_render($indentation) : '',
            '#markup' => $c_item->label()
          ),
          'operations' => array(
            '#type' => 'operations',
            '#links' => $this->getOperations($c_item),
          )
        );
      }
    }
    return $form;
  }

}
