<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

ini_set('set_time_limit', 300);
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 43200);

define('DEBUG', 0);

require dirname(__FILE__) . '/vendor/autoload.php';
require('inc/config.php');
require('lib/vidiq.php');

$vidiq = new Vidiq();

$reader = PHPExcel_IOFactory::createReader('Excel2007');
$reader = $reader->load(FOLDER_EXCEL.FILE_EXCEL.'.xlsx');
$reader->setActiveSheetIndex(0);

$number = 1;
$number_sleep = 1;

function level($number) {
    if ($number <= 20) {
        return "Very Low";
    } elseif ($number > 20 && $number <= 40) {
        return "Low";
    } elseif ($number > 40 && $number <= 60) {
        return "Medium";
    } elseif ($number > 60 && $number <= 80) {
        return "High";
    } else {
        return "Very High";
    }
}
while($number > 0) {
    $cells_no = $reader->getActiveSheet()->getCell('A'.($number+1))->getValue();
    $cells_tag = $reader->getActiveSheet()->getCell('B'.($number+1))->getValue();
    $cells_ems = $reader->getActiveSheet()->getCell('C'.($number+1))->getValue();

    if ($cells_ems == "0") $cells_ems = true;
    if(empty($cells_tag) && empty($cells_ems)) {
        echo "file ".FILE_EXCEL." not data or is blank\n"; break;
    }

    if($cells_tag && empty($cells_ems)) {
        $msg = $vidiq->init(trim($cells_tag));
        $vidiq->DEBUG($vidiq->Json('encode', $msg));
        if (empty($msg->response)) {
            $vidiq->DEBUG($vidiq->Json('encode', $msg), false, true);
            echo "sorry, daily limit or acsess invalid.\n"; break;
        }

        if (!is_array($vidiq->Json('decode', $msg->response, true))) {
            $vidiq->DEBUG($vidiq->Json('encode', $msg), false, true);
            echo $vidiq->Json('encode', $msg->response)."\n";
        }

        $cells = 'C';
        $cells_tag = trim($cells_tag);
        foreach ((array) $vidiq->Json('decode', $msg->response)->search_stats->compvol->$cells_tag as $key => $value) {
            if ($key == 'volume') {
                $volume = level(ceil($value));
            }

            if ($key == "overall") {
                $value = number_format($value, 0, '.', ',');
                $overall = level(ceil($value));

                $set_value = $reader->getActiveSheet()->setCellValue($cells.($number+1), $value); ++$cells;
                $set_value = $reader->getActiveSheet()->setCellValue($cells.($number+1), $overall); ++$cells;
                $writer = PHPExcel_IOFactory::createWriter($reader, 'Excel2007');
                $writer->save(FOLDER_EXCEL.FILE_EXCEL.'.xlsx');
            }
            if ($key == "competition") {
                $value = number_format($value, 2, '.', ',');
                $competition = level(ceil($value));

                $set_value = $reader->getActiveSheet()->setCellValue($cells.($number+1), $value); ++$cells;
                $set_value = $reader->getActiveSheet()->setCellValue($cells.($number+1), $competition); ++$cells;
                $writer = PHPExcel_IOFactory::createWriter($reader, 'Excel2007');
                $writer->save(FOLDER_EXCEL.FILE_EXCEL.'.xlsx');
            }
            if ($key == "estimated_monthly_search") {
                $value = number_format($value, 1, '.', ',');

                $set_value = $reader->getActiveSheet()->setCellValue($cells.($number+1), $value); ++$cells;
                $set_value = $reader->getActiveSheet()->setCellValue($cells.($number+1), $volume); ++$cells;
                $writer = PHPExcel_IOFactory::createWriter($reader, 'Excel2007');
                $writer->save(FOLDER_EXCEL.FILE_EXCEL.'.xlsx');
            }
        }

        echo $cells_no."|".$cells_tag."|done\n"; sleep(SLEEP);
        if (fmod($number_sleep/10, 1) == 0.0) sleep(MAX_SLEEP);
        $number_sleep++;
    }

    $number++;
}
