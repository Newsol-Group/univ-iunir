<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class cn_ent_agregar_eliminar_alumnos_acta_examen extends cn_ent_acta_examen
{
	protected $s__ids_agregados; // mantiene los ids de los alumnos asociados originales para determinar si hubo cambios.

	function get_listado($filtro)
	{
		$filtro_tipo_normal = "sga_actas.tipo_acta = '".acta::tipo_normal."'";
		$filtro = $filtro ? "$filtro AND $filtro_tipo_normal" : $filtro;
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	/**
	 * Se sobreescribe el método de carga para setear el campo externo agregado, que se utiliza para conocer si un alumno esta asociado o no.
	 *
	 * @param $id
	 * @param $parametros
	 *
	 */
	function cargar($id, $parametros = array())
	{
		parent::cargar($id, $parametros);
		$this->s__ids_agregados = $this->get_ids_detalle(array('id_acta' => $this->get_acta()));
		$this->modificar_filas_columna_detalle($this->s__ids_agregados, 'agregado', true);
	}

	//-----------------------------------------------------------------------------------
	//---- Proceso de Sincronización y actualización de la Versión del Acta -------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Guarda las modificaciones realizadas sobre el detalle de evaluaciones del Acta (es decir sincroniza el DR). De ser 
	 * necesario reestablece la versión actual del la misma. Para esto recibe los datos del Acta seleccionada editada.
	 * 
	 * @param array	datos_acta	datos básicos del Acta de exámen modificada.
	 */
	function guardar_acta_modificada($datos_acta)
	{
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();
			$this->actualizar_detalles_acta($this->get_acta());
			//Sincroniza, guardando las modificaciones realizadas sobre el detalle de evaluaciones de exámenes asociado al Acta.
			$this->sincronizar();
			//Actualiza la versión del Acta editada.
			$this->actualizar_version_acta($datos_acta);
			toba::db()->cerrar_transaccion();
			$this->activar_transacciones_anidadas();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Asocia o disocia el acta del detalle segun corresponda
	 * En el caso que el campo agregado del detalle sea true (se agregó al acta), se le pone el acta actual al id_acta (se asocia)
	 * En el caso que el campo agregado del detalle sea false (se elimino del acta), y el id_acta sea el actual, se asigna null al id_acta (se disocia)
	 */
	function actualizar_detalles_acta($id_acta)
	{
		$condiciones = array('agregado' => true);
		$ids = $this->get_ids_detalle($condiciones);
		$this->modificar_filas_columna_detalle($ids, 'id_acta', $id_acta);

		$condiciones = array('agregado' => false, 'id_acta' => $id_acta);
		$ids = $this->get_ids_detalle($condiciones);
		$this->modificar_filas_columna_detalle($ids, 'id_acta', null);
	}

	/**
	 * Verifica si la versión impresa del Acta modificada coincide con su versión actual. 
	 * De ser así la versión debe modificarse a una nueva incrementándola.
	 * 
	 * @param array	datos_acta	datos básicos del Acta de exámen modificada.
	 */
	function actualizar_version_acta($datos_acta)
	{
		$id = $datos_acta['id_acta'];
		$version = $datos_acta['version'];
		$version_impresa = $datos_acta['version_impresa'];
		if ($version == $version_impresa) {
			guarani::act('act_actas')->actualizar_version_acta($id, $version);
		}
	}

    function eliminar_alumno_disponible($acta, $alumno)
    {
		$this->guardar_acta_modificada($acta);
		guarani::act('act_actas')->eliminar_eval_detalle_examen($this->get_evaluacion(), $alumno);
		unset($this->s__eliminados);
    }
	
	/**
	 * Retorna si hubo cambios en la seleccion de alumnos, realiza la diferencia 
	 * entre los alumnos originalmente asociados y los asociados actuales.
	 *
	 * @return boolean
	 */
	function hay_cambios_seleccion_alumnos()
	{
		$agregados_nuevos = $this->get_ids_detalle(array('agregado' => true));
		$diferencia = array_merge(array_diff($this->s__ids_agregados, $agregados_nuevos), array_diff($agregados_nuevos, $this->s__ids_agregados));
		return !empty($diferencia);
	}

	function resetear()
	{
		parent::resetear();
		unset($this->s__ids_agregados);
	}
}
?>