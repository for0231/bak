<?php
/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetCycleAdd.php
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class WorkSheetCycleAddForm extends FormBase {

  protected $cycle_key = 'work_sheet_cycle_exec';

  public function getFormId() {
    return 'worksheet_cycleadd_form';
  }

  public function buildHeader(){
    $header['exec_time'] = '执行时间';
    $header['type'] = '类型';
    $header['name'] = '名称';
    $header['content'] = '内容'; 
    $header['status'] = '状态';
    $header['op'] = '操作';
    return $header;
  }
    
  public function buildRow(){
    $state = \Drupal::state();
    $state->get('key');
  }

  public function getHours(){
    $hours = array();
    for($i=0;$i<24;++$i){
      $hours[$i] = $i;
    }
    return $hours;
  }

  public function getMinOrSecond(){
    $ms = array();
    for($i=1;$i<60;++$i){
      $ms[$i] = $i;
    }
    return $ms;
  }

  public function buildList(){
    $state = \Drupal::state();
    $data = $state->get($this->cycle_key);
    if(empty($data)){
      return array();
    }
    $list = array();
    $cycle_type = \Drupal::service('worksheet.type')->getCycleType();
    foreach($data as $k=>$item){
      $row = array();
      $row['exec_time'] = '每天'.$item['hours'].'时'.$item['min'].'分';
      $row['type'] = $cycle_type[$item['type']];
      $row['name'] = $item['name'];
      $row['content'] = $item['content'];
      $row['status'] = $item['switch'] ? '开' : '关';
      $row['op']['data'] = array(
        '#type' => 'operations',
        '#links' => $this->getOperations($k)
      );
      $list[] = $row;
    }
    return $list;
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $form['created'] = array(
      '#type' => 'details',
      '#title' => '创建周期性工单'
    );
    $form['created']['type'] = array(
      '#type' => 'select',
      '#title' => '所属分类',
      '#options' => \Drupal::service('worksheet.type')->getCycleType()
    );
    $form['created']['name'] = array(
      '#type' => 'textfield',
      '#title' => '名称',
      '#default_value' => '周期性工单'
    );
    $form['created']['content'] = array(
      '#type' => 'textarea',
      '#title' => '内容',
    );
    $form['created']['exec_hours'] = array(
      '#type' => 'select',
      '#title' => '执行小时',
      '#options' => $this->getHours()
    );
    $form['created']['exec_min'] =  array(
      '#type' => 'select',
      '#title' => '执行分钟',
      '#options' => $this->getMinOrSecond()
    );
    $form['created']['client'] =  array(
      '#type' => 'textfield',
      '#title' => '公司名称',
      '#default_value' => 'Hostspace'
    );
    $form['created']['submit'] = array(
      '#type' => 'submit',
      '#value' => '创建定时工单'
    );
    $form['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildList(),
      '#empty' => t('No data.'),
      '#attached' => array(
        'library' => array('worksheet/drupal.work-sheet-cycle-exec')
      )
    );
    return $form;
  }

  public function getOperations($key){
    $state = \Drupal::state();
    $data = $state->get($this->cycle_key);
    $item = $data[$key];
    $op = array();
    $op['edit'] = array(
      'title' => '编辑',
      'url' => new Url('admin.worksheet.sop.cycle.edit', array('work_sheet_cycle_key' => $key))
    );
    $op['delete'] = array(
      'title' => '删除',
      'url' => new Url('admin.worksheet.sop.cycle.delete', array('work_sheet_cycle_key' => $key)),
      '#attached' => array(
        'library' => array('worksheet/drupal.work-sheet-cycle-exec')
      )
    );
    return $op;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $data = (array)$state->get($this->cycle_key);
    $hours =  intval($form_state->getValue('exec_hours'));
    $min = intval($form_state->getValue('exec_min'));
    $content = $form_state->getValue('content');
    $type = $form_state->getValue('type');
    $name = $form_state->getValue('name');
    $client = $form_state->getValue('client');
    $key = intval(microtime(true)*10000);
    $insert = array(
      'hours' => $hours,
      'min' => $min,
      'content' => $content,
      'type' => $type,
      'name' => $name,
      'key' => $key,
      'client' => $client,
      'switch' => true
    );
    $data[$key] = $insert;
    $state->set($this->cycle_key,$data);
  }

}
