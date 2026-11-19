<?php
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class elemento_validacion_nucleo extends entidad_validacion
{
	protected $regla = null;
	protected $cumplido;

	function search($id)
	{
		if ($this->id == $id) {
			return $this;
		}
		return null;
	}

	function set_regla($opcion, $regla)
	{
		$this->regla[$opcion] = $regla;
	}

	function cumplido($opcion)
	{
		return $this->cumplido[$opcion];
	}

	function get_observaciones($opcion)
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		if (isset($this->cumplido[$opcion])) {
			$texto .= $this->cumplido[$opcion] ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		}
		$texto .= ' '.$this->get_nombre().'</div>';
		$texto .= '</li>';
		return $texto;
	}

	function get_config_ha($opcion)
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		if (isset($this->cumplido[$opcion])) {
			$texto .= $this->cumplido[$opcion] ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		}
		$texto .= ' '.$this->get_nombre().'</div>';
		$texto .= '</li>';
		return $texto;
	}
}
?>