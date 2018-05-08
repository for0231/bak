<?php

/**
 * @file 
 * Contains \Drupal\published_information\Controller\PublishSystemController.
 */

namespace Drupal\published_information\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\published_information\front\NewsListBuilder;
use Drupal\published_information\front\PublishedListBuilder;

/**
 * Returns responses for  routes.
 */
class PublishSystemController extends ControllerBase {

  /**
   * 发布信息列表
   *
   * @param $node_type string
   *  node的类型
   */ 
  public function PublishList($node_type,$p_category) {
    $list = new PublishedListBuilder();
    return $list->render(\Drupal::getContainer(),$node_type,$p_category);
  }  

  /**
   * 新闻/文章详情
   */
  public function publishedDetail($node_id) {
    
    //嵌套模板
    $build['#title'] = $news->getTitle();
    $build['detail'] = array(
      '#theme' => 'news_detail',
      '#news_obj' => $news
    );     
    return $build;
  }
}
  
