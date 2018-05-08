<?php

/**
 * @file
 * Contains \Drupal\question\QuestionCategoryListBuilder.
 */

namespace Drupal\question;
use Drupal\Core\Url;

class QuestionCategoryListBuilder {

  /**
   * 加载数据
   */
  private function load() {
    //得到分类的父节点
    $question_class = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren(\Drupal::config('question.settings')->get('question.category'));
    return $question_class;
  }

  /**
   * 渲染模板
   */
  public function render() {
     $build['list'] = array(
      '#theme' =>'admin_category_list',
      '#category' => $this->load(),
      '#add' =>\Drupal::l(t('+ Add category'), new Url('question.category.add_form'))
    );
    return $build;
  }
}

