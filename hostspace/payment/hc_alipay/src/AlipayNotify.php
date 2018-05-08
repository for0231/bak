<?php
/**
 * @file \Drupal\hc_alipay\AlipayNotify
 *
 * 类名：AlipayNotify
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 *
 */
namespace Drupal\hc_alipay;

use Drupal\hc_alipay\AlipayCore;
use Drupal\hc_alipay\AlipayMd5;

class AlipayNotify {
  /**
   * HTTPS形式消息验证地址
   */
  var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';

  /**
   * HTTP形式消息验证地址
   */
  var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
  var $alipay_config;

  public function __construct($alipay_config) {
    $this->alipay_config = $alipay_config;
    $this->_core = new AlipayCore;
    $this->_md5  = new AlipayMd5;
  }
  public function AlipayNotify($alipay_config) {
    $this->__construct($alipay_config);
  }
  /**
   * 针对notify_url验证消息是否是支付宝发出的合法消息
   * @return 验证结果
   */
  public function verifyNotify() {
    $stack = \Drupal::request()->request->all();
    if (empty($stack)) {
      return false;
    }
    else {
      //生成签名结果
      $isSign = $this->getSignVeryfy($stack, \Drupal::request()->request->get("sign"));
      // 获取支付宝远程服务器ATN结果(验证是否是支付宝发来的消息)
      $responseText = 'true';
      $notify_id = \Drupal::request()->request->get("notify_id");
      if (!empty($notify_id)) {
        $responseText = $this->getResponse(\Drupal::request()->request->get("notify_id"));
      }


      if (preg_match("/true$/i", $responseText) && $isSign) {
        return true;
      } else {
        return false;
      }
    }
  }

  /**
   * 针对return_url验证消息是否是支付宝发出的合法消息
   * @return 验证结果
   */
  public function verifyReturn() {
    $stack = \Drupal::request()->query->all();
    //判断POST来的数组是否为空
    if (empty($stack)) {
      return false;
    }
    else {
      $isSign = $this->getSignVeryfy($stack, \Drupal::request()->query->get("sign"));
      $notify_id = \Drupal::request()->query->get("notify_id");
      $responseText = 'true';
      if (!empty($notify_id)) {
        $responseText = $this->getResponse(\Drupal::request()->query->get("notify_id"));
        //$responseText = 'true';
      }

      if (preg_match("/true$/i", $responseText) && $isSign) {
        return true;
      } else {
        return false;
      }
    }
  }

  /**
   * 获取返回时的签名验证结果
   * @param $para_temp 通知返回来的参数数组
   * @param $sign 返回的签名结果
   * @return 签名验证结果
   */
	public function getSignVeryfy($para_temp, $sign) {
		//除去待签名参数数组中的空值和签名参数
		$para_filter = $this->_core->paraFilter($para_temp);

		//对待签名参数数组排序
		$para_sort = $this->_core->argSort($para_filter);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = $this->_core->createLinkstring($para_sort);

		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$isSgin = $this->_md5->md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			default :
				$isSgin = false;
		}

		return $isSgin;
	}
  /**
   * 获取远程服务器ATN结果,验证返回URL
   * @param $notify_id 通知校验ID
   * @return 服务器ATN结果
   * 验证结果集：
   * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
   * true 返回正确信息
   * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
   */
  public function getResponse($notify_id) {
    $transport = strtolower(trim($this->alipay_config['transport']));
    $partner = trim($this->alipay_config['partner']);
    $veryfy_url = '';
    if ($transport == 'https') {
      $veryfy_url = $this->https_verify_url;
    }
    else {
      $veryfy_url = $this->http_verify_url;
    }
    $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
    $responseText = $this->_core->getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

    return $responseText;
  }


}
