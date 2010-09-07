<?php
require_once 'ImageCanvas.php';


/**
 * Regular graph axis
 */
define('GRAPH_REGULAR', 0);

/**
 * Graph axis contains timestamp information
 */
define('GRAPH_DATE', 1);

/**
 * A base class containing common functions for drawing various kinds
 * of nicely formated 2D graphs. This class contains methods for
 * drawing legends, axis, ticks, color handling and various other code
 * common to all types of graphs.
 *
 * As of yet, this class is rather incomplete. There are various
 * combinations of axis modes which don't work together.
 */

class Graph
{

    /**
     * The canvas object
     */
    var $canvas=false;
    
    /**
     * The color mapping 
     */
    var $color_map=array();

    /**
     * Width of graph image
     */
    var $width=0;
    /**
     * Height of graph image
     */
    var $height=0;
    
    /**
     This can be set to GRAPH_DATE to denote that the X axis contains dates
    */
    var $xAxisType=GRAPH_REGULAR;
    
    /**
     * List of parameters that may be set using the setPArams method
     */
    var $params = array('width','height','color_map','major_mark','legend');

    /**
     * Internal storage for legend data
     */
    var $legend=false;
    var $_ledend_width = 0;
    var $major_mark = false;
    
    var $item_count=0;
    
    var $histogram_desc=array();
    var $histogram_val=array();
    var $histogram_idx=array();

    var $plot_val=array();
    var $plot_metadata=array();
    

    var $x_ticks=false;
    var $y_ticks=false;
    var $x_ticks_pos=false;
    var $y_ticks_pos=false;
    
    var $x_origo=false;
    var $y_origo=true;
    
    var $default;

    var $text=array();

    /**
     * Constructor for the histogram class
     * 
     * \param $type the image type, as accepted by Image_Canvas::factory()
     * \param $param the parameter array, as accepted by setParams()
     */
    
    function Graph($type, $params=array()) 
    {

	$this->type = $type;

	$this->setParams($params);

	$this->x_margin = 20;
	$this->y_margin = 30;

        /*
         Set default colors
        */

        $this->setDefaults();
        
    }
    

    function setDefaults()
    {
        /*
         Default color values
        */
	$this->setDefault("mark_color", array(192, 192, 192));
	$this->setDefault("major_mark_color", array(128, 128, 128));
	$this->setDefault("axis_color", array( 64,  64,  64));
	$this->setDefault("bg_color", array(255, 255, 255));
        $this->setDefault("text_color", array(64, 64, 64));
	$this->setDefault("legend_color", array(128, 128, 128));
	$this->setDefault("text_color_weekend", array(192, 64, 64));
        $this->setDefault("bar_outline_color", array(128, 128, 128));

        // The relative amount of space around histogram bars
        $this->setDefault("histogram_margin", 0.15);
        
        /*
         The default size of the color box in the legend (w/h)
        */
        $this->setDefault("legend_box_size", array(30,30));
        
        /*
         The default margins around the legend (t,r,b,l). 
        */
        $this->setDefault("legend_margin", array(0,0,5,10));
        
        /*
         Distance between legend box and legend text
        */
        $this->setDefault("legend_internal_margin", 5);
        
        /*
         Target number of tick marks in graphs
        */
        $this->setDefault("y_mark_count",16);
        $this->setDefault("x_mark_count",16);

        /*
         The relative amount of empty space at the top and bottom of the graph
        */
        $this->setDefault("graph_margin_y",1/8);

        /*
         Defaut font size
        */
        $this->setDefault("font_size",10);

        /*
         The amount of whitespace between the ticks and the diagram axis
        */
        $this->setDefault("y_label_margin",4);

        $this->setDefault("x_label_step", $this->getDefault("font_size")*2.4);
        
    }

    /**
     *
     * Set parameters for the histogram
     *
     * Currently accepted keys are 'width', 'height' and 'color_map'
     */  
    function setParams($params) 
    {
	foreach ($params as $key => $value) {
	    if (array_search($key, $this->params) !== false) {
		$this->$key = $value;
	    } else {
		echo "Invalid param $key<br>";
	    }

	}
    }

    /**
     * Set the legend data for this graph
     */
    function setLegend($legend)
    {
        $this->setParams(array("legend"=>$legend));
    }
        
