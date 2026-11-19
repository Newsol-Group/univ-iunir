<?php
/**
 *
 * @package Administración
 * @subpackage Circuitos de Trámite
 */
class co_circuitos_tramite
{
	//------------------------------------------------------------------------
	//-- Circuitos de Trámite
	//------------------------------------------------------------------------

	function get_listado_circuitos_egreso($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		mce_circuitos.circuito,
							mce_circuitos.nombre,
							".guarani_sql::get_case_si_no('mce_circuitos', 'activo', 'activo')." 
				FROM		mce_circuitos
				WHERE		mce_circuitos.tipo = '".circuito_tramite::tipo_egreso."'
				$where
				ORDER BY	mce_circuitos.nombre
		";
		return guarani::db()->consultar($sql);
	}

	function get_listado_circuitos_tesis($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		mce_circuitos.circuito,
							mce_circuitos.nombre,
							".guarani_sql::get_case_si_no('mce_circuitos', 'activo', 'activo')." 
				FROM		mce_circuitos
				WHERE		mce_circuitos.tipo = '".circuito_tramite::tipo_tesis."'
				$where
				ORDER BY	mce_circuitos.nombre
		";
		return guarani::db()->consultar($sql);
	}
	
	function get_circuitos_egreso_activos()
	{
		$sql = "SELECT		mce_circuitos.circuito as valor,
							mce_circuitos.nombre as descr
				FROM		mce_circuitos
				WHERE		mce_circuitos.tipo = '".circuito_tramite::tipo_egreso."' AND
							mce_circuitos.activo = '".comunes::si."'
				ORDER BY	mce_circuitos.nombre
		";
    	return guarani_db::consultar($sql);
	}
	
	function get_circuitos_tesis_activos()
	{
		$sql = "SELECT		mce_circuitos.circuito AS valor,
							mce_circuitos.nombre AS descr
				FROM		mce_circuitos
				WHERE		mce_circuitos.tipo = '".circuito_tramite::tipo_tesis."' AND
							mce_circuitos.activo = '".comunes::si."'
				ORDER BY	mce_circuitos.nombre
		";
    	return guarani_db::consultar($sql);
	}

	function get_circuitos_tipos()
	{
		$sql = "SELECT		mce_circuitos_tipos.tipo	AS valor,
							mce_circuitos_tipos.nombre	AS descr
				FROM		mce_circuitos_tipos
				ORDER BY	mce_circuitos_tipos.nombre
		";
    	return guarani_db::consultar($sql);
	}
	
	//------------------------------------------------------------------------
	//-- Caminos de Circuitos de Trámite
	//------------------------------------------------------------------------

