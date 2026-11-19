<?php
namespace siu\operaciones\horarios_cursadas;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;
use siu\operaciones\_comun\operaciones\reporte\generador_excel;
use siu\guarani;

class controlador extends controlador_g3w2
{
	protected $form_builder;
	public $datos_reporte;
	public $datos_filtro;

	function modelo()
	{
		return null;
	}

	function accion__index()
	{
		$form = $this->get_form();
		if (!empty($_GET['formulario_filtro'])) {

			$validacion_ok = $this->recuperar_filtro();

			if ($validacion_ok) {//Si el form es valido prosigo
				$datos = $form->get_datos();

				$carrera = "";
				if ($datos['carrera'] != "")
					$carrera = $this->decodificar_carrera($datos['carrera']);
				$responsable_academica = "";
				if ($datos['ra'] != "")
					$responsable_academica = $this->decodificar_ra($datos['ra']);
				$materia = "";
				$materia_descr = "";
				if ($datos['materia'] != "") {
					list($materia, $materia_descr) = $this->decodificar_materia($datos['materia'], $carrera);
				}
				$this->datos_filtro["materia_descr"] = $materia_descr;
				$form->set_datos(array("materia_descr" => $materia_descr));
				$sede = "";
				if ($datos['ubicacion'] != "")
					$sede = $this->decodificar_sede($datos['ubicacion']);

				$parametros = array(
					'periodo_lectivo' => $datos['periodo'],
					'elemento' => $materia,
					'turno' => $datos['turno'],
					'ubicacion' => $sede,
					'dia' => $datos['dia_semana'],
					'propuesta' => $carrera,
					'anio_cursada' => $datos['anio_cursada'],
					'responsable_academica' => $responsable_academica,
				);
				$datos = catalogo::consultar('usuario_anonimo', 'horarios_cursadas', $parametros);

				$pagelet = 'operaciones\horarios_cursadas\pagelet_reporte';
				$pl = kernel::localizador()->instanciar($pagelet, 'reporte');
				$pl->data['datos'] = $datos;
				$this->datos_reporte = $datos;
				$this->vista()->add_pagelet($pl);
			}
		}
	}

