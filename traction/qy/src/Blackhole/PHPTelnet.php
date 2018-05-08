<?php
/*
PHPTelnet 1.1.1
by Antone Roundy
adapted from code found on the PHP website
public domain
*/

namespace Drupal\qy\Blackhole;

class PHPTelnet {
  var $sock=NULL;
  
  var $loginprompt;
  
  function Connect($ip, $user, $pass, $what) {
    if($this->sock = fsockopen($ip, 23, $errno, $errstr, 5)) {
      stream_set_timeout($this->sock, 2);
      $res = $this->GetResponseTwo();
      $r = explode("\n", $res);
      $this->loginprompt=$r[count($r)-1];
      fputs($this->sock,"$user\r");
      $this->Sleep();

      fputs($this->sock,"$pass\r");
      $this->sleep();
      $this->GetResponse($r, $what);
      $r = explode("\n",$r);
      if (($r[count($r)-1]=='')||($this->loginprompt==$r[count($r)-1])) {
        $this->Disconnect();
        return 1;
      }
      return 0;
    } else {
      echo $errstr;
      return 1;
    }
  }
  
  function Disconnect($exit=1) {
    if ($this->sock) {
      if ($exit) $this->DoCommand('exit', $junk);
      fclose($this->sock);
      $this->sock=NULL;
    }
  }
  
  function isConnect() {
    if($this->sock) {
      return true;
    }
    return false;
  }
  
  /**
   * 执行命令
   *  $c: 要执行的命令
   *  $what: 执行命令的结尾是什么字符串
   *  $accident: 如果结局不等于$what，将匹配$accident里的数据并执行对应的命令
   */
  function DoCommand($c, &$r, $what='', $accident=array()) {
    if($this->sock) {
      fputs($this->sock,"$c\r");
      if(empty($what)) {
        $r = $this->GetResponseTwo();
      } else {
        $this->Sleep();
        $this->GetResponse($r, $what, $accident);
      }
    }
  }
  
  function GetResponse(&$r, $what, $accident=array()) {
    $r = '';
    for($i=0; $i < 500; $i++) {
      do {
        $r.=fread($this->sock, 1024);
        $s = socket_get_status($this->sock);
      } while ($s['unread_bytes']);
      //判断结尾
      if(strrchr($r,$what)==$what) {
        echo $r;
        break;
      }
      if(!empty($accident)) {
        $endstr = $accident['what'];
        $value = $accident['contain']['value'];
        if(strrchr($r, $endstr)==$endstr) {
          echo $r;
          if(strpos($r, $value) !== false) {
            $comm = $accident['contain']['comm'];
            $this->DoCommand($comm, $res);
          }
          break;
        }
      }
      $this->Sleep();
    }
  }
  
  function Sleep() {
    usleep(2000);
  }

  private function GetResponseTwo() {
    $buf = '';
    while (1) {
      $IAC = chr(255);
      $DONT = chr(254);
      $DO = chr(253);
      $WONT = chr(252);
      $WILL = chr(251);
      $theNULL = chr(0);

      $c = fgetc($this->sock);
      if ($c === false) {
        echo $buf;
        return $buf;
      }
      if ($c == $theNULL) {
        continue;
      }
      if ($c != $IAC) {
        $buf .= $c;
        continue;
      }
      $c = fgetc($this->sock);
      if ($c == $IAC) {
        $buf .= $c;
      } else if (($c == $DO) || ($c == $DONT)) {
        $opt = fgetc($this->sock);
        fwrite($this->sock,$IAC.$WONT.$opt);
      } elseif (($c == $WILL) || ($c == $WONT)) {
        $opt = fgetc($this->sock);
        fwrite($this->sock,$IAC.$DONT.$opt);
      } else {
        // echo "where are we? c=".ord($c)."\n";
      }
    }
  }
}
