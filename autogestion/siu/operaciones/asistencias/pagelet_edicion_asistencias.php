<?php
namespace siu\operaciones\asistencias;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_edicion_asistencias extends pagelet
{
	protected $encabezado;
	protected $renglon = false;

	function get_nombre()
	{
		return 'edicion_asistencias';
	}

	/**
	 * @return carga_notas_cursada
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	protected function get_clase_encabezado()
	{
		return $this->controlador->get_clase_encabezado();
	}

	protected function get_clase_detalle()
	{
		$detalle = $this->controlador->get_clase_detalle();

		return $detalle;
	}

	protected function get_id_clase()
	{
		return $this->controlador->clase;
	}

	protected function get_url_guardar()
	{
		return kernel::vinculador()->crear('asistencias', 'index', array($this->controlador->hash_comision, $this->controlador->clase));
	}

	function set_highlight($renglon)
	{
		$this->renglon = $renglon;
	}

	function prepare()
	{
		$this->data = array();
		$this->data['clase_detalle'] = $this->get_clase_detalle();

		$this->data['clase_id'] = $this->controlador->clase;
		$this->data['url_guardar'] = $this->get_url_guardar();
		$this->add_var_js('highlight_renglon', $this->renglon);
	}

	function get_tipo_carga()
	{
		return $this->controlador->get_tipo_carga();
	}

	function get_valor_defecto()
	{
		return $this->controlador->get_valor_defecto();
	}

	function get_cant_horas_dictadas()
	{
		return $this->controlador->get_cant_horas_dictadas();
	}

}
