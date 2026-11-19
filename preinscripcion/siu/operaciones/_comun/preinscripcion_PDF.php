<?php

namespace siu\operaciones\_comun;

use siu\bootstrap;
require_once bootstrap::get_dir_raiz() . '/vendor/rebuy/ezpdf/src/ezpdf/class.ezpdf.php';


class preinscripcion_PDF extends \Cezpdf
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

	function addText($x, $y, $size, $text, $angle = 0, $wordSpaceAdjust = 0)
	{
		$text = $this->decode($text);
		return parent::addText($x, $y, $size, $text, $angle, $wordSpaceAdjust);
	}

	function ezText($text, $size = 0, $options = array(), $test = 0)
	{
		$text = $this->decode($text);
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

	/**
	 * add a PNG image into the document, from a file
	 * this should work with remote files
	 */
	function addPngFromFile($file, $x, $y, $w = 0, $h = 0)
	{
		// read in a png file, interpret it, then add to the system
		$error = 0;

		$fp = @fopen($file, 'rb');
		if ($fp) {
			$data = '';
			while (!feof($fp)) {
				$data .= fread($fp, 1024);
			}
			fclose($fp);
		} else {
			$error = 1;
			$errormsg = 'trouble opening file: ' . $file;
		}

		if (!$error) {
			$header = chr(137) . chr(80) . chr(78) . chr(71) . chr(13) . chr(10) . chr(26) . chr(10);
			if (substr($data, 0, 8) != $header) {
				$error = 1;
				$errormsg = 'this file does not have a valid header';
			}
		}

		if (!$error) {
			// set pointer
			$p = 8;
			$len = strlen($data);
			// cycle through the file, identifying chunks
			$haveHeader = 0;
			$info = array();
			$idata = '';
			$pdata = '';
			while ($p < $len) {
				$chunkLen = $this->PRVT_getBytes($data, $p, 4);
				$chunkType = substr($data, $p + 4, 4);

				switch ($chunkType) {
					case 'IHDR':
						// this is where all the file information comes from
						$info['width'] = $this->PRVT_getBytes($data, $p + 8, 4);
						$info['height'] = $this->PRVT_getBytes($data, $p + 12, 4);
						$info['bitDepth'] = ord($data[$p + 16]);
						$info['colorType'] = ord($data[$p + 17]);
						$info['compressionMethod'] = ord($data[$p + 18]);
						$info['filterMethod'] = ord($data[$p + 19]);
						$info['interlaceMethod'] = ord($data[$p + 20]);

						$haveHeader = 1;
						if ($info['compressionMethod'] != 0) {
							$error = 1;
							$errormsg = 'unsupported compression method';
						}
						if ($info['filterMethod'] != 0) {
							$error = 1;
							$errormsg = 'unsupported filter method';
						}
						break;
					case 'PLTE':
						$pdata.=substr($data, $p + 8, $chunkLen);
						break;
					case 'IDAT':
						$idata.=substr($data, $p + 8, $chunkLen);
						break;
					case 'tRNS':
						//this chunk can only occur once and it must occur after the PLTE chunk and before IDAT chunk 
						//print "tRNS found, color type = ".$info['colorType']."<BR>"; 
						$transparency = array();
						if ($info['colorType'] == 3) { // indexed color, rbg 
							/* corresponding to entries in the plte chunk 
							  Alpha for palette index 0: 1 byte
							  Alpha for palette index 1: 1 byte
							  ...etc...
							 */
							// there will be one entry for each palette entry. up until the last non-opaque entry.
							// set up an array, stretching over all palette entries which will be o (opaque) or 1 (transparent)
							$transparency['type'] = 'indexed';
							$numPalette = strlen($pdata) / 3;
							$trans = 0;
							for ($i = $chunkLen; $i >= 0; $i--) {
								if (ord($data[$p + 8 + $i]) == 0) {
									$trans = $i;
								}
							}
							$transparency['data'] = $trans;
						} elseif ($info['colorType'] == 0) { // grayscale 
							/* corresponding to entries in the plte chunk 
							  Gray: 2 bytes, range 0 .. (2^bitdepth)-1
							 */
							$transparency['type'] = 'indexed';
							$transparency['data'] = ord($data[$p + 8 + 1]);
						} elseif ($info['colorType'] == 2) { // truecolor 
							/* corresponding to entries in the plte chunk 
							  Red: 2 bytes, range 0 .. (2^bitdepth)-1
							  Green: 2 bytes, range 0 .. (2^bitdepth)-1
							  Blue: 2 bytes, range 0 .. (2^bitdepth)-1
							 */
							$transparency['r'] = $this->PRVT_getBytes($data, $p + 8, 2); // r from truecolor 
							$transparency['g'] = $this->PRVT_getBytes($data, $p + 10, 2); // g from truecolor 
							$transparency['b'] = $this->PRVT_getBytes($data, $p + 12, 2); // b from truecolor 
						} else {
							//unsupported transparency type 
						}
						// KS End new code 
						break;
					default:
						break;
				}

				$p += $chunkLen + 12;
			}

			if (!$haveHeader) {
				$error = 1;
				$errormsg = 'information header is missing';
			}
			if (isset($info['interlaceMethod']) && $info['interlaceMethod']) {
				$error = 1;
				$errormsg = 'There appears to be no support for interlaced images in pdf.';
			}
		}

		if (!$error && $info['bitDepth'] > 8) {
			$error = 1;
			$errormsg = 'only bit depth of 8 or less is supported';
		}

		if (!$error) {
			if ($info['colorType'] != 2 && $info['colorType'] != 0 && $info['colorType'] != 3) {
				$error = 1;
				$errormsg = 'transparancey alpha channel not supported, transparency only supported for palette images.';
			} else {
				switch ($info['colorType']) {
					case 3:
						$color = 'DeviceRGB';
						$ncolor = 1;
						break;
					case 2:
						$color = 'DeviceRGB';
						$ncolor = 3;
						break;
					case 0:
						$color = 'DeviceGray';
						$ncolor = 1;
						break;
				}
			}
		}
		if ($error) {
			$this->addMessage('PNG error - (' . $file . ') ' . $errormsg);
			return;
		}
		if ($w == 0) {
			$w = $h / $info['height'] * $info['width'];
		}
		if ($h == 0) {
			$h = $w * $info['height'] / $info['width'];
		}
//print_r($info);
		// so this image is ok... add it in.
		$this->numImages++;
		$im = $this->numImages;
		$label = 'I' . $im;
		$this->numObj++;

		$options = array('label' => $label, 'data' => $idata, 'bitsPerComponent' => $info['bitDepth'], 'pdata' => $pdata
			, 'iw' => $info['width'], 'ih' => $info['height'], 'type' => 'png', 'color' => $color, 'ncolor' => $ncolor);
		if (isset($transparency)) {
			$options['transparency'] = $transparency;
		}
		$this->o_image($this->numObj, 'new', $options);

		$this->objects[$this->currentContents]['c'].="\nq";
		$this->objects[$this->currentContents]['c'].="\n" . sprintf('%.3f', $w) . " 0 0 " . sprintf('%.3f', $h) . " " . sprintf('%.3f', $x) . " " . sprintf('%.3f', $y) . " cm";
		$this->objects[$this->currentContents]['c'].="\n/" . $label . ' Do';
		$this->objects[$this->currentContents]['c'].="\nQ";
	}

}