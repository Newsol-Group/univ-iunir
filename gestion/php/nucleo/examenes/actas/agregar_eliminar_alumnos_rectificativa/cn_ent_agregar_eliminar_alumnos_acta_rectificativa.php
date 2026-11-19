<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class cn_ent_agregar_eliminar_alumnos_acta_rectificativa extends cn_ent_acta_examen
{
	protected $s__acta_referencia;
	protected $s__ids_agregados; // mantiene los ids de los alumnos asociados originales para determinar si hubo cambios.

	function get_listado($filtro)
	{
		$filtro_tipo_rectificativa = "	sga_actas.tipo_acta = '".acta::tipo_rectificativa."' AND
										sga_actas.acta_referencia IS NOT NULL";
		$filtro = $filtro ? "$filtro AND $filtro_tipo_rectificativa" : $filtro;
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro);
	}

	/**
	 * Se sobreescribe el método de carga para setear el campo externo agregado, 
	 * que se utiliza para conocer si un alumno esta asociado o no.
	 */
	function cargar($id, $parametros = array())
	{
		parent::cargar($id, $parametros);
		$this->s__acta_referencia = $parametros['acta']['acta_referencia'];
		$this->s__ids_agregados = $this->get_ids_detalle(array('id_acta' => $this->get_acta()));
		$this->modificar_filas_columna_detalle($this->s__ids_agregados, 'agregado', true);
	}

	/**
	 * Redefine el método utilizado desde el selector para recuperar los alumnos 
	 * de la mesa posibles a agregar/eliminar de la Rectificativa.
	 */
	function get_metodo_alumnos_mesa_disponibles()
	{
		$array['clase_co'] = 'co_actas';
		$array['metodo'] = 'get_alumnos_acta_examen_rectificada';
		$array['parametros'] = array($this->get_evaluacion(), $this->get_acta(), $this->get_acta_referencia());
		return $array;
	}

	function filtro_alumnos_otros_disponibles()
	{
		// El texto [EVALUACION] será reemplazado donde se use esta query por el id de evaluacion de la mesa de examen.
		$filtro = "AND	sga_alumnos.alumno NOT IN (
					-- Alumnos que no estén en actas cerradas (esto es no anuladas ya que las abiertas estan en eval_detalle)
					SELECT	sga_actas_detalle.alumno
					  FROM sga_actas 
					  JOIN sga_actas_detalle ON sga_actas_detalle.id_acta = sga_actas.id_acta
					 WHERE sga_actas.evaluacion			= [EVALUACION]
					   AND sga_actas.estado				= '" . acta::estado_cerrada . "'
					   AND sga_actas_detalle.rectificado	= '" . comunes::no . "'
					   AND sga_actas_detalle.estado		= '" . acta::detalle_estado_activo . "'
					UNION
					-- Y que no esten en actas abiertas
					SELECT sga_eval_detalle_examenes.alumno
					  FROM sga_eval_detalle_examenes
					 WHERE sga_eval_detalle_examenes.evaluacion = [EVALUACION]
					   AND sga_eval_detalle_examenes.id_acta IS NOT NULL
					)";
		return $filtro;
	}

	//-----------------------------------------------------------------------------------
	//---- Tabla de Detalle de Evaluaciones de Alumnos en Exámen ------------------------
	//-----------------------------------------------------------------------------------

	function agregar_alumnos_mesa_llamado($alumnos, $llamado_mesa)
	{
		if (!empty($alumnos)) {
			foreach ($alumnos as $alumno) {
				$this->agregar_alumno_mesa_llamado($alumno, $llamado_mesa);
			}
		}
	}

	function agregar_alumno_mesa_llamado($alumno, $llamado_mesa)
	{
		$condiciones = array('alumno' => $alumno['alumno']);
		if (!$this->existe_detalle($condiciones)) {
			$datos_detalle_original = toba::consulta_php('co_actas')->get_detalle_alumno_llamado_mesa_examen($alumno['alumno'], $llamado_mesa);
			$datos_detalle_original['inscripto'] = toba::consulta_php('co_alumnos')->tiene_inscripciones_examen($alumno['alumno'], "vw_insc_examen.llamado_mesa = $llamado_mesa") ? comunes::si : comunes::no;
			$datos_detalle_original['agregado'] = true;
			$this->agregar_detalle($datos_detalle_original);
			$this->s__alumnos_agregados[$alumno['alumno']] = true;
		} else {
			parent::agregar_alumno_mesa($alumno);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Proceso de Sincronización y actualización de la Versión del Acta -------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Guarda las modificaciones realizadas sobre el detalle de evaluaciones del Acta Rectificativa (es decir sincroniza 
	 * el DR). De ser necesario reestablece la versión actual del la misma. Para esto recibe los datos del Acta Rectificativa
	 * seleccionada editada.
	 * 
	 * @param array	datos_acta	datos básicos del Acta Rectificativa modificada.
	 */
	function guardar_acta_rectificativa_modificada($datos_acta)
	{
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();
			$this->actualizar_detalles_acta_rectificativa($this->get_acta());
			// Sincroniza, guardando las modificaciones realizadas sobre el detalle de evaluaciones de exámenes asociado al Acta Rectificativa.
			$this->sincronizar();
			// Actualiza la versión del Acta Rectificativa editada.
			$this->actualizar_version_acta_rectificativa($datos_acta);
			toba::db()->cerrar_transaccion();
			$this->activar_transacciones_anidadas();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	function eliminar_alumno_disponible($acta, $alumno)
    {
		$this->guardar_acta_rectificativa_modificada($acta);
		guarani::act('act_actas')->eliminar_eval_detalle_examen($this->get_evaluacion(), $alumno);
		unset($this->s__eliminados);
    }
	
	/**
	 *   Asocia o Disocia el acta rectificativa del detalle segun corresponda.
	 * 	 En el caso que el campo agregado del detalle sea true (se agregó al acta), se le pone el acta actual al id_acta (se asocia)
	 *   En el caso que el campo agregado del detalle sea false (se elimino del acta), y el id_acta sea el actual, se asigna null al id_acta (se disocia)
	 */
	function actualizar_detalles_acta_rectificativa($id_acta)
	{
		$condiciones = array('agregado' => true);
		$ids = $this->get_ids_detalle($condiciones);
		$this->modificar_filas_columna_detalle($ids, 'id_acta', $id_acta);

		$condiciones = array('agregado' => false, 'id_acta' => $id_acta);
		$ids = $this->get_ids_detalle($condiciones);
		$this->modificar_filas_columna_detalle($ids, 'id_acta', null);
	}

	/**
	 * Verifica si la versión impresa del Acta Rectificativa modificada coincide con su versión actual. De ser así 
	 * la versión debe modificarse a una nueva incrementándola.
	 * @param array	datos_acta	datos básicos del Acta Rectificativa modificada.
	 */
	function actualizar_version_acta_rectificativa($datos_acta)
	{
		$id = $datos_acta['id_acta'];
		$version = $datos_acta['version'];
		$version_impresa = $datos_acta['version_impresa'];
		if ($version == $version_impresa) {
			guarani::act('act_actas')->actualizar_version_acta($id, $version);
		}
	}

	/**
	 * Retorna si hubo cambios en la seleccion de alumnos, realiza la diferencia 
	 * entre los alumnos originalmente asociados y los asociados actuales.
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

	//-----------------------------------------------------------------------------------
	//---- Atributos --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_acta_referencia()
	{
		return $this->s__acta_referencia;
	}

}
?>