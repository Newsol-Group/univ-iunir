<?php
namespace siu\operaciones\asistencias_planilla;

use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\generador_pdf;

class controlador extends controlador_g3w2
{
	protected $filtros = null;
	protected $params_comision = null;
	protected $hash_comision = null;
	protected $cantidad_calses = 5;

	/* Variables para generación de PDF */
	protected $pdf_hoja = "A4";
	protected $pdf_encabezado = array("", "", "");
	protected $pdf_encabezado_img = array("img" => "", "width" => "");
	protected $pdf_fuente_texto = 10;
	protected $pdf_fuente_titulo = 10;
	protected $pdf_fuente_subtitulo = 12;
	public $datos_consulta;

	/**
	 * 
	 * @return modelo\transacciones\carga_asistencias
	 */
	function modelo()
	{
		return guarani::carga_asistencias();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision['comision'])) {
			return array('zona' => 'clase', 'seleccion' => $this->params_comision);
		} return null;
	}

	function accion__index()
	{
		$this->load_params();

		// Si la comisión / subcomisión tiene banda horaria asignada proceso el formulario
		if ($this->vista()->get_from_contexto('tiene_banda_horaria_asignada')) {
			$filtros = null;
			$form = $this->get_form();
			if (kernel::request()->isPost()) {
				if ($form->procesar()) {
					$this->filtros = $form->get_datos();
					if (isset($this->filtros)) {
						if ($this->filtros['flg_pdf'] == 1) {
							$this->generar_pdf();
						}
						$pagelet = $this->vista()->pagelet('planilla');
						$pagelet->set_filtros($this->filtros);
						$pagelet->data['es_pdf'] = $this->filtros['flg_pdf'];
					}
				}
			}
		}
	}

	function get_filtros()
	{
		$filtros = null;
		$form = $this->get_form();
		if (kernel::request()->isPost()) {
			$form->procesar();
			$filtros = $form->get_datos();
		}
		return $filtros;
	}

	/**
	 * Este método se encarga de cargar información de la zona para ser usada desde la vista
	 */
	function load_params_for_vista()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision, 'index');
	}

	protected function load_params()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision);
	}

	function get_planilla()
	{
		$datos_planilla = null;
		if (isset($this->filtros)) {
			$parametros = array(
				'comision' => $this->params_comision['comision'],
				'subcomision' => $this->params_comision['subcomision'],
				'fecha_desde' => str_replace("/", "-", $this->filtros['fecha']),
				'cantidad_clases' => $this->cantidad_calses,
				'imprime_alumnos_libres' => $this->filtros['imprime_libres'],
				'tipo_clase' => empty($this->filtros['tipo']) ? null : $this->filtros['tipo']
			);

			$datos_planilla = $this->modelo()->get_planilla($parametros);

			for ($i = 0; $i < $this->cantidad_calses; $i++) {
				if (!empty($datos_planilla['clases'][$i]['fecha_clase_visual'])) {
					$datos_clases[$i] = $datos_planilla['clases'][$i]['fecha_clase_visual'];
				} else {
					$datos_clases[$i] = "        ";
				}
			}

			$datos_planilla['clases'] = $datos_clases;
		}
		return $datos_planilla;
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
		if (!isset($this->form_builder)) {
			$this->form_builder = kernel::localizador()->instanciar('operaciones\asistencias_planilla\planilla\builder_form_filtro');
		}
		$this->form_builder->hash_comision = $this->hash_comision;
		$this->form_builder->comision = $this->params_comision['comision'];
		$this->form_builder->subcomision = $this->params_comision['subcomision'];
		return $this->form_builder;
	}

	function get_hash_comision()
	{
		return $this->hash_comision;
	}

	/* GENERADOR PDF */
	function get_columnas()
	{
		$titulo = array();
		$titulo['nro'] = "Nro";
		$titulo['alumno_legajo'] = "Legajo";
		$titulo['alumno_nombre_elegido'] = "Apellido y Nombres";
		$titulo['total_inasistencias'] = "Acum";

		$datos_clases = $this->datos_consulta['clases'];

		foreach ($datos_clases as $key => $clase) {
			$clase = trim($clase);
			if($clase) {
				$fecha = \DateTime::createFromFormat('d/m/Y', $clase);
				$titulo['fecha' . $key] = $fecha->format('d/m/y');
			}
			else{
				$titulo['fecha' . $key] = '';
			}
		}

		return $titulo;
	}

	/**
	 * Recupera los datos
	 * @param entero $pagina
	 * Numero de página que se desea recuperar
	 * @param buleano $todo
	 * Recuperar todos los datos
	 * @return array
	 */
	function get_data()
	{
		$datos_alumnos = array();
		if (!isset($this->datos_consulta)) {
			$this->datos_consulta = $this->get_planilla();
		}

		if (!empty($this->datos_consulta)) {
			$datos_alumnos = $this->datos_consulta['alumnos'];
			$datos_clases = $this->datos_consulta['clases'];

			foreach ($datos_alumnos as $key => $dato) {
				foreach ($datos_clases as $keyf => $clase) {
					$datos_alumnos[$key]['fecha' . $keyf] = null;
				}
				$datos_alumnos[$key]['nro'] = $key + 1;
			}
		}
		return $datos_alumnos;
	}

	function generar_pdf()
	{
		$pdf = $this->get_generador_pdf();
		$universidad = guarani::get_nombre_universidad() . "\n";
		$pdf->set_font($this->pdf_fuente);
		$pdf->set_options(array('showHeadings' => 1, 'protectRows' => 1, 'maxWidth' => 500, 'width' => 500, 'fontSize' => $this->pdf_fuente_texto, 'shadeCol' => array(0.8, 0.8, 0.8), 'cols' => array()));
		$pdf->set_text_options(array('left' => 5, 'top' => 20, 'maxWidth' => 500, 'width' => 500));
		$pdf->set_pdf_fuente_texto($this->pdf_fuente_texto);
		$pdf->set_pdf_fuente_titulo($this->pdf_fuente_titulo);
		$pdf->set_pdf_fuente_subtitulo($this->pdf_fuente_subtitulo);

		$this->pdf_encabezado = array($universidad, "Planilla de Asistencias", 110);
		$pdf->set_encabezado($this->pdf_encabezado[0], $this->pdf_encabezado[1], $this->pdf_encabezado[2]);

		$logo = kernel::localizador()->encontrar_img('www/img', 'logo_comprobante_con_bajada_nuevo.jpg');
		$pdf->set_encabezado_img($logo['path'], '103');

		// ENCABEZADO
		$data = $this->get_data();

		$datos_encabezado = $this->datos_consulta['cabecera'];

		$datos_clases = $this->datos_consulta['clases'];
		$titulos = $this->get_columnas();

		$pdf->agregar_texto("");
		$pdf->agregar_texto("Año Académico: " . $datos_encabezado['anio_academico'] . "      Período Lectivo: " . $datos_encabezado['periodo_lectivo_nombre']);
		$pdf->agregar_texto("Actividad: (" . $datos_encabezado['actividad_codigo'] . ") - " . $datos_encabezado['actividad_nombre']);
		$comision = "Comisión: " . $datos_encabezado['comision_nombre'];
		if (!empty($datos_encabezado['subcomision'])) {
			$pdf->agregar_texto($comision . "      Subcomisión: " . $datos_encabezado['subcomision_nombre']);
		} else {
			$pdf->agregar_texto($comision . "      Subcomisión: -");
		}
		if (!empty($datos_encabezado['catedra_nombre'])) {
			$pdf->agregar_texto("Cátedra: " . $datos_encabezado['catedra_nombre']);
		} else {
			$pdf->agregar_texto("Cátedra: -");
		}
		if (!empty($datos_encabezado['docentes'])) {
			$pdf->agregar_texto("Docentes: " . $datos_encabezado['docentes']);
		} else {
			$pdf->agregar_texto("Docentes: -");
		}
		if (!empty($datos_encabezado['horario_aulas'])) {
			$pdf->agregar_texto("Aula: " . $datos_encabezado['horario_aulas']);
		} else {
			$pdf->agregar_texto("Aula: -");
		}
		$pdf->agregar_texto("");
		$pdf->agregar_texto("");

		// ALUMNOS
		$pdf->agregar_tabla($data, $titulos, '');
		$pdf->agregar_texto("");
		$pdf->agregar_texto("");

		// TABLA PARA FIRMA DOCENTE
		$titulos_firma = array();
		$titulos_firma['DATOS'] = "Datos";
		foreach ($datos_clases as $key => $clase) {
			$clase = trim($clase);
			if($clase) {
				$fecha = \DateTime::createFromFormat('d/m/Y', $clase);
				$titulos_firma['fecha' . $key] = $fecha->format('d/m/y');
			}
			else{
				$titulos_firma['fecha' . $key] = '';
			}
		}

		$datos_firma = array();
		$datos_firma[0]['DATOS'] = "Tema";
		$datos_firma[1]['DATOS'] = "Firma Docente";
		foreach ($datos_clases as $key => $clase) {
			$datos_firma[0]['fecha' . $key] = null;
			$datos_firma[1]['fecha' . $key] = null;
		}

		$pdf->agregar_tabla($datos_firma, $titulos_firma, '');
		$pdf->agregar_texto("");

		$pdf->set_nombre_archivo($this->get_nombre_archivo());
		$archivo = $pdf->generar(false);
                $this->descargar_archivo($archivo, $this->get_nombre_archivo(), true);
	}

	protected function get_generador_pdf()
	{
		return new generador_pdf($this->crear_ezpdf('portrait'));
	}

	protected function crear_ezpdf($posicion_hoja = null)
	{
		$instancia = new \Cezpdf($this->pdf_hoja, $posicion_hoja);
		return $instancia;
	}

	function get_nombre_archivo()
	{
		return "planilla.pdf";
	}

}