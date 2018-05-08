<?php
/**
 * @file
 * Contains \Drupal\question\user\ClientQuestionListBuilder.
 */

namespace Drupal\question\user;

use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ClientQuestionListBuilder {
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

  /**
   * 筛选订单 组装筛选条件
   *
   * @return $conditiond
   *   筛选条件组成的数组
   */
  public function filterQuestion() {
    $condition = array();
    if(!empty($_SESSION['client_question_filter'])) {
			if(!empty($_SESSION['client_question_filter']['category'])){
       $condition['parent_question_class'] =  array('field' => 'parent_question_class' , 'op' => '=', 'value' =>$_SESSION['client_question_filter']['category']);
			}
      if(!empty($_SESSION['client_question_filter']['status'])){
				$condition['status'] =  array('field' => 'status' , 'op' => '=', 'value' =>$_SESSION['client_question_filter']['status']);
			}
			if(!empty($_SESSION['client_question_filter']['content'])){
			  $condition['content__value'] = array('field' => 'content__value' , 'op' => 'like', 'value' =>'%'.$_SESSION['client_question_filter']['content'].'%');
			}
       $start = isset($_SESSION['client_question_filter']['start']) ? strtotime($_SESSION['client_question_filter']['start']) : '' ;
      $expire = isset($_SESSION['client_question_filter']['expire']) ? strtotime($_SESSION['client_question_filter']['expire']) : '' ;

      if(!empty($start)) {
        $condition['start'] = array('field' => 'created' , 'op' => '>=', 'value' =>$start);
      }
      if(!empty($expire)) {
        $condition['expire'] = array('field' => 'created' , 'op' => '<=','value' => $expire);
      }
		}
    return $condition;
  }

  /**
   * 加载模板所需要的参数
   *
   */
  public function load() {
    $user = \Drupal::currentUser();
    $condition = $this->filterQuestion();
    $all_question = getQuestionService()->getQuestionByClient(\Drupal::currentUser()->id(), $condition,array());
    return $all_question;

  }

  /**
   * 渲染模板
   */

  public function render() {

    $build['#theme'] = 'user_question_list';

    $build['filter'] = $this->formBuilder->getForm('Drupal\question\user\ClientQuestionFilterForm');

    $build['list'] = array(
      '#theme' => 'client_question_list',
      '#question_list' => $this->load()
    );
    $build['my_question_pager'] = array('#type' => 'pager');
    return $build;
  }

}
