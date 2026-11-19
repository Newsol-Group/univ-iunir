<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class cn_man_convalidar_ha extends guarani_pers_cn
{
	protected $s__datos;
	protected $s__sel_aprobadas = array();
	protected $s__sel_regularizadas = array();

	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_alumnos');
		} else {
			parent::etiquetas($dep);
		}
	}

	function unset_vars()
	{
		unset($this->s__datos);
		unset($this->s__sel_aprobadas);
		unset($this->s__sel_regularizadas);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Consultas
	//-----------------------------------------------------------------------------------
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($filtro);
	}
	
	function get_actividades_inv_aprobadas($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro = "vw_hist_academica_inv.alumno = $alumno";
		return toba::consulta_php('co_alumnos')->get_historia_academica_invalidada($filtro);
	}

	function get_actividades_inv_regularizadas($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro = "vw_regularidades_inv.alumno = $alumno";

		// Se excluyen las promociones aprobadas (comisiones que no tienen asociada la instancia CURSAR).
		$filtro .= " AND NOT (vw_regularidades_inv.origen = '".acta::origen_promociones."' AND vw_regularidades_inv.resultado = '".escalas_notas::resultado_aprobado."')";

		return toba::consulta_php('co_alumnos')->get_regularidades_invalidadas($filtro);
	}

	//-----------------------------------------------------------------------------------
	//---- Seteos
	//-----------------------------------------------------------------------------------
	
	function set_datos($datos)
	{
		$this->s__datos = $datos;
	}
	
	function set_seleccion_aprobadas($seleccion)
	{
		$this->s__sel_aprobadas = $seleccion;
	}
	
	function set_seleccion_regularizadas($seleccion)
	{
		$this->s__sel_regularizadas = $seleccion;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Proceso de Invalidación
	//-----------------------------------------------------------------------------------
	
	function convalidar($alumno)
	{
		if (empty($this->s__sel_aprobadas) && empty($this->s__sel_regularizadas)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un registro'));
			throw guarani::error($mje);
		} else {
			try {
				toba::db()->abrir_transaccion();

				$this->convalidar_seleccion($alumno, $this->s__sel_aprobadas);
				$this->convalidar_seleccion($alumno, $this->s__sel_regularizadas);

				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				throw $e;
			}
		}
	}
	
	function convalidar_seleccion($alumno, $seleccion)
	{
		foreach ($seleccion as $s) {
			$id_acta =  !isset($s['id_acta']) ? null : $s['id_acta'];
			$equivalencia =  !isset($s['equivalencia']) ? null : $s['equivalencia'];
			$reconocimiento_act =  !isset($s['reconocimiento_act']) ? null : $s['reconocimiento_act'];
			
			switch ($s['origen']) {
				//Actividades con Actas
				case acta::origen_regulares:
				case acta::origen_promociones:
				case acta::origen_examen:
					guarani::act('act_actas')->convalidar_detalle($id_acta, $alumno);
					break;
				
				//Equivalencias
				case acta::origen_equivalencias_regulares:
				case acta::origen_equivalencias:
				case acta::origen_equivalencias_parciales:
					guarani::act('act_equivalencias')->convalidar_detalle($equivalencia);
					break;
				
				//Actividades aprobadas.
				case  acta::origen_aprobacion_resolucion:
					guarani::act('act_aprobacion_por_resolucion')->convalidar_detalle($reconocimiento_act);
					break;
			} 
			
			/*
			 * Inserto el movimiento en movimiento_ha
			 */			
			$usuario_responsable = isset($this->s__datos['usuario_responsable']) ? $this->s__datos['usuario_responsable'] : null;
			guarani::act('act_alumnos')->insertar_movimiento_ha($alumno, $this->s__datos['motivo_movimiento'], $this->s__datos['documento'], $id_acta, $equivalencia, $reconocimiento_act, null, $this->s__datos['observaciones'], $usuario_responsable);
		}
	}
}
?>
