<?php

class preinscripcion_PDF_nucleo extends Cezpdf
{
	function getY()
	{
		return $this->y;
	}
    
	function decode($cadena)
	{
		$juegocaractercode = 'UTF-8';
		$estilocomillas = ENT_COMPAT;

		return html_entity_decode($cadena, $estilocomillas, $juegocaractercode);
	}
    
	function addText($x, $y, $size, $text, $width = 0, $justification = 'left', $angle = 0, $wordSpaceAdjust = 0, $test = 0)
	{
		$text = $this->decode($text);
		return parent::addText($x, $y, $size, $text, $width = 0, $justification = 'left', $angle = 0, $wordSpaceAdjust = 0, $test = 0);
	}
    
    function ezText($text, $size=0, $options=array(), $test=0) 
    {
        if (comunes::es_interfaz_gestion()) {
            $text = utf8_encode($text); // or use mb_convert_encoding(), according to your needs
        }
        return parent::ezText($text, $size, $options, $test);
    }

	function stream($options = '')
	{
		// setting the options allows the adjustment of the headers
		// values at the moment are:
		// 'Content-Disposition'=>'filename'  - sets the filename, though not too sure how well this will 
		//        work as in my trial the browser seems to use the filename of the php file with .pdf on the end
		// 'Accept-Ranges'=>1 or 0 - if this is not set to 1, then this header is not included, off by default
		//    this header seems to have caused some problems despite tha fact that it is supposed to solve
		//    them, so I am leaving it off by default.
		// 'compress'=> 1 or 0 - apply content stream compression, this is on (1) by default
		if (!is_array($options)) {
			$options = array();
		}
		if (isset($options['compress']) && $options['compress'] == 0) {
			$tmp = $this->output(1);
		} else {
			$tmp = $this->output();
		}
		header('Content-type: application/octet-stream');
		header("Content-Length: " . strlen(ltrim($tmp)));
		$fileName = (isset($options['Content-Disposition']) ? $options['Content-Disposition'] : 'salida.pdf');
		header("Content-Disposition: attachment; filename=" . $fileName);
		if (isset($options['Accept-Ranges']) && $options['Accept-Ranges'] == 1) {
			header("Accept-Ranges: " . strlen(ltrim($tmp)));
		}
		header("Content-Description: file");

		echo ltrim($tmp);
	}

    function PRVT_getBytes(&$data,$pos,$num)
    {
        // return the integer represented by $num bytes from $pos within $data
        $ret=0;
        for ($i=0;$i<$num;$i++){
          $ret=$ret*256;
          $ret+=ord($data[$pos+$i]);
        }
        return $ret;
    }
}
?>