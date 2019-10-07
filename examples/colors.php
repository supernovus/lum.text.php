<?php

require_once 'vendor/autoload.php';

use Lum\Text\Colors as C;

echo C::bg('red').C::fg('white', true)."Red".C::NORMAL."\n";
echo C::fg('blue').C::bg('white')."White".C::NORMAL."\n";
echo C::get(['fg'=>'red','bg'=>'blue','bold'=>true])."Blue".C::get()."\n";

