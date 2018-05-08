<?php

$ch = curl_init();
$url = 'http://58.84.54.7/admin/cycle/exec';
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$post = array(
  'access' => 'xunyun'
);
$post = http_build_query($post);
curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
$data = curl_exec($ch);
$resp = curl_multi_getcontent($ch);
curl_close($ch);
