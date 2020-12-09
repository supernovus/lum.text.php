<?php

namespace Lum\Text;

class Util
{
  const ALIGN_LEFT   = STR_PAD_RIGHT;
  const ALIGN_RIGHT  = STR_PAD_LEFT;
  const ALIGN_CENTER = STR_PAD_BOTH;

  const DEFAULT_THRESHOLD = 5;

  static function make_identifier ($string, $maxlen=null, $threshold=self::DEFAULT_THRESHOLD)
  {
    $ident = preg_replace('/[^A-Za-z_0-9]*/', '', 
             preg_replace('/[\s\.\-]+/',      '_', 
             $string));

    if (isset($maxlen) && is_numeric($maxlen) && $maxlen > 0)
    {
      $opts =
      [
        'join' => '...',
        'threshold' => $threshold,
      ];
      $ident = self::truncate($ident, $maxlen, $opts);
    }
    
    return $ident;
  }

  /**
   * Super simple text truncation method.
   *
   * There's no validation in this, so generally not recommended to use
   * it directly, instead use truncate() or pad() which can call this
   * if needed.
   *
   * @param string $string  The string we are truncating.
   * @param int    $len     The length to truncate to.
   * @param int    $offset  The position in the string to start from.
   *                        Default: 0
   * @param string $append  Text to append after truncation.
   *                        Default: ''
   *
   * @return string  The truncated string.
   */
  static function tr ($string, $len, $offset=0, $append='')
  {
    return substr($string, $offset, ($len - strlen($append))) . $append;
  }

  /**
   * A more comprehensive text truncation method.
   *
   * This version supports a few options including the ability to truncate
   * from the middle of a long string instead of the end, so that the first
   * and last characters are still able to be read.
   *
   * @param string $string   The string we are truncating.
   * @param int    $maxlen   The maximum length of the string we want returned.
   * @param array  $opts     An assoc array supporting the following keys.
   *
   *  'offset'      =>  The position in the string to start from.
   *                    Default: 0
   *  'join'        =>  The string to insert in middle-of-string truncation.
   *                    Default: '...'
   *  'threshold'   =>  The number of characters over $maxlen we'll use the
   *                    middle-of-string style truncation. Default: 5
   *  'append'      =>  The string to append if using end-of-string truncation.
   *                    Default: ''
   *
   * @return string  The truncated string.
   */
  static function truncate ($string, $maxlen, $opts=[])
  {
    $offset = 
      isset($opts['offset']) 
      ? $opts['offset'] 
      : 0;
    $join = isset($opts['join'])
      ? $opts['join']
      : '...';
    $threshold = isset($opts['threshold'])
      ? $opts['threshold']
      : static::DEFAULT_THRESHOLD;
    $append = isset($opts['append'])
      ? $opts['append']
      : '';

    $len = strlen($string);
    if ($len > $maxlen)
    { // It's longer than the maximum.
      if ($len > ($maxlen + $threshold))
      { // It's over the threshold, use middle-of-string truncation.
        $jlen = strlen($join);
        $margin = 1;      // base margin from 0.
        $margin += $jlen; // margin includes size of join string.
        $size = floor((($maxlen/2)-$margin));
        $str1 = substr($string, $offset, $size);
        $size *= -1;
        $str2 = substr($string, $size);
        $string = $str1 . $join . $str2;
      }
      else
      { // Pass through to simple tr() truncation method.
        $string = self::tr($string, $len, $offset, $append);
      }
    }
    return $string;
  }

  /**
   * Pad a string to a certain length using PHP's str_pad() function.
   * Also can perform automatic truncation using either tr() or truncate().
   *
   * @param string $string  The string we are padding.
   * @param int    $len     The desired length we want the string.
   * @param array  $opts    An assoc array supporting the following keys.
   *
   *  'pad'         =>  The character to use for padding. Default ' '.
   *  'align'       =>  Alignment of padding. Can use any STR_PAD_* constant,
   *                    or ALIGN_LEFT, ALIGN_RIGHT, ALIGN_CENTER static class
   *                    constants in the \Lum\Text\Util class.
   *                    Default: ALIGN_LEFT.
   *  'offset'      =>  The position in the string to start from.
   *                    Default: 0
   *  'append'      =>  The string to append if using end-of-string truncation.
   *                    Default: ''
   *
   *  'join'        =>  If this option is set, the string will be passed to
   *                    the truncate() method along with all of the options
   *                    in the $opts (so it also supports 'threshold'.)
   *                    
   *                    If the 'join' option is not set, or is an empty string,
   *                    then this method will use the tr() method to truncate
   *                    strings longer than the $len.
   * 
   * @return string  The padded and/or truncated string.
   */
  static function pad ($str, $len, $opts=[])
  {
    $offset = 
      isset($opts['offset']) 
      ? $opts['offset'] 
      : 0;
    $align = isset($opts['align'])
      ? $opts['align']
      : static::ALIGN_LEFT;
    $append = isset($opts['append'])
      ? $opts['append']
      : '';
    $pad = isset($opts['pad'])
      ? $opts['pad']
      : ' ';

    $slen = strlen($str);
    if ($offset >= $slen)
    { // A blank line, sure.
      return str_pad('', $len, $pad, $align);
    }
    else
    {
      if (isset($opts['join']) && $opts['join'])
      { // A non-blank string was used for the 'join' option, use truncate()
        $str = self::truncate($str, $len, $opts);
      }
      elseif ($slen > $len)
      { // Use the simple truncation method instead.
        $str = self::tr($str, $len, $offset, $append);
      }
      return str_pad($str, $len, $pad, $align);
    }
  }

}

