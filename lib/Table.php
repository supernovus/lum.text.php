<?php

namespace Lum\Text;

/**
 * Let's draw some ASCII tables!
 */
class Table
{
  const ALIGN_LEFT   = STR_PAD_RIGHT;
  const ALIGN_RIGHT  = STR_PAD_LEFT;
  const ALIGN_CENTER = STR_PAD_BOTH;

  const POS_FIRST  = -1;
  const POS_MIDDLE = 0;
  const POS_LAST   = 1;

  const LINE_BEFORE = 1;
  const LINE_AFTER  = 2;

  public $trunc   = '~';
  public $pad     = ' ';
  public $term    = "\n";
  public $addline = 0;

  public $headerColor = Colors::LIGHT_WHITE; 
  public $normalColor = Colors::NORMAL;

  public $multiline = false;

  protected $tl = '2554';
  protected $tr = '2557';
  protected $tc = '2564';

  protected $bl = '255A';
  protected $br = '255D';
  protected $bc = '2567';

  protected $ll = '255F';
  protected $rl = '2562';
  protected $ml = '253C';

  protected $hh = '2550';
  protected $hl = '2500';
  protected $vl = '2502';
  protected $vh = '2551';

  protected $columns = [];

  static function huc ($code)
  {
    return html_entity_decode("&#x$code;", ENT_NOQUOTES, 'UTF-8');
  }

  public function __construct ($opts=[])
  {
    $optfilter = ['columns'];
    $optmap = array_keys(get_object_vars($this));
    foreach ($optmap as $opt)
    {
      if (in_array($opt, $optfilter))
      { // Skip filtered options.
        continue;
      }
      if (isset($opts[$opt]))
      { // Redefining a property via the constructor.
        $this->$opt = $opts[$opt];
      }
    }
    if (isset($opts['columns']) && is_array($opts['columns']))
    {
      foreach ($opts['columns'] as $coldef)
      {
        if (is_array($coldef))
        {
          $this->addColumn($coldef);
        }
        elseif ($coldef instanceof Column)
        {
          $this->columns[] = $coldef;
        }
      }
    }
  }

  public function addColumn (Array $def)
  {
    $this->columns[] = new Column($this, $def);
  }

  public function addLine ($pos=self::POS_MIDDLE)
  {
    if ($pos === self::POS_FIRST)
    {
      $l = self::huc($this->hh);
      $s = self::huc($this->tl);
      $m = self::huc($this->tc);
      $f = self::huc($this->tr);
    }
    elseif ($pos == self::POS_LAST)
    {
      $l = self::huc($this->hh);
      $s = self::huc($this->bl);
      $m = self::huc($this->bc);
      $f = self::huc($this->br);
    }
    else
    {
      $l = self::huc($this->hl);
      $s = self::huc($this->ll);
      $m = self::huc($this->ml);
      $f = self::huc($this->rl);
    }
    
    $lc = count($this->columns)-1;
    $line = '';
    foreach ($this->columns as $c => $col)
    {
      if ($c == 0)
      { // First column.
        $line .= $s;
      }
      else
      { // Non-first columns.
        $line .= $m;
      }

      $line .= str_repeat($l, $col->length+2);

      if ($c == $lc)
      { // Last column.
        $line .= $f;
      }
    }

    return $line.$this->term;
  }

  public function addTop ()
  {
    return $this->addLine(self::POS_FIRST);
  }

  public function addBottom ()
  {
    return $this->addLine(self::POS_LAST);
  }

  public function addHeader (Array $def, $addTop=false, $addBottom=false)
  {
    $header = '';
    if ($addTop)
      $header .= $this->addTop();
    $opts =
    [
      'addline' => $addBottom ? self::LINE_AFTER : 0,
      'color'   => $this->headerColor,
    ];
    $header .= $this->addRow($def, $opts);
    return $header;
  }

  public function addRow (Array $def, $opts=[])
  {
    $addLine = isset($opts['addline']) ? $opts['addline'] : $this->addline;
    $color = isset($opts['color']) ? $opts['color'] : null;

    $cc = count($this->columns);
    $lc = $cc-1;
    if (count($def) != $cc)
    {
      throw new \Exception("Row doesn't have the right number of columns.");
    }

    if ($addLine & self::LINE_BEFORE)
      $row = $this->addLine();
    else
      $row = '';

    $l = self::huc($this->vl);
    $h = self::huc($this->vh);

    if ($this->multiline)
    { // First we need to figure out how many pages.
      $pageCount = 1;
      foreach ($this->columns as $c => $col)
      {
        $colCount = $col->pagecount($def[$c]);
        if ($colCount > $pageCount)
        {
          $pageCount = $colCount;
        }
      }
      // Okay, now for each page, let's draw a row line.
      for ($page=1; $page<=$pageCount; $page++)
      {
        $this->addRowLine($row, $def, $page, $color, $l, $h, $lc, false);
      }
    }
    else
    { // Single-line rows are easy.
      $this->addRowLine($row, $def, 1, $color, $l, $h, $lc, true);
    }

    if ($addLine & self::LINE_AFTER)
      $row .= $this->addLine();

    return $row;
  }

  protected function addRowLine (&$row, $def, $page, $color, $l, $h, $lc, $tr)
  {
    foreach ($this->columns as $c => $col)
    {
      if ($c == 0)
      { // First column.
        $row .= $h;
      }
      else
      { // Non-first columns.
        $row .= $l;
      }

      if (isset($color))
        $row .= $color;
      $row .= ' ' . $col->get($def[$c], $page, $tr) . ' ';
      if (isset($color))
        $row .= $this->normalColor;

      if ($c == $lc)
      { // Last column.
        $row .= $h;
      }
    }

    $row .= $this->term;
  }
}

class Column
{
  protected $table;
  public $length = 16;
  public $align  = Table::ALIGN_LEFT;

  static function pad ($str, $len, $start=0, $align=ALIGN_LEFT, $trunc='', $pad=' ')
  {
    $slen = strlen($str);
    if ($start >= $slen)
    {
      return str_pad('', $len, $pad, $align);
    }
    elseif ($slen > $len)
    {
      $tlen = $len - strlen($trunc);
      $sstr = substr($str, $start, $tlen).$trunc;
      return str_pad($sstr, $len, $pad, $align);
    }
    else
    {
      return str_pad($str, $len, $pad, $align);
    }
  }

  public function __construct (Table $table, $opts=[])
  {
    $this->table = $table;
    if (isset($opts['length']))
    {
      $this->length = $opts['length'];
    }
    if (isset($opts['align']))
    {
      $this->align = $opts['align'];
    }
  }

  public function pagecount ($text)
  {
    return ceil(strlen($text) / $this->length);
  }

  public function get ($text, $page=1, $addTrunc=true)
  {
    $start = ($this->length * ($page - 1));
    $trunc = $addTrunc ? $this->table->trunc : '';
    return self::pad($text, $this->length, $start, $this->align, $trunc, $this->table->pad);
  }
}
