<?php

    require '../vendor/autoload.php';

    $file1 = './data/1.dat';
    $file2 = './data/2.dat';
    $file3 = './data/3.dat';
    $file4 = './data/4.dat';

    $savePath = './decoded/';

    is_dir($savePath) or mkdir($savePath, 777, true);

    \Coco\weDecode\WeDecoder::decode($file1, $savePath, '1');
//    \Coco\weDecode\WeDecoder::decode($file2, $savePath, '2');
//    \Coco\weDecode\WeDecoder::decode($file3, $savePath, '3');
//    \Coco\weDecode\WeDecoder::decode($file4, $savePath, '4');

