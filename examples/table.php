<?php

require_once 'vendor/autoload.php';

use Lum\Text\Table as T;
use Lum\Text\Colors as C;

$table = new T(
[
  'addline' => T::LINE_BEFORE,
  'headerColor' => C::LIGHT_YELLOW,
  'multiline' => false,
  'columns' =>
  [
    ['length'=>8],
    ['length'=>32, 'align'=>T::ALIGN_RIGHT],
    ['align'=>T::ALIGN_CENTER],
  ],
]);

function draw_table ($table)
{
  $text = $table->addHeader(['One','Two','Three'], true);
  $text .= $table->addRow(['Hello world', 'First one', 'Foo bar']);
  $text .= $table->addRow(['Goodbye', 'Another one', 'Bar foo']);
  $text .= $table->addRow(['It\'s the end of the', 'world as we know', 'it, and I feel fine.']);
  $text .= $table->addBottom();
  echo $text;
}

// Draw a table with regular single-line rows.
draw_table($table);

// Now draw the table again, with multi-line rows.
$table->multiline = true;
draw_table($table);

