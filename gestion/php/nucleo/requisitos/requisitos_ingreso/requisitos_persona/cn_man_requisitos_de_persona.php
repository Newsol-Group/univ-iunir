<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class cn_man_requisitos_de_persona extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'requisito_de_persona';
	}

	function get_listado($filtro)
	{
		return toba::consulta_php('co_personas')->get_personas_alumnos($filtro);
	}

	function get_datos_requisitos_aspirante_excepcion($requisito)
	{
		$datos = $this->entidad()->get_datos_requisitos_aspirante($requisito);
		foreach ($datos as $key => $dato) {
			$datos[$key]['ya_exceptuado'] = $dato['exceptuado'];
			// Si la propuesta no es visible al perfil de datos del usuario logueado, se excluye el registro.
			if (!isset($dato['propuesta_nombre'])) {
				unset($datos[$key]);
			}
		}
		return $datos;
	}

	function get_datos_requisitos_aspirante_prorroga($requisito)
	{
		$datos = $this->entidad()->get_datos_requisitos_aspirante($requisito);
		foreach ($datos as $key => $dato) {
			if (!empty($dato['fecha_prorroga'])) {
				$datos[$key]['prorrogado'] = 1;
			}
			// Si la propuesta no es visible al perfil de datos del usuario logueado, se excluye el registro.
			if (!isset($dato['propuesta_nombre'])) {
				unset($datos[$key]);
			}
		}
		return $datos;
	}

}
?>