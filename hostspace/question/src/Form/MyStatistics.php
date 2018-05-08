<?php
/**
 * @file 
 * Contains \Drupal\question\Form\MyStatistics.
 */

namespace Drupal\question\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MyStatistics {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
			$container->get('form_builder')
    );
  }

  private function load() {
    $parent = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren(\Drupal::config('question.settings')->get('question.category'));
    foreach ($parent as $k=>$v) {
		  $category_parent[$k] = $v->label();
	  }
    ksort($category_parent);
    return $parent;
  }

  public function render() {
    // @todo 时间筛选

    // 模板渲染
    $build['statistics'] = array(
      '#theme' => 'my_statistics',
      '#statistics' => $this->load(),
      '#year' => date('Y',REQUEST_TIME),
      '#month' => date('m',REQUEST_TIME),
    );
    return $build;

  }
}
