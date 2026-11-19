<?php
/**
 * 
 *
 *
 * @package Propuestas
 * @subpackage Validador Planes
 */
class certificado_validacion_nucleo extends entidad_validacion
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
		$aprobadas = 0;
		foreach ($this->hijos as $hijo) {
			if ($hijo->validar($alumno, $caso_test)) {
				$aprobadas++;
			}
		}
		$this->estado = $aprobadas > 0;
		$mensaje = $this->estado ? 'Certificado obtenido.' : 'Certificado no obtenido. Ninguna de las opciones ha sido cumplida.';
		$this->set_observaciones($mensaje);
		return $this->estado;
	}
	
	function get_observaciones()
	{
		$texto = '<li>';
		$texto .= "<div style='font-weight: bold'>";
		$texto .= $this->estado ? toba_recurso::imagen_toba('tilde.gif',true) : toba_recurso::imagen_toba('error.png',true);
		$texto .= ' '.$this->get_nombre().'</div>';
		$texto .= '<div>'.$this->get_texto_observaciones().'</div>';
		if (guarani_arreglos::count($this->hijos) > 0) {
			$texto .= '<ul>';
			foreach ($this->hijos as $hijo) {
				$texto .= $hijo->get_observaciones();	
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
		$texto .= ' '.$this->get_nombre().'</div>';
		$texto .= '<div>'.$this->get_texto_observaciones().'</div>';
		if (guarani_arreglos::count($this->hijos) > 0) {
			$texto .= '<ul>';
			foreach ($this->hijos as $hijo) {
				$texto .= $hijo->get_config_ha();	
			}
			$texto .= '</ul>';
		}
		$texto .= '</li>';
		return $texto;
	}
}
?>