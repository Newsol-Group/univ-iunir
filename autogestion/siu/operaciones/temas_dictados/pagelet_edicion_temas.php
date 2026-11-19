<?php
namespace siu\operaciones\temas_dictados;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_edicion_temas extends pagelet
{
	protected $encabezado;
	protected $renglon = false;

	function get_nombre()
	{
		return 'edicion_temas';
	}

	/**
	 * @return carga_temas
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
		return kernel::vinculador()->crear('temas_dictados', 'index', array($this->controlador->hash_comision, $this->controlador->clase));
	}


	function prepare()
	{
		$this->data = array();
		$this->data['clase_detalle'] = $this->get_clase_detalle();
  
		$this->data['clase_id'] = $this->controlador->clase;
		$this->data['url_guardar'] = $this->get_url_guardar();
	}
}
?>