    /**
     Set the type of data on the X axis
    */
    function setXAxisType($type, $date_format=null) 
    {
	$this->xAxisType = $type;
        $this->date_format = $date_format;
        
    }


    /**
     * Converts a coordinate from relative coordinates (0..1) to a x
     * screen coordinate
     */

    function _x2s($i)
    {
	return floor($i *($this->width-1-$this->x_margin-$this->_legend_width)) + $this->x_margin;
    }

    /**
     * Converts a coordinate from relative coordinates (0..1) to a y
     * screen coordinate
     */
    function _y2s($i) 
    {
	return floor((1.0-$i) * ($this->height-1-$this->y_margin-$this->getTopMargin()))+$this->getTopMargin();
    }

    /**
     * Return the number of pixels of empty space at the top
     */
    function getTopMargin()
    {
        return 5;
        
    }
       
    /**
     * Returns the top margin (in pixels)
     */
    function getXTickYPos() 
    {
	return $this->height -2;
    }

    /**
     * Returns the left side margins (in pixels)
     */
    function getYTickXPos()
    {
	return 3;
    }

    /**
     * Write out the legend
     */
    function _writeLegend()
    {

	$legend_color = $this->getDefault("legend_color");
	$bar_outline_color = $this->getDefault("bar_outline_color");
	$text_color = $this->getDefault("text_color");
        $font_size = $this->getDefault("font_size");
        
        if (!$this->legend) {
            $this->_legend_width = 0;
            return;
        }
        
        $max = 0;
        
        $margin = $this->getDefault("legend_margin");
        
        $box = $this->getDefault("legend_box_size");

        $internal = $this->getDefault("legend_internal_margin");
        
        
        $box_width = $box[0];
        $box_step = min($box[1]+$margin[0]+$margin[2], (float)($this->height-$margin[0]-$margin[2])/(count($this->legend)));

        //        echo "wisth $box_width<br>";
        //echo "height $box_height";
        


        for ($i=0; $i<count($this->legend); $i++) {
            $item = $this->legend[$i];
            
            $max = max($max, $this->canvas->textWidth($item));
        }
        
        //        $max += $margin[1] + $margin[3];
        
        $this->_legend_width = $max + $margin[1] + $margin[3] + $box_width + $internal;
        
        for ($i=0; $i<count($this->legend); $i++) {
            $item = $this->legend[$i];

            $y_pos = count($this->legend) - 1 -$i;
            
            $y0 = ($y_pos)*$box_step+$margin[0];
            $y1 = ($y_pos+1)*$box_step-$margin[2];
            
            $x0 = $this->width - $this->_legend_width + $margin[3];
            $x1 = $x0 + $box_width;

            $this->canvas->rectangle(array('x0' => $x0,
                                           'y0' => $y0,
                                           'x1' => $x1,
                                           'y1' => $y1,
                                           'color' => $bar_outline_color,
                                           'fill' => $this->color_map[$i] ) );
             
            $this->canvas->addText(array('y' => ($y0+$y1)/2 +$font_size/2, 
                                         'x' => $x1 + $internal, 
                                         'color' => $text_color,
                                         'text' => $item ));
       }
        
    }

    /**
     * Fill in the color map with enough colors to draw the graph
     *
     * @param col_count The number of colors needed 
     */
    function calcColorMap($col_count)
    {
        
        $factor = array(1.0, 0.6);
        $col = array(array(1,0,0), 
                     array(0,1,0),
                     array (0,0,1),
                     array(0,1,1),
                     array(1,0,1),
                     array(0.85,0.85,0),
                     array(0.01,0,0),
                     array(1,0.5,0),
                     array(1,0,0.5),
                     array(0,1,0.5),
                     array(0,0.5,1),
                     array(0.5,1,0),
                     array(0.5,0,1),
                     array(1,1,0.5),
                     array(1,0.5,1),
                     array(0.5,1,1),
            );
        
        if (count($this->color_map) < $col_count) {
            foreach( $factor as $f) {
                foreach ($col as $c) {
                    $this->color_map[] = array(255.0*$c[0]*$f, 255.0*$c[1]*$f, 255.0*$c[2]*$f);
                }
            }
        }
        /*
         Ouch, we-re out of colors. Add random colors...
        */
        while (count($this->color_map) < $col_count) {
            $this->color_map[] = array( rand()%255, rand()%255, rand()%255 );
        }
        
    }
    
