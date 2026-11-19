<?php
namespace siu\operaciones\_comun\operaciones\reporte;

use \SIU\Chulupi\kernel;
use \siu\guarani;

class super_generador_pdf
{
	private $config;
	private $data;

	public function __construct(array $config = null)
	{
		$this->config = empty($config) ? $this->CONFIG_DEFAULT : $this->completar_configuracion($config, $this->CONFIG_DEFAULT);
		$this->data = array();
	}

	public function set_report($name) { $this->config['report'] = $name; }

	public function set_header_logo($logo) { $this->config['header']['logo'] = $logo; }
	public function set_header_title($title) { $this->config['header']['title'] = $title; }
	public function set_header_subtitle($subtitle) { $this->config['header']['subtitle'] = $subtitle; }

	public function set_document_title($title) { $this->config['doc_info']['Title'] = $title; }
	public function set_document_author($author) { $this->config['doc_info']['Author'] = $author; }
	public function set_document_subject($subject) { $this->config['doc_info']['Subject'] = $subject; }
	public function set_document_creator($creator) { $this->config['doc_info']['Creator'] = $creator; }
	public function set_document_producer($producer) { $this->config['doc_info']['Producer'] = $producer; }

	public function texto($texto)
	{
		$this->data[] = array(
			'function' => 'ezText',
			'arguments' => array(
				$texto,
				$this->config['styles']['text']['size'],
				$this->config['styles']['text']['options'],
			),
		);
	}

	public function tabla(array $datos, array $columnas = null, $titulo = '')
	{
		$this->data[] = array(
			'function' => 'ezTable',
			'arguments' => array(
				$datos,
				$columnas,
				$titulo,
				$this->config['styles']['table']['options'],
			),
		);
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
	public function generate($stream = true)
	{
		$cezpdf = new \Cezpdf($this->config['paper'], $this->config['orientation']);
		$cezpdf->ezSetMargins($this->get_real_top_margin(), $this->config['margins']['bottom'], $this->config['margins']['left'], $this->config['margins']['right']);
		$cezpdf->ezStartPageNumbers((int)($cezpdf->ez['pageWidth']/2), (int)($cezpdf->ez['bottomMargin']/2), 10, 'left', '{PAGENUM} de {TOTALPAGENUM}', 1);
		$cezpdf->ezStartPageNumbers((int)($cezpdf->ez['pageWidth'] - $cezpdf->ez['leftMargin'] - $cezpdf->ez['rightMargin']), (int)($cezpdf->ez['bottomMargin']/2), 9, 'left', date('d/m/Y H:i:s'));
		$cezpdf->selectFont(guarani::dir().$this->config['font']['path'].$this->config['font']['name']);
		if (!empty($this->config['doc_info']))
			$cezpdf->addInfo($this->config['doc_info']);

		foreach($this->data as $data) {
			if($data['function'] == 'ezTable'){
				$cezpdf->ezTable($data['arguments'][0], $data['arguments'][1], $data['arguments'][2], $data['arguments'][3]);
			}
			else {
				call_user_func_array(array($cezpdf, $data['function']), $data['arguments']);
			}
		}

		foreach($cezpdf->ezPages as $page)
		{
			$cezpdf->reopenObject($page);
			$yPos = (int)$cezpdf->ez['pageHeight'] - $this->config['margins']['top']/2;
			$xPos = (int)$cezpdf->ez['pageWidth'] - $cezpdf->ez['leftMargin'] - $cezpdf->ez['rightMargin'] - 15;
			//logo
			$cezpdf->ezSetY($yPos + 5);
			$cezpdf->ezImage(realpath(\siu\guarani::dir().$this->config['header']['logo']), $this->config['styles']['logo']['pad'], $this->config['styles']['logo']['width'], $this->config['styles']['logo']['resize'], $this->config['styles']['logo']['justification']);
			//title
			$cezpdf->ezSetY($yPos + $this->config['styles']['title']['size']);
			$cezpdf->ezText($this->config['header']['title'], $this->config['styles']['title']['size'], $this->config['styles']['title']['options']);
			//subtitle
			$cezpdf->ezSetY($yPos);
			$cezpdf->ezText($this->config['header']['subtitle'], $this->config['styles']['subtitle']['size'], $this->config['styles']['subtitle']['options']);
			$cezpdf->closeObject();
		}

                // Si se descarga por stream
		if ($stream) {
			$options['compress'] = 1;
			$options['Content-Disposition'] = $this->config['report'];
			$cezpdf->ezStream($options);
		} else {// Si se guarda en el filesystem
			$archivo = kernel::proyecto()->get_dir_temp().'/'.'archivo.pdf';
			$pdfcode = $cezpdf->ezOutput();
			$fp = fopen($archivo, 'wb');
			fwrite($fp, $pdfcode);
			fclose($fp);
			return $archivo;
		}
	}

	private function completar_configuracion(array $config, array $default)
	{
		foreach($default as $key => $value)
		{
			if (!isset($config[$key]))
				$config[$key] = $value;
			elseif (is_array($value))
				$config[$key] = $this->completar_configuracion($config[$key], $default[$key]);
		}
		return $config;
	}

	private function get_real_top_margin()
	{
		return $this->config['margins']['top']
			+ $this->config['styles']['title']['size']
			+ $this->config['styles']['subtitle']['size']/1.5 * \guarani_arreglos::count(explode(PHP_EOL, $this->config['header']['subtitle']));
	}

	public $CONFIG_DEFAULT = array(
		'report' => 'file',
		'doc_info' => array(),
		'paper' => 'A4',
		//'orientation' => 'portrait',
		'orientation' => 'landscape',
		'margins' => array('top' => 80, 'bottom' => 50, 'left' => 40, 'right' => 40),
		'font' => array(
			'path' => '/../../vendor/rebuy/ezpdf/src/ezpdf/fonts/',
			'name' => 'Courier.afm',
		),
		'header' => array(
			'title' => null,
			'subtitle' => null,
			'logo' => '/www/img/logo_comprobante_con_bajada_nuevo.jpg',
		),
		'styles' => array(
			'logo' => array(
				'pad' => 0,
				'width' => 100,
				'resize' => 'none',
				'justification' => 'left',
				'border' => '',
			),
			'title' => array(
				'size' => 10,
				'options' => array(
					'left' => 112,
					'width' => 747,
					'maxWidth' => 747,
				),
			),
			'subtitle' => array(
				'size' => 10,
				'options' => array(
					'left' => 112,
					'width' => 747,
					'maxWidth' => 747,
				),
			),
			'text' => array(
				'size' => 10,
				'options' => array(),
			),
			'table' => array(
				'options' => array(
					'showHeadings' => 1,
					'protectRows' => 1,
					'maxWidth' => 500,
					'width' => 800,
					'fontSize' => 10,
					'shadeCol' => array(0.8, 0.8, 0.8),
				),
			),
		),
	);
}