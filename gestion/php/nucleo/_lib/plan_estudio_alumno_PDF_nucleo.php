<?php
class plan_estudio_alumno_PDF_nucleo extends Cezpdf
{
	public $datacreator = array('Title' => '', 'Author' => '', 'Subject' => '', 'Creator' => '', 'Producer' => '');
	public $options = array();
	public $text_options = array();
	public $encabezado = array("titulo" => "", "subtitulo" => "", "margen" => "", "imagen" => "", "imagen_with" => "");
	public $instancia;
	public $font_path = "";
	public $pdf_fuente_texto = 10;
	public $pdf_fuente_titulo = 10;
	public $pdf_fuente_subtitulo = 12;
	public $nombre_archivo = "file";
	public $tiene_encabezado = false;
	public $tiene_encabezado_long = false;
	public $encabezado_reporte = "";
	public $encabezado_tabla = null;
	public $encabezado_tabla2 = null;

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

	function ezText($text, $size = 0, $options = array(), $test = 0)
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

	function PRVT_getBytes(&$data, $pos, $num)
	{
		// return the integer represented by $num bytes from $pos within $data
		$ret = 0;
		for ($i = 0; $i < $num; $i++) {
			$ret = $ret * 256;
			$ret += ord($data[$pos + $i]);
		}
		return $ret;
	}

	/* -------------------------------- */
	public function set_doc_title($title = "")
	{
		$this->datacreator['Title'] = $title;
	}

	public function set_doc_author($author = "")
	{
		$this->datacreator['Author'] = $author;
	}

	public function set_doc_subject($subject = "")
	{
		$this->datacreator['Subject'] = $subject;
	}

	public function set_doc_creator($creator = "")
	{
		$this->datacreator['Creator'] = $creator;
	}

	public function set_doc_producer($producer)
	{
		$this->datacreator['Producer'] = $producer;
	}

	public function set_font($font = "")
	{
		//Selecciono la fuente que tiene la librería.
        if (comunes::es_interfaz_gestion()) {
            $this->instancia->selectFont(toba::proyecto()->get_path().'/vendor/rospdf/pdf-php/src/fonts/Times-Roman.afm');
        } else {
            $this->instancia->selectFont(\siu\bootstrap::get_dir_raiz() . '/vendor/rebuy/ezpdf/src/ezpdf/fonts/Times-Roman.afm');
        }
	}

	public function set_pdf_fuente_texto($nro = 0)
	{
		$this->pdf_fuente_texto = $nro;
	}

	public function set_pdf_fuente_titulo($nro = 0)
	{
		$this->pdf_fuente_titulo = $nro;
	}

	public function set_pdf_fuente_subtitulo($nro = 0)
	{
		$this->pdf_fuente_subtitulo = $nro;
	}

	public function set_options($options = array())
	{
		$this->options = $options;
	}

	public function set_text_options($options = array())
	{
		$this->text_options = $options;
	}

	public function set_encabezado($titulo = "", $subtitulo = "", $margen = 0)
	{
		$this->encabezado["titulo"] = $titulo;
		$this->encabezado["subtitulo"] = $subtitulo;
		$this->encabezado["margen"] = $margen;
	}

	public function set_tiene_encabezado($tiene_encabezado = false)
	{
		$this->tiene_encabezado = $tiene_encabezado;
	}

	public function set_tiene_encabezado_long($tiene_encabezado_long = false)
	{
		$this->tiene_encabezado_long = $tiene_encabezado_long;
	}

	public function set_encabezado_reporte($encabezado)
	{
		$this->encabezado_reporte = $encabezado;
	}

	public function set_encabezado_tabla($encabezado)
	{
		$this->encabezado_tabla = $encabezado;
	}

	public function set_encabezado_tabla2($encabezado)
	{
		$this->encabezado_tabla2 = $encabezado;
	}

	public function set_encabezado_img($path, $width = "")
	{
		$this->encabezado["imagen"] = $path;
		$this->encabezado["imagen_with"] = $width;
	}

