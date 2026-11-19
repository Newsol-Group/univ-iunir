<?php
namespace siu\operaciones\reporte_actas;

use \SIU\Chulupi\kernel;

abstract class pagelet_acta
	extends \SIU\Chulupi\interfaz\pagelet
{
	const LISTADO = 0;
	const DETALLE = 1;
	const IMPRIMIR_LISTADO_PDF = 2;
	const IMPRIMIR_LISTADO_XLS = 3;
	const IMPRIMIR_DETALLE_PDF = 4;
	const IMPRIMIR_DETALLE_XLS = 5;

	private $mode = self::LISTADO;

	public function set_mode($modo)
	{
		$this->mode = $modo;
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_autocompletar.js");
		return $archivos;
	}

	function prepare()
	{
		switch($this->mode)
		{
			case self::LISTADO:
				$this->prepare_listado();
				break;
			case self::DETALLE:
				$this->set_template('detalle');
				$this->prepare_detalle();
				break;
			case self::IMPRIMIR_LISTADO_PDF:
				return $this->generar_pdf_listado();
				break;
			case self::IMPRIMIR_LISTADO_XLS:
				$this->generar_xls_listado();
				break;
			case self::IMPRIMIR_DETALLE_PDF:
				return $this->generar_pdf_detalle();
				break;
			case self::IMPRIMIR_DETALLE_XLS:
				$this->generar_xls_detalle();
				break;
			default:
				throw new \siu\errores\error_guarani();
		}
	}

	public static function get_actas()
	{
		return array(
			'R' => 'cursadas',
			'E' => 'examenes',
			'P' => 'promociones',
		);
	}

	public static function instanciar($alias = null)
	{
		$origen = kernel::request()->getParam('origen');
		if (empty($origen))
			return kernel::localizador()->instanciar('operaciones\reporte_actas\pagelet_cursadas', $alias);
		$actas = static::get_actas();
		return kernel::localizador()->instanciar('operaciones\reporte_actas\pagelet_'.$actas[$origen], $alias);
	}

	public abstract function filtrar();
	protected abstract function prepare_listado();
	protected abstract function prepare_detalle();

	protected abstract function datos_listado();
	protected abstract function get_nombre_archivo_listado();
	protected abstract function get_columnas_impresion_listado();

	protected function generar_pdf_listado()
	{
		$pdf = new \siu\operaciones\_comun\operaciones\reporte\generador_pdf(new \Cezpdf('A4', 'landscape'));
		$pdf->set_font(10);
		$pdf->set_options(array(
			'showHeadings' => 1,
			'protectRows' => 1,
			'maxWidth' => 500,
			'width' => 800,
			'fontSize' => 10,
			'shadeCol' => array(0.8, 0.8, 0.8),
			'cols' => array()
		));
		$pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 800, 'width' => 500));
		$pdf->set_pdf_fuente_texto(10);
		$pdf->set_pdf_fuente_titulo(10);
		$pdf->set_pdf_fuente_subtitulo(10);
                $nombre_archivo = $this->get_nombre_archivo_listado().'.pdf';
		$pdf->set_nombre_archivo($nombre_archivo);

		$pdf->set_encabezado($this->get_nombre_archivo_listado(), "", 112);
		
		$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
		$pdf->set_encabezado_img($logo['path'], 103);

		$pdf->agregar_tabla($this->datos_listado(), $this->get_columnas_impresion_listado(), '');
		$path_archivo = $pdf->generar(false);
                return [$path_archivo, $nombre_archivo];
	}

	function generar_xls_listado()
	{
		$xls = new \siu\operaciones\_comun\operaciones\reporte\generador_excel();
		$xls->set_nombre_archivo($this->get_nombre_archivo_listado());
		$xls->agregar_tabla($this->datos_listado(), $this->get_columnas_impresion_listado(), 'Listado de actas');
		$xls->generar();
	}

	protected abstract function datos_acta();
	protected abstract function get_encabezado_detalle($nro_acta);
	protected abstract function get_nombre_archivo_detalle();
	protected abstract function get_columnas_impresion_detalle();
	protected abstract function prepare_encabezado_pdf($pdf, $datos_acta);
	protected abstract function prepare_encabezado_xls($datos_acta);

	function generar_pdf_detalle()
	{
		$datos_acta = $this->datos_acta();

		$pdf = new \siu\operaciones\_comun\operaciones\reporte\generador_pdf(new \Cezpdf('A4', 'landscape'));
		$pdf->set_font(10);
		$pdf->set_options(array(
			'showHeadings' => 1,
			'protectRows' => 1,
			'maxWidth' => 500,
			'width' => 800,
			'fontSize' => 10,
			'shadeCol' => array(0.8, 0.8, 0.8),
			'cols' => array()
		));
		$pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 800, 'width' => 500));
		$pdf->set_pdf_fuente_texto(10);
		$pdf->set_pdf_fuente_titulo(10);
		$pdf->set_pdf_fuente_subtitulo(10);
                $nombre_archivo = $this->get_nombre_archivo_detalle().'.pdf';
		$pdf->set_nombre_archivo($nombre_archivo);

		$pdf->set_encabezado($this->get_nombre_archivo_detalle(), "", 112);
		$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
		$pdf->set_encabezado_img($logo['path'], 103);


		if (isset($datos_acta))
		{
			$pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.codigo_verificacion')).': '.$datos_acta['encabezado']['version']);
            $this->prepare_encabezado_pdf($pdf, $datos_acta);
			foreach($datos_acta['alumnos'] as $folio => $alumnos)
			{
				$pdf->agregar_texto(ucfirst(kernel::traductor()->trans('reporte_actas.folio')).': '.$folio.PHP_EOL);
				$pdf->agregar_tabla($alumnos, $this->get_columnas_impresion_detalle(), '');
			}
		}
		$path_archivo = $pdf->generar(false);
                return [$path_archivo, $nombre_archivo];
	}

	function generar_xls_detalle()
	{
		$datos_acta = $this->datos_acta();
		$titulos = $this->get_columnas_impresion_detalle();
		$titulos['folio'] = ucfirst(kernel::traductor()->trans('reporte_actas.folio'));

		$resultado = array();
		foreach ($datos_acta['alumnos'] as $folio)
			foreach($folio as $alumno)
				$resultado[] = $alumno;

		$xls = new \siu\operaciones\_comun\operaciones\reporte\generador_excel();
		$xls->set_nombre_archivo($this->get_nombre_archivo_detalle());

        $encabezado = $this->prepare_encabezado_xls($datos_acta);
        $xls->agregar_tabla($encabezado, array("Encabezado", ""), 'Encabezado');

		$xls->agregar_tabla($resultado, $titulos, 'Detalle de acta ('.$datos_acta['datos']['nro_acta'].')');
		$xls->generar();
	}
}