    /**
     * Calculate the smallest resonable step length that is larger
     * than the specified step length. Step length is always smaller
     * than double the minimum step lenth. Steps of less than 1 are
     * not yet handled.
     */
    function _calcStep($min_step)
    {
        $multiplier = 1;
        while (true) {
            if ($min_step <= 1) {
                return $multiplier;
            } else if ($min_step <= 2) {
                return 2 * $multiplier;
            } else if ($min_step <= 4) {
                return 4 * $multiplier;
            } else if ($min_step <= 5) {
                return 5 * $multiplier;
            }
            
            $multiplier *= 10;
            $min_step /= 10;
            
        }
    }

    /**
     * Set the color for the specified graph part
     */
    function setDefault($name, $val)
    {
        $this->default[$name] = $val;
    }
    
    /**
     * Get the color for the specified graph path
     */
    function getDefault($name)
    {
        return $this->default[$name];
    }
    
    /**
     * Create a canvas object and prepare it for drawing by clearing
     * it, seting up a nice default font, etc..
     */  
    function createCanvas() 
    {
        
	/*
	 * Colors for various parts of the histogram
	 */

	$bg_color = $this->getDefault("bg_color");

	$font_size = $this->getDefault("font_size");
	  
	$this->canvas = Image_Canvas::factory( $this->type, array("width"=>$this->width,
                                                                  "height"=>$this->height));
        
        
	$fontName = './static/fonts/histogram.ttf';
	$this->canvas->setFont(array('name' => $fontName,
                                     'size' => 10));

	$this->canvas->rectangle(array('x0' => 0,
                                       'y0' => 0,
                                       'x1' => $this->width,
                                       'y1' => $this->height,
                                       'color' => $bg_color,
                                       'fill' => $bg_color));

	$this->canvas->setFont(array('size' => $font_size));

    }

    function formatNumber($n)
    {
        $l = strlen($n);
        
        if ($l<3) {
            return $n;
        }
        $s1 = substr($n, 0, $l-3);
        $s2 = substr($n, $l-3, 3);
        return $this->formatNumber($s1)." ".$s2;
        
        
    }
    

