<?php
namespace siu\operaciones\actas_cursadas;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\util\validador;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;
use siu\operaciones\_comun\operaciones\reporte\generador_excel;

class controlador extends controlador_g3w2
{
	protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 10;
	protected $pdf_fuente_subtitulo = 10;
	protected $nombre_archivo = 'actas_cursadas';
	
	function modelo()
	{
	}

	function get_clase_vista()
	{
		switch ($this->accion) {
			case 'acta': 
				return 'vista_acta';
			default: 
				return 'vista';
		}
	}

	function accion__index()
	{
		// Parámetros
		$elemento = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));

		$anio_per_lec = $this->validate_param('per_lec', 'get', validador::TIPO_ALPHANUM, array(
			'default' => $this->get_periodo_actual()
		));

		$pagelet = $this->vista()->pagelet('listado');
		$pagelet->actividad_elemento = $elemento;
		$pagelet->periodo_lectivo = $anio_per_lec;
		$pagelet->data['datos'] = $this->datos_listado($elemento, $anio_per_lec);
	}

	protected function crear_ezpdf(){
		$instancia = new \Cezpdf($this->pdf_hoja, 'landscape');
		return $instancia;
	}

	function accion__generar_pdf_listado()
	{
		// Parámetros
		$elemento = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));

		$anio_per_lec = $this->validate_param('per_lec', 'get', validador::TIPO_ALPHANUM, array(
			'default' => $this->get_periodo_actual()
		));

		$datos = $this->datos_listado($elemento, $anio_per_lec);
		$titulos = $this->get_columnas();

		$pdf = new generador_pdf($this->crear_ezpdf());
		$pdf->set_font($this->pdf_fuente);
		$pdf->set_options(array('showHeadings'=>1, 'protectRows' => 1, 'maxWidth' => 500, 'width' => 800, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8,0.8,0.8),'cols' => array()));
		$pdf->set_text_options(array('left'=>5, 'top' => 20, 'maxWidth' => 800, 'width' => 500));
		$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
		$pdf->set_nombre_archivo($this->nombre_archivo);

		$datos_carrera = guarani::persona()->get_info_propuesta_activa();
		$nombre_carrera = "";		
		if (isset($datos_carrera)){
			$nombre_carrera = $datos_carrera['nombre'];
		}

		$this->pdf_encabezado = array(kernel::traductor()->trans("tit_actas_cursada"), ucfirst($this->trans('actas_cursadas.propuesta')).': '.$nombre_carrera, 112);
		$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
		$this->pdf_encabezado_img = array('img'=>$logo['path'],'width' => '103');

		$pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);
		$pdf->set_encabezado_img($this->pdf_encabezado_img["img"], $this->pdf_encabezado_img["width"]);

		$pdf->agregar_tabla($datos, $titulos, '');
		$pdf->generar();
	}

	function accion__generar_xls_listado()
	{
		// Parámetros
		$elemento = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));

		$anio_per_lec = $this->validate_param('per_lec', 'get', validador::TIPO_ALPHANUM, array(
			'default' => $this->get_periodo_actual()
		));

		$datos = $this->datos_listado($elemento, $anio_per_lec);
		$titulos = $this->get_columnas();

		$xls = new generador_excel();
		$xls->set_nombre_archivo($this->nombre_archivo);
		$xls->agregar_tabla($datos, $titulos, '');
		$xls->generar();
	}

	function accion__acta()
	{
		$url_acta = $this->validate_param('url_acta', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));
		$elemento = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));
		$per_lec = $this->validate_param('per_lec', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));

		$pagelet = $this->vista()->pagelet('acta');
		$pagelet->actividad_elemento = $elemento;
		$pagelet->periodo_lectivo = $per_lec;

		$datos = $this->datos_listado($elemento, $per_lec);
		foreach($datos as $dato){
			if ($dato['url_acta'] == $url_acta){
				$pagelet->data = $this->datos_acta($dato['id_acta'], $dato['origen']);
				$pagelet->acta = $url_acta;
				break;
			}
		}
	}

	function accion__generar_pdf_acta()
	{
		$url_acta = $this->validate_param('acta', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));
		$elemento = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));
		$per_lec = $this->validate_param('per_lec', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));

		$datos = $this->datos_listado($elemento, $per_lec);
		$datos_acta = array();
		$acta = "";
		foreach($datos as $dato){
			if ($dato['url_acta'] == $url_acta){
				$datos_acta = $this->datos_acta($dato['id_acta'], $dato['origen']);
				$acta = $dato['nro_acta'];
				break;
			}
		}

		$titulos = $this->get_columnas_acta();

		$pdf = new generador_pdf($this->crear_ezpdf());
		$pdf->set_font($this->pdf_fuente);
		$pdf->set_options(array('showHeadings'=>1, 'protectRows' => 1, 'maxWidth' => 500, 'width' => 800, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8,0.8,0.8),'cols' => array()));
		$pdf->set_text_options(array('left'=>5, 'top' => 20, 'maxWidth' => 800, 'width' => 500));
		$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
		$nombre_archivo = $this->nombre_archivo."_acta";
		$pdf->set_nombre_archivo($nombre_archivo);

		$datos_carrera = guarani::persona()->get_info_propuesta_activa();
		$nombre_carrera = "";		
		if (isset($datos_carrera)){
			$nombre_carrera = $datos_carrera['nombre'];
		}

		$this->pdf_encabezado = array(kernel::traductor()->trans("tit_actas_cursada"), ucfirst($this->trans('actas_cursadas.propuesta')).': '.$nombre_carrera.PHP_EOL.ucfirst($this->trans('actas_cursadas.acta')).': '.$acta, 112);
		$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
		$this->pdf_encabezado_img = array('img'=>$logo['path'],'width' => '103');

		$pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);
		$pdf->set_encabezado_img($this->pdf_encabezado_img["img"], $this->pdf_encabezado_img["width"]);

		if (isset($datos_acta)){
			foreach($datos_acta['alumnos'] as $folio => $alumnos){
				$pdf->agregar_texto($this->trans('actas_cursadas.folio').": ".$folio.PHP_EOL);
				$pdf->agregar_tabla($alumnos, $titulos, '');
			}
		}
		$pdf->generar();
	}

	function accion__generar_xls_acta()
	{
		$url_acta = $this->validate_param('acta', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));
		$elemento = $this->validate_param('actividad', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));
		$per_lec = $this->validate_param('per_lec', 'get', validador::TIPO_ALPHANUM, array(
			'default' => null
		));

		$datos = $this->datos_listado($elemento, $per_lec);
		$datos_acta = array();
		$acta = "";
		foreach($datos as $dato){
			if ($dato['url_acta'] == $url_acta){
				$datos_acta = $this->datos_acta($dato['id_acta'], $dato['origen']);
				$acta = $dato['nro_acta'];
				break;
			}
		}

		$titulos = $this->get_columnas_acta();
		$titulos['folio'] = ucfirst($this->trans('actas_cursadas.folio'));
		$resultado = array();
		foreach ($datos_acta['alumnos'] as $folio){
			foreach($folio as $alumno){
				$resultado[] = $alumno;
			}
		}

		$xls = new generador_excel();
		$nombre_archivo = $this->nombre_archivo."_acta";
		$xls->set_nombre_archivo($nombre_archivo);
		$xls->agregar_tabla($resultado, $titulos, '');
		$xls->generar();
	}

	public function accion__buscar_actividades()
	{
		$json = array();
		$actividades = catalogo::consultar('unidad_academica', 'actividades', array(
			'term' => $this->getAutocompleteTerm(),
			'plan_version' => guarani::persona()->es_perfil_docente() ? null : guarani::persona()->get_id_plan_version_activo()
		));
		$json[] = array('id' => '', 'label' => 'Todas');
		foreach($actividades as $actividad)
		{
			$json[] = array(
				'id' => $actividad['elemento'],
				'label' => $actividad['nombre'].' ('.$actividad['codigo'].')'
			);
		}
		$this->render_raw_json($json);
	}

	public function get_periodos_lectivos(){
		return catalogo::consultar('unidad_academica', 'periodos_lectivos_todos');
	}

	public function get_datos_periodo($url_per_lec){
		$datos = catalogo::consultar('unidad_academica', 'periodos_lectivos_todos');

		foreach($datos as $periodo){
			if ($periodo['url_perlec'] == $url_per_lec){
				return $periodo;
			}
		}
		return false;
	}

	public function get_periodo_actual(){
		$datos = catalogo::consultar('unidad_academica', 'periodos_lectivos_todos');

		foreach($datos as $periodo){
			if ($periodo['vigente'] == true){
				return $periodo['url_perlec'];
			}
		}
		return false;
	}

	public function get_datos_actividad($elemento = null){
		if (isset($elemento)){
			$datos = catalogo::consultar('unidad_academica', 'actividad', array('actividad' => $elemento));
			return $datos;
		}

		return false;
	}

	public function get_columnas()
	{
		return array(
			'anio_academico' => ucfirst($this->trans("actas_cursadas.anio_academico")),
			'periodo_lectivo_nombre' => ucfirst($this->trans("actas_cursadas.periodo_lectivo")),
			'ubicacion_nombre' => ucfirst($this->trans("actas_cursadas.nombre_sede")),
			'id_acta' => ucfirst($this->trans("actas_cursadas.acta")),
			'actividad_nombre_completo' => ucfirst($this->trans("actas_cursadas.materia")),
			'estado_acta' => ucfirst($this->trans("actas_cursadas.estado")),
			'fecha_cierre' => ucfirst($this->trans("actas_cursadas.fecha_cierre")),
		);
	}

	private function datos_listado($elemento, $hash_perlec){
		$datos_actividad = $this->get_datos_actividad($elemento);
		$parametros = array();
		$parametros['docente'] = kernel::persona()->get_id_docente();
		$parametros['actividad_codigo'] = isset($datos_actividad['codigo']) ? $datos_actividad['codigo'] : null;
		$parametros['anio_academico'] = null;
		$parametros['periodo_lectivo'] = null;
		$datos_periodo = $this->get_datos_periodo($hash_perlec);
		if ($datos_periodo){
			$parametros['anio_academico'] = $datos_periodo['anio_academico'];
			$parametros['periodo_lectivo'] = $datos_periodo['periodo_lectivo'];
		}
		$parametros['origen_acta'] = "R"; // actas de cursada
		return catalogo::consultar('docente', 'lista_actas_cursadas_promociones', $parametros);
	}

	public function get_columnas_acta()
	{
		return array(
			'legajo' => ucfirst($this->trans("actas_cursadas.legajo")),
			'nombre_completo' => ucfirst($this->trans("actas_cursadas.nombre_alumno")),
			'fecha' => ucfirst($this->trans("actas_cursadas.fecha")),
			'nota_completa' => ucfirst($this->trans("actas_cursadas.nota")),
			'concepto_desc' => ucfirst($this->trans("actas_cursadas.condicion")),
			'resultado_desc' => ucfirst($this->trans("actas_cursadas.resultado")),
		);
	}

	private function datos_acta($id_acta, $origen){
		$resultado = array();
		$alumnos = catalogo::consultar('docente', 'detalle_acta_cursada_promocion', array('id_acta' => $id_acta));
		$encabezado = catalogo::consultar('docente', 'get_datos_cabecera_acta', array('id_acta' => $id_acta, 'origen' => $origen));
		$resultado['encabezado'] = $encabezado;

		// Corte de alumnos por folio
		$corte_alumno = array();
		foreach ($alumnos as $alumno){
			$alumno['nombre_completo'] = $alumno['apellido'].", ".$alumno['nombres'];
			$alumno['nota_completa'] = $alumno['nota']." (".$alumno['nota_desc'].")";
			if (isset($corte_alumno[$alumno['folio']])){
				$corte_alumno[$alumno['folio']][\guarani_arreglos::count($corte_alumno[$alumno['folio']])] = $alumno;
			} else {
				$corte_alumno[$alumno['folio']][0] = $alumno;
			}
		}
		$resultado['alumnos'] = $corte_alumno;
		$resultado['encabezado']['folios'] = $alumno['folio'];
		return $resultado;
	}
}