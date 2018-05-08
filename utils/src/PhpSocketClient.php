<?php

namespace Drupal\utils;

class PhpSocketClient {
  
  protected $socket;
  
  public function __construct($ip, $port) {
    $this->socket = socket_create(AF_INET, SOCK_STREAM, 0);
    if (@socket_connect($this->socket, $ip, $port) == false) {
       throw new \Exception("socket connect error.");
    }
  }
  
  /**
   * 执行命令
   */
  public function command($str, $action) {
    $req_len = strlen($str);
    $header = json_encode(array('length' => $req_len, 'action' => $action));
    $send_h = socket_write($this->socket, $header, strlen($header));
    if($send_h === false || $send_h < strlen($header)) {
       return false;
    }
    $head_res = socket_read($this->socket, 1024);
    if($head_res == '200') {
      $sends = socket_write($this->socket, $str, $req_len);
      if($sends === false || $sends < $req_len ) {
         return false;
      }
      $response = "";
      while(true) {
        $recv = "";
        if (($recv = socket_read($this->socket, 1024)) === false) {
          return false;
        }
        if ($recv == ""){
          break;
        }
        $response .= $recv;
      }
      return $response;
    } else {
      return false;
    }
  }
  
  public function close() {
    socket_close($this->socket);
  }
}
