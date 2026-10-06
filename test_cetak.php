<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/mlite-rsgm/admin/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "username=admin&password=password&login=1");
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
$login = curl_exec($ch);

curl_setopt($ch, CURLOPT_URL, "http://localhost/mlite-rsgm/admin/update_bmt/ans1cetak?id=1");
curl_setopt($ch, CURLOPT_POST, 0);
$cetak = curl_exec($ch);
echo $cetak;