    /**
     * Write out the axis, the ticks, support lines, etc.
     */
    function writeTicks($param)
    {

        $mark_color = $this->getDefault("mark_color");
	$text_color = $this->getDefault("text_color");
	$text_color_weekend = $this->getDefault("text_color_weekend");

	// The desired maximum number of horizontal grey lines 
	$y_mark_count = $this->getDefault("y_mark_count");
	$x_mark_count = $this->getDefault("x_mark_count");

        $x_label_step = $this->getDefault("x_label_step");
        
	$y_label_margin = $this->getDefault("y_label_margin");

        $y_min = $param['y_min'];
        $y_max = $param['y_max'];
        
        $x_min = $param['x_min'];
        $x_max = $param['x_max'];
        
        $y_mark_step = $this->_calcStep(($y_max-$y_min) / $y_mark_count);
        $x_mark_step = $this->_calcStep(($x_max-$x_min) / $x_mark_count);

        $y_label_step = $y_mark_step*2;

        

        $this->x_margin = 0;

        if (isset($this->y_unit)) {
            $this->x_margin = max ($this->x_margin, $this->canvas->textWidth("(1/)".$this->y_unit)+2);
        }
            
        //        echo $this->x_margin;// = max ($this->x_margin, strlen(($this->y_unit)+4)*9);
        //(strlen($this->y_unit)+4)*9;
        


        if( isSet($param['y_ticks'])) {
            foreach( $param['y_ticks'] as $str)
            {
                $w = $this->canvas->textWidth($str)+$y_label_margin;
                $this->x_margin = max ($this->x_margin, $w);
            }
            
        } else {
            for ($i = $y_min; $i < ($y_max-$y_label_step/2); $i+= $y_label_step) {
                $txt = $this->formatNumber($i);
                $w = $this->canvas->textWidth($txt)+$y_label_margin;
                $this->x_margin = max ($this->x_margin, $w);
            }
            
        }
        
        


        //echo $mark_step ."<br>";
        //$mark_step = $this->_calcStep($mark_step);
        //echo $mark_step;
        
        $this->canvas->setColor($mark_color);
        
        for ($i=$y_min+$y_mark_step; $i<= $y_max; $i+= $y_mark_step) {
            
            $y = $this->_y2s(((float)$i-$y_min)/($y_max-$y_min));
            
            $this->canvas->line(array('x0' => $this->_x2s(0),
                                      'y0' => $y,
                                      'x1' => $this->_x2s(1),
                                      'y1' => $y ) );
        }
        
        $prev_label_pos = -1000;

        if (isSet($param['x_ticks']))
        {
            $this->canvas->setFont(array('angle' => 23));
            $val = $param['x_ticks'];
            if (isSet($param['x_ticks_pos'])) {
                $pos_arr = $param['x_ticks_pos'];
            } else {
                $pos_arr = range(0, count($val)-1);
            }
            
            for ($i = 0; $i < count($val); $i++) {
                
                $pos = $pos_arr[$i];
                $it = $val[$i];
                
                $x = $this->_x2s(($pos-$x_min)/($x_max-$x_min)); 
                
                if ($prev_label_pos + $x_label_step < $x) {
                    
                    if ($this->xAxisType == GRAPH_DATE) {
                        $color = $text_color;
                        $day = date('w', $it);
                        if ($day == 0 || $day == 6) {
                            $color = $text_color_weekend;
                        }
                        $txt = date($this->date_format, $it);
                    } else {	
                        $color = $text_color;
                        $txt = $it;
                    }
                    
                    $this->canvas->addText(array('x' =>$x - $this->canvas->textWidth($txt)/2, 
                                                 'y' => $this->getXTickYPos(),
                                                 'color' => $color,
                                                 'text' => $txt) );
                    
                    $prev_label_pos = $x;
                    
                }
                
            }
            
        }
        else
        {
            $this->canvas->setFont(array('angle' => 0));
            
            for ($i = $x_min; $i < $x_max; $i+= $x_mark_step) {

                if ($this->xAxisType == GRAPH_DATE) {
                    $color = $text_color;
                    $day = date('w', $i);
                    if ($day == 0 || $day == 6) {
                        $color = $text_color_weekend;
                    }
                    $txt = date($this->date_format, $i);
                }
                else
                {
                    $color = $text_color;
                    $txt = $this->formatNumber($i);
                }
                
                $this->canvas->addText(array('y' => $this->getXTickYPos(),
                                             'x' => $this->_x2s(($i-$x_min)/($x_max-$x_min)), 
                                             'color' => $color,
                                             'text' => $txt ));
            }
        }

        $this->canvas->setFont(array('angle' => 0));        

        if (isSet( $this->x_unit)) {
            $this->canvas->addText(array('y' => $this->getXTickYPos(),
                                         'x' => $this->_x2s(1)+10, 
                                         'color' => $text_color,
                                         'text' => "(1/{$this->x_unit})" ));
        }
        
        

        if( !isSet($param['y_ticks']))
        {
            for ($i = $y_min; $i < ($y_max-$y_label_step/2); $i+= $y_label_step) {
                $txt = $this->formatNumber($i);
                $w = $this->canvas->textWidth($txt);
                
                $this->canvas->addText(array('y' => $this->_y2s(($i-$y_min)/($y_max-$y_min) )+5, 
                                             'x' => max(0,$this->_x2s(0)-$w-$y_label_margin), 
                                             'color' => $text_color,
                                             'text' => $txt ));
            }
        }

        if (isSet( $this->y_unit)) {
            $txt = "(1/{$this->y_unit})";
            $w = $this->canvas->textWidth($txt);

            $this->canvas->addText(array('x' => max(0,$this->_x2s(0)-$w-$y_label_margin), 
                                         'y' => $this->_y2s(1)+10, 
                                         'color' => $text_color,
                                         'text' => $txt ));
            
        }



    }
    

