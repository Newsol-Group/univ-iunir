<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Actas
 * 
 */
class cn_man_administrar_actas_fuera_calendario extends cn_manejador_entidad
{
	function cargar_atributos_parametros_acta()
	{
		$operacion = guarani::get_id_operacion();
		// Cuando hay acta creada, se obtienen las propuestas de la mesa para usar como atributos de parámetros 
		$propuestas = aplanar_matriz($this->entidad()->get_datos_mesa_propuestas(), 'propuesta');
		$propuestas = $this->get_propuestas_distintas($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}

	function cargar_atributos_parametros_actividad($elemento)
	{
		$operacion = guarani::get_id_operacion();
		// Cuando no hay acta creada, se obtienen de las propuestas de la actividad para usar como atributos de parámetros 
		$propuestas = aplanar_matriz(toba::consulta_php('co_propuestas')->get_propuestas_por_elemento_para_examen($elemento), 'propuesta');
		$propuestas = $this->get_propuestas_distintas($propuestas);
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras); 
	}
	
	/**
	 * Obtiene las distintas propuestas asociadas a la actividad o a la mesa
	 * 
	 * @param array $propuestas
	 * @return array
	 */
	protected function get_propuestas_distintas($propuestas)
	{
		$salida = array();
		foreach ($propuestas as $propuesta) {
			if (!in_array($propuesta, $salida)) {
				$salida[] = $propuesta;
			}
		}
		return $salida;
	}

	function get_indice_instancia()
	{
		return 'acta_fuera_calendario';
	}

	function get_listado($filtro)
	{
		// Se buscan las actas abiertas sin un llamado-turno de examen asociado.
		$where = "vw_mesas_examen.llamado IS NULL";
		$where .= " AND vw_mesas_examen.mesa_examen_en_turno = '" . comunes::no . "'";
		$where .= " AND sga_actas.estado = '" . acta::estado_abierta . "'";
		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}
		return toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($where);
	}

	function get_listado_actividades($filtro = '')
	{
		// Se buscan las Actividades a las cuales se les puede asociar el Acta a crear.
		$where = '';

		// Las Actividades que no estén "Disponibles para" Mesas de Examen no deben listarse nunca. 
		$where .= " sga_elementos.disponible_para IN ('" . elemento::solo_para_creacion_mesas_de_examen . "', '" . elemento::para_mesas_de_examen_y_comisiones . "')";

		// Las Actividades que tengan el campo "Requiere examen" en "No", no deben listarse nunca. 
		$where .= " AND sga_elementos_plan.requiere_examen = '" . comunes::si . "'";

		// Las Actividades dadas de baja no deben listarse nunca.
		$where .= " AND vw_actividades_plan.estado <> '" . elemento::estado_baja . "'";

		// Las Actividades deben estar asociadas a al menos un Plan de Estudios en una Versión Activa, ya sea vigente o no vigente.
		$where .= " AND sga_planes_versiones.estado IN ('" . planes::estado_activo . "', '" . planes::estado_activo_vigente . "')";
		$where .= " AND sga_planes.estado IN ('" . planes::estado_activo . "', '" . planes::estado_activo_vigente . "')";
		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}

		return toba::consulta_php('co_elementos')->get_actividades_propuesta_plan_con_ra_ubicacion($where);
	}

	/**
	 * En el Alta de Acta-Mesa-Llamado debe sincronizarse primero el DR para generar la Evaluación Automática asociada
	 * al Llamado Mesa a la cual se asignará el Acta. Luego, recuperando el id de Acta generada, se vuelve a cargar el
	 * DR para asociarle a la Evaluación automática los detalles de Alumnos mantenidos en un arreglo en memoria.
	 * En caso de que el nro de Acta se generara de forma automática, también se asigna en este momento debido a que los
	 * parámetros para generarlo involucran al Llamado y la Mesa recién creados.
	 * 
	 */
	function generar_acta()
	{
		try {
			$this->desactivar_transacciones_anidadas();
			toba::db()->abrir_transaccion();
			
			// Sincroniza el DR recuperando la clave primaria de la tabla padre.
			// Se sincroniza de un modo especial en el alta ya que no coincide con el DR definido
			// Primero se sincronizan las tablas explicitadas y despues el DR completo
			$mesa = $this->entidad()->sincronizar_mesa();

			$this->entidad()->set_datos_llamado_mesa($mesa);

			$datos_mesa = $this->entidad()->get_datos_mesa();
			$datos_posibles = toba::consulta_php('co_propuestas')->get_propuestas_por_elemento_para_examen($datos_mesa['elemento']);
			foreach ($datos_posibles as $dato) {
				$this->entidad()->set_datos_mesa_propuestas($dato);
			}

			$llamado = $this->entidad()->sincronizar_llamado_mesa();

			$this->entidad()->set_datos_acta($llamado);
			$this->entidad()->sincronizar_acta();

			if (!$this->entidad()->hay_cambios_relacion()) {
				$clave = $this->entidad()->recuperar_clave_db();
			} else {
				$clave = $this->entidad()->sincronizar();
			}

			// Carga nuevamente la relación con la evaluación automática para el llamado mesa generado.
			$this->entidad()->cargar(array($clave['id_acta']));

			// Verifica si debe generarse el nro de acta automático.
			if (guarani::parametros()->get_valor('exa_carga_manual_nro_acta_examen') == comunes::no) {
				$llamado_mesa = $this->entidad()->get_llamado_mesa();
				$nro_acta = $this->generar_nro_acta(acta::tipo_normal, $llamado_mesa);
				$this->entidad()->set_nro_acta($nro_acta);
			}
			// Se setea el id de Evaluación automática generada por trigger para el Llamado Mesa en el Acta.
			$this->entidad()->set_id_eval_automatica_acta();

			// Vuelve a sincronizar el DR para terminar.
			$this->entidad()->sincronizar();
			$this->resetear();
			toba::db()->cerrar_transaccion();
			$this->activar_transacciones_anidadas();
			return $clave['id_acta'];
		} catch (toba_error $e) {
			$this->entidad()->deshacer_registro_insercion_filas_mesa();
			$this->entidad()->deshacer_registro_insercion_filas_llamado_mesa();
			$this->entidad()->deshacer_registro_insercion_filas_acta();
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Genera un nuevo número de acta
	 */
	function generar_nro_acta($tipo_acta, $llamado_mesa)
	{
		$parametros['origen'] = acta::origen_examen;
		$parametros['tipo_acta'] = $tipo_acta;
		$parametros['llamado_mesa'] = $llamado_mesa;

		try {
			return toba::consulta_php('co_actas')->generar_nro_acta($parametros);
		} catch (toba_error $e) {
			throw guarani::error(guarani_mensajes::get('800_error_generar_nro_acta'));
		}
	}

	function hay_cambios()
	{
		$acta = $this->entidad()->get_datos_acta();
		if ($this->entidad()->hay_cambios()) {
			// Sólo se incrementa la versión si la versión impresa y versión son iguales
			if ($acta['version'] == $acta['version_impresa']) {
				$acta['version'] = $acta['version'] + 1;
				$this->entidad()->set_datos_acta($acta);
			}
		}
	}

}
?>
