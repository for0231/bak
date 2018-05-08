<?php

namespace Drupal\taxonomy_menu_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Core\Url;

/**
 * Provides route responses for taxonomy.module.
 */
class TaxonomyMenuPageController extends ControllerBase {

  /**
   *
   */
  public function getEnterprises() {
    $entity = entity_load('taxonomy_vocabulary', 'enterprises');
    $build['add'] = [
      '#type' => 'link',
      '#title' => '+增加',
      '#url' => new Url('admin.taxonomy_menu_page.add_form', ['taxonomy_vocabulary' => 'enterprises']),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];
    $build['list'] = \Drupal::formBuilder()->getForm('Drupal\taxonomy\Form\OverviewTerms', $entity);
    return $build;
  }

  /**
   *
   */
  public function getSupply() {
    $entity = entity_load('taxonomy_vocabulary', 'supply');
    $build['add'] = [
      '#type' => 'link',
      '#title' => '+增加',
      '#url' => new Url('admin.taxonomy_menu_page.add_form', ['taxonomy_vocabulary' => 'supply']),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];
    $build['list'] = \Drupal::formBuilder()->getForm('Drupal\taxonomy\Form\OverviewTerms', $entity);
    return $build;
  }

  /**
   *
   */
  public function getUnit() {
    $entity = entity_load('taxonomy_vocabulary', 'unit');
    $build['add'] = [
      '#type' => 'link',
      '#title' => '+增加',
      '#url' => new Url('admin.taxonomy_menu_page.add_form', ['taxonomy_vocabulary' => 'unit']),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];
    $build['list'] = \Drupal::formBuilder()->getForm('Drupal\taxonomy\Form\OverviewTerms', $entity);
    return $build;
  }

  /**
   *
   */
  public function getLocated() {
    $entity = entity_load('taxonomy_vocabulary', 'located');
    $build['add'] = [
      '#type' => 'link',
      '#title' => '+增加',
      '#url' => new Url('admin.taxonomy_menu_page.add_form', ['taxonomy_vocabulary' => 'located']),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];
    $build['list'] = \Drupal::formBuilder()->getForm('Drupal\taxonomy\Form\OverviewTerms', $entity);
    return $build;
  }

  /**
   *
   */
  public function getShips() {
    $entity = entity_load('taxonomy_vocabulary', 'ships');
    $build['add'] = [
      '#type' => 'link',
      '#title' => '+增加',
      '#url' => new Url('admin.taxonomy_menu_page.add_form', ['taxonomy_vocabulary' => 'ships']),
      '#attributes' => [
        'class' => ['btn btn-primary'],
      ],
    ];
    $build['list'] = \Drupal::formBuilder()->getForm('Drupal\taxonomy\Form\OverviewTerms', $entity);
    return $build;
  }

  /**
   *
   */
  public function getParts() {
    $entity = entity_load('taxonomy_vocabulary', 'parts');
    $build['add'] = [
      '#type' => 'link',
      '#title' => '+增加',
      '#url' => new Url('admin.taxonomy_menu_page.add_form', ['taxonomy_vocabulary' => 'parts']),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];
    $build['list'] = \Drupal::formBuilder()->getForm('Drupal\taxonomy\Form\OverviewTerms', $entity);
    return $build;
  }

  /**
   *
   */
  public function addForm(VocabularyInterface $taxonomy_vocabulary) {
    $term = $this->entityManager()->getStorage('taxonomy_term')->create(['vid' => $taxonomy_vocabulary->id()]);
    return $this->entityFormBuilder()->getForm($term);
  }

}