    function writeAxis($param)
    {
        $y_min = $param['y_min'];
        $y_max = $param['y_max'];
        
        $x_min = $param['x_min'];
        $x_max = $param['x_max'];

        $major_mark_color = $this->getDefault("major_mark_color");
	$axis_color = $this->getDefault("text_color");
                
        $this->canvas->line(array('x0' => $this->_x2s(0),
                                  'y0' => $this->_y2s(0),
                                  'x1' => $this->_x2s(1),
                                  'y1' => $this->_y2s(0),
                                  'color' => $axis_color));

        $this->canvas->line(array('x0' => $this->_x2s(0),
                                  'y0' => $this->_y2s(0),
                                  'x1' => $this->_x2s(0),
                                  'y1' => $this->_y2s(1),
                                  'color' => $axis_color));

        if (isSet($this->major_mark) && $this->major_mark !== false) {
            $this->canvas->setColor($major_mark_color);
        
            $y = $this->_y2s(((float)$this->major_mark-$y_min)/($y_max-$y_min));
            
            $this->canvas->line(array('x0' => $this->_x2s(0),
                                      'y0' => $y,
                                      'x1' => $this->_x2s(1),
                                      'y1' => $y ) );
        }
        
    }

    /**
     * Write out the generated graph to stdout
     */

    function writeImage()
    {
	header('Content-type: image/png');
	$this->canvas->show();
    }

    /**
     * Set the unit along the X axis
     */
    function setXUnit($unit)
    {
        $this->x_unit = $unit;
    }
    
    /**
     * Set the unit along the Y axis
     */
    function setYUnit($unit)
    {
        $this->y_unit = $unit;
    }
  

    /**
     * Add a new histogram bar. 
     *
     * \param $d the description to print at the bottom
     * \param $v array of bar segment heights at this point
     */

    function addHistogramPoint($d, $v) 
    {
        
	$this->histogram_desc[] = $d;
	if( !is_array($v)) 
	    {
		$v = array($v);
	    }
        

        while (count($this->histogram_idx) < count($v)) {
            $this->histogram_idx[] = $this->item_count++;
        }
        
        $v2 = array();
        
        for ($i=0; $i<count($v); $i++) {
            $v2[$i]->val = $v[$i];
            $v2[$i]->idx = $this->histogram_idx[$i];
        }

	$this->histogram_val[] = $v2;
    }
  

    /**
     * Add a data series to plot. If two vectors are given, they are
     * the X and Y data for the plot. Otherwise, the X data is assumed
     * to start from 0 and have a constant step length of 1.
     *
     * @param arr1 If arr2 is set, this is the X data, otherwise it is
     * the Y data
     *
     * @param If set, this is the Y data
     */
    function addPlot($arr1, $arr2 = null, $meta=array()) 
    {

        if ($arr2)
        {
            $v2->x_val = $arr1;
            $v2->y_val = $arr2;
        } else {
            $v2->x_val = range(0, count($arr1)-1);
            $v2->y_val = $arr1;
        }

        $v2->idx = $this->item_count++;
        $this->plot_val[] = $v2;
        $this->plot_metadata[] = $meta;
        
    }

    /**
     * Write a text entry somewhere in the graphing area
     */
    function addText($pos, $text, $prop=array())
    {
        $item->pos = $pos;
        $item->content = $text;
        $item->prop = $prop;
        
        $this->text[] = $item;
    }

    function get($arr, $key, $def=null) 
    {
        return ( isSet($arr[$key]) )?$arr[$key]:$def;
    }
    
