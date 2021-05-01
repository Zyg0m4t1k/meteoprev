<?php

require_once __DIR__ . '/../../core/php/core.inc.php';

$file = __DIR__ . '/core/config/forecast.html';
$html = file_get_contents($file);

$file = __DIR__ . '/data/vevey_days.json';
$datas = json_decode(file_get_contents($file),true);
$replace = array();

foreach ($datas as $key => $values) {
	foreach($values as $k => $v) {
		if(!is_array($v)) {
			$replace[ '#' . $key . '_' . $k . '#' ] = $v;
		}
	}
}
echo template_replace($replace, $html);
