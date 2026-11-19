<?php
/**
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos de Ingreso
 */
class ci_rep_ingresantes_adeudan_requisito extends ci_rep_sin_listado_inicial 
{
	function get_tabla_principal()
	{
		return 'sga_propuestas_aspira';
	}
	
	function get_datos($filtro)
	{
		$columna_requisito = $this->dep('filtro')->columna('requisito')->get_estado();
		$datos_requisito = current(toba::consulta_php('co_requisitos')->get_datos_requisito($columna_requisito['valor']));
		if ($datos_requisito['requisito_tipo'] == requisito::tipo_procesos) {
			$datos = toba::consulta_php('co_requisitos_ingreso')->get_ingresantes_adeudan_requisito($filtro);
			foreach ($datos as $clave => $valor) {
				$regla = guarani::regla($datos_requisito['regla']);
				$regla->set_parametros($valor['parametros']);

				$parametros_contexto['fecha'] = $valor['fecha_tope_presentacion'];
				$parametros_contexto['persona'] = $valor['persona'];
				$parametros_contexto['propuesta'] = $valor['propuesta'];
				$parametros_contexto['anio_academico'] = $valor['anio_academico'];
				$parametros_contexto['plan_version'] = $valor['plan_version'];
				$regla->set_parametros_contexto($parametros_contexto);

				if ($regla->validar()) {
					unset($datos[$clave]);
				}
			}
		} else {
			$filtro .= " AND NOT f_requisito_presentado(sga_propuestas_aspira.persona, sga_requisitos_aspirante.requisito, CURRENT_DATE, sga_propuestas_aspira.propuesta_aspira, 
			                     GREATEST(sga_requisitos_aspirante.fecha_prorroga, COALESCE(sga_requisitos_ingreso_excep.fecha_tope, vw_periodos_insc_propuesta.fecha_cierre_requisitos)))";
			$datos = toba::consulta_php('co_requisitos_ingreso')->get_ingresantes_adeudan_requisito($filtro);
		}
		return $datos;
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos = $datos;
			$filtro_ra = '';
			if (isset($datos['responsable_academica'])) {
				$filtro_ra .= " AND EXISTS (SELECT * FROM sga_propuestas_ra WHERE sga_propuestas_ra.propuesta = sga_propuestas.propuesta AND sga_propuestas_ra.responsable_academica = '{$datos['responsable_academica']['valor']}')";
				$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			}
			$this->s__filtro = "{$this->dep('filtro')->get_sql_where()} $filtro_ra";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
}
?>