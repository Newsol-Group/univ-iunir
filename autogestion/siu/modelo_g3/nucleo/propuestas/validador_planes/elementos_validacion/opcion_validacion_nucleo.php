<?php
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class opcion_validacion_nucleo extends entidad_validacion
{
	protected $hijos;
	protected $estado;

	function agregar_hijo($e)
	{
		$this->hijos[] = $e;
	}
	
	function get_hijos()
	{
		return $this->hijos;
	}

	function validar($alumno=null, $caso_test=null)
	{
		$cant_req_no_cumplidos = 0;
		foreach ($this->hijos as $hijo) {
			if (! empty($caso_test)) {
				$hijo->validar_caso_test($caso_test, $this->id);
			} else {
				$hijo->validar($alumno, $this->id);
			}
			if (! $hijo->cumplido($this->id)) {
				$cant_req_no_cumplidos++;
			}
		}
		$this->estado = $cant_req_no_cumplidos == 0;
		if ($this->estado) {
			$mensaje = 'Opción cumplida.';
		} else {
			$mensaje = ($cant_req_no_cumplidos == 1) ? "Opción no cumplida. Existe un requisito no cumplido." : "Opción no cumplida. Existen $cant_req_no_cumplidos requisitos no cumplidos.";
		}
		$this->set_observaciones($mensaje);
		return $this->estado;
	}

	function get_observaciones()
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		$texto .= $this->estado ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		$texto .= ' Opción '.$this->get_nombre().'</div>';
		$texto .= '<div>'. $this->get_texto_observaciones().'</div>';
		if (guarani_arreglos::count($this->hijos) > 0) {
			$texto .= '<ul>';
			foreach ($this->hijos as $hijo) {
				$texto .= $hijo->get_observaciones($this->id);	
			}
			$texto .= '</ul>';
		}
		$texto .= '</li>';
		return $texto;
	}

	function get_config_ha()
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		$texto .= $this->estado ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		$texto .= ' Opción '.$this->get_nombre().'</div>';
		$texto .= '<div>'. $this->get_texto_observaciones().'</div>';
		if (guarani_arreglos::count($this->hijos) > 0) {
			$texto .= '<ul>';
			foreach ($this->hijos as $hijo) {
				$texto .= $hijo->get_config_ha($this->id);	
			}
			$texto .= '</ul>';
		}
		$texto .= '</li>';
		return $texto;
	}
}
?>