    function writeText()
    {
        $bg_color = $this->getDefault("bg_color");
        $dy = $this->getDefault("font_size");

        foreach ($this->text as $t) {
            $x = $this->_x2s($t->pos['x']);
            $y = $this->_y2s($t->pos['y']);

            $dx = $this->canvas->textWidth($t->content);
            
            switch ($this->get($t->prop, "valign", "center") )
            {

            case 'bottom':
                break;

            case 'top':
                $y +=$dy;                
                break;

            case 'center':
                $y +=$dy/2;
                break;

            }
            
            switch ($this->get($t->prop, "halign", "center") )
            {

            case 'left':
                break;
                
            case 'right':
                $x -=$dx;                
                break;
                
            case 'center':
                $x -=$dx/2;
                break;
            }
            
            $this->canvas->rectangle(array('x0' => $x-2,
                                           'x1' => $x+$dx+1,
                                           'y0' => $y,
                                           'y1' => $y-$dy,
                                           'color'=>$bg_color,
                                           'fill'=>$bg_color));
            

                                     
        }
        foreach ($this->text as $t) {
            $x = $this->_x2s($t->pos['x']);
            $y = $this->_y2s($t->pos['y']);

            $dx = $this->canvas->textWidth($t->content);
            
            switch ($this->get($t->prop, "valign", "center") )
            {

            case 'bottom':
                break;

            case 'top':
                $y +=$dy;                
                break;

            case 'center':
                $y +=$dy/2;
                break;

            }
            
            switch ($this->get($t->prop, "halign", "center") )
            {

            case 'left':
                break;

            case 'right':
                $x -=$dx;                
                break;
                
            case 'center':
                $x -=$dx/2;
                break;
            }
            
            $text_color = $this->getDefault("text_color");
            $text_color = $this->get( $t->prop, "text_color", $text_color );
            
            $this->canvas->addText(array('y' => $y,
                                         'x' => $x,
                                         'color' => $text_color,
                                         'text' => $t->content ));

                                     
        }
    }
    

    /**
     * Write the histogram image to stdout. This function does what
     * little heavy ligting is left after the Graph class does most of
     * the work..
     */  
    function write() 
    {
	  
	$bar_outline_color = $this->getDefault("bar_outline_color");
        $histogram_margin = $this->getDefault("histogram_margin")*0.5;

        $this->createCanvas();
        
	if( count( $this->histogram_val ) || count($this->plot_val)) {

            
            /*
             Calculate range
            */

            /*
             workaround, if PHP_INT_MAX is undefined, set it to some pretty large value and hope for the best...             
            */
            if (!defined('PHP_INT_MAX'))
                define('PHP_INT_MAX',2000000000);

            $x_min = PHP_INT_MAX;
            $x_max = -PHP_INT_MAX;
            $y_min = PHP_INT_MAX;
            $y_max = -PHP_INT_MAX;
            //var_dump($this->histogram_val);
            
	    foreach ($this->histogram_val as $i) {
                $y_min = 0;
		$sum = 0;
		foreach ($i as $j) {
		    $sum += $j->val;
		}

		$y_max = ( $sum > $y_max )? $sum : $y_max;

	    }

	    for ($i=0; $i < count($this->plot_val); $i++) {
                $y_val = $this->plot_val[$i]->y_val;
                $x_val = $this->plot_val[$i]->x_val;
                
                for ($j=0; $j < count($y_val); $j++) {
                    $y = $y_val[$j];
                    $x = $x_val[$j];
                    
                    $x_min = min($x_min, $x);
                    $x_max = max($x_max, $x);
                    $y_min = min($y_min, $y);
                    $y_max = max($y_max, $y);
		}
	    }

            /* 
             Add a bit of top side margin in the graph so that the
             plot doesn't look to cramped.
            */
            $graph_margin_y = $this->getDefault("graph_margin_y");
            if ($y_max != 0) {
                $y_max += ($y_max-$y_min)*$graph_margin_y;
            }
            
            /*
             Do the same at the bottom 
            */
            if ($y_min != 0) {
                $y_min -= ($y_max-$y_min)*$graph_margin_y;
            }
            
            if ($this->x_origo)
            {
                $x_min = min ( $x_min, 0 );
            }
            
            if ($this->y_origo)
            {
                $y_min = min ( $y_min, 0 );
            }


            /*
              If no color scheme was specified, set up some decent defaults
            */
            $this->calcColorMap($this->item_count);

            /*
             Write out the legend, if one exists
            */
            $this->_writeLegend();


            /*
             Write out the axis.
            */
            $param = array('y_max' => $y_max,
                           'y_min' => $y_min);
            
            if (count($this->histogram_desc)) {
                $param['x_ticks'] = $this->histogram_desc;
                $param ['x_min'] = -0.5;
                $param['x_max'] = count($this->histogram_val)-0.5;
            } else {
                $param['x_min'] = $x_min;
                $param['x_max'] = $x_max;    
                if ($this->x_ticks) {
                    $param['x_ticks'] = $this->x_ticks;
                    $param['x_ticks_pos'] = $this->x_ticks_pos;
                }

            }
            
            if ($this->y_ticks) {
                $param['y_ticks'] = $this->y_ticks;
                $param['y_ticks_pos'] = $this->y_ticks_pos;
            }
            
            $this->writeTicks($param);

            /*
             Add any histogram bars
            */
	    for ($i = 0; $i < count( $this->histogram_val ); $i++) {

		$x0 = $this->_x2s(($i+$histogram_margin)/count($this->histogram_val) ); 
		$x1 = $this->_x2s(($i+(1-$histogram_margin))/count($this->histogram_val) ); 
		$it = $this->histogram_val[$i];
	    
		$y0 = 0;
		$y1 = 0;

		for ($j = 0; $j < count( $it ); $j++) {
                    
		    $y0 = $y1;
		    $y1+= $it[$j]->val/$y_max;
		    $this->canvas->rectangle(array('x0' => $x0,
                                                   'y0' => $this->_y2s($y0),
                                                   'x1' => $x1,
                                                   'y1' => $this->_y2s($y1),
                                                   'color' => $bar_outline_color,
                                                   'fill' => $this->color_map[$it[$j]->idx] ) );
		}

	    }
            
            /*
             Plot the data
            */
	    for ($i=0; $i < count($this->plot_val); $i++) {
                
                $y_val = $this->plot_val[$i]->y_val;
                $x_val = $this->plot_val[$i]->x_val;
                
                $j = 0;
                
                $y = $y_val[$j];
                $x = $x_val[$j];
                
                $y1 = ($y-$y_min)/($y_max-$y_min);
                $x1 = ($x-$x_min)/($x_max-$x_min);

                $color = $this->color_map[$this->plot_val[$i]->idx];

                $this->write_plot_mark($this->plot_metadata[$i], $x1, $y1, $color);
                
                for ($j=1; $j < count($y_val); $j++) {
                    $y = $y_val[$j];
                    $x = $x_val[$j];
                    
                    
		    $y0 = $y1;
		    $y1 = ($y-$y_min)/($y_max-$y_min);
                    $x0 = $x1;
                    $x1 = ($x-$x_min)/($x_max-$x_min);
                                    
                    
		    $this->canvas->line(array('x0' => $this->_x2s($x0),
                                              'y0' => $this->_y2s($y0),
                                              'x1' => $this->_x2s($x1),
                                              'y1' => $this->_y2s($y1),
                                              'color' => $color) );
                    
                    $this->write_plot_mark($this->plot_metadata[$i], $x1, $y1, $color);

		}

	    }
            
            $this->writeText();
                        
            $this->writeAxis($param);

        }

        /*
         Write out the graphics
        */
        $this->writeImage();
        

    }

