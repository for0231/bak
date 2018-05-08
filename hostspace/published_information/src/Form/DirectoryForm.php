<?php
/**
 * @file 
 * Contains Drupal\published_information\Form\DirectoryForm.
 */

namespace Drupal\published_information\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DirectoryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'directory_form';
  }

    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $type = $this->getRequest()->get('d_type');
    if($type == 'news') {
      $form['#title'] = t('News directory');
    }elseif($type == 'article') {
      $form['#title'] = t('Article directory');
    }  

    $form['directory'] = array(
      '#type' => 'table',
      '#header' => array('ID', t('Directory name'),t('Operations')),
      '#rows' => $this->buildRow($type)
    );
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {


  }

  /**
   * 创建行数据
   *
   * @return $rows array
   *   创建好的行数据数组
   */
  private function buildRow($type) {
    if($type == 'news') {
      $directory = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('newsCategory',0,1);
    }elseif($type == 'article') {
      $directory = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('articleCategory',0,1);
    }
     
    $rows = array();
    foreach ($directory as $v) {
	    $rows[$v->tid] = array(
        'ID' => $v->tid,
        'Name' => $v->name
      );
      $rows[$v->tid]['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => $this->getOpArr($v->tid) 
       );
	  }
    return $rows;
  }

  

  /**
   * 构建操作的链接数组
   *
   * @param $question_id
   *   问题编号
   *
   * @return 组装好的Operations数组
   */
  private function getOpArr($tid) {
    $op = array();
    $op['Edit'] = array(
      'title' => 'Edit',
      'url' => new Url('entity.taxonomy_term.edit_form', array('taxonomy_term' => $tid))
    );
    $op['Delete'] = array(
      'title' => 'Delete',
      'url' => new Url('entity.taxonomy_term.delete_form', array('taxonomy_term' => $tid))
    );
    return $op;
  } 

}
