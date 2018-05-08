<?php
/**
 * @file
 * Contains Drupal\published_information\PublishListBuilder.
 */

namespace Drupal\published_information;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\published_information\Form\PublishedFilterForm;


class PublishListBuilder extends PublishedFilterForm {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'article_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form,$form_state);
    $form['list'] = array(
      '#type' => 'table',
      '#header' => $this->createArticleHeader(),
      '#rows' => $this->createArticleRow()
    );
   // $form['article_pager']['#type'] = 'pager';

    return $form;
  }

  /**
   * 创建表头
   */
 private function createArticleHeader() {
  	$node_type = $this->getRequest()->get('node_type');
    $header = array(
      'ID' => t('ID'),
      'title' => t('Title'),
      'author' => t('Author'),
      'category' => $node_type == "articles" ? t('Article category') : t('News category'),
      'status' => t('Status'),
      'update_time' => t('Update time'),
      'op' => t('Operation')
    );

    return $header;
  }

  /**
   * 创建行
   */
  private function createArticleRow() {

    //得到要加载的文章的类型
    //articles 文章   news 新闻
    $node_type = $this->getRequest()->get('node_type');
    // 筛选条件
    $condition = $this->filterForm($node_type) + array('type' => $node_type);

    // 加载对应类型的数据
    $articles = entity_load_multiple_by_properties('node', $condition);

    $rows = array();
    if(!empty($articles)) {
      $i = 0;
      foreach($articles as $article) {
        $i++;
        $category = ($node_type == 'articles') ? $article->get('article_category')->entity->label() : $article->get('category')->entity->label();
        $author = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $article->get('uid')->entity->id());
        /*$mark = array(
          '#theme' => 'mark',
          '#mark_type' => node_mark($article->id(), $article->getChangedTime()),
        );*/
        $uri = $article->urlInfo();
        $langcode = $article->language()->getId();
        $options = $uri->getOptions();
        $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? array('language' => $languages[$langcode]) : array());
        $uri->setOptions($options);
        $rows[$article->id()] = array(
          'id' => $i,
        );
        $rows[$article->id()]['title']['data'] = array(
          '#type' => 'link',
          '#title' => $article->label(),
          '#suffix' => ' ' . drupal_render($mark),
          '#url' => $uri,
        );
        $rows[$article->id()] += array(
          'id' => $article->id(),
          'title' => $article->label(),
          'author' => isset($author->employee_name) ? $author->employee_name : 'none',
          'category' => $category,
          'status' => $article->isPublished() ? $this->t('published') : $this->t('not published'),
          'update_time' => format_date($article->get('changed')->value, 'custom', 'Y-m-d H:i:s')
        );
        $rows[$article->id()]['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $this->getOperations($article->id(), $article->isPublished())
       );
      }
    }
    return $rows;
  }


  /**
   * 表单筛选
   */
  private function filterForm($node_type) {
    $condition = array();
    if(!empty($_SESSION['admin_published_filter'])) {
      if(!empty($_SESSION['admin_published_filter']['title'])) {
        $condition['title'] = $_SESSION['admin_published_filter']['title'];
      }
      if(!empty($_SESSION['admin_published_filter']['author'])) {
        $condition['uid'] = $_SESSION['admin_published_filter']['author'];
      }
      if($_SESSION['admin_published_filter']['status'] != -1) {
        $condition['status'] = $_SESSION['admin_published_filter']['status'];
      }
      if(!empty($_SESSION['admin_published_filter']['p_category'])) {
        $cate = $_SESSION['admin_published_filter']['p_category'];
        if($node_type == 'articles') {
          $condition['article_category'] = $cate;
        } elseif($node_type == 'news') {
          $condition['category'] = $cate;
        }

      }
    }
    return $condition;
  }

  /**
   * 构建操作NODE节点的链接数组
   *
   * @param $node_id
   *   节点编号
   *
   * @return $op array
   *   组装好的Operations数组
   */

  private function getOperations($node_id, $isPublish) {
    $op = array();
    $op['Edit'] = array(
      'title' => 'Edit',
      'url' => new Url('entity.node.edit_form', array('node' => $node_id))
    );
    $op['Delete'] = array(
      'title' => 'Delete',
      'url' => new Url('entity.node.delete_form',array('node' => $node_id))
    );

    return $op;
  }

}
