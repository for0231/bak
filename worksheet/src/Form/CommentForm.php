<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\SettingForm.
 */
namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CommentForm extends FormBase {
  protected $wid;
  protected $entity_type;
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$wid='',$entity_type='') {
    $this->wid = $wid;
    $this->entity_type = $entity_type;
    $list = \Drupal::service('worksheet.dbservice')->getCommentByid($this->wid,$this->entity_type);
    $comment_uid =\Drupal::service('worksheet.dbservice')->getCommentuid($this->wid,$this->entity_type);
    $commentlist = array();
    foreach($comment_uid as $item){
      $commentlist[$item->uid] = entity_load('user', $item->uid)->label();
    }
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '工单评价',
      '#open' => True,
    );
    $form['filters']['if_question'] = array(
      '#type' => 'select',
      '#title' => '工单是否有问题',
      '#options' => array(
        '0'=>'正常',
        '1'=>'异常',
      ),
      '#default_value'=>$list?$list[0]->if_question:0
    );
    $form['filters']['if_right'] = array(
      '#type' => 'select',
      '#title' => '定位分类是否正确',
      '#options' => array(
        '0'=>'正常',
        '1'=>'异常',
      ),
      '#default_value'=>$list?$list[0]->if_right:0
    );
    $form['filters']['if_deal'] = array(
      '#type' => 'select',
      '#title' => '是否正确处理',
      '#options' => array(
        '0'=>'正常',
        '1'=>'异常',
      ),
      '#default_value'=>$list?$list[0]->if_deal:0
    );
    $form['filters']['if_quality'] = array(
      '#type' => 'select',
      '#title' => '是否是优质工单',
      '#options' => array(
        '0'=>'否',
        '1'=>'是',
      ),
      '#default_value'=>$list?$list[0]->if_quality:0
    );
    $form['filters']['comment_note'] = array(
      '#type' => 'textarea',
      '#title' => '备注',
      '#default_value'=>$list?$list[0]->comment_note:''
    );
    $form['filters']['performance'] = array(
      '#type' => 'select',
      '#title' => '绩效加分',
      '#options' => array(
        '0'=>'0',
        '-1'=>'-1',
        '-2'=>'-2',
        '-3'=>'-3',
        '-4'=>'-4',
        '-5'=>'-5',
        '-6'=>'-6',
        '-7'=>'-7',
        '-8'=>'-8',
        '-9'=>'-9',
        '-10'=>'-10',
        '1'=>'1',
        '2'=>'2',
        '3'=>'3',
        '4'=>'4',
        '5'=>'5',
        '6'=>'6',
        '7'=>'7',
        '8'=>'8',
        '9'=>'9',
        '10'=>'10',
      ),
      '#default_value'=>$list?$list[0]->performance:0,
    );
    $form['filters']['comment_uid'] = array(
      '#type' => 'select',
      '#title' => '评论对象',
      '#options' =>$commentlist,
      '#default_value'=>$list[0]->comment_uid?entity_load('user',$list[0]->comment_uid)->label():0
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存'
    );
    $form['#theme'] = 'comment_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}cornsilk
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $wid = $this->wid;
    $type = $this->entity_type;
    $if_question = $form_state->getValue('if_question');
    $if_right = $form_state->getValue('if_right');
    $if_deal = $form_state->getValue('if_deal');
    $if_quality = $form_state->getValue('if_quality');
    $comment_note = $form_state->getValue('comment_note');
    $performance = $form_state->getValue('performance');
    $comment_uid = $form_state->getValue('comment_uid');
    $fields = array(
      'if_question'=>$if_question,
      'if_right'=>$if_right,
      'if_deal'=>$if_deal,
      'if_quality'=>$if_quality,
      'comment_note'=>$comment_note,
      'performance'=>$performance,
      'isno_comment'=>1,
      'comment_uid'=>$comment_uid
    );
    $rs = \Drupal::service('worksheet.dbservice')->saveWorkSheetComment($fields,$wid,$type);
    if($rs){
      drupal_set_message('评论提交成功');
    }else{
      drupal_set_message('评论提交失败或者该类型不需要评论','error');
    }
  }
}
