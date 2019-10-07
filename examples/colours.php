<?php

require_once 'vendor/autoload.php';

use Lum\Text\Colours as C;

echo C::fg('red')."Warning".C::NORMAL."\n";
echo C::bg('red')."Extra warning".C::NORMAL."\n";
echo C::get(['fg'=>'yellow','bg'=>'blue'])."Hello world".C::get()."\n";

