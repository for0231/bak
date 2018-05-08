<?php
/**
 * @file
 * Contains \Drupal\qy_jd\Controller\FirewallController.
 */

namespace Drupal\member\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\common\fpdf\pdf_chinese;

class BuildPdfController extends ControllerBase {
  /**
   * 生成pdf
   */
  public function certificateBuild($key) {
    $uid = \Drupal::currentUser()->id();
    $member_service = \Drupal::service('member.memberservice');
    if(is_numeric($key)) {
      $proofs = $member_service->loadProof(array('id' => $key));
      if(!empty($proofs)) {
        $proof = reset($proofs);
        if($proof->uid == $uid) {
          $str_oids = explode(',', $proof->order_ids);
          array_shift($str_oids);
          array_pop($str_oids);
          $_SESSION['build_pdf'] = array(
            'key' => $proof->file_name,
            'oids' => $str_oids,
            'save' => true
          );
          $key = $proof->file_name;
        }
      }
    }
    if(empty($_SESSION['build_pdf']) || $_SESSION['build_pdf']['key'] != $key) {
      return new RedirectResponse('/user/account/certificate');
    }
    $oids = $_SESSION['build_pdf']['oids'];
    if(!$_SESSION['build_pdf']['save']) {
      $member_service->addProof(array(
        'file_name' => $key,
        'created' => time(),
        'order_ids' => '0,' . implode(',', $oids). ',0',
        'uid' => $uid,
      ));
      $_SESSION['build_pdf']['save'] = true;
    }
    $orders = entity_load_multiple('order', $oids);
    $pdf=new pdf_chinese();
    $pdf->SetMargins(15,20);
    $pdf->AddGBFont();
    $values = array();
    $i = 0;
    foreach($orders as $order) {
      $values[$i] = $order;
      $i++;
      if($i%10 == 0) {
        $this->pdfpage($pdf, $values);
        $values = array();
        $i = 0;
      }
    }
    if(!empty($values)) {
      $this->pdfpage($pdf, $values);
    }
    $pdf->Output();
    return new \Symfony\Component\HttpFoundation\Response();
  }

  private function pdfpage($pdf, $orders) {
    $pdf->AddPage();
    $path = $_SERVER['DOCUMENT_ROOT'] . '/' . drupal_get_path('module', 'member');
    //加载图片
    $pdf->Image( $path . '/image/logo.png');
    //横线上下文本
    $pdf->SetFont('Arial','',32);
    $pdf->SetTextColor(190,190,190);
    $pdf->Cell(180, 10, "INVOICE", 'B', 1, 'C', false);
    $pdf->SetFont('Arial','B',10);
    $pdf->SetTextColor(0);
    $pdf->Cell(180, 5,"HOSTSPACE NETWORKS LLC", 0, 0, 'C', false);
    //头
    $username = \Drupal::currentUser()->getUserName();
    $title = "HS" . date('ymdHis');
    $date = date('Y-m-d H:i:s');
    $pdf->SetFont('Arial','',10);
    $pdf->SetY(40);
    $pdf->Cell(90, 5,"    To: {$username}", 0, 0, 'L', false);
    $pdf->Cell(55, 5,"Invoice No.:", 0, 0, 'R', false);
    $pdf->Cell(35, 5, $title, 0, 0, 'L', false);
    $pdf->Ln();
    $pdf->Cell(90, 5,"", 0, 0, 'C', false);
    $pdf->Cell(55, 5,"Invoice Date:", 0, 0, 'R', false);
    $pdf->Cell(35, 5, $date, 0, 0, 'L', false);
    $pdf->Ln();
    //提示
    $pdf->SetY(60);
    $pdf->SetFont('GB','',10);
    $pdf->SetFillColor(217,217,217);
    $pdf->Cell(180, 7, "Note:", 1, 1, 'L', true);
    $pdf->Cell(180, 5, "1、The invoice with stamp is exclusive evidence of settlement", "LR", 1, 'L', false);
    $pdf->Cell(180, 5, "2、All bank charge(s) incurred by the paying bank & intermediary bank(s) shall be borne by the customer", "LRB", 1, 'L', false);
    //内容表格
    $pdf->SetY(83);
    $pdf->Cell(70,7,"Description",1,0,'C',true);
    $pdf->Cell(50,7,"Billing Period",1,0,'C',true);
    $pdf->Cell(30,7,"Unit",1,0,'C',true);
    $pdf->Cell(30,7,"Amount(RMB)",1,0,'C',true);
    $pdf->Ln();
    $pdf->SetFillColor(236,236,236);
    $total = 0;
    for($i=0; $i < 10; $i++) {
      if(isset($orders[$i])) {
        $order = $orders[$i];
        $desc = $order->label();
        $text = mb_convert_encoding($desc, "GB2312");
        $period = date('Y-m-d H:i:s',$order->getSimpleValue('payment_date'));
        $products = \Drupal::service('order.product')->getProductByOrderId($order->id());
        $unit = count($products);
        $amount = $order->getSimpleValue('order_price') - $order->getSimpleValue('discount_price');
        $total += $amount; 
        $pdf->Cell(70, 8, $text, 1,0,'C',false);
        $pdf->Cell(50, 8, $period, 1,0,'C',false);
        $pdf->Cell(30, 8, "{$unit}", 1,0,'C',false);
        $pdf->Cell(30, 8, "￥{$amount}", 1,0,'C',true);
      } else {
        $pdf->Cell(70, 8, '', 1,0,'C',false);
        $pdf->Cell(50, 8, '', 1,0,'C',false);
        $pdf->Cell(30, 8, '', 1,0,'C',false);
        $pdf->Cell(30, 8, '', 1,0,'C',true);
      }
      $pdf->Ln();
    }
    $pdf->Cell(70,8,"",0,0,'C',false);
    $pdf->Cell(50,8,"",0,0,'C',false);
    $pdf->Cell(30,8,"Total ",0,0,'R',false);
    $pdf->Cell(30,8,"￥{$total}",1,0,'C',true);
    $pdf->Ln();
    //底部
    $pdf->SetY(195);
    $pdf->SetFont('GB','B',10);
    $pdf->Cell(180,5, 'E-mail：admin@hostspaces.net', 'T', 0, 'R', false);
    $pdf->Ln();
    $pdf->Cell(180,5, 'Tel: +1(323)545-6668  Fax: +1(888)299-6858', 0, 0, 'R', false);
    $pdf->Ln();
    $pdf->Cell(180,5, 'Address: 1788 Sierra Leone Ave #108-100, Rowland Heights, CA, 91748', 0, 0, 'R', false);
    $pdf->Ln();
    $pdf->Cell(180,5, 'Url：http://www.hostspaces.net', 0, 0, 'R', false);
    $pdf->Ln();
  }
}
