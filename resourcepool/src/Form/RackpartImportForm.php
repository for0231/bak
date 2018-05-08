<?php
/**
 * @file
 * Contains \Drupal\resourcepool\Form\SettingForm.
 */
namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RackpartImportForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rackpart_import_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '机柜配置导入',
      '#open' => True,
    );
    $form['filters']['button'] = array(
      '#type' => 'link',
      '#title' => '下载模板',
      '#url' => new Url('admin.rackpart.download'),
    );
    $form['filters']['file_upload'] = array(
      '#type'=>'file',
      '#title'=>'机柜配置模板文件',
      '#description' => "请上传机柜配置文件,允许的文件格式为xlsx xls",
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
    if (strtolower($file_type) != "xls" && strtolower($file_type) != "xlsx") {
      $form_state->setErrorByName('file_upload', '文件格式不正确');
    }
  }

  /**
   * {@inheritdoc}cornsilk
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sheet= 0;
    $datetime = date('Ymdhis');
    $uploaded_file = $_FILES['myfile']['name'];
    $save = realpath('sites/default/files');
    $savePath = $save.'/'.$datetime.$uploaded_file;
    $file_name = $datetime.$uploaded_file;
    if(is_uploaded_file($_FILES['myfile']['tmp_name'])) {
        if(move_uploaded_file($_FILES['myfile']['tmp_name'],$savePath)) {
          drupal_set_message('文件上传成功');
          $rows = $this->format_excel2array($savePath,$sheet,$file_name);
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
  public function format_excel2array($filePath = '',$sheet = 0,$file_name)
  {
    require_once  'modules/resourcepool/src/Plugin/PHPExcel/PHPExcel/IOFactory.php';
    require_once  'modules/resourcepool/src/Plugin/PHPExcel/PHPExcel.php';
    require_once  'modules/resourcepool/src/Plugin/PHPExcel/PHPExcel/Reader/Excel5.php';
    require_once  'modules/resourcepool/src/Plugin/PHPExcel/PHPExcel/Reader/Excel2007.php';
    if (empty($filePath) or ! file_exists($filePath)) {
      die('file not exists');
    }
    set_time_limit(0);
    // 建立reader对象
    $PHPReader = \PHPExcel_IOFactory::createReader('Excel2007');
    if (!$PHPReader->canRead($filePath)) {
      $PHPReader = \PHPExcel_IOFactory::createReader('Excel5');
      if (!$PHPReader->canRead($filePath)) {
        echo 'no Excel';
        return;
      }
    }
    $PHPExcel = $PHPReader->load($filePath); // 建立excel对象
    $currentSheet = $PHPExcel->getSheet($sheet); //读取excel文件中的指定工作表
    $allColumn = $currentSheet->getHighestColumn(); //取得最大的列号
    $allRow = $currentSheet->getHighestRow(); //取得一共有多少行
    $data = array();

    for ($rowIndex = 2; $rowIndex <= $allRow; $rowIndex ++) { // 循环读取每个单元格的内容。注意行从1开始，列从A开始
      for ($colIndex = 'A'; $colIndex <= 'Q'; $colIndex ++) {
        $addr = $colIndex . $rowIndex;

        $cell = $currentSheet->getCell($addr)->getValue();
        if ($cell instanceof \PHPExcel_RichText) { // 副文本转换字符串
            $cell = $cell->__toString();
        }
        $data[$rowIndex][$colIndex] = $cell;

      }
    }
    $rows = \Drupal::service('resourcepool.dbservice')->insert_rackpart($data);
    return $rows;
  }
}
