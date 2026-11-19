<?php
namespace siu\operaciones\inscriptos_cursadas;

use SIU\Chulupi\kernel;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte as pagelet_reporte_padre;

class pagelet_reporte extends pagelet_reporte_padre
{
	protected $sedes = null;

	public function get_nombre()
	{
		return 'reporte';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos = array_merge($this->get_vista_form()->get_js_files(), $archivos);
		return $archivos;
	}

	public function get_datos_reporte()
	{
		return $this->controlador->get_lista_comisiones();
	}

	public function get_vista_form()
	{
		return $this->controlador->get_vista_form();
	}

	protected function configurar()
	{
		$this->set_rpp(15);
		$this->set_columnas_reporte($this->get_columnas());

		// Configuracion pdf
		$this->set_pdf_encabezado(kernel::traductor()->trans("tit_inscriptos_cursada"), "", 112);

		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
	}

	protected function get_data_reporte($inicio = 0, $todo = false)
	{
		//@todo: no tiene en cuenta los parametros
		$datos = $this->get_datos_reporte();
		$columnas = array_keys($this->get_columnas());
		$nuevo = array();
		$i = 0;

		foreach ($datos as $dato) {
			foreach ($columnas as $columna) {
				if (isset($dato[$columna]))
					$nuevo[$i][$columna] = $dato[$columna];
				else
					$nuevo[$i][$columna] = null;
			}
			if ($dato['es_subcomision'])
				$nuevo[$i]['cant_inscriptos_subcomision'] = $dato['cant_inscriptos_subcomision'];
			else
				$nuevo[$i]['cant_inscriptos_comision'] = $dato['cant_inscriptos_comision'];
			if (isset($dato['anio_academico']) && isset($dato['periodo_lectivo_nombre']))
				$nuevo[$i]['periodo_lectivo_nombre'] = $dato['anio_academico'] . ' - ' . $dato['periodo_lectivo_nombre'];

			$nuevo[$i]['orden'] = $i + 1;
			$i++;
		}

		return $nuevo;
	}

	protected function get_total_registros()
	{ 
		//@todo: hace toda la consulta solo para sacar cantidad de registros? Se deberia cachear get_data_reporte
		return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}

	public function get_columnas()
	{
		$titulo = array();
		$titulo['orden'] = array("", array('justification' => 'center', 'width' => 70));
		$titulo['actividad_orden'] = array("Actividad", array('justification' => 'center', 'width' => 70));
		$titulo['periodo_lectivo_nombre'] = array("Período Lectivo", array('justification' => 'center', 'width' => 65));
		$titulo['comision_nombre'] = array("Comisión", array('justification' => 'center', 'width' => 55));
		$titulo['subcomision_nombre'] = array("Subcomisión", array('justification' => 'center', 'width' => 75));
		$titulo['ubicacion_nombre'] = array("Ubicación", array('justification' => 'center', 'width' => 60));
		$titulo['resp_docente'] = array("Responsabilidad", array('justification' => 'center', 'width' => 60));
		$titulo['turno_nombre'] = array("Turno", array('justification' => 'center', 'width' => 50));
		$titulo['cant_inscriptos_comision'] = array("Inscriptos", array('justification' => 'center', 'width' => 70));
		return $titulo;
	}

	/*
	 * Recupera los datos
	 * @param entero $pagina
	 * Numero de página que se desea recuperar
	 * @param buleano $todo
	 * Recuperar todos los datos
	 * @return array
	 */
	private function get_data()
	{
		/** @var $rs ProcesadorVista */
		$rs = $this->get_datos_reporte();
		$rs->link('LINK', 'inscriptos_cursadas', 'info_comision'); //quiero solo el id --TODO: cambiar
		$this->sedes = array();
		$columnas = array('comision_nombre', 'subcomision_nombre', 'ubicacion_nombre', 'resp_docente', 'turno_nombre', 'cant_inscriptos_comision');
		$datos = array();
		foreach ($rs as $fila) {
			$periodo_key = $fila['anio_academico'] . ' - ' . $fila['periodo_lectivo_nombre'];
			$actividad_key = $fila['actividad_orden'];
			foreach ($columnas as $campo)
				$nuevo[$campo] = isset($fila[$campo]) ? $fila[$campo] : null;
			$nuevo['tipo_clase'] = isset($fila['tipo_clase']) ? $fila['tipo_clase'] : null;
			if ($fila['es_subcomision']) {
				$nuevo['es_subcomision'] = 1;
				$nuevo['hash'] = $fila['__ID__'];
				$nuevo['cant_inscriptos'] = $fila['cant_inscriptos_subcomision'];
			} else {
				$nuevo['es_subcomision'] = 0;
				$nuevo['hash'] = $fila['__ID__'];
				$nuevo['cant_inscriptos'] = $fila['cant_inscriptos_comision'];
			}
			$datos[$periodo_key][$actividad_key][] = $nuevo;
			$this->sedes[$fila['ubicacion']] = $fila['ubicacion_nombre'];
		}
		return $datos;
	}

	public function prepare()
	{
		parent::prepare();
		$this->data['datos'] = $this->get_data();

		$this->data['url_info_comision'] = kernel::vinculador()->crear('inscriptos_cursadas', 'info_comision');
		$this->data['url_info_subcomision'] = kernel::vinculador()->crear('inscriptos_cursadas', 'info_subcomision');

		$this->add_var_js('url_filtrar_comisiones', kernel::vinculador()->crear('inscriptos_cursadas', 'index'));
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('inscriptos_cursadas', 'generar_pdf'));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('inscriptos_cursadas', 'generar_excel'));
	}

	public function get_nombre_archivo()
	{
		return "inscriptos_cursadas";
	}

}
?>