<?php

/**
 * @file
 * Contains \Drupal\common\Controller\CommonAreaController.
 */

namespace Drupal\common\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the common area data response content.
 */
class CommonAreaController extends ControllerBase {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
   protected $formBuilder;

  /**
   * Constructs an CommonAreaController object.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The user autocomplete helper class to find matching user names.
   */
  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * 导入
   */
  public function import() {
  	$build['common_area_import_form'] = $this->formBuilder->getForm('Drupal\common\Form\CommonAreaDataImportForm');
  	return $build;
  }
}
