<?php

namespace Lum\Text;

/**
 * Let's draw some ASCII tables.
 *
 * Uses the Box library for the line drawing characters.
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

  protected $box;

  protected $columns = [];

  public function __construct ($opts=[])
  {
    $optfilter = ['columns', 'box'];
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
    if (isset($opts['box']) && $opts['box'] instanceof Box)
    { // A Box, or subclass of Box.
      $this->box = $opts['box'];
    }
    else
    { // Make a default Box, passing our options to it.
      $this->box = new Box($opts);
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
      $l = $this->box->get('hh');
      $s = $this->box->get('tl');
      $m = $this->box->get('tc');
      $f = $this->box->get('tr');
    }
    elseif ($pos == self::POS_LAST)
    {
      $l = $this->box->get('hh');
      $s = $this->box->get('bl');
      $m = $this->box->get('bc');
      $f = $this->box->get('br');
    }
    else
    {
      $l = $this->box->get('hl'); 
      $s = $this->box->get('ll'); 
      $m = $this->box->get('ml'); 
      $f = $this->box->get('rl'); 
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

    $l = $this->box->get('vl'); 
    $h = $this->box->get('vh'); 

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

  public function get ($text, $page=1, $opts=[])
  {
    $start = ($this->length * ($page - 1));
    if (!is_array($opts))
    { // Boolean opts is the addTrunc parameter. String is 'trunc'.
      $opts = ['append'=>$opts];
    }

    $opts['offset'] = $start;

    if (!isset($opts['append']))
    { // Wasn't specified, use the default.
      $opts['append'] = $this->table->trunc;
    }
    elseif (is_bool($opts['append']))
    { // It was boolean, use the addTrunc logic.
      $opts['append'] = $opts['append'] ? $this->table->trunc : '';
    }

    if (!isset($opts['align']))
    {
      $opts['align'] = $this->align;
    }
    if (!isset($opts['pad']))
    {
      $opts['pad'] = $this->table->pad;
    }

    return Util::pad($text, $this->length, $opts);
  }
}
