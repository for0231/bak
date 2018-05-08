<?php

/**
 * @file
 * Contains \Drupal\question\Controller\QuestionController.
 */

namespace Drupal\question\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\question\QuestionCategoryListBuilder;
use Drupal\question\user\ClientQuestionListBuilder;
use Drupal\question\IDealWithListBuilder;
use Drupal\question\Form\MyStatistics;


/**
 * Returns responses for part routes.
 */
class QuestionController extends ControllerBase {

  /**
   * 故障分类管理
   *
   */
  public function adminCategoryList() {
    $category_list = new QuestionCategoryListBuilder();
    return $category_list->render();
  }

  /**
   * 显示我申报的故障
   */
  public function ClientQuestionList(){
    $question_list = ClientQuestionListBuilder::createInstance(\Drupal::getContainer());
    return $question_list->render();
  }

  /**
   * 构建显示我经手的故障问题
   */
  public function viewIDealWith() {
    $list = IDealWithListBuilder ::createInstance(\Drupal::getContainer());
    return $list->render();
  }

  /**
   * 对为经手处理的故障进行统计
   *
   */
  public function myStatistics() {
    $statistics = MyStatistics::createInstance(\Drupal::getContainer());
    return $statistics->render();

  }

 }

