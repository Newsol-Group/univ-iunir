<?php
/**
 *
 * 
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 * 
 */
class ci_rep_requisitos_ingreso_por_propuesta extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_requisitos_propuestas';
	}

	function get_datos($filtro)
	{
		return toba::consulta_php('co_requisitos_ingreso')->get_requisitos_ingreso_propuesta($filtro);
	}

	function evt__aplanar()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (empty($perfil_datos)) {
			// Se ejecuta el proceso de aplanado del requisito de ingreso.
			$aplanador = toba::cn('proceso_aplanado_requisitos_ingreso');
			$aplanador->procesar(array('requisito_para' => requisito::requisito_propuesta_para_ingreso));
		} else {
			throw new toba_error(guarani_mensajes::get('800_aplanado_perfil_datos'));
		}
	}
}
?>