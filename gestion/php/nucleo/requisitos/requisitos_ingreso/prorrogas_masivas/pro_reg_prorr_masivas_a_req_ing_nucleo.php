<?php
/**
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 */
class pro_reg_prorr_masivas_a_req_ing_nucleo extends guarani_procesos_bk
{
	protected $requisitos_aspirante;
	protected $parametros_tabla;

	const nombre = 'pro_reg_prorr_masivas_a_req_ing';

	function set_datos($datos = array())
	{
		$this->requisitos_aspirante = $datos['requisitos_aspirante'];
		$this->parametros_tabla = $datos['parametros_tabla'];
	}

	function procesar()
	{
		$paso  = 100/guarani_arreglos::count($this->requisitos_aspirante);
		$array = array();

		foreach ($this->requisitos_aspirante as $valor) {			
            $estado = $this->get_estado();
            if (isset($estado['abortar']) && $estado['abortar']) {
                $this->abortar = true;
                break;
            }
			$es_inscrip_pendiente = $this->es_inscripcion_pendiente($valor['propuesta_aspira']);
			if ($es_inscrip_pendiente){
				$registro = array();
				$registro['requisito_aspirante'] = $valor['requisito_aspirante'];
				array_push($array, $registro);
			}
			$this->log_estado->incrementar_progreso($paso);
		}

		if ($array != array()) {
			$condicion_in = guarani_sql::armar_condicion_in($array, 'sga_requisitos_aspirante', 'requisito_aspirante', 'requisito_aspirante');
			guarani::act('act_requisitos_aspirante')->registrar_prorrogas($condicion_in, $this->parametros_tabla);
		}
	}

	function es_inscripcion_pendiente($propuesta_aspira)
	{
		$datos_prop = toba::consulta_php('co_propuestas')->get_datos_propuesta_aspira($propuesta_aspira);
		return $datos_prop[0]['resultado_asp'] == propuesta::inscripcion_pendiente;
	}
}
?>