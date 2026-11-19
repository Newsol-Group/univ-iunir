<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Alumnos
 *
 */
class cn_man_invalidar_ha extends guarani_pers_cn
{
	protected $s__desactivar_transacciones = false;

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

	function desactivar_transacciones()
	{
		$this->s__desactivar_transacciones = true;
	}

	//-----------------------------------------------------------------------------------
	//---- Listados
	//-----------------------------------------------------------------------------------
	
	function get_listado($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_alumnos_perfil_de_datos($filtro);
	}
	
	function get_actividades_aprobadas($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro = "ha.alumno = $alumno AND ha.origen_otra_propuesta = false";
		$origen_invalidar_historia_academica = true;		
		return toba::consulta_php('co_alumnos')->get_historia_academica($filtro, false, $origen_invalidar_historia_academica);
	}

	function get_actividades_regularizadas($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro = "vw_regularidades.alumno = $alumno AND vw_regularidades.origen_otra_propuesta = false";

		// Se excluyen las promociones aprobadas (comisiones que no tienen asociada la instancia CURSAR).
		$filtro .= " AND NOT (vw_regularidades.origen = '".acta::origen_promociones."' AND vw_regularidades.resultado = '".escalas_notas::resultado_aprobado."')";
		
		return toba::consulta_php('co_alumnos')->get_regularidades($filtro);
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
	
	function invalidar($alumno)
	{
		if (empty($this->s__sel_aprobadas) && empty($this->s__sel_regularizadas)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('un registro'));
			throw guarani::error($mje);
		} else {
			try {
				if (! $this->s__desactivar_transacciones) {
					toba::db()->abrir_transaccion();
				}
				/*ei_arbol($this->s__sel_aprobadas, 'aprobadas');
				ei_arbol($this->s__sel_regularizadas, 'regularizadas');

				throw new toba_error('aaa');*/
				$this->invalidar_seleccion($alumno, $this->s__sel_aprobadas);				
				$this->invalidar_seleccion($alumno, $this->s__sel_regularizadas);

				if (! $this->s__desactivar_transacciones) {
					toba::db()->cerrar_transaccion();
				}
			} catch (toba_error $e) {
				if (! $this->s__desactivar_transacciones) {
					toba::db()->abortar_transaccion();
				}
				throw $e;
			}
		}
	}
	
	function invalidar_seleccion($alumno, $seleccion)
	{		
		foreach ($seleccion as $s) {
			
			$id_acta =              $s['id_acta'] ?? null;
			$equivalencia =         $s['equivalencia'] ?? null;
                        // Este dato solo viene en el cuadro de Aprobadas
			$reconocimiento_act =   $s['reconocimiento_act'] ?? null;

			switch ($s['origen']) {
				//Actividades con Actas
				case acta::origen_regulares:
				case acta::origen_promociones:
				case acta::origen_examen:
					guarani::act('act_actas')->invalidar_detalle($id_acta, $alumno);
					break;
				
				//Equivalencias
				case acta::origen_equivalencias_regulares:
				case acta::origen_equivalencias:
				case acta::origen_equivalencias_parciales:
					guarani::act('act_equivalencias')->invalidar_detalle($equivalencia);
					break;
				
				//Actividades aprobadas.
				case  acta::origen_aprobacion_resolucion:
					guarani::act('act_aprobacion_por_resolucion')->invalidar_detalle($reconocimiento_act);
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
