<?php

/**
 * @file
 * Contains \Drupal\contract\Form\ContractForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
class ContractForm extends ContentEntityForm {

  /**
   * The custom buainsess ip storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;	

	/**
   * Constructs a businessIp object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The custom block storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityStorageInterface $entity_storage) {
    parent::__construct($entity_manager);
    $this->entityStorage = $entity_storage;
  }
 	
	 /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager,
      $entity_manager->getStorage('host_contract')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {   
    $form = parent::form($form, $form_state);
    $users = entity_load_multiple('contract_user');
    if(!empty($users)) {
      foreach($users as $user) {
        $ops[$user->id()] = $user->label();
      }
    }
    $form['client'] = array(
      '#type' => 'select',
      '#options' => isset($ops) ?( array('' => '选择客户') +$ops) : array(),
      '#required' => TRUE,
      '#title' => '客户',
      '#weight' => 3,
      '#default_value' => !$this->entity->isNew() ? $this->entity->get('client')->entity->id() : '' 
    );
    $form['date'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );        
    // 时间
    $form['date']['start'] = array(
    	'#title' => '开始时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#default_value' => $this->entity->getproperty('effect_time') ? format_date($this->entity->getproperty('effect_time'), 'custom', 'Y-m-d') : '',
      '#required' =>TRUE,
    );
    $form['date']['expire'] = array(
    	'#title' => '结束时间',
    	'#type' => 'textfield',
      '#size' => 12,
      '#default_value' => $this->entity->getproperty('invalid_time') ? format_date($this->entity->getproperty('invalid_time'), 'custom', 'Y-m-d') : '',
      '#required' =>TRUE,
    );   

    $form['project'] = array(
      '#type' => 'select',
      '#title' => '关联项目',
      '#default_value' => $this->entity->getPropertyObject('project') ? $this->entity->getPropertyObject('project')->id() : '',
      '#options' => array('' => '选择项目') + $this->getProjectOps(),
      '#required' =>TRUE,
      '#weight' => 2
    );

    $form['remark'] = array(
      '#type' => 'textarea',
      '#title' => '合同说明',
      '#weight' => 4
    ); 
  
    if(!$this->entity->isNew()) {
      $form += $this->getFundsList($form, $form_state);
      $form += $this->getGoodsList($form, $form_state);
      $form['status'] = array(
        '#type' => 'select',
        '#title' => '合同状态',
        '#default_value' => $this->entity->getproperty('status'),
        '#options' => contractStatus(),
        '#weight' => 2,
        '#disabled' => TRUE
      );
      if(in_array($this->entity->getProperty('status'), array(3, 4))) {
        $form['#disabled'] = TRUE;
      }
    }
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');
    return $form;
  }

  /**
   * 交货计划列表表头
   */
  private function buildGoodsHeader() {
    $header['name'] = array(
       'data' => '货物名称',
       'field' => 'name',
       'specifier' => 'name'
    );
    $header['method'] = array(
       'data' => '交货方式',
       'field' => 'method',
       'specifier' => 'method'
    );
    $header['delivery_stamp'] = array(
       'data' => '交货时间',
       'field' => 'delivery_stamp',
       'specifier' => 'delivery_stamp'
    );
		$header['remark'] = array(
       'data' => '说明',
       'field' => 'remark',
       'specifier' => 'remark'
    );
    $header['status'] = array(
       'data' => '状态',
       'field' => 'status',
       'specifier' => 'status'
    );
    $header['op'] = array(
       'data' => '操作',
    );
   return $header;
  }
  /**
   * 给货物列表绑定数据
   */
  private function createGoodsRows() {
    $rows = array();
    $contract_status = $this->entity->getproperty('status');
    $data = \Drupal::service('contract.contractservice')->getAllGoodsPlanByContract($this->entity->id(), $this->buildGoodsHeader());
    foreach($data as $plan) {
      $rows[$plan->id] = array(
        'name' => $plan->name,
        'method' => $plan->method,
        'delivery_stamp' => format_date($plan->delivery_stamp, 'custom', 'Y-m-d'),
        'remark' => $plan->remark ? $plan->remark : '---',
        'status' => fundsStatus()[$plan->status]
      );
      if($contract_status == 2) { // 合同执行中
        $rows[$plan->id]['operations']['data'] = array(
          '#type' => 'operations',     
          '#links' => $this->getGoodsOp($plan->id, $plan->status) 
        );
      } else {
        $rows[$plan->id]['op'] = '该合同' . contractStatus()[$contract_status];
      }
    }
    return $rows;
  }

  /**  
   * 得到对交货计划列表的操作菜单
   */
  private function getGoodsOp($id, $status=0) {
    $op = array();
    $contract_status = $this->entity->getproperty('status');
    if($contract_status == 2) { // 合同执行中 
      if(!$status) {
        $op['delete'] = array(
          'title' => '删除',
          'url' => new Url('contract.goods.delete', array('host_contract'=> $this->entity->id(), 'goods_plan'=>$id))
        );
        $op['complete'] = array(
          'title' => '完成',
          'url' => new Url('contract.goods.complete', array('host_contract'=> $this->entity->id(), 'goods_plan'=>$id))
        );
      }    
   }
    return $op;
  }


  /**
   * 得到交货计划列表
   */

  private function getGoodsList(array $form, FormStateInterface $form_state) {
    if(!in_array($this->entity->getProperty('status'), array(3, 4))) {
       $form['add_goods'] = array(
        '#type' => 'link',
        '#title' => t('添加交货计划'),
        '#weight' => -1000,
        '#attributes' => array(
          'class' => array('button', 'button--foo'),
        ),
        '#url' => new Url('contract.goods.add', array('host_contract' => $this->entity->id()))
      ); 
    }
    $form['goods'] = array(
      '#type' => 'details',
      '#title' => '交货计划',
      '#open' => TRUE,
      '#weight' => -43 
    );
    $form['goods']['goods_list'] = array(
      '#type' => 'table',
      '#header' => $this->buildGoodsHeader(),
      '#rows' => $this->createGoodsRows()
    );
    return $form;
  }

  /**
   * 得到资金计划列表
   */
  private function getFundsList(array $form, FormStateInterface $form_state) {
    $host_contract = $this->entity->id();
    // 合同总金额
    $total = $this->entity->getproperty('amount');
    // 收款总计
    $income = \Drupal::service('contract.contractservice')->getAmount(1, $host_contract);
    // 已经收到的金额
    $hasin = \Drupal::service('contract.contractservice')->getAmount(1, $host_contract, 1);
    // 付款总计
    $pay= \Drupal::service('contract.contractservice')->getAmount(2, $host_contract);
    // 已经付款的金额
    $hasout= \Drupal::service('contract.contractservice')->getAmount(2, $host_contract);
    if(!in_array($this->entity->getProperty('status'), array(3, 4)) && ($total- $income + $pay) > 0) {
       $form['add_plan'] = array(
        '#type' => 'link',
        '#title' => t('添加资金计划'),
        '#weight' => -1000,
        '#attributes' => array(
          'class' => array('button', 'button--foo'),
        ),
        '#url' => new Url('contract.funds.add_form', array('host_contract' => $this->entity->id()))
      ); 
    }
    $form['funds'] = array(
      '#type' => 'details',
      '#title' => '资金计划',
      '#open' => TRUE,
      '#weight' => -44,
      '#disabled' => TRUE
    );

    $form['funds']['get'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['funds']['out'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['funds']['diff_amounts'] = array(
      '#type' => 'container',
      '#attributes' => array('class' => array('container-inline')),
    );

    $form['funds']['contract_amount'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' . $total,
      '#title' => '合同总金额',
      '#size' =>15 ,
      '#weight' => -1
    );
    $form['funds']['get']['income'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' . $income,
      '#title' => '计划收款',
      '#size' =>15 
    );
    $form['funds']['get']['has_income'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' . $hasin,
      '#title' => '实际收款',
      '#size' =>15 
    );  
    $form['funds']['out']['spend'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' .$pay,
      '#title' => '计划付款',
      '#size' =>15 
    ); 
    $form['funds']['out']['has_spend'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' .$hasout,
      '#title' => '实际付款',
      '#size' =>15 
    ); 

    $form['funds']['diff_amounts']['diff'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' .($total- ($income-$pay)),
      '#title' => '理论差额',
      '#size' =>15 
    ); 
     $form['funds']['diff_amounts']['has_diff'] = array(
      '#type' => 'textfield',
      '#value' => '￥' . ' : ' .($total- ($hasin - $hasout)),
      '#title' => '实际差额',
      '#size' =>15 
    ); 
    $form['funds']['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->createRows(),
      '#empty' => '此合同还未添加资金计划!'
    );
    $form['funds']['pager']['#type'] = 'pager';  
    return $form;
  }
  /**
   * 给列表绑定数据
   */
  private function createRows() {
    $rows = array();
    $contract_status = $this->entity->getproperty('status');
    $data = \Drupal::service('contract.contractservice')->getPlaneByContract($this->entity->id(), $this->buildHeader());
    foreach($data as $plan) {
      $rows[$plan->id] = array(
        'amount' => $plan->amount,
        'type' => fundsType()[$plan->type],
        'method' => fundsMethod()[$plan->method],
        'created' => format_date($plan->plan_time, 'custom', 'Y-m-d'),
        'success_time' => $plan->success_time ? format_date($plan->success_time, 'custom', 'Y-m-d') : '---',
        'remark' => $plan->remark ? $plan->remark : '---',
        'status' => fundsStatus()[$plan->status]
      );
      if($contract_status == 2) { // 合同执行中
        $rows[$plan->id]['operations']['data'] = array(
          '#type' => 'operations',     
          '#links' => $this->getOp($plan->id, $plan->status) 
        );
      } else {
        $rows[$plan->id]['op'] = '该合同' . contractStatus()[$contract_status];
      }
    }
    return $rows;
  }

  /**  
   * 得到对资金计划列表的操作菜单
   */
  private function getOp($id, $status=0) {
    $op = array();
    $contract_status = $this->entity->getproperty('status');
    if($contract_status == 2) { // 合同执行中 
      if(!$status) {
        $op['delete'] = array(
          'title' => '删除',
          'url' => new Url('contract.funds.delete', array('host_contract'=> $this->entity->id(), 'funds_plan'=>$id))
        );        
      }    
   }
    return $op;
  }

  /**
   * 资金计划列表表头
   */
  private function buildHeader() {
    $header['amount'] = array(
       'data' => '资金金额',
       'field' => 'amount',
       'specifier' => 'amount'
    );
    $header['type'] = array(
       'data' => '资金性质',
       'field' => 'type',
       'specifier' => 'type'
    );
    $header['method'] = array(
       'data' => '结算方式',
       'field' => 'method',
       'specifier' => 'method'
    );
    $header['create'] = array(
       'data' => '计划完成时间',
       'field' => 'created',
       'specifier' => 'plan_time'
    );
    $header['success_time'] = array(
       'data' => '完成时间',
       'field' => 'success_time',
       'specifier' => 'success_time'
    );
		$header['remark'] = array(
       'data' => '资金说明',
       'field' => 'remark',
       'specifier' => 'remark'
    );
    $header['status'] = array(
       'data' => '计划状态',
       'field' => 'status',
       'specifier' => 'status'
    );
    $header['op'] = array(
       'data' => '操作',
    );

   return $header;
  }

  /**
   * 得到所有正在执行中的项目
   */
  private function getProjectOps() {
    $projects =  entity_load_multiple_by_properties('host_project', array('status' => 1));
    $ops = array();
    foreach($projects as $p) {
      $ops[$p->id()] = $p->label() . '[' . $p->getProjectproperty('name') . ']';
    }
    return $ops;
  }

  /**
   * 生成合同编号
   */
  private function creatContractID() {
    $num_id = 'HS';
    $time = date("i-s");
    $num_id .= str_replace("-","",$time);
    $num_id .= rand(1,9999);
    return $num_id;
  }

 /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $start = $form_state->getValue('start');
    $expire = $form_state->getValue('expire');
    $status = $form_state->getValue('status');
    $remark = $form_state->getValue('remark');
    $pid = $form_state->getValue('project');
    $client = $form_state->getValue('client');
    $project = entity_load('host_project', $pid);
    
    $entity->set('code', $this->creatContractID());
    $entity->set('uid', \Drupal::currentUser()->id());
    $entity->set('project', $project);
    $entity->set('effect_time', strtotime($start));
    $entity->set('invalid_time', strtotime($expire));
    $entity->set('status', $status ? $status : 1);
    $entity->set('remark', $remark);
    $entity->set('client', $client);
    
    $entity->save();
 
    drupal_set_message('合同创建成功！');
    $form_state->setRedirectUrl(new Url('contract.admin'));
  }
}
