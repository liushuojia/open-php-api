<?php
  $array = array(
        "Md5Key" => "Md5Key",
        "mobile" => "mobile",
        "UserAgent" => "UserAgent",
        "0" => "a",
        "2" => "b",
        "9" => "c",
        "8" => "d",
        "7" => "e",
        "a" => "e",
        "b" => "e",
        "e" => "e",
        "m" => "e",
        "M" => "e",
        "d" => "e",
  );
  ksort($array,SORT_STRING);
  print_r($array);
