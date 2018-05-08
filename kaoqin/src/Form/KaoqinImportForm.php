<?php
/**
 * @file
 * Contains \Drupal\kaoqin\Form\SettingForm.
 */
namespace Drupal\kaoqin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class KaoqinImportForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'kaoqin_import_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '考勤数据导入',
      '#open' => True,
    );

    $form['filters']['button'] = array(
      '#type' => 'link',
      '#title' => '下载模板',
      '#url' => new Url('admin.kaoqin.download'),
    );

    $form['filters']['file_upload'] = array(
      '#type'=>'file',
      '#title'=>'考勤数据模板文件',
      '#description' => "请上传考勤数据文件,允许的文件格式为xlsx xls",
      '#attributes' => array(
        'name' => 'myfile',
      ),
    );

    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '导入',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $tem_file = $_FILES['myfile']['name'];
    $file_types = explode(".", $tem_file);
    $file_type = $file_types[count($file_types) - 1];
    if (strtolower($file_type) != "xls" && strtolower($file_type) != "xlsx" && strtolower($file_type) != "csv") {
      $form_state->setErrorByName('file_upload', '文件格式不正确');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sheet= 0;
    $datetime = date('Ymdhis');
    $uploaded_file = $_FILES['myfile']['name'];
    $file_name = $datetime.$uploaded_file;

    $save = 'public://kaoqin';
    if (!is_dir($save)) {
      drupal_mkdir($save);
    }
    $savePath = $save.'/'.$file_name;
    if(is_uploaded_file($_FILES['myfile']['tmp_name'])) {
        if(drupal_move_uploaded_file($_FILES['myfile']['tmp_name'],$savePath)) {
          drupal_set_message('文件上传成功');
          $rows = $this->format_excel2array_from_kaoqin($savePath);
          if($rows){
            drupal_set_message('数据导入成功,成功个数'.$rows);
          }
          else{
            drupal_set_message('数据导入失败','error');
          }
        } else {
          drupal_set_message('文件上传失败','error');
        }
    }else {
      drupal_set_message('文件上传失败','error');
    }
  }

  public function format_excel2array_from_kaoqin($file_uri) {
    module_load_include('inc', 'phpexcel');

    $result = phpexcel_import(drupal_realpath($file_uri));
    if (is_array($result)) {
      $kaoqin_array = $result[0]; // 这里仅第一个sheet表单的数据.
      $formal = $this->updateKaoqin($kaoqin_array);
    } else {
    drupal_set_message(t("Oops ! An error occured !"), 'error');
    }

    return count($result[0]);
  }

  private function updateKaoqin($result) {
    $datas = [];
    $i = 0;
    foreach ($result as $row) {
      list($morning, $afternoon) = $this->getFirstAndLastTime($row);
      // 屏蔽导入时未打卡的记录.
      /**
      if (!$morning && !$afternoon) {
        continue;
      }**/
      $datas[$i] = [
        'code' => $row['人员编号'],
        'emname' => $row['姓名'],
        'logdate' => $this->getTransformDate($row['考勤日期']),
        'weekday' => $row['星期'],
        'banci' => $row['班次'],
        'morningsign' => $this->getKaoqinTimestamp($this->getTransformDate($row['考勤日期']), $morning),
        'afternoonsign' => $this->getKaoqinTimestamp($this->getTransformDate($row['考勤日期']), $afternoon),
      ];
      $i++;
    }
    \Drupal::service('kaoqin.kaoqinservice')->saveImportData($datas);
  }


  private function getFirstAndLastTime($times) {
    $daka = [
      1 => $times['上班1'],
      2 => $times['下班2'],
      3 => $times['上班3'],
      4 => $times['下班4'],
      5 => $times['上班5'],
      6 => $times['下班6'],
    ];
    $new_daka = array_filter($daka);

    return [reset($new_daka), array_pop($new_daka)];
  }

  /**
   * @description 获取变换后的时间戳.
   */
  private function getKaoqinTimestamp($date, $time) {
    $hour = $this->getTransformHour($time);
    $minute = $this->getTransformMinute($time);

    $hour = ($hour < 10) ? '0'.$hour : $hour;
    $minute = ($minute < 10) ? '0'.$minute : $minute;

    $time_str = date('Y-m-d', $date) . " " . $hour . ":" . $minute . ":05";

    return strtotime($time_str);
  }
  /**
   * @description 数据信息参考
   *              http://blog.csdn.net/wendan564447508/article/details/52596246
   */
  private function getTransformDate($datenumber) {
    $d = 25569;
    $t = 24 * 60 * 60;
    return strtotime(gmdate('Y-m-d', ($datenumber - $d) * $t));
  }

  private function getTransformHour($decimal) {
    $hour = floor($decimal * 24);
    return $hour;
  }

  private function getTransformMinute($decimal) {
    $minute_decimal = $decimal * 24 - floor($decimal * 24);
    $minute = round($minute_decimal * 60);
    return $minute;
  }

}

