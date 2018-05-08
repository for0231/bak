<?php
namespace Drupal\worksheet;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class WorksheetOptionService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  protected $cache_options = array();
  public function __construct(Connection $database) {
    $this->database = $database;
  }
  
  /**
   * 选项类型
   */
  public function optionType()  {
    return array(
      'ip_type' => 'IP类型',
      'system' => '操作系统',
      'problem_difficulty' => '问题难度',
      'problem_type' => '问题类型'
    );
  }
  /**
   * 获取操作系统选项
   */
  public function getSystemOptions() {
    return $this->getOptions('system');
  }
  /**
   * 获取IP类型选项
   */
  public function getIpClassOptions() {
    return $this->getOptions('ip_type');
  }
 /**
  * 问题类型
  */
  public function getProblemTypesOptions() {
    return $this->getOptions('problem_type');
  }
  /**
   * 问题难度
   */
  public function getProblemDifficultyOptions() {
    return $this->getOptions('problem_difficulty');
  }
  /**
   * 操作部门
   */
  public function getOpDept() {
    return $this->getOptions('op_dept');
  }
  /**
   * 工作类型
   */
  public function getJobContent() {
    return $this->getOptions('job_content');
  }
  /**
   * 机房
   */
  public function getRoom() {
    return $this->getOptions('room');
  }
  /**
   * 影响方向
   */
  public function getAffectDirection() {
    return $this->getOptions('affect_direction');
  }
  /**
   * 影响范围
   */
  public function getAffectRange() {
    return $this->getOptions('affect_range');
  }
  /**
   * 影响程度
   */
  public function getAffectLevel() {
    return $this->getOptions('affect_level');
  }
  /**
   * 故障定位
   */
  public function getFaultLocation() {
    return $this->getOptions('fault_location');
  }

  /**
   * 获取Options
   */
  public function getOptions($type, $parent_id = 0) {
    $datas = $this->database->select('work_sheet_options', 't')
      ->fields('t')
      ->condition('t.option_type', $type)
      ->condition('t.parent_id', $parent_id)
      ->condition('t.is_delete', 0)
      ->execute()
      ->fetchAll();
    $options = array();
    foreach($datas as $data) {
      $options[$data->id] = $data->optin_name;
    }
    return $options;
  }

  public function getOptionByid($id) {
    if(array_key_exists($id, $this->cache_options)) {
      return $this->cache_options[$id];
    }
    $object = $this->database->select('work_sheet_options', 't')
      ->fields('t')
      ->condition('t.id', $id)
      ->execute()
      ->fetchObject();
    $this->cache_options[$id] = $object;
    return $object;
  }
  /**
   * 获取所有的选项
   */
  public function getOptionAll() {
    $this->database->select('work_sheet_options', 't')
      ->fields('t')
      ->condition('t.is_delete', 0)
      ->execute()
      ->fetchAll();
  }
  /**
   * 各选项的默认值
   */
  public function optionDefault() {
    return array(
      array('optin_name'=> 'win03', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'win08(未激活状态)', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'win2012(未激活状态)', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'CentOS5', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'CentOS6', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'CentOS7', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'UBUNTU', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'RedHat', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'XEN', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'VMWARE(未激活状态)', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'DEBIAN', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '不需要安装', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '新系统', 'option_type' => 'system', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '普通IP', 'option_type' => 'ip_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '高防IP', 'option_type' => 'ip_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '大带宽IP', 'option_type' => 'ip_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'VPS', 'option_type' => 'ip_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '站群IP', 'option_type' => 'ip_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '简单', 'option_type' => 'problem_difficulty', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '基本', 'option_type' => 'problem_difficulty', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '困难', 'option_type' => 'problem_difficulty', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '极其困难', 'option_type' => 'problem_difficulty', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '升级求助', 'option_type' => 'problem_difficulty', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '无法解决', 'option_type' => 'problem_difficulty', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '内网问题', 'option_type' => 'problem_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '外网问题', 'option_type' => 'problem_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '电信问题', 'option_type' => 'problem_type', 'parent_id'=>26, 'is_delete'=>0),
      array('optin_name'=> '联通问题', 'option_type' => 'problem_type', 'parent_id'=>26, 'is_delete'=>0),
      array('optin_name'=> '移动问题', 'option_type' => 'problem_type', 'parent_id'=>26, 'is_delete'=>0),
      array('optin_name'=> '海外问题', 'option_type' => 'problem_type', 'parent_id'=>26, 'is_delete'=>0),
      array('optin_name'=> 'CN2问题', 'option_type' => 'problem_type', 'parent_id'=>26, 'is_delete'=>0),
      array('optin_name'=> '防御问题', 'option_type' => 'problem_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'DDOS攻击', 'option_type' => 'problem_type', 'parent_id'=>32, 'is_delete'=>0),
      array('optin_name'=> 'CC攻击', 'option_type' => 'problem_type', 'parent_id'=>32, 'is_delete'=>0),
      array('optin_name'=> '服务器问题', 'option_type' => 'problem_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'P3以上故障', 'option_type' => 'problem_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '无法分类问题', 'option_type' => 'problem_type', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ATOM专用', 'option_type' => 'ip_type', 'parent_id'=>0, 'is_delete'=>0),
    );
  }
  /**
   * 各选项的默认值2
   */
  public function optionDefault2() {
    return array(
      array('optin_name'=> 'LA机房', 'option_type' => 'room', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'HK机房', 'option_type' => 'room', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'DC机房', 'option_type' => 'room', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '电信用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '海外用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '联通用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '所有用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '移动用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '整机柜用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '多ISP用户', 'option_type' => 'affect_direction', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '25%', 'option_type' => 'affect_range', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '50%', 'option_type' => 'affect_range', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '75%', 'option_type' => 'affect_range', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '100%', 'option_type' => 'affect_range', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '业务受影响', 'option_type' => 'affect_level', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '业务无法使用', 'option_type' => 'affect_level', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '业务严重影响', 'option_type' => 'affect_level', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ISP-机房段线路问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ISP-骨干网问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ISP-国际出入口问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ISP-机房段线路问题-36678', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ISP-机房段线路问题-CN2', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> 'ISP-其他问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '恶意Ddos攻击-内部', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '恶意Ddos攻击-外部', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '防火墙故障-金盾', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '防火墙故障-唯盾', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '牵引系统故障-系统问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '牵引系统故障-黑洞BGP问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '牵引系统故障-人为问题', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      array('optin_name'=> '其他', 'option_type' => 'fault_location', 'parent_id'=>0, 'is_delete'=>0),
      
    );
  }
}