	public function agregar_tabla($datos = array(), $columnas = array(), $titulo = "")
	{
		//ei_arbol($this->options);die();

		$this->instancia->ezTable($datos, $columnas, $titulo, $this->options);
	}

	public function agregar_texto($title = "")
	{
		//Devuelve la posicion Y del puntero de escritura luego de haber agregado el texto.
		return $this->instancia->ezText($title, $this->pdf_fuente_texto, $this->text_options);
	}

	public function agregar_bajada_linea($cantidad_lineas = 1)
	{
		for ($i = 0; $i < $cantidad_lineas; $i++) {
			$this->agregar_texto();
		}
	}

	public function agregar_img($path, $pad = 5, $width = 0, $resize = 'full', $just = 'center', $border = '')
	{
		$this->instancia->ezImage($path, $pad, $width, $resize, $just, $border);
	}

	public function agregar_img_png_desde_archivo($path_img_file, $x, $y, $w = 0, $h = 0)
	{
		$this->instancia->addPngFromFile($path_img_file, $x, $y, $w, $h);
	}

	public function agregar_img_jpeg_desde_archivo($path_img_file, $x, $y, $w = 0, $h = 0)
	{
		$this->instancia->addJpegFromFile($path_img_file, $x, $y, $w, $h);
	}

	public function set_nombre_archivo($nombre)
	{
		$this->nombre_archivo = $nombre;
	}

	function __construct($instancia = null)
	{
		if ($instancia === null) {
			$this->instancia = new \Cezpdf('a4', 'landscape');
		} else {
			$this->instancia = $instancia;
		}
		$this->instancia->ezStartPageNumbers((int) ($this->instancia->ez['pageWidth'] / 2), 22, 10, 'left', '{PAGENUM} de {TOTALPAGENUM}', 1);
		$this->instancia->ezStartPageNumbers((int) ($this->instancia->ez['pageWidth'] - $this->instancia->ez['leftMargin'] - $this->instancia->ez['rightMargin'] - 65), 22, 9, 'left', date('d/m/Y H:i:s'), 1);
		$this->instancia->ezSetMargins('120', '50', '40', '40');
	}

	/**
	 * Genera el PDF con ezPDF.
	 * 
	 * @see https://hotexamples.com/examples/-/Cezpdf/ezOutput/php-cezpdf-ezoutput-method-examples.html PHP Cezpdf::ezOutput Examples.
	 *
	 * @param bool $stream TRUE para que se descargue como stream, FALSE para que se guarde en el filesystem.
	 *
	 * @return null|string Si $stream = false devuelve donde se guardó el PDF, null cuando $stream = true.
	 */
	public function generar($stream = true)
	{
		// Datos del documento
		$this->instancia->addInfo($this->datacreator);
		// Encabezados
		foreach ($this->instancia->ezPages as $pageNum => $id) {
			$this->instancia->reopenObject($id);

			$xPos = (int) $this->instancia->ez['pageWidth'] - $this->instancia->ez['leftMargin'] - $this->instancia->ez['rightMargin'] - 15;

			$yPos = (int) $this->instancia->ez['pageHeight'] - $this->instancia->ez['topMargin'] / 2;
			if ($this->tiene_encabezado) {
				$yPos = (int) $this->instancia->ez['pageHeight'] - $this->instancia->ez['topMargin'] / 3;
			}
			if ($this->tiene_encabezado_long) {
				$yPos = (int) $this->instancia->ez['pageHeight'] - $this->instancia->ez['topMargin'] / 6;
			}

			$this->instancia->ezSetY($yPos);
			$this->instancia->ezImage($this->encabezado["imagen"], 0, $this->encabezado["imagen_with"], "none", "left");

			$this->instancia->ezSetY($yPos + $this->pdf_fuente_titulo + 7);
			$this->instancia->ezText($this->encabezado["titulo"], $this->pdf_fuente_titulo, array('left' => $this->encabezado["margen"], 'width' => $xPos, 'maxWidth' => $xPos, 'spacing' => 1));

			$this->instancia->ezText($this->encabezado["subtitulo"], $this->pdf_fuente_subtitulo, array('width' => $xPos, 'maxWidth' => $xPos, 'justification' => 'center', 'spacing' => 2));

			if ($this->tiene_encabezado) {
				if (isset($this->encabezado_tabla)) {
					$this->instancia->ezSetY($yPos + $this->pdf_fuente_titulo - 55);
					$this->agregar_tabla_encabezado($this->encabezado_tabla["cant_columnas"], $this->encabezado_tabla["datos"]);
					if (isset($this->encabezado_tabla2)) {
						//$this->instancia->ezSetY($yPos+$this->pdf_fuente_titulo);
						$this->agregar_tabla_encabezado($this->encabezado_tabla2["cant_columnas"], $this->encabezado_tabla2["datos"]);
					}
				} else {
					$this->instancia->ezText($this->encabezado_reporte, $this->pdf_fuente_texto, array('left' => 0, 'width' => $xPos, 'maxWidth' => $xPos, 'spacing' => 1));
				}
			}

			$this->instancia->closeObject();
		}

		// Si se descarga por stream
		if ($stream) {
			$options['compress'] = 1;
			$options['download'] = 0;
			$options['Content-Disposition'] = $this->nombre_archivo;
			$contenido = $this->instancia->ezStream($options);
			$longitud = filesize($contenido);
		} else {// Si se guarda en el filesystem
                        $path_temp = (\comunes::es_interfaz_autogestion())? SIU\Chulupi\kernel::proyecto()->get_dir_temp() : toba::proyecto()->get_path_temp();
                        $archivo = $path_temp . '/' . 'archivo.pdf';
			$pdfcode = $this->instancia->ezOutput();
			$fp = fopen($archivo, 'wb');
			fwrite($fp, $pdfcode);
			fclose($fp);
			return $archivo;
		}
	}

