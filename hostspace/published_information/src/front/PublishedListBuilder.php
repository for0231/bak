<?php
/**
 * @file
 * Contains \Drupal\published_information\front\PublishedListBuilder.
 */

namespace Drupal\published_information\front;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PublishedListBuilder {
  public function render(ContainerInterface $container, $type, $p_category) {
    // 加载当前目录所对应的术语编号
    $voc = ($type == 'news')? 'newsCategory' : 'articleCategory';
    $tid = '';
    if($p_category) {
      $arr = taxonomy_term_load_multiple_by_name($p_category, $voc);
      foreach($arr as $t) {
         $tid = $t->id();
      }
    }
    // 加载对应的数据
    $storage = $container->get('entity.manager')->getStorage('node');
    $queryFactory = $container->get('entity.query');
    $entity_query = $queryFactory->get('node');
    $entity_query->condition('type',$type);
    if($type == 'news' && $tid) {
      $entity_query->condition('category',$tid);
    } elseif($type == 'articles' && $tid) {
      $entity_query->condition('article_category',$tid);
    }
    $entity_query->pager(20);
    $node_id = $entity_query->execute();
    $data = $storage->loadMultiple($node_id);
    // 绑定对应的模板
    $temp = ($type == 'articles') ? $this->bundArticleTemplate($data, $p_category) : $this->bundNewsTemplate($data, $p_category);

    return $temp;
  }

  /**
   * 绑定显示文章列表的模板
   */
  public function bundArticleTemplate($data, $catg) {
    $build['#title'] = t('article');
    $build['#theme'] = 'article_box';
    $build['catg'] = array('#markup' => $catg ? '>' . $catg : '');
    $build['list'] = array(
      '#theme' => 'article_list',
      '#articleData' => array_reverse($data),
    );

    $build['list_pager'] = array('#type' => 'pager');

    return $build;
  }

  /**
   * 绑定显示新闻列表的模板
   */
  public function bundNewsTemplate($data, $catg) {
    $build['#theme'] = 'news_box';
    $build['#title'] = t('news');
    $build['catg'] = array('#markup' => $catg ? '>' . $catg : '');
    $build['list'] = array(
      '#theme' => 'news_list',
      '#newsData' => array_reverse($data),
    );
    $build['list_pager']= array('#type' => 'pager');

    return $build;
  }

}