	function decodificar_materia($materia_hash, $carrera)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'lista_actividades_propuesta', array('propuesta' => $carrera, 'plan' => "", 'materia' => "", 'persona' => ''));
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $materia_hash) {
					return array($value['elemento'], $value['nombre']);
				}
			}
		}
		return false;
	}

	function decodificar_ra($ra_hash)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_responsables_academicas');

		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $ra_hash) {
					return $value['responsable_academica'];
				}
			}
		}
		return false;
	}

	function decodificar_sede($sede_hash)
	{
		$datos = catalogo::consultar('sistema', 'ubicaciones');
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['ubicacion'] == $sede_hash) {
					return $value['ubicacion'];
				}
			}
		}
		return false;
	}

	function decodificar_carrera($carrera_hash)
	{
		$datos = catalogo::consultar('usuario_anonimo', 'get_carreras');
		if (!empty($datos)) {
			foreach ($datos as $value) {
				if ($value['_ID_'] == $carrera_hash) {
					return $value['propuesta'];
				}
			}
		}
		return false;
	}

	private function recuperar_filtro()
	{
		$form = $this->get_form();
		/* if (!$form->procesar('GET')) {
		  throw new ErrorKernelValidacion("Error al validar el filtro");
		  } */
		$validacion_ok = $form->procesar('GET');
		//si no se llega aca, aplican los valores default
		$this->datos_filtro = $form->get_datos();
		return $validacion_ok;
	}

	/**
	 * @return guarani_form
	 */
	function get_form()
	{
		return $this->get_form_builder()->get_formulario();
	}

	function get_vista_form()
	{
		return $this->get_form_builder()->get_vista();
	}

	/**
	 * @return builder_form_filtro
	 */
	function get_form_builder()
	{
		// se hace esta función para no construir cada vez el builder
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\horarios_cursadas\filtro\builder_form_filtro');
		}

		return $this->form_builder;
	}

	/* REPORTES PDF Y EXCEL */
	public function get_columnas()
	{
		$titulo = array();
		$titulo['COMISION_NOMBRE'] = "Comisión";
		$titulo['TURNO_DESC'] = "Turno";
		$titulo['DIA_SEMANA_DESC'] = "Día de semana";
		$titulo['INICIO_FIN_CLASE'] = "Hora inicio-fin";
		$titulo['TIPO_CLASE_DESC'] = "Tipo clase";
		$titulo['AULA_EDIF'] = "Aula";
		//$titulo['EDIFICIO'] = "Edificio";
		$titulo['CUPO'] = "Cupo";
		return $titulo;
	}

	protected function get_generador_pdf()
	{
		return new generador_pdf($this->crear_ezpdf('landscape'));
	}

	protected function crear_ezpdf($posicion_hoja = null)
	{
		$instancia = new \Cezpdf("a4", $posicion_hoja);
		return $instancia;
	}

	protected function get_datos()
	{
		if (!isset($this->datos_reporte)) {

			//Parámetros filtro
			$hash_materia = $this->validate_param('materia', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$hash_sede = $this->validate_param('sede', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$periodo = $this->validate_param('periodo', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$hash_carrera = $this->validate_param('carrera', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$dia = $this->validate_param('dia', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$turno = $this->validate_param('turno', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$anio_cursada = $this->validate_param('anio_curs', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));
			$responsable_academica = $this->validate_param('ra', 'get', validador::TIPO_ALPHANUM, array('allowempty' => true));

			//Seteo parámetros
			$carrera = "";
			if ($hash_carrera != "")
				$carrera = $this->decodificar_carrera($hash_carrera);
			$materia = "";
			$materia_descr = "";
			if ($hash_materia != "")
				list($materia, $materia_descr) = $this->decodificar_materia($hash_materia, $carrera);
			$sede = "";
			if ($hash_sede != "")
				$sede = $this->decodificar_sede($hash_sede);

			$parametros = array(
				'periodo_lectivo' => $periodo,
				'elemento' => $materia,
				'turno' => $turno,
				'ubicacion' => $sede,
				'dia' => $dia,
				'propuesta' => $carrera,
				'anio_cursada' => $anio_cursada,
				'responsable_academica' => $responsable_academica,
			);

			//Realizo consulta	
			$this->datos_reporte = catalogo::consultar('usuario_anonimo', 'horarios_cursadas', $parametros);
		}
		return $this->datos_reporte;
	}

	public function accion__generar_pdf()
	{
		$pdf = $this->get_generador_pdf();
		$pdf->set_font($this->pdf_fuente);
		$pdf->set_options(array('showHeadings' => 1, 'protectRows' => 1, 'maxWidth' => 750, 'width' => 750, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8, 0.8, 0.8), 'cols' => array()));
		$pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 750, 'width' => 750));
		$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);
		$pdf->set_tiene_encabezado(false);
		$institucion = guarani::get_nombre_universidad() . "\n";
		$pdf->set_encabezado($institucion, '<b><u>Horarios Cursadas</u></b>', 110);

		if (!empty($this->pdf_encabezado_img["img"])) {
			$pdf->set_encabezado_img($this->pdf_encabezado_img["img"], $this->pdf_encabezado_img["width"]);
		} else {
			$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
			$pdf->set_encabezado_img($logo['path'], "103");
		}

		$data = $this->get_datos();
		$titulos = $this->get_columnas();

		foreach ($data as $sede) {
			$pdf->agregar_texto('<b>Sede: </b>' . $sede['UBICACION_NOMBRE']);
			$pdf->agregar_texto(' ');

			foreach ($sede['PERIODOS_LECTIVOS'] as $periodo_lectivo) {
				$pdf->agregar_texto('<b>Período Lectivo: </b>' . $periodo_lectivo['PERIODO_LECTIVO']);
				$pdf->agregar_texto(' ');

				foreach ($periodo_lectivo['MATERIAS'] as $materia) {
					$pdf->agregar_texto('<b>Materia: </b>' . $materia['MATERIA_NOMBRE']);
					$pdf->agregar_texto(' ');
					$pdf->agregar_tabla($materia['HORARIOS'], $titulos, '');
					$pdf->agregar_texto(' ');
					$pdf->agregar_texto(' ');
				}
			}
			$pdf->agregar_texto('');
		}

		$pdf->set_nombre_archivo('horarios_cursadas');
                $archivo = $pdf->generar(false);
                $this->descargar_archivo($archivo, 'horarios_cursadas.pdf', true);
	}

	protected function get_generador_excel()
	{
		return new generador_excel();
	}

	public function accion__generar_excel()
	{
		$excel = $this->get_generador_excel();
		$data = $this->get_datos();
		$titulos = $this->get_columnas();
		$institucion = guarani::get_nombre_universidad();
		$estilo_titulo = array(
			'font' => array('bold' => true));

		$excel->agregar_texto_estilos($institucion, 1, $estilo_titulo);
		$excel->agregar_texto_estilos('Horarios Cursadas', 1, $estilo_titulo);



		foreach ($data as $sede) {
			$excel->agregar_texto('Sede: ' . $sede['UBICACION_NOMBRE'], 1);

			foreach ($sede['PERIODOS_LECTIVOS'] as $periodo_lectivo) {
				$excel->agregar_texto('Período Lectivo: ' . $periodo_lectivo['PERIODO_LECTIVO'], 1);

				foreach ($periodo_lectivo['MATERIAS'] as $materia) {
					$excel->agregar_tabla($materia['HORARIOS'], $titulos, 'Materia: ' . $materia['MATERIA_NOMBRE']);
					$excel->agregar_texto('', 1);
				}
			}
			$excel->agregar_texto('', 1);
		}

		$excel->set_nombre_archivo('horarios_cursadas');
		$excel->generar();
	}

	function accion__buscar_materias()
	{
		$term = $this->getAutocompleteTerm();

		$carrera_hash = $this->validate_param('carrera', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$carrera = "";
		if ($carrera_hash != "")
			$carrera = $this->decodificar_carrera($carrera_hash);

		$paquete = array();
		if (!is_null($term)) {

			$datos = catalogo::consultar('usuario_anonimo', 'lista_actividades_propuesta', array('propuesta' => $carrera, 'plan' => '', 'materia' => $term, 'persona' => ''));
			foreach ($datos as $dato) {
				$paquete[] = array(
					'id' => $dato['_ID_'],
					'label' => $dato['nombre']
				);
			}
		}
		$this->render_raw_json($paquete);
	}

        function accion__buscar_carreras()
	{
		$ra_hash = $this->validate_param('ra', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$ra = '';
		if ($ra_hash != "")
			$ra = $this->decodificar_ra($ra_hash);

		$datos = array();
		$datos['cod'] = 1;
		$datos['carrera'] = catalogo::consultar('usuario_anonimo', 'get_carreras', $ra);
		if (empty($datos['carrera'])) {
			$datos['carrera'] = array('' => array('nombre' => 'No hay carreras asociadas a la responsable académica', '_ID_' => 'nohaycarreras', 'propuesta' => 'nohaycarreras'));
		}
		$this->render_raw_json($datos);
	}
        
	function accion__buscar_periodo()
	{


		$ra_hash = $this->validate_param('ra', 'get', validador::TIPO_ALPHANUM, array('default' => ''));
		$ra = '';
		if ($ra_hash != "")
			$ra = $this->decodificar_ra($ra_hash);
		$datos = array();
		$datos['periodo'] = catalogo::consultar('unidad_academica', 'periodos_lectivos_vigentes_por_ra', array('responsable_academica' => $ra));
		if (empty($datos['periodo'])) {
			$datos['periodo'] = array('' => array('periodo_lectivo_nombre' => 'No hay periodos asociados a la responsable académica', 'periodo_lectivo' => ''));
		}
		$this->render_raw_json($datos['periodo']);
	}

}