    function write_plot_mark($opt, $x1, $y1, $color)
    {
        
        if (isSet($opt['cross'])) {
            
            $this->canvas->line(array('x0' => $this->_x2s($x1)-3,
                                      'y0' => $this->_y2s($y1)-3,
                                      'x1' => $this->_x2s($x1)+3,
                                      'y1' => $this->_y2s($y1)+3,
                                      'color' => $color) );
            
            $this->canvas->line(array('x0' => $this->_x2s($x1)-3,
                                      'y0' => $this->_y2s($y1)+3,
                                      'x1' => $this->_x2s($x1)+3,
                                      'y1' => $this->_y2s($y1)-3,
                                      'color' => $color) );
            
        }
                    
    }
    

    /** 
     * Sets wheter the Plot should begin at Origo even if the smallest
     * value is positive
     *
     * @param $x show Origo along X axis
     * @param $x show Origo along Y axis
    */
    function showOrigo($x, $y)
    {
        $this->x_origo = $x;
        $this->y_origo = $y;
    }

    /**
     * Hardcode the specified X ticks
     */
    function setXTicks($pos, $ticks)
    {
        $this->x_ticks = $ticks;
        $this->x_ticks_pos = $pos;
    }
    
    /**
     * Hardcode the specified Y ticks
     */    
    function setYTicks($pos, $ticks)
    {
        $this->y_ticks = $ticks;
        $this->y_ticks_pos = $pos;
    }
    


}

?>