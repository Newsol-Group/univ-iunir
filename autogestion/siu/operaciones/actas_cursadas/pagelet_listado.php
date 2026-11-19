<?php
namespace siu\operaciones\actas_cursadas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\guarani;

class pagelet_listado extends pagelet
{
	public $actividad_elemento;
	public $periodo_lectivo;
	
	function get_nombre()
	{
		return 'listado';
	}

	function get_js_files()
	{
		$archivos = parent::get_js_files();
		$archivos[] = kernel::vinculador()->vinculo_recurso("js/plugin_autocompletar.js");
		return $archivos;
	}

	function get_actividad()
	{
		return $this->controlador->get_datos_actividad($this->actividad_elemento);
	}

	function get_per_lec()
	{
		return $this->periodo_lectivo;
	}

	function get_periodos_lectivos()
	{
		return $this->controlador->get_periodos_lectivos();
	}

	function prepare()
	{
		$this->add_var_js('url_buscar_actividades', kernel::vinculador()->crear('actas_cursadas', 'buscar_actividades'));
		$this->add_var_js('url_listado', kernel::vinculador()->crear('actas_cursadas', 'index'));
		$this->add_var_js('url_acta_detalle', kernel::vinculador()->crear('actas_cursadas', 'acta'));
		$this->add_var_js('url_generar_pdf', kernel::vinculador()->crear('actas_cursadas', 'generar_pdf_listado'));
		$this->add_var_js('url_generar_xls', kernel::vinculador()->crear('actas_cursadas', 'generar_xls_listado'));

		$actividad = $this->get_actividad();
		$this->add_var_js('actividad_nombre_default', $actividad ? $actividad['nombre']." (".$actividad['codigo'].")" : '');
		$this->add_var_js('actividad_elemento_default', $actividad ? $actividad['elemento'] : '');
		$this->data['titulos'] = $this->controlador->get_columnas();
	}
}
?>