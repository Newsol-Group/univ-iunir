<?php
namespace siu\operaciones\inscriptos_examenes;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\modelo\ProcesadorVista;
use SIU\Chulupi\util\u;
use siu\operaciones\_comun\operaciones\reporte\pagelet_reporte as pagelet_reporte_padre;
use siu\modelo\datos\catalogo;

class pagelet_reporte extends pagelet_reporte_padre
{

	//private $sedes = null;

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
		return $this->controlador->get_lista_mesas();
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
		$this->set_pdf_encabezado(kernel::traductor()->trans("inscriptos_examenes"), "", 112);
		$this->set_pdf_fuente("Courier.afm");
		$this->set_pdf_fuente_texto(10);
		$this->set_pdf_fuente_titulo(13);
		$this->set_pdf_fuente_subtitulo(11);
		$this->set_pdf_hoja("a4");
	}

	public function get_columnas()
	{
		$titulo = array();
		$titulo['fecha_display'] = array("Fecha", array('justification' => 'center', 'width' => 70));
		$titulo['llamado_nombre'] = array("Llamado", array('justification' => 'center', 'width' => 70));
		$titulo['turno_examen_nombre'] = array("Turno", array('justification' => 'center', 'width' => 50));
		$titulo['mesa_examen_nombre'] = array("Mesa", array('justification' => 'center', 'width' => 70));
		$titulo['actividad_nombre'] = array("Actividad", array('justification' => 'center', 'width' => 90));
		$titulo['ubicacion_nombre'] = array("Ubicación", array('justification' => 'center', 'width' => 65));
		$titulo['rol_docente'] = array("Rol", array('justification' => 'center', 'width' => 70));
		$titulo['cant_inscriptos'] = array("Inscriptos", array('justification' => 'center', 'width' => 80));
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
		$data = $this->get_datos_reporte();
		$data->link('LINK', 'inscriptos_examenes', 'info_mesa');
		$mesas = array();
		foreach ($data as $mesa) {
			$mesas[$mesa['llamado_nombre']]['llamado'] = $mesa['llamado_nombre'];
			$mesas[$mesa['llamado_nombre']]['turno_examen_nombre'] = $mesa['turno_examen_nombre'];
			$mesas[$mesa['llamado_nombre']]['actividades'][$mesa['actividad_nombre'].' ('.$mesa['actividad_codigo'].')'][] = $mesa;
		}
		return $mesas;
	}
	
	protected function get_data_reporte($inicio = 0, $todo = false)
	{
		return $this->get_datos_reporte();
	}

	protected function get_total_registros()
	{
		return \guarani_arreglos::count($this->get_data_reporte(0, true));
	}

	public function prepare()
	{
		parent::prepare();
		$this->data['datos'] = $this->get_data();
		$this->add_var_js('url_filtrar_mesas', kernel::vinculador()->crear('inscriptos_examenes', 'index'));
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('inscriptos_examenes', 'generar_pdf'));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('inscriptos_examenes', 'generar_excel'));
	}

	public function get_nombre_archivo()
	{
		return "inscriptos_examenes";
	}
}

?>