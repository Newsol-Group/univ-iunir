<?php
/**
 *
 * 
 * @package 	Requisitos
 * @subpackage 	-
 */
class ci_rep_requisitos_por_propuesta extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_requisitos';
	}

	function get_datos($filtro)
	{
		$datos = toba::consulta_php('co_requisitos')->get_requisitos_propuesta($filtro);
		foreach ($datos as $clave => $valor) {
			$operador_not = '';
			if ($valor['operador_not']) {
				$operador_not = comunes::no_desc;
			}
			if ($valor['tipo'] == requisito::comp_requisito) {
				$lista_params = isset($valor['parametros']) ? guarani_texto::string_armar_lista($valor['parametros']) : '';
				$datos[$clave]['requisito_entidad_estado'] = "$operador_not {$valor['requisito_nombre']} $lista_params";
			} else {
				$datos[$clave]['requisito_entidad_estado'] = "$operador_not {$valor['requisito_nombre']} - {$valor['estado_nombre']}";
			}
		}
		return $datos;
	}

	function evt__aplanar()
	{
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (empty($perfil_datos)) {
			$aplanador = toba::cn('proceso_aplanado_requisitos_x_accion');
			$aplanador->procesar();
		} else {
			throw new toba_error(guarani_mensajes::get('800_aplanado_perfil_datos'));
		}
	}
}
?>