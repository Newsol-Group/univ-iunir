<?php 
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	-
 * 
 */
class ci_rep_requisitos_x_oper extends ci_rep_sin_listado_inicial
{
	function get_tabla_principal()
	{
		return 'sga_requisitos_conf_x_oper';
	}
	
	function get_datos($filtro)
	{
		$datos = toba::consulta_php('co_requisitos_x_operacion')->get_listado_reporte_req_x_oper($filtro);
		/**
		 * Se da a los requisitos el formato necesario para mostrarlos en el cuadro con su operador not y sus atributos 
		 * (entidad-estado / requisito-parámetros) y que el usuario pueda distinguir unívocamente cuál se visualiza.
		 */
		foreach ($datos as $key=>$req_x_op) {
			$op_not = '';
			if ($req_x_op['operador_not']) {
				$op_not = 'NO ';
			}
			if ($req_x_op['tipo'] == requisito::comp_requisito) {
				if (isset($req_x_op['parametros'])) {
					$lista_params = guarani_texto::string_armar_lista($req_x_op['parametros']);
				} else {
					$lista_params = '';
				}
				$datos[$key]['requisito_descr'] = 'Requisito: ' . $op_not . $req_x_op['requisito_nombre'] . ' ' . $lista_params; 
			} elseif ($req_x_accion['tipo'] = requisito::comp_entidad) {
				$datos[$key]['requisito_descr'] = 'Requisito: ' . $op_not . $req_x_op['requisito_nombre'] . ' - ' . $req_x_op['estado_nombre']; 
			}
		}
		return $datos;
	}
}