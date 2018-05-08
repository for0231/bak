<?php

/**
 * @file
 * Contains Drupal\published_information\Form\ClientFilterForm.
 */

namespace Drupal\published_information\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PublishedFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'publish_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  	
  	$node_type = $this->getRequest()->get('node_type');
  	
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => ($node_type == 'articles') ? t('Filter article messages') : t('Filter news messages'),
      '#open' => !empty($_SESSION['admin_published_filter']),
    );
    $form['filters']['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title')
    );
    $authors = \Drupal::service('member.memberservice')->getAllAuthor();
    $author_ops = array();
    foreach($authors as $author) {
      $author_ops[$author->uid] = $author->employee_name;
    }
    $form['filters']['author'] = array(
      '#type' => 'select',
      '#title' => t('Author'),
      '#options' => array('' => 'All') + $author_ops
    );
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => t('Status'),
      '#options' => array('-1' => 'All', '0' => t('Not published'), '1' => t('Published'))
    );

//--------- 根据节点类型的不同 加载栏目数据到下拉框 -------

    //得到要加载的文章的类型 
    //articles 文章   news 新闻
    
    if($node_type == 'articles') {
      $cate = 'articleCategory';
    } elseif($node_type == 'news') {
      $cate = 'newsCategory';
    }
    // 加载栏目
    $category = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($cate,0,1);
    $ops = array(''=> 'All');
	  foreach ($category as $v) {
	 	  $ops[$v->tid] = $v->name;
	  } 
    $form['filters']['p_category'] = array(
      '#type' => 'select',
      '#title' => $this->t('Category'),
			'#options' => $ops,
    );
// -------------- 加载完成 --------------

    $fields = array('title', 'author','status','p_category');
    $allempty = true;
    foreach ($fields as $field) {
      if($field == 'status') {
        if(!isset($_SESSION['admin_published_filter'][$field])) {
          $_SESSION['admin_published_filter'][$field] =-1;
        }
        if($_SESSION['admin_published_filter'][$field] != -1) {
          $form['filters'][$field]['#default_value'] = $_SESSION['admin_published_filter'][$field];
          $allempty = false;
        }
      } else { 
        if(!empty($_SESSION['admin_published_filter'][$field])) {
          $form['filters'][$field]['#default_value'] = $_SESSION['admin_published_filter'][$field];
          $allempty = false;
        }
      }
    }
    if($allempty) {
      $_SESSION['admin_published_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_published_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    return $form;
  }

  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_published_filter']['title'] = $form_state->getValue('title');
    $_SESSION['admin_published_filter']['author'] = $form_state->getValue('author');
    $_SESSION['admin_published_filter']['status'] = $form_state->getValue('status');
    $_SESSION['admin_published_filter']['p_category'] = $form_state->getValue('p_category');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_published_filter'] = array();
  }
}