	/**
 	  * Retorna el camino correspondiente a un circuito, un estado inicial (posiblemente vacío) y una acción.
      *	@param integer código de circuito.
      * @param integer código de estado inicial (puede ser nulo).
      * @param integer código de acción.
      *
    */
	function get_camino_por_circuito_estado_accion($circuito, $estado, $accion)
	{
		$circuito = toba::db()->quote($circuito);
		if (empty($estado)) {
			$where = ' AND mce_caminos.estado_inicial IS NULL';
		} else {
			$estado = toba::db()->quote($estado);
			$where = " AND mce_caminos.estado_inicial = $estado";
		}
		$accion = toba::db()->quote($accion);
		$sql = "SELECT	mce_caminos.camino,
						mce_caminos.controlar_pago,
						mce_caminos.enviar_aviso_alumno,
						mce_caminos.recalcular_histacad
				FROM	mce_caminos
				WHERE	mce_caminos.circuito = $circuito AND
						mce_caminos.accion = $accion
						$where
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Determina si el estado es final (no tiene transiciones salientes hacia otro estado) en el circuito dado.
	 * 
	 * @param integer $estado
	 * @param integer $circuito
	 * @return boolean
	 */
	function es_estado_final_de_circuito($estado, $circuito)
	{
		$estado = toba::db()->quote($estado);
		$circuito = toba::db()->quote($circuito);
		$sql = "SELECT	COUNT(mce_caminos.camino) as cant
				FROM	mce_caminos
				WHERE	mce_caminos.circuito = $circuito AND
						mce_caminos.estado_inicial = $estado AND
						mce_caminos.estado_final <> $estado
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] == 0);
	}

	/**
	 * Determina si el estado tiene transiciones recursivas (estado inicial = estado final) en el circuito dado.
	 * 
	 * @param integer $estado
	 * @param integer $circuito
	 * @return boolean
	 */
	function estado_tiene_transiciones_recursivas_circuito($estado, $circuito)
	{
		$estado = toba::db()->quote($estado);
		$circuito = toba::db()->quote($circuito);
		$sql = "SELECT	COUNT(mce_caminos.camino) as cant
				FROM	mce_caminos
				WHERE	mce_caminos.circuito = $circuito AND
						mce_caminos.estado_inicial = $estado AND
						mce_caminos.estado_final = $estado
		";
		$rs = guarani_db::consultar_fila($sql);
		return ($rs['cant'] > 0);
	}

	//------------------------------------------------------------------------
	//-- Acciones de Circuitos de Trámite
	//------------------------------------------------------------------------

	function get_listado_acciones($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = 'SELECT		mce_acciones.accion,
							mce_acciones.nombre as accion_nombre,
							'.guarani_sql::get_case_si_no('mce_acciones', 'activo', 'activo')." 
				FROM		mce_acciones
				$where
				ORDER BY	mce_acciones.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_acciones_egreso_activas()
	{
		$where = "	mce_acciones.tipo = '".circuito_tramite::tipo_egreso."'
					AND mce_acciones.activo = '".comunes::si."'";
		return $this->get_listado_acciones($where);
	}

	function get_nombre_accion($accion)
	{
		$where = "mce_acciones.accion = " . toba::db()->quote($accion);
		$datos = current($this->get_listado_acciones($where));
		return $datos['accion_nombre'];
	}

	function get_acciones_tesis_activas()
	{
		$where = "	mce_acciones.tipo = '".circuito_tramite::tipo_tesis."'
					AND mce_acciones.activo = '".comunes::si."'";
		return $this->get_listado_acciones($where);
	}
	
	/**
 	  * Retorna los datos correspondientes a una acción en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
      *	@param integer/array código de acción o arreglo de código/s de accion/es.
    */
	function get_datos_accion($accion)
	{
		if (is_array($accion)) {
			$filtro = guarani_sql::armar_condicion_in($accion, 'mce_acciones', 'accion');
		} else {
			$accion = toba::db()->quote($accion);
			$filtro = "mce_acciones.accion = $accion";
		}
		return $this->get_listado_acciones($filtro);
	}

	/**
     *  Valida si un nombre de acción de circuito de egreso se puede usar o no.
     */
	function validar_nombre_accion($nombre, $accion)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(mce_acciones.accion) as cant
                FROM 	mce_acciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('mce_acciones.nombre', $nombre).'
		';
		
		if (!is_null($accion)) {
			$accion = toba::db()->quote($accion);
			$sql .= " AND mce_acciones.accion <> $accion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	/**
 	  * Retorna las acciones posibles para un circuito y un estado inicial (posiblemente vacío).
      *	@param integer código de circuito.
      * @param integer código de estado inicial (puede ser nulo).
      *
    */
	function get_acciones_por_circuito_estado($circuito, $estado)
	{
		$circuito = toba::db()->quote($circuito);
		if (empty($estado)) {
			$where = ' AND mce_caminos.estado_inicial IS NULL';
		} else {
			$estado = toba::db()->quote($estado);
			$where = " AND mce_caminos.estado_inicial = $estado";
		}
		$sql = "SELECT		mce_caminos.accion,
							mce_acciones.nombre
				FROM		mce_caminos,
							mce_acciones
				WHERE		mce_caminos.accion = mce_acciones.accion AND
							mce_caminos.circuito = $circuito
							$where
				ORDER BY	mce_acciones.nombre
		";
		return guarani_db::consultar($sql);
	}

	//------------------------------------------------------------------------
	//-- Estados de Circuitos de Trámite
	//------------------------------------------------------------------------

	function get_listado_estados($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = 'SELECT	mce_estados.estado,
						mce_estados.nombre		AS estado_nombre,
						'.guarani_sql::get_case_si_no('mce_estados', 'activo', 'activo').",
						mce_circuitos_tipos.nombre		AS circuito
				FROM	mce_estados
						LEFT JOIN mce_circuitos_tipos ON mce_circuitos_tipos.tipo = mce_estados.tipo
				$where
				ORDER BY	mce_estados.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_estados_egreso_activos()
	{
		$where = "	mce_estados.tipo = '".circuito_tramite::tipo_egreso."'
					AND mce_estados.activo = '".comunes::si."'";
		return $this->get_listado_estados($where);
	}

	function get_opciones_combo_estados_egreso()
	{
		$datos = $this->get_estados_egreso_activos();
		$datos[] = array('estado' => -1, 'estado_nombre' => '<Sin estado>');
		return $datos;
	}

	function get_estados_tesis_activos()
	{
		$where = "	mce_estados.tipo = '".circuito_tramite::tipo_tesis."'
					AND mce_estados.activo = '".comunes::si."'";
		return $this->get_listado_estados($where);
	}

	/**
 	  * Retorna los datos correspondientes a un estado en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
      *	@param integer/array código de estado o arreglo de código/s de estado/s.
    */
	function get_datos_estado($estado)
	{
		if (is_array($estado)) {
			$filtro = guarani_sql::armar_condicion_in($estado, 'mce_estados', 'estado');
		} else {
			$estado = toba::db()->quote($estado);
			$filtro = "mce_estados.estado = $estado";
		}
		return $this->get_listado_estados($filtro);
	}

	function get_datos_estado_inicial($estado)
	{
		if ($estado) {
			$datos = $this->get_datos_estado($estado);
			$datos_estado = array();
			foreach ($datos as $d) {
				$datos_estado[] = array('estado_inicial' => $d['estado'], 'estado_inicial_nombre' => $d['estado_nombre']);
			}
			return $datos_estado;
		}
		return array();
	}

	function get_datos_estado_final($estado)
	{
		if ($estado) {
			$datos = $this->get_datos_estado($estado);
			$datos_estado = array();
			foreach ($datos as $d) {
				$datos_estado[] = array('estado_final' => $d['estado'], 'estado_final_nombre' => $d['estado_nombre']);
			}
			return $datos_estado;
		}
		return array();
	}

	/**
     *  Valida si un nombre de estado de circuito de egreso se puede usar o no.
     */
	function validar_nombre_estado($nombre, $estado)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(mce_estados.estado) as cant
                FROM 	mce_estados
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('mce_estados.nombre', $nombre).'
		';
		
		if (!is_null($estado)) {
			$estado = toba::db()->quote($estado);
			$sql .= " AND mce_estados.estado <> $estado ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	/**
 	  * Retorna el estado final para un circuito, un estado inicial (posiblemente vacío) y una acción.
      *	@param integer código de circuito.
      * @param integer código de estado inicial (puede ser nulo).
      * @param integer código de acción.
      *
    */
	function get_estado_final_por_circuito_estado_accion($circuito, $estado, $accion)
	{
		$circuito = toba::db()->quote($circuito);
		if (empty($estado)) {
			$where = ' AND mce_caminos.estado_inicial IS NULL';
		} else {
			$estado = toba::db()->quote($estado);
			$where = " AND mce_caminos.estado_inicial = $estado";
		}
		$accion = toba::db()->quote($accion);
		$sql = "SELECT	mce_caminos.estado_final,
						mce_estados.nombre
				FROM	mce_caminos,
						mce_estados
				WHERE	mce_caminos.estado_final = mce_estados.estado AND
						mce_caminos.circuito = $circuito AND
						mce_caminos.accion = $accion
						$where
		";
		return guarani_db::consultar($sql);
	}

	//------------------------------------------------------------------------
	//-- Formularios de Trámite
	//------------------------------------------------------------------------

	function get_listado_formularios($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$sql = 'SELECT		mce_formularios.formulario,
							mce_formularios.nombre as formulario_nombre,
							'.guarani_sql::get_case_si_no('mce_formularios', 'activo', 'activo')." 
				FROM		mce_formularios
				$where
				ORDER BY	mce_formularios.nombre
		";
		return guarani_db::consultar($sql);
	}

	function get_listado_formularios_egreso($where='')
	{
		if ($where) {
			$where .= ' AND ';
		}
		$where .= "mce_formularios.tipo = '".circuito_tramite::tipo_egreso."'";
		return $this->get_listado_formularios($where);
	}

	function get_listado_formularios_tesis($where='')
	{
		if ($where) {
			$where .= ' AND ';
		}
		$where .= "mce_formularios.tipo = '".circuito_tramite::tipo_tesis."'";
		return $this->get_listado_formularios($where);
	}

	function get_listado_formularios_expediente($where='')
	{
		if ($where) {
			$where .= ' AND ';
		}
		$where .= "mce_formularios.tipo = '".circuito_tramite::tipo_egreso."'";
		$where .= " AND mce_formularios.incluir_en_expediente = '".comunes::si."'";
		return $this->get_listado_formularios($where);
	}
	
	function get_listado_formularios_expediente_por_nro_solicitud($nro_solicitud)
	{
		$nro_solicitud = toba::db()->quote($nro_solicitud);

		$sql = "SELECT		
					mce_formularios.formulario,
					mce_formularios.nombre as formulario_nombre
				FROM sga_certificados_otorg_form
				JOIN mce_formularios ON (sga_certificados_otorg_form.formulario = mce_formularios.formulario)
				WHERE sga_certificados_otorg_form.nro_solicitud = $nro_solicitud
					AND sga_certificados_otorg_form.id_documento_digital IS NULL
					AND mce_formularios.tipo = '" . circuito_tramite::tipo_egreso . "'
					AND mce_formularios.incluir_en_expediente = '" . comunes::si . "'";

		return guarani_db::consultar($sql);
	}

	function get_plantilla_by_formulario($formulario)
	{
		$formulario = toba::db()->quote($formulario);

		$sql = "SELECT	mce_plantillas.plantilla, 
						mce_plantillas.nombre, 
						mce_plantillas.descripcion, 
						mce_plantillas.archivo_plantilla, 
						mce_plantillas.admite_solicitud
				FROM mce_plantillas
				JOIN mce_formularios ON (mce_plantillas.plantilla = mce_formularios.plantilla)
				WHERE mce_formularios.formulario = $formulario
				LIMIT 1";

		return guarani_db::consultar_fila($sql);
	}
	
	/**
 	  * Retorna los datos correspondientes a un formulario en formato RecordSet.
 	  * Tiene en cuenta el caso de la carga masiva cuando el parámetro es un arreglo.
      *	@param integer/array código de formulario o arreglo de código/s de formulario/s.
    */
	function get_datos_formulario($formulario)
	{
		if (is_array($formulario)) {
			$filtro = guarani_sql::armar_condicion_in($formulario, 'mce_formularios', 'formulario');
		} else {
			$formulario = toba::db()->quote($formulario);
			$filtro = "mce_formularios.formulario = $formulario";
		}
		return $this->get_listado_formularios($filtro);
	}

	/**
 	  * Retorna los formularios a imprimir para un circuito, un estado inicial (posiblemente vacío) y una acción.
      *	@param integer código de circuito.
      * @param integer código de estado inicial (puede ser nulo).
      * @param integer código de acción.
      *
    */
	function get_formularios_por_circuito_estado_accion($circuito, $estado, $accion)
	{
		$circuito = toba::db()->quote($circuito);
		if (empty($estado)) {
			$where = ' AND mce_caminos.estado_inicial IS NULL';
		} else {
			$estado = toba::db()->quote($estado);
			$where = " AND mce_caminos.estado_inicial = $estado";
		}
		$accion = toba::db()->quote($accion);
		$sql = "SELECT		mce_caminos_form.formulario,
							mce_formularios.nombre,
							mce_formularios.plantilla,
							mce_formularios.admite_reimpresion,
							mce_caminos_form.cant_copias
				FROM		mce_caminos_form,
							mce_formularios,
							mce_caminos
				WHERE		mce_caminos_form.formulario = mce_formularios.formulario AND
							mce_caminos_form.camino = mce_caminos.camino AND
							mce_caminos.circuito = $circuito AND
							mce_caminos.accion = $accion
							$where
				ORDER BY	mce_formularios.nombre
		";
		return guarani_db::consultar($sql);
	}
	
	/**
     *  Valida si un nombre de formulario se puede usar o no.
     */
	function validar_nombre_formulario($nombre, $formulario)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(mce_formularios.formulario) as cant
                FROM 	mce_formularios
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('mce_formularios.nombre', $nombre).'
		';
		
		if (!is_null($formulario)) {
			$formulario = toba::db()->quote($formulario);
			$sql .= " AND mce_formularios.formulario <> $formulario ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	//------------------------------------------------------------------------
	//-- Plantillas
	//------------------------------------------------------------------------

	function get_plantillas()
	{
		$sql = 'SELECT		mce_plantillas.plantilla as valor,
							mce_plantillas.nombre as descr
				FROM		mce_plantillas
				ORDER BY	mce_plantillas.nombre
		';
		return guarani_db::consultar($sql);
	}

	//------------------------------------------------------------------------
	//-- Campos
	//------------------------------------------------------------------------

	function get_listado_campos_egreso($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		mce_campos.campo,
							mce_campos.etiqueta,
							mce_campos.tipo_de_dato
				FROM		mce_circuitos_tipos_campos,
							mce_campos
				WHERE		mce_circuitos_tipos_campos.campo = mce_campos.campo AND
							mce_circuitos_tipos_campos.tipo = '".circuito_tramite::tipo_egreso."'
							$where
				ORDER BY    mce_campos.etiqueta
		";
		return guarani_db::consultar($sql);
	}

	/**
 	  * Retorna los campos a completar para un circuito, un estado inicial y una acción (posiblemente vacíos).
      *	@param integer código de circuito.
      * @param integer código de estado inicial (puede ser nulo).
      * @param integer código de acción (puede ser nula).
      *
    */
	function get_campos_por_circuito_estado_accion($circuito, $estado, $accion=null)
	{
		$circuito = toba::db()->quote($circuito);
		if (empty($estado)) {
			$where = ' AND mce_caminos.estado_inicial IS NULL';
		} else {
			$estado = toba::db()->quote($estado);
			$where = " AND mce_caminos.estado_inicial = $estado";
		}
		if (! empty($accion)) {
			$accion = toba::db()->quote($accion);
			$where .= " AND mce_caminos.accion = $accion";
		}
		$sql = "SELECT	mce_caminos_campos.campo,
						mce_caminos_campos.obligatorio
				FROM	mce_caminos_campos,
						mce_caminos
				WHERE	mce_caminos_campos.camino = mce_caminos.camino AND
						mce_caminos.circuito = $circuito
						$where
		";
		return guarani_db::consultar($sql);
	}

	function get_persona_a_notificar_cambio_estado_certificacion($nro_tramite)
	{
		$nro_tramite = toba::db()->quote($nro_tramite);

		$sql = "SELECT	sga_certificados_otorg.persona,
						COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) AS persona_apellido,
						COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) AS persona_nombres,
						mce_estados.nombre 		AS estado_nombre,
						sga_certificados.nombre AS certificado_nombre,
						mce_caminos.enviar_aviso_alumno
				FROM	sga_certificados_otorg
						JOIN mce_estados				ON (sga_certificados_otorg.estado = mce_estados.estado)
						JOIN mdp_personas				ON (mdp_personas.persona = sga_certificados_otorg.persona)
						JOIN sga_certificados			ON (sga_certificados.certificado = sga_certificados_otorg.certificado)
						JOIN sga_certificados_otorg_cmb ON (sga_certificados_otorg_cmb.nro_solicitud = sga_certificados_otorg.nro_solicitud)
						JOIN mce_caminos				ON (mce_caminos.circuito = sga_certificados_otorg.circuito)
				WHERE	sga_certificados_otorg.nro_solicitud = $nro_tramite
						AND ((mce_caminos.estado_inicial = sga_certificados_otorg_cmb.estado_anterior) OR (sga_certificados_otorg_cmb.estado_anterior IS NULL AND mce_caminos.estado_inicial IS NULL))
						AND mce_caminos.estado_final = sga_certificados_otorg_cmb.estado_nuevo
						AND mce_caminos.accion = sga_certificados_otorg_cmb.accion
				ORDER BY sga_certificados_otorg_cmb.fecha DESC
				LIMIT 1";

		return guarani_db::consultar_fila($sql);
	}

}