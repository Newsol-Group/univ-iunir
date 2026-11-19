<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 * 
 */
class cn_man_vencimiento_regularidad extends cn_manejador_entidad
{	
	function get_indice_instancia()
	{
		return 'vencimiento_regularidad';
	}
	
	function guardar()
	{
		$this->desactivar_transacciones_anidadas();
		try {
			toba::db()->abrir_transaccion();

			$datos = $this->entidad()->get_datos();
			
			$this->entidad()->sincronizar();
			
			if (!is_null($datos['id_acta'])) {
				// Se actualiza el detalle de acta
				guarani::act('act_actas')->actualizar_fecha_vigencia_detalle($datos['id_acta'], $datos['alumno'], $datos['fecha_vigencia_nuevo']);
			} elseif (!is_null($datos['equivalencia'])) {
				// Se actualiza el detalle de equivalencia
				guarani::act('act_equivalencias')->actualizar_fecha_vigencia_equivalencia($datos['equivalencia'], $datos['fecha_vigencia_nuevo']);
			}

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- LISTADOS
	//-----------------------------------------------------------------------------------

	/**
	 * Obtiene un listado de alumnos según filtro especificado.
	 *
	 * @param 	string 	$filtro
	 * @return	array
	 */
	function get_listado_preseleccion($filtro)
	{
		return toba::consulta_php('co_alumnos')->get_listado($filtro);
	}

	/**
	 * Obtiene el listado de actividades regularizadas para un alumno dado.
	 *
	 * @param 	int		$alumno
	 * @return 	array
	 */
	function get_listado_vencimiento_regularidad($alumno)
	{
		// Filtrar por alumno.
		$alumno = toba::db()->quote($alumno);
		return toba::consulta_php('co_alumnos')->get_regularidades_equiv_parciales_a_prorrogar_vencimiento($alumno);
	}

	/**
	 * Obtiene un listado con las modificaciones de vencimiento de regularidad de un alumno para un registro de acta
	 * o equivalencia dados.
	 *
	 * @param 	int	 	$alumno
	 * @param 	int 	$id_acta
	 * @param 	int 	$equivalencia
	 * @return 	array
	 */
	function get_listado_modificatorias($alumno, $id_acta=null, $equivalencia=null)
	{
		$alumno = toba::db()->quote($alumno);
		$filtro = "sga_regularidades_venc.alumno = $alumno";

		if (!is_null($id_acta)) {
			$id_acta = toba::db()->quote($id_acta);
			$filtro .= " AND sga_regularidades_venc.id_acta = $id_acta";
		} elseif (!is_null($equivalencia)) {
			$equivalencia = toba::db()->quote($equivalencia);
			$filtro .= " AND sga_regularidades_venc.equivalencia = $equivalencia";
		}

		return toba::consulta_php('co_vencimientos_regularidad')->get_listado($filtro);
	}
}	
?>