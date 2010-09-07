<?php

/**
 * This is a workalike for the Canvas class as developed in the PEAR
 * project. As of the writing of this project, the PEAR canvas has yet
 * to be released, therefore this workalike has been implemented. It
 * should be replaced by the 'real thing' once available.
 *
 * It should be noted that this class only implements the parts of the
 * PEAR canvas that the histogram class below uses, and that there is
 * positively _no_ error handling.
 */

class GDCanvas
{

    var $color;
    var $color_arr;
    
    function GDCanvas ($param) 
    {
        $w = $param['width'];
        $h = $param['height'];
        
        $this->img = imagecreatetruecolor ($w, $h);
        
        $this->width = $w;
        $this->height = $h;
        
        $this->setColor ('black');
        $this->setFillColor (null);
        
        $this->font = array ();
        $this->setFont (array ('angle'=> 0, 'size' => 10));
        
        if( function_exists('imageantialias')) {
            imageantialias($this->img, true);
        } else {
            $this->fake_aa = true;
        }
        
    }

    function setColor ($c) 
    {
        $this->color = $this->parseColor ($c);
        $this->color_arr = $c;
        
    }

    function setFillColor ($c) 
    {
        $this->fillColor = $this->parseColor ($c);
    }

    function parseColor ($c) 
    {
        if (!$c) 
            {
                return null;
            }

        if (is_array ($c)) 
            {
                if (count ($c) >= 4)
                    return imagecolorallocatealpha ($this->img, $c[0], $c[1], $c[2], $c[3]);
                else
                    return imagecolorallocate ($this->img, $c[0], $c[1], $c[2]);
            }	  

        switch ($c) 
            {

            case 'black':
                return imagecolorallocate ($this->img, 0, 0, 0);
                break;

            case 'white':
                return imagecolorallocate ($this->img, 255,255,255);
                break;

            case 'gray':
                return imagecolorallocate ($this->img, 192,192,192);
                break;
	  
            case 'blue':
                return imagecolorallocate ($this->img, 0, 0, 255);
                break;

            case 'red':
                return imagecolorallocate ($this->img, 255, 0, 0);
                break;

            case 'green':
                return imagecolorallocate ($this->img, 0, 255, 0);
                break;

            }
    }

    function line ($param) 
    {
        $x0 = $param["x0"];
        $x1 = $param["x1"];
        $y0 = $param["y0"];
        $y1 = $param["y1"];
        
        
        $dx = abs($x0-$x1);
        $dy = abs($y0-$y1);

            

        if (isset ($param['color'])) {
            $this->setColor( $param['color'] );
        }

        if ($this->fake_aa && $dx != 0 && $dy != 0) {
            
            $factor = max($dx, $dy)/min($dx,$dy);
            
            $step = 1;
            
            if ($factor > 5) {
                $step = 2;
            }

            $oc = $this->color_arr;
            
            $c2 = $oc;
            $c2[0] = 255-((255-$c2[0])/2.5);
            $c2[1] = 255-((255-$c2[1])/2.5);
            $c2[2] = 255-((255-$c2[2])/2.5);
            
            $this->setColor( $c2 );
            
            if ($dx > $dy) {
                imageline($this->img, $param["x0"]+$step, $param["y0"], $param["x1"]+$step, $param["y1"], $this->color);
                imageline($this->img, $param["x0"]-$step, $param["y0"], $param["x1"]-$step, $param["y1"], $this->color);
            } else {
                imageline($this->img, $param["x0"], $param["y0"]+$step, $param["x1"], $param["y1"]+$step, $this->color);
                imageline($this->img, $param["x0"], $param["y0"]-$step, $param["x1"], $param["y1"]-$step, $this->color);
            }
            
            $this->setColor($oc);
        }
        
        imageline( $this->img, $param["x0"], $param["y0"], $param["x1"], $param["y1"], $this->color );        

    }

    function rectangle ($param) 
    {
        if (isset ($param['color'])) 
            {
                $this->setColor( $param['color'] );
            }

        if (isset ($param['fill'])) 
            {
                $this->setFillColor( $param['fill'] );
            }
      
        $x0 = min( $param["x0"], $param["x1"] );
        $x1 = max( $param["x0"], $param["x1"] );
        $y0 = min( $param["y0"], $param["y1"] );
        $y1 = max( $param["y0"], $param["y1"] );

        if( $x0 == $x1 || $y0 == $y1 )
            return;
        


        if ($this->fillColor) 
            {


                imagefilledrectangle( $this->img, $x0, $y0, $x1, $y1, $this->fillColor );

            }
      
        imagerectangle( $this->img,
                        $x0,
                        $y0,
                        $x1,
                        $y1,
                        $this->color );
      
    }

    function addText ($param) 
    {

        $this->setFont( $param );
    
        if (isset ($param['color'])) 
            {
                $this->setColor ($param['color']);
            }

        $x = $param["x"];
        $y = $param["y"];
    
        imagettftext( $this->img, 
                      $this->font['size'],
                      $this->font['angle'],
                      $x,
                      $y,
                      $this->color,
                      $this->font['name'],
                      $param['text'] );
    }

    function textWidth($text)
    {
        $bbox = imagettfbbox($this->font['size'],
                             $this->font['angle'],
                             $this->font['name'],
                             $text);
        return max($bbox[2], $bbox[4]) - min($bbox[0],$bbox[6]);
    }
  

    function setFont ($param) 
    {
        foreach( array ('name', 'size', 'angle') as $i )
	if (isSet ($param[$i]))
            $this->font[$i] = $param[$i];
    }

    function show () 
    {
        imagepng ($this->img);
    }

}

class Image_Canvas 
{

    function factory ($t, $param) 
    {
        return new GDCanvas ($param);
    }

}

?>