	public function dejar_espacio($espacio)
	{
		$this->instancia->ezSetDY($espacio);
	}

	public function posY($posY)
	{
		$this->instancia->ezSetY($posY);
	}

	public function salto_pagina()
	{
		$this->instancia->ezNewPage();
	}

	public function agregar_tabla_encabezado($columnas_cant, $datos = [])
	{
		$xPos = (int) $this->instancia->ez['pageWidth'] - $this->instancia->ez['leftMargin'] - $this->instancia->ez['rightMargin'] - 15;
		$options = ['showHeadings' => 0, 'shaded' => 0, 'showLines' => 0, 'fontSize' => $this->pdf_fuente_texto, 'maxWidth' => $xPos, 'width' => $xPos, 'rowGap' => 1];
		$columnas = [];
		for ($i = 1; $i <= $columnas_cant; $i++) {
			$columnas[$i] = $i;
		}
		$cant_filas = ceil(count($datos) / $columnas_cant);
		$datos_encabezado = [];

		$fila = 1;
		$col = 1;
		$indice = 0;
		while ($fila <= $cant_filas) {
			while ($col <= $columnas_cant) {
				$datos_encabezado[$fila][$col] = $datos[$indice] ?? '';
				$indice++;
				$col++;
			}
			$fila++;
			$col = 1;
		};
		$this->instancia->ezTable($datos_encabezado, $columnas, '', $options);
	}

	private function uniord($c)
	{
		$h = ord($c[0]);
		if ($h <= 0x7F) {
			return $h;
		} elseif ($h < 0xC2) {
			return false;
		} elseif ($h <= 0xDF) {
			return ($h & 0x1F) << 6 | (ord($c[1]) & 0x3F);
		} elseif ($h <= 0xEF) {
			return ($h & 0x0F) << 12 | (ord($c[1]) & 0x3F) << 6 | (ord($c[2]) & 0x3F);
		} elseif ($h <= 0xF4) {
			return ($h & 0x0F) << 18 | (ord($c[1]) & 0x3F) << 12 | (ord(isset($c[2])) & 0x3F) << 6 | (ord(isset($c[3])) & 0x3F);
		} else {
			return false;
		}
	}

}