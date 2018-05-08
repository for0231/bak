<?php
/**
 * @file
 * Contains \Drupal\question\user\DeclareQuestionForm.
 */

namespace Drupal\question\user;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use \Drupal\taxonomy\Entity\Vocabulary;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for question declare.
 */

class  DeclareQuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return  'declare_question_form';
  }



  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, $hid=null) {
    $form = parent::form($form, $form_state);
    $form['tips'] = array(
      '#markup' => '根据不同服务器的业务IP会拆分成多个问题工单!',
    );
   //得到所有的分类父节点并组装为数组
    $category_parent = array('' => '-- Select Category --');
    $parent = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren(\Drupal::config('question.settings')->get('question.category'));
    foreach ($parent as $k=>$v) {
		  $category_parent[$k] = $v->label();
	  }
    ksort($category_parent);
    //显示故障分类的表单元素
    $form['category'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    $form['category']['parent_category'] = array(
      '#type' => 'select',
      '#title' => t('Category'),
      '#options' => $category_parent,
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => array(get_class($this), 'loadChild'),
        'wrapper' => 'child_category',
        'effect' => 'none',
        'method' => 'html'
      )
    );
    $form['category']['child_category'] = array(
      '#type' => 'container',
      '#id' => 'child_category'
    );
    //得到选择的父节点id
    $parent_id = $form_state->getValue('parent_category');
    // 根据选择的字节点 得到该节点下的子分类  并加载到页面
    if($parent_id){
      $question_class = entity_load_multiple_by_properties('question_class',array('parent' => $parent_id));
      $child_arr = array('' => '-- select a value --');
      foreach($question_class as $key=>$value) {
        $child_arr[$value->id()] = $value->label();
      }
      $form['category']['child_category']['parent_question_class'] = array(
        '#type' => 'select',
        '#required' => TRUE,
        '#options' => $child_arr,
      );
    }
    //开始选择IP
    $form['server'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('server')
      )
    );
    $form['server']['choosedip'] = array(
      '#type' => 'select',
      '#title' => $this->t('Srver IP'),
      '#size' => 10,
      '#multiple' => true,
      '#validated' => true,
      '#attributes' => array(
        'class' => array('ip_selected')
      )
    );
    $form['ip_search'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ip_search')
      )
    );
     $form['ip_search']['ip_search_text'] = array(
      '#type' => 'textfield',
      '#size' => 12
    );

    $form['ip_search']['ip_serach_btn'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#id' => 'ipb_search_submit',
      '#submit' => array(array(get_class($this), 'ajaxSelectIPSubmit')),
      '#limit_validation_errors' => array(array('ip_search_text')),
      '#ajax' => array(
        'callback' => array(get_class($this), 'ajaxSelectIP'),
        'wrapper' => 'ip_search_wrapper',
        'method' => 'html'
      )
    );

    $form['ip_search']['ip_content'] = array(
      '#type' => 'container',
      '#id' => 'ip_search_wrapper'
    );
    // 得到当前用户所有的租用IP
    // IP的搜索条件
    $condition = $form_state->getValue('ip_search_text');
    // 查询所有的租用服务器IP
    $server_ip = getQuestionService()->getAllHireIpByUser(\Drupal::currentUser()->id(), $condition, $hid);
    $ipb_arr = array();
    if(!empty($server_ip)) {
      foreach($server_ip as $b_ip) {
        $ipb_arr[$b_ip->ip] = $b_ip->ip;
      }
    } else {
      $ipb_arr = array('' => t('No matching data'));
    }
    $form['ip_search']['ip_content']['ip_raw_data'] = array(
      '#type' => 'select',
      '#size' => '10',
      '#options' => $ipb_arr,
      '#attributes' => array(
        'class' => array('ip_search_sel')
      )
    );
   // 选择IP结束
   $form['#attached']['library'] = array('question/question.declare');
   return $form;
  }

  /**
   * 重新渲染表单
   */
  public function  ajaxSelectIP (array $form, FormStateInterface $form_state){
    return $form['ip_search']['ip_content']['ip_raw_data'];
  }

  /**
   * 查找IP 的表单提交函数
   */
  public static function ajaxSelectIPSubmit(array $form, FormStateInterface $form_state) {
    // 重新加载表单
    $form_state->setRebuild();
  }

  /**
   * 根据选择的分类父节点加载子分类 Ajax的回调函数
   *
   * @param $form  array
   *
   * @param $form_state  FormStateInterface
   *
   */
  public function loadChild(array $form, FormStateInterface $form_state){
    return $form['category']['child_category'];
  }

   /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $selected_ip = $form_state->getValue('choosedip');
    if(empty($selected_ip)) {
      $form_state->setErrorByName('choosedip',$this->t('Please select IP .'));
    }
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#id'] = 'declare-question';
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   *
   * Submit question
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $selected_ip = $form_state->getValue('choosedip');
    //将客户选择的IP组装成字符串。IP之间使用 \r\n 隔开
    $trans = $this->transMipBips($selected_ip);
    foreach($trans as $key=>$ip) {
      $ipstring = '';
      //得到每个IP对应的管理IP
      $ipm = entity_load('ipm',$key);
      foreach ($ip as $row) {
        $ipstring .= $row . "\r\n";
      }
      $mipstring = $ipm->label(). "\r\n". $ipstring ;

		  $duplicate = $entity->createDuplicate();
      //给实体元素设置值
      $duplicate->set('ipstring', $ipstring) ;
      $duplicate->set('mipstring', $mipstring);
      $duplicate->set('status', 2);
      $duplicate->set('uid', \Drupal::currentUser()->id());
      $duplicate->save();

      $hostclient = entity_load_multiple_by_properties('hostclient', array('ipm_id' => $ipm->id()));
      $hostclient = reset($hostclient);
      $extra = array(
        'qid' => $duplicate->id(),
        'level' => 0,
        'hid' => $hostclient->id(),
        'handle_id' => 0,
        'description' => $form_state->getValue('content')[0],
        'os' => $hostclient->get('server_system')->target_id,
        'sop_type' => 'p1', //默认使用P1
      );
      \Drupal::service('sop.soptaskservice')->question2failure($extra);
      HostLogFactory::OperationLog('question')->log($duplicate, 'insert');
    }
    drupal_set_message($this->t('Your question has been declared successful. We will deal with as soon as possible!'));
    $form_state->setRedirectUrl(new Url('question.client.admin'));
  }

  /**
   * 内部处理
   */
  private function transMipBips($ips) {
    foreach ($ips as $ip) {
      $ipm_id = getQuestionService()->getIPmIdByBip($ip);
      $ipm = entity_load('ipm', $ipm_id);
      $trans[$ipm_id][] = $ip;
    }

    return $trans;
  }
}

