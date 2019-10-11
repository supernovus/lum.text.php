<?php

namespace Lum\Text;

/**
 * Let's get some Box drawing characters.
 *
 * This version has some quick character codes used by the Table class.
 * I might add more in the future. You can make subclasses of this and
 * override the individual character codes to make different box types.
 */
class Box
{
  public $default_encoding = 'UTF-8';

  public $characters =
  [
    'tl' => '2554',
    'tr' => '2557',
    'tc' => '2564',

    'bl' => '255A',
    'br' => '255D',
    'bc' => '2567',

    'll' => '255F',
    'rl' => '2562',
    'ml' => '253C',

    'hh' => '2550',
    'hl' => '2500',
    'vl' => '2502',
    'vh' => '2551',
  ];

  public function __construct ($opts=[])
  {
    if (isset($opts['default_encoding']) 
      && is_string($opts['default_encoding']))
    { // Overriding the default encoding.
      $this->default_encoding = $opts['default_encoding'];
    }
    if (isset($opts['characters']) && is_array($opts['characters']))
    { // Overriding the full set of characters, make sure they are valid!
      $this->characters = $opts['characters'];
    }
    else
    { // Let's look for options for each of the known characters.
      foreach (array_keys($this->characters) as $charopt)
      {
        if (isset($opts[$charopt]) && ctype_xdigit($opts[$charopt]))
        {
          $this->characters[$charopt] = $opts[$charopt];
        }
      }
    }
  }

  public function get ($code, $encoding=null)
  {
    if (is_null($encoding))
    {
      $encoding = $this->default_encoding;
    }
    if (isset($this->characters[$code]))
    {
      $code = $this->characters[$code];
    }
    elseif (!ctype_xdigit($code))
    {
      throw new \Exception("Invalid code sent to Box::get()");
    }
    return html_entity_decode("&#x$code;", ENT_NOQUOTES, $encoding);
  }

}
