<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\modelo\lib\abm;
use siu\modelo\lib\catalogo;
use siu\preinscripcion;
use siu\modelo\util;
use siu\modelo\propuesta;
use siu\modelo\g3\g3;
use siu\modelo\comunes;

class manejador_datos
{
	protected $id_preinscripcion;
	protected $datos;
	protected $datos_formulario;
	protected $datos_insc;
	protected $campos_preinscripcion;
	
	// Datos extra solicitados a preinscriptos a posgrados
	protected $datos_actuacion_docente;
	protected $datos_actuacion_profesional;
	protected $datos_formacion_acad;
	
	protected $campos_secciones;
	protected $campos_descriptivos;
	
	protected $requisitos;

	function __construct($id_preinscripcion)
	{
		$this->id_preinscripcion = $id_preinscripcion;
	}

	function get_id_preinscripcion()
	{
		return $this->id_preinscripcion;
	}

	/**
	 * id_preinscripcion / version_impresa
	 */
	function get_codigo_preinscripcion()
	{
		$datos = $this->get_datos_preinscripcion();
		return $datos['id_preinscripcion'] . '/' . $datos['version_impresa'];
	}
	
	function get_estado_cuenta_preinscripcion()
	{
		$datos = $this->get_datos_preinscripcion();
		return $datos['estado'];
	}

	function get_apellido_y_nombres()
	{
		$datos = $this->get_datos_preinscripcion();
		return $datos['apellido'] . ', ' . $datos['nombres'];
	}

	function hay_datos_preinscripcion()
	{
		$datos = $this->get_datos_preinscripcion();
		return !empty($datos);
	}

	function get_datos_preinscripcion()
	{
		if (!isset($this->datos)) {
			$this->datos = util::consultas()->get_datos_preinscripcion($this->id_preinscripcion);
		}
		return $this->datos;
	}

	function set_datos_preinscripcion($datos)
	{
		$datos = $this->controlar_hiddens($datos);
		$datos = $this->filtrar_datos_preinscripcion($datos);
		
		// Limpio los datos de discapacidad
		$this->limpiar_discapacidad($datos);
		
		// Siempre opera en modo UPDATE sobre , el registro se da de alta en la creaci�n de usuario
		$clave = array('id_preinscripcion' => $this->get_id_preinscripcion());
		abm::modificacion('sga_preinscripcion', $datos, $clave, null, 'version_modificacion = version_modificacion + 1');

		// borrar cache
		unset($this->datos);
		unset($this->datos_formulario);
	}
	
	//--------------------------------------------------------------------------

	function actualizar_version_modificacion()
	{
		$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);

		$sql = "UPDATE	sga_preinscripcion
				SET		version_modificacion = version_modificacion + 1
				WHERE	id_preinscripcion = $id_preinscripcion
				";

		kernel::db()->ejecutar($sql);

		// Borrar cache
		unset($this->datos);
	}

	function actualizar_version_impresa()
	{
		$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);

		$sql = "UPDATE	sga_preinscripcion
				SET		version_impresa = version_modificacion
				WHERE	id_preinscripcion = $id_preinscripcion
				";

		kernel::db()->ejecutar($sql);

		// Borrar cache
		unset($this->datos);
	}

	function actualizar_estado($estado)
	{
		$estado = kernel::db()->quote($estado);
		$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);

		$sql = "UPDATE	sga_preinscripcion
				SET		estado = $estado
				WHERE	id_preinscripcion = $id_preinscripcion
				";

		kernel::db()->ejecutar($sql);

		// Borrar cache
		unset($this->datos);
	}
	
	function eliminar_observaciones()
	{
		$estado = kernel::db()->quote(preinscripcion::estado_preinsc_pendiente);
		$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);

		$sql = "UPDATE sga_preinscripcion_propuestas
				SET observaciones = NULL,
					estado = $estado
				WHERE id_preinscripcion = $id_preinscripcion;
				";

		kernel::db()->ejecutar($sql);
	}
	

	//--------------------------------------------------------------------------
	//---- Datos de nacionalidad / documentaci�n -------------------------------
	//--------------------------------------------------------------------------

	function permite_cedula()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		$pais = $this->datos['pais_documento'];
		return g3::paises()->permite_cedula($pais);
	}

	function necesita_visa()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		$pais = $this->datos['pais_documento'];
		return g3::paises()->necesita_visa($pais);
	}

	function get_nacionalidad()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		return $this->datos['nacionalidad'];
	}

	function get_tipo_doc()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		return $this->datos['tipo_documento'];
	}
	function get_nro_documento()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		return $this->datos['nro_documento'];
	}
	function get_pais_documento()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		return $this->datos['pais_documento'];
	}

	function es_argentino()
	{
		if (!isset($this->datos)) {
			$this->datos = $this->get_datos_preinscripcion();
		}
		return $this->datos['pais_documento'] == 54;
	}

	//--------------------------------------------------------------------------
	//---- Datos de inscripciones ----------------------------------------------
	//--------------------------------------------------------------------------

	/**
	 * Determina si se efectu� alguna inscripci�n a propuesta
	 * 
	 * @return boolean
	 */
	function hay_inscripcion_propuesta()
	{
		$propuestas_insc = util::consultas()->get_propuestas_insc($this->id_preinscripcion);
		return !empty($propuestas_insc);
	}

	/**
	 * Obtiene los distintos tipos de propuetas en las que se inscribi� el aspirante
	 * 
	 * @return array
	 */
	function get_tipos_propuestas_insc()
	{
		// Se obtienen las inscripciones a propuestas (sin descripciones)
		$propuestas_insc = util::consultas()->get_propuestas_insc($this->id_preinscripcion);
		// Se construye un array con los tipos de propuestas (sin repeticiones)
		$tipos_propuesta = array();
		foreach ($propuestas_insc as $propuesta) {
			$tipo = g3::propuestas()->get_id_tipo_propuesta($propuesta['propuesta']);
			if (!in_array($tipo, $tipos_propuesta)) {
				$tipos_propuesta[] = $tipo;
			}
		}
		return $tipos_propuesta;
	}

	/**
	 * Obtiene la cantidad de inscripciones del aspirante
	 * 
	 * @return int
	 */
	function get_cantidad_inscripciones()
	{
		// Se obtienen las propuestas (sin descripciones)
		$propuestas_insc = util::consultas()->get_propuestas_insc($this->id_preinscripcion);
		return count($propuestas_insc);
	}
	
	
	/**
	 * Determina si el aspirante se inscribi� a alguna propuesta de posgrado
	 * 
	 * @return boolean
	 */
	function hay_insc_posgrado()
	{
		$tipos_propuesta = $this->get_tipos_propuestas_insc();
		foreach ($tipos_propuesta as $tipo) {
			if ($tipo == propuesta::tipo_POSGRADO) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Obtiene la cantidad de inscripciones a propuestas de posgrado
	 * 
	 * @return int
	 */
	function get_cantidad_inscripciones_posgrado()
	{
		$tipos_propuesta = $this->get_tipos_propuestas_insc();
		$cont = 0;
		foreach ($tipos_propuesta as $tipo) {
			if ($tipo == propuesta::tipo_POSGRADO) {
				$cont++;
			}
		}
		return $cont;
	}

	/**
	 * Obtiene los datos de las inscripciones efectuadas por el aspirante
	 * 
	 * @return array
	 */
	function get_datos_inscripciones()
	{
		if (!isset($this->datos_insc)) {
			$propuestas_insc = util::consultas()->get_propuestas_insc($this->id_preinscripcion);
			$nuevo = array();
			foreach ($propuestas_insc as $dato) {
				$dato['fecha_fin_vigencia_visual'] = convertir_fecha_formato_frontend($dato['fecha_fin_vigencia']);
				$clave = $dato['id_preinscripcion'] . '_' . $dato['propuesta'];
				$id = util::catalogo()->generar_id($clave);
				$nuevo[$id] = $dato;
				$nuevo[$id][catalogo::id] = $id;
			}
			$this->datos_insc = $nuevo;
		}
		return $this->datos_insc;
	}

	/**
	 * Retorna un string con los nombres abreviados de las propuestas elegidas por el aspirante. 
	 * Si hay m�s de una, se muestran separan por " / "
	 * 
	 * @return array
	 */
	function get_nombres_propuestas_insc()
	{
		$propuestas_insc = $this->get_datos_inscripciones();
		$salida = '';
		foreach ($propuestas_insc as $propuesta) {
			if ($salida) {
				$salida .= ' / ';
			}
			$salida .= g3::propuestas()->get_nombre_abreviado($propuesta['propuesta']);
		}
		return $salida;
	}

	function set_propuesta_insc($datos)
	{
		try {
			kernel::db()->abrir_transaccion();
			
			// Se obtiene fecha de vencimiento de la preinsripci�n
			$sql_fecha = 'SELECT CURRENT_DATE as fecha';
			$res_fecha = kernel::db()->consultar_fila($sql_fecha);
			$hoy = $res_fecha['fecha'];
			$pre_meses_vigencia_preinsc = g3::parametros()->get_valor('pre_meses_vigencia_preinsc');
			$fecha_vencimiento = get_fecha_desplazada($hoy, $pre_meses_vigencia_preinsc, 'mes');			
			
			$datos_alta = array(
				'id_preinscripcion' => $this->id_preinscripcion,
				'responsable_academica' => $datos['responsable_academica'],
				'propuesta' => $datos['propuesta'],
				'ubicacion' => $datos['ubicacion'],
				'modalidad' => $datos['modalidad'],
				'periodo_insc' => g3::periodos()->get_periodo_preinscripcion($datos['propuesta'], $datos['ubicacion'], $datos['modalidad']),
				'fecha_fin_vigencia' => $fecha_vencimiento,
			);
			abm::alta('sga_preinscripcion_propuestas', $datos_alta);

			// Se actualiza versi�n de modificaci�n y fecha de �ltima modificaci�n
			$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);
			$sql = "UPDATE	sga_preinscripcion 
					SET		version_modificacion = version_modificacion + 1,
							fecha_ult_modif = 'now()'
					WHERE	id_preinscripcion = $id_preinscripcion";
			kernel::db()->ejecutar($sql);

			// Borrar cach�
			unset($this->datos_insc);

			kernel::db()->cerrar_transaccion();
		} catch (Exception $e) {
			kernel::db()->abortar_transaccion();
		}
	}

	function eliminar_insc($id)
	{
		/**
		 * Se recuperan los datos de la inscripci�n a partir del ID encriptado
		 */
		$datos_insc = $this->get_datos_inscripciones();
		if (empty($datos_insc[$id])) {
			return;
		}
		$datos = $datos_insc[$id];

		try {
			kernel::db()->abrir_transaccion();
			
			$tipo_propuesta = g3::propuestas()->get_id_tipo_propuesta($datos['propuesta']);

			if ($tipo_propuesta == propuesta::tipo_POSGRADO) {
				$cantidad_insc_posgrado = $this->get_cantidad_inscripciones_posgrado();
			}

			$clave = array();
			$clave['id_preinscripcion'] = $datos['id_preinscripcion'];
			$clave['propuesta'] = $datos['propuesta'];
			abm::baja('sga_preinscripcion_propuestas', $clave);

			if (($tipo_propuesta == propuesta::tipo_POSGRADO) && ($cantidad_insc_posgrado == 1)) {
				// Si se elimina la �nica propuesta de posgrado, se eliminan tambi�n 
				// los registros de Actuaci�n docente, Actuaci�n profesional y 
				// Antecedentes acad�mico
				$clave = array('id_preinscripcion' => $datos['id_preinscripcion']);
				abm::baja('sga_actuacion_doc', $clave);
				abm::baja('sga_actuacion_prof', $clave);
				abm::baja('sga_formacion_acad', $clave);
			}

			// Se actualiza versi�n de modificaci�n y fecha de �ltima modificaci�n
			$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);
			$sql = "UPDATE	sga_preinscripcion 
					SET		version_modificacion = version_modificacion + 1,
							fecha_ult_modif = 'now()'
					WHERE	id_preinscripcion = $id_preinscripcion";
			kernel::db()->ejecutar($sql);

                        $ra_inscripcion_eliminada = $datos['responsable_academica'];
                        $inscripciones_restantes = $datos_insc;
                        // Quito la inscripci�n eliminada
                        unset($inscripciones_restantes[$id]);
                        $ra_inscripciones_restantes = array_column($inscripciones_restantes, 'responsable_academica');
                        // Si no hay otras inscripciones para la RA de la inscripci�n eliminada
                        // entonces devuelvo el turno
                        if (!in_array($ra_inscripcion_eliminada, $ra_inscripciones_restantes)) {
                            $this->devolver_turno($ra_inscripcion_eliminada);
                        }
                        
			// Borrar cach�		
			unset($this->datos_insc);
//			catalogo::invalidar($this->get_sql_propuestas_insc());
//			catalogo::invalidar($this->get_sql_tipos_de_propuesta_insc());
//			catalogo::cache()->eliminar($_SERVER['UA'] . 'parametros_campos');

			kernel::db()->cerrar_transaccion();
		} catch (Exception $e) {
			kernel::db()->abortar_transaccion();
		}
	}

	//--------------------------------------------------------------------------
	//---- Actuaci�n docente ---------------------------------------------------
	//--------------------------------------------------------------------------

	function get_datos_actuacion_docente()
	{
		if (!isset($this->datos_actuacion_docente)) {
			$this->datos_actuacion_docente = util::consultas()->get_datos_actuacion_docente($this->id_preinscripcion);
		}
		return $this->datos_actuacion_docente;
	}

	function set_actuacion_docente($datos)
	{
		$datos['id_preinscripcion'] = $this->id_preinscripcion;
		$datos = $this->filtrar_datos_comunes($datos, 'fecha_desde', 'fecha_hasta');
		
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			abm::alta('sga_actuacion_doc', $datos);
		} else {
			$datos_act_doc = $this->get_datos_actuacion_docente();
			if (!empty($datos_act_doc[$datos[catalogo::id]])) {
				$clave = array('actuacion_doc' => $datos_act_doc[$datos[catalogo::id]]['actuacion_doc']);
				unset($datos[catalogo::id]);
				abm::modificacion('sga_actuacion_doc', $datos, $clave);
			}
		}

		// Borrar cach�
		unset($this->datos_actuacion_docente);
	}

	function eliminar_actuacion_docente($id)
	{
		$datos_insc = $this->get_datos_actuacion_docente();
		if (empty($datos_insc[$id])) {
			return;
		}
		$datos = $datos_insc[$id];

		$clave = array();
		$clave['actuacion_doc'] = $datos['actuacion_doc'];
		abm::baja('sga_actuacion_doc', $clave);

		// Borrar cach�		
		unset($this->datos_actuacion_docente);
	}

	//--------------------------------------------------------------------------
	//---- Actuaci�n profesional -----------------------------------------------
	//--------------------------------------------------------------------------

	function get_datos_actuacion_profesional()
	{
		if (!isset($this->datos_actuacion_profesional)) {
			$this->datos_actuacion_profesional = util::consultas()->get_datos_actuacion_profesional($this->id_preinscripcion);
		}
		return $this->datos_actuacion_profesional;
	}

	function set_actuacion_profesional($datos)
	{
		$datos['id_preinscripcion'] = $this->id_preinscripcion;
		$datos = $this->filtrar_datos_comunes($datos, 'fecha_desde', 'fecha_hasta');
		
		// Reemplazo de claves para almacenar en la tabla 'sga_actuacion_prof' 
		$datos['empresa'] = $datos['antec_empresa'];
		unset($datos['antec_empresa']);
		
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			abm::alta('sga_actuacion_prof', $datos);
		} else {
			$datos_act_prof = $this->get_datos_actuacion_profesional();
			if (!empty($datos_act_prof[$datos[catalogo::id]])) {
				$clave = array('actuacion_prof' => $datos_act_prof[$datos[catalogo::id]]['actuacion_prof']);
				unset($datos[catalogo::id]);
				abm::modificacion('sga_actuacion_prof', $datos, $clave);
			}
		}

		// Borrar cach�
		unset($this->datos_actuacion_profesional);
	}

	function eliminar_actuacion_profesional($id)
	{
		$datos_insc = $this->get_datos_actuacion_profesional();
		if (empty($datos_insc[$id])) {
			return;
		}
		$datos = $datos_insc[$id];

		$clave = array();
		$clave['actuacion_prof'] = $datos['actuacion_prof'];
		abm::baja('sga_actuacion_prof', $clave);

		// Borrar cach�		
		unset($this->datos_actuacion_profesional);
	}

	//--------------------------------------------------------------------------
	//---- Antecedentes acad�micos ---------------------------------------------
	//--------------------------------------------------------------------------

	function get_datos_formacion_acad()
	{
		if (!isset($this->datos_formacion_acad)) {
			$this->datos_formacion_acad = util::consultas()->get_datos_formacion_acad($this->id_preinscripcion);
		}
		return $this->datos_formacion_acad;
	}

	function set_formacion_acad($datos)
	{
		$datos['id_preinscripcion'] = $this->id_preinscripcion;
		$no_formatear = array('categoria_coneau', 'nivel_estudio', 'estado');
		$datos = $this->filtrar_datos_comunes($datos, 'fecha_ingreso', 'fecha_egreso', $no_formatear);
		
		if (empty($datos[catalogo::id])) {
			unset($datos[catalogo::id]);
			abm::alta('sga_formacion_acad', $datos);
		} else {
			$datos_form_acad = $this->get_datos_formacion_acad();
			if (!empty($datos_form_acad[$datos[catalogo::id]])) {
				$clave = array('formacion_acad' => $datos_form_acad[$datos[catalogo::id]]['formacion_acad']);
				unset($datos[catalogo::id]);
				abm::modificacion('sga_formacion_acad', $datos, $clave);
			}
		}

		// Borrar cach�
		unset($this->datos_formacion_acad);
	}

	function eliminar_formacion_acad($id)
	{
		$datos_insc = $this->get_datos_formacion_acad();
		if (empty($datos_insc[$id])) {
			return;
		}
		$datos = $datos_insc[$id];

		$clave = array();
		$clave['formacion_acad'] = $datos['formacion_acad'];
		abm::baja('sga_formacion_acad', $clave);

		// Borrar cach�		
		unset($this->datos_formacion_acad);
	}

	//--------------------------------------------------------------------------
	//---- Turno de presentaci�n -----------------------------------------------
	//--------------------------------------------------------------------------

	function get_listado_turnos()
	{
		return util::consultas()->get_turnos_presentacion($this->id_preinscripcion);
	}
	
	function devolver_turno($ra)
	{
		g3::turnos()->devolver_turno($this->id_preinscripcion, $ra);
	}

	function set_turno($ra, $datos)
	{
		try {
			kernel::db()->abrir_transaccion();
			// Se registra el nuevo turno
			g3::turnos()->reservar_turno($this->id_preinscripcion, $ra, $datos);
			// Se incrementa la versi�n de modificaci�n de la preinscripci�n
			$this->actualizar_version_modificacion();
			kernel::db()->cerrar_transaccion();
		} catch (\siu\errores\error_preinscripcion $e) {
			kernel::db()->abortar_transaccion();
			throw $e;
		}
	}

        /**
	 * Invalida cache de turno de presentaci�n(en caso que haya sido solicitado) de
	 * un aspirante en una RA dada.
	 *
	 * @param int $ra
	 * @return void
	 */
        function invalidar_cache_turno_presentacion($ra)
        {
            g3::turnos()->invalidar_cache_turno_presentacion($this->id_preinscripcion, $ra);
        }

	//--------------------------------------------------------------------------
	//---- Configuraci�n de formulario -----------------------------------------
	//--------------------------------------------------------------------------

	function get_config_campos_aspirante()
	{
		$tipos_propuestas = $this->get_tipos_propuestas_insc();
		return util::catalogo()->get_configuracion_formulario($tipos_propuestas);
	}
	
	/**
	 * Control de hiddens: se valida que los valores provinientes de inputs de tipo
	 * 'hidden' est�n entre los posibles para cada caso. Si no es v�lido, se fuerza
	 * el valor null.
	 * 
	 * @param array $datos
	 * @return array
	 */
	protected function controlar_hiddens($datos)
	{
		if ( isset($datos['colegio_secundario']) && ($datos['colegio_secundario'] == 'null') || ((!empty($datos['colegio_secundario'])) && (!g3::entidad('colegios')->existe($datos['colegio_secundario'])))) {
			$datos['colegio_secundario'] = null;
		}
		if ( isset($datos['titulo_secundario']) && ($datos['titulo_secundario'] == 'null') || ((!empty($datos['titulo_secundario'])) && (!g3::titulos_secundario()->existe($datos['titulo_secundario'])))) {
			$datos['titulo_secundario'] = null;
		}
		if ( isset($datos['loc_per_lect']) && ($datos['loc_per_lect'] == 'null') || ((!empty($datos['loc_per_lect'])) && (!g3::entidad('localidades')->existe($datos['loc_per_lect'])))) {
			$datos['loc_per_lect'] = null;
		}
		if ( isset($datos['loc_proc']) && ($datos['loc_proc'] == 'null') || ((!empty($datos['loc_proc'])) && (!g3::entidad('localidades')->existe($datos['loc_proc'])))) {
			$datos['loc_proc'] = null;
		}

		if ( isset($datos['loc_nacimiento']) && ($datos['loc_nacimiento'] == 'null') || ((!empty($datos['loc_nacimiento'])) && (!g3::entidad('localidades')->existe($datos['loc_nacimiento'])))) {
			$datos['loc_nacimiento'] = null;
		}
		
		return $datos;
	}
	
	protected function limpiar_discapacidad(&$datos)
	{
		// Si no se trata de datos de discapacidad salgo
		if (!isset($datos['condicion_discapacidad'])) {
			return false;
		}
		
		if ($datos['condicion_discapacidad'] == comunes::no) {
			$datos['tiene_cud'] = comunes::no;
			$datos['disc_auditiva'] = comunes::no;
			$datos['disc_visual'] = comunes::no;
			$datos['disc_motora'] = comunes::no;
			$datos['disc_cond_psicosocial'] = comunes::no;
			$datos['disc_otra'] = comunes::no;
			$datos['informacion_importante'] = "";
		}
		
		$this->limpiar_discapacidad_auditiva($datos);
		$this->limpiar_discapacidad_visual($datos);
		$this->limpiar_discapacidad_motora($datos);
		$this->limpiar_discapacidad_psicosocial($datos);
		$this->limpiar_discapacidad_otras($datos);
	}
	
	protected function limpiar_discapacidad_auditiva(&$datos)
	{
		if ($datos['disc_auditiva'] == comunes::no) {
			$datos['aud_tipo'] = comunes::NO_HAY_DIFICULTAD_VAL;
			$datos['aud_lenguaje_senias'] = comunes::no;
			$datos['aud_lenguaje_labial'] = comunes::no;
			$datos['aud_comunicacion_otra'] = "";
			$datos['aud_apoyo_vida_diaria'] = "";
			$datos['aud_req_interprete_lengua_senias'] = comunes::no;
			$datos['aud_req_aro_magnetico'] = comunes::no;
			$datos['aud_req_otros_apoyos'] = comunes::no;
			$datos['aud_otros_apoyos'] = "";
		}
		
		if ($datos['aud_req_otros_apoyos'] == comunes::no) {
			$datos['aud_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_visual(&$datos)
	{
		if ($datos['disc_visual'] == comunes::no) {
			$datos['vis_dificultad_para_ver'] = comunes::NO_HAY_DIFICULTAD_VAL;
			$datos['vis_apoyo_vida_diaria'] = "";
			$datos['vis_req_archivo_audio'] = comunes::no;
			$datos['vis_req_texto_digital'] = comunes::no;
			$datos['vis_req_texto_braile'] = comunes::no;
			$datos['vis_req_otros_apoyos'] = comunes::no;
			$datos['vis_otros_apoyos'] = "";
		}
		
		if ($datos['vis_req_otros_apoyos'] == comunes::no) {
			$datos['vis_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_motora(&$datos)
	{
		if ($datos['disc_motora'] == comunes::no) {
			$datos['mot_apoyo_vida_diaria'] = "";
			$datos['mot_req_apoyo_aula'] = comunes::no;
			$datos['mot_apoyo_aula'] = "";
			$datos['mot_req_otros_apoyos'] = comunes::no;
			$datos['mot_otros_apoyos'] = "";
		}
		
		if ($datos['mot_req_apoyo_aula'] == comunes::no) {
			$datos['mot_apoyo_aula'] = "";
		}
		
		if ($datos['mot_req_otros_apoyos'] == comunes::no) {
			$datos['mot_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_psicosocial(&$datos)
	{
		if ($datos['disc_cond_psicosocial'] == comunes::no) {
			$datos['psi_descripcion'] = "";
			$datos['psi_apoyo_vida_diaria'] = "";
			$datos['psi_req_otros_apoyos'] = comunes::no;
			$datos['psi_otros_apoyos'] = "";
		}
		
		if ($datos['psi_req_otros_apoyos'] == comunes::no) {
			$datos['psi_otros_apoyos'] = "";
		}
	}
	
	protected function limpiar_discapacidad_otras(&$datos)
	{
		if ($datos['disc_otra'] == comunes::no) {
			$datos['otra_descripcion'] = "";
			$datos['otra_req_apoyo_vida_diaria'] = comunes::no;
			$datos['otra_apoyo_vida_diaria'] = "";
		}
		
		if ($datos['otra_req_apoyo_vida_diaria'] == comunes::no) {
			$datos['otra_apoyo_vida_diaria'] = "";
		}
	}

	protected function filtrar_datos_comunes($datos, $campo_fecha_desde = '', $campo_fecha_hasta = '', $campos_no_formatear = array())
	{
		//---- Filtrado de datos -----------------------------------------------
		$salida = array();
		$no_formatear = array_merge(array(catalogo::id), $campos_no_formatear);

		foreach ($datos as $campo => $dato) {
			if (is_null($dato) || ($dato == opciones::NO_SELECCION)) {
				$dato = null;
			}
			if (!in_array($campo, $no_formatear)) {
				$dato = formatear($dato);
			}
			$salida[$campo] = $dato;
		}

		if ((!empty($campo_fecha_desde)) && (!empty($datos[$campo_fecha_desde]))) {
			$salida[$campo_fecha_desde] = convertir_fecha_formato_base($datos[$campo_fecha_desde]);
		}
		if ((!empty($campo_fecha_hasta)) && (!empty($datos[$campo_fecha_hasta]))) {
			$salida[$campo_fecha_hasta] = convertir_fecha_formato_base($datos[$campo_fecha_hasta]);
		}

		return $salida;
	}

	protected function filtrar_datos_preinscripcion($datos)
	{
		//---- Conversiones de formato / unificaciones de campos ---------------
		$celular_numero = $this->get_nro_celular($datos);
		if (!is_null($celular_numero)) {
			$datos['celular_numero'] = $celular_numero;
		}
		if (!empty($datos['fecha_nacimiento'])) {
			$datos['fecha_nacimiento'] = convertir_fecha_formato_base($datos['fecha_nacimiento']);
		}
		if (!empty($datos['fecha_respre_otorg'])) {
			$datos['fecha_respre_otorg'] = convertir_fecha_formato_base($datos['fecha_respre_otorg']);
		}
		if (!empty($datos['fecha_respre_venc'])) {
			$datos['fecha_respre_venc'] = convertir_fecha_formato_base($datos['fecha_respre_venc']);
		}
		if (!empty($datos['otorgamiento_visa'])) {
			$datos['otorgamiento_visa'] = convertir_fecha_formato_base($datos['otorgamiento_visa']);
		}
		if (!empty($datos['vencimiento_visa'])) {
			$datos['vencimiento_visa'] = convertir_fecha_formato_base($datos['vencimiento_visa']);
		}

		//---- Filtrado de datos -----------------------------------------------
		$campos = util::form()->get_campos_preinscripcion();
		$salida = array();
		$no_formatear = array('e_mail');
		foreach ($datos as $campo => $dato) {
			if (in_array($campo, $campos)) {
				if (is_null($dato) || ($dato == opciones::NO_SELECCION)) {
					$dato = null;
				}
				if (!in_array($campo, $no_formatear)) {
					$dato = formatear($dato);
				}
				$salida[$campo] = $dato;
			}
		}

		$salida['fecha_ult_modif'] = 'now()';
		return $salida;
	}

	function get_datos_formularios_preinsc()
	{
		if (empty($this->datos_formulario)) {
			$this->datos_formulario = util::consultas()->get_datos_preinscripcion_formateados($this->id_preinscripcion);
		}
                return $this->datos_formulario;
	}

	/**
	 * Se deja esta ventana de extensi�n por si se requiere modificar el formateo
	 * de campos para mostrar en el comprobante.
	 * 
	 * @return array
	 */
	function get_datos_formulario_impresion()
	{
		return $this->get_datos_formularios_preinsc();
	}

	protected function get_nro_celular($datos)
	{
		if (isset($datos['celular_caracteristica']) && isset($datos['celular_valor'])) {
			return '0-' . $datos['celular_caracteristica'] . '-15-' . $datos['celular_valor'];
		} else {
			return null;
		}
	}

	//--------------------------------------------------------------------------
	//---- Control de campos faltantes -----------------------------------------
	//--------------------------------------------------------------------------

	/**
	 * Determina si faltan completar campos definidos como obligatorios.
	 * 
	 * @return boolean
	 */
	function hay_datos_faltantes()
	{
		$hay_faltantes = false;
		$datos = $this->get_datos_preinscripcion();
                if (!empty($datos['celular_numero'])) {
			$datos_cel = explode('-', $datos['celular_numero']);
			$datos['celular_caracteristica'] = trim($datos_cel[1]);
			$datos['celular_valor'] = trim($datos_cel[3]);
		}
                if(!empty($datos['es_mayor_25_sin_secundario']) && $datos['es_mayor_25_sin_secundario']== comunes::si){
                    //quito del array los campos de secundario
                    unset($datos['colegio_secundario']); 
                    unset($datos['titulo_secundario']); 
                    unset($datos['anio_egreso_sec']); 
                    unset($datos['adeuda_materias']); 
                    unset($datos['sec_egreso']); 
                }
                
		foreach ($datos as $campo => $dato) {
			if (array_key_exists($campo, $datos) && (is_null($dato)) && (util::form()->visible($campo)) && (util::form()->obligatorio($campo))) {
				$hay_faltantes = true;
				break;
			}
		}
		if (!$hay_faltantes) {
			if (((empty($datos['celular_caracteristica'])) || (empty($datos['celular_valor']))) && (util::form()->visible('celular_numero')) && (util::form()->obligatorio('celular_numero'))) {
				$hay_faltantes = true;
			}
	/*Discapacidad*/
		//si tiene discapacidad pero no marca una de la lista
						if ( $datos['condicion_discapacidad']=='S'&&(
						 $datos['disc_auditiva']=='N' &&
						 $datos['disc_visual']=='N' &&
						 $datos['disc_motora']=='N' &&
						 $datos['disc_cond_psicosocial']=='N' &&
						 $datos['disc_otra']=='N')) {					
							$hay_faltantes = true;
							
						}
		if ($datos['aud_req_otros_apoyos']=='S' && empty($datos['aud_otros_apoyos'])) {
			$hay_faltantes = true;
		}
		if ($datos['vis_req_otros_apoyos']=='S' && empty($datos['vis_otros_apoyos'])) {
			$hay_faltantes = true;
		}
		if ($datos['mot_req_otros_apoyos']=='S' && empty($datos['mot_otros_apoyos'])) {
			$hay_faltantes = true;
		}
		if ($datos['psi_req_otros_apoyos']=='S' && empty($datos['psi_otros_apoyos'])) {
			$hay_faltantes = true;
		}
		if ($datos['otra_req_apoyo_vida_diaria']=='S' && empty($datos['otra_apoyo_vida_diaria'])) {
			$hay_faltantes = true;
		}
		}

		if ((!$hay_faltantes) && (kernel::proyecto()->carga_turno_presentacion())) {
			if ($this->falta_turno_presentacion()) {
				$hay_faltantes = true;
			}
		}
		//chequeo de requisitos visibles obligatorios
		if ((!$hay_faltantes) && (kernel::proyecto()->usa_rdi())) {
			if ($this->falta_requisitos_visibles_obligatorio()) {

				$hay_faltantes = true;
			
		}
		}
		
		return $hay_faltantes;
	}
	function falta_requisitos_visibles_obligatorio()
	{
		$faltantes = '';

		$requisitos_a_presentar_obligatorios = kernel::persona()->datos()->get_requisitos_obligatorios_a_presentar();
		$requisitos_aspirante = kernel::persona()->datos()->get_requisitos_aspirante();
		//si hay $requisitos_a_presentar_obligatorios que no estan en $requisitos_aspirante faltantes =true
		if (!empty($requisitos_a_presentar_obligatorios)) {
			if (!empty($requisitos_aspirante)) {
				$faltantes = array_diff(array_column($requisitos_a_presentar_obligatorios, 'requisito'), array_column($requisitos_aspirante, 'requisito'));
				if (!empty($faltantes)) {
					return true;
				}
			} else {
				return true;
			}
		}
		return false;
	}

	function falta_turno_presentacion()
	{
		$turnos = $this->get_listado_turnos();
		foreach ($turnos as $turno) {
			if (!$turno['asignado']) {
				return true;
			}
		}
		return false;
	}

	function get_datos_faltantes($vista_censo)
	{
		$datos = $this->get_datos_preinscripcion();
                //quito del array los campos de secundario

                if (!empty($datos['es_mayor_25_sin_secundario']) && $datos['es_mayor_25_sin_secundario'] == comunes::si) {
                    unset($datos['colegio_secundario']);
                    unset($datos['titulo_secundario']);
                    unset($datos['anio_egreso_sec']);
                    unset($datos['adeuda_materias']);
                    unset($datos['sec_egreso']);
                }
        if (!empty($datos['celular_numero'])) {
			$datos_cel = explode('-', $datos['celular_numero']);
			$datos['celular_caracteristica'] = trim($datos_cel[1]);
			$datos['celular_valor'] = trim($datos_cel[3]);
		}
		
		$forms = $vista_censo->get_forms();
		$campos_pagelets = util::form()->get_campos_secciones_aplanado();
		$datos_faltantes = array();
		
		foreach ($forms as $form) {
			if (isset($campos_pagelets[$form])) {
				$campos_pagelet = $campos_pagelets[$form];
				$hay_faltantes = false;
				foreach ($campos_pagelet as $campo) {
					if (array_key_exists($campo, $datos) && (is_null($datos[$campo])) && (util::form()->visible($campo)) && (util::form()->obligatorio($campo))) {
						if (!$hay_faltantes) {
							$datos_faltantes[$form] = array();
						}
						$datos_faltantes[$form][] = $campo;
						$hay_faltantes = true;
					}
                     if (($form == 'datos_principales') && (($campo == 'celular_caracteristica') || ($campo == 'celular_valor')) && (empty($datos[$campo])) &&
							(util::form()->visible('celular_numero')) && (util::form()->obligatorio('celular_numero'))) {
						/**
						 * Caso especial: celular_numero
						 */

						if (!$hay_faltantes) {
							$datos_faltantes[$form] = array();
						}
						$datos_faltantes[$form][] = $campo;
						$hay_faltantes = true;
					}
					/*Discapacidad*/

					if ($form == 'discapacidad'){
								//si tiene discapacidad pero no marca una de la lista
						if ( $datos['condicion_discapacidad']=='S'&&(
						 $datos['disc_auditiva']=='N' &&
						 $datos['disc_visual']=='N' &&
						 $datos['disc_motora']=='N' &&
						 $datos['disc_cond_psicosocial']=='N' &&
						 $datos['disc_otra']=='N')) {					
							$hay_faltantes = true;
							$datos_faltantes[$form] =  [ 'disc_auditiva', 'disc_visual','disc_motora','disc_cond_psicosocial','disc_otra'];

						}
						if ( $datos['aud_req_otros_apoyos']=='S' && empty($datos['aud_otros_apoyos'])) {
							$hay_faltantes = true;
							$datos_faltantes[$form][] = 'aud_otros_apoyos';
						}
						if ($datos['vis_req_otros_apoyos']=='S' && empty($datos['vis_otros_apoyos'])) {
							$hay_faltantes = true;
							$datos_faltantes[$form][] = 'vis_otros_apoyos';
						}
						if ( $datos['mot_req_otros_apoyos']=='S' && empty($datos['mot_otros_apoyos'])) {
							$hay_faltantes = true;
							$datos_faltantes[$form][] = 'mot_otros_apoyos';
						}
						if ( $datos['psi_req_otros_apoyos']=='S' && empty($datos['psi_otros_apoyos'])) {
							$hay_faltantes = true;
							$datos_faltantes[$form][] = 'psi_otros_apoyos';
						}
						if ( $datos['otra_req_apoyo_vida_diaria']=='S' && empty($datos['otra_apoyo_vida_diaria'])) {
							$hay_faltantes = true;
							$datos_faltantes[$form][] = 'otra_apoyo_vida_diaria';
						}

					}
				}
			}
		}

		// Se controla que haya cargado documentacion
		if (kernel::proyecto()->usa_rdi()) {
			if($this->falta_requisitos_visibles_obligatorio()){
				$datos_faltantes['documentacion'] = array();
				$hay_faltantes = true;
			}
		}
		
		// Se controla que haya cargado un turno de presentaci�n si corresponde
		if (kernel::proyecto()->carga_turno_presentacion()) {
			if ($this->falta_turno_presentacion()) {
				$datos_faltantes['turno_presentacion'] = array();
				$hay_faltantes = true;
			}
		}

		return $datos_faltantes;
	}
	function es_la_primera_impresion()
	{
		$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);
		
		$sql = "SELECT 	version_modificacion,
						version_impresa,
						estado
				FROM	sga_preinscripcion
				WHERE	id_preinscripcion = $id_preinscripcion
				AND version_impresa='0'";
		
		$datos = kernel::db()->consultar_fila($sql);

		return (isset($datos['estado']) && ($datos['estado'] == 'C'));
	}
	
	function hay_cambios_desde_impresion()
	{
		$id_preinscripcion = kernel::db()->quote($this->id_preinscripcion);
		
		$sql = "SELECT 	version_modificacion,
						version_impresa,
						estado
				FROM	sga_preinscripcion
				WHERE	id_preinscripcion = $id_preinscripcion";
		
		$datos = kernel::db()->consultar_fila($sql);
		
		return ($datos['estado'] == 'C') && ($datos['version_impresa'] != $datos['version_modificacion']);
	}
	
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene el valor a mostrar en el comprobante de un campo dado
	 * 
	 * @param $campo
	 * @return string
	 */
	function get_campo_comprobante($campo)
	{
		$datos = $this->get_datos_formulario_impresion();

		if (!is_null($datos[$campo])) {
			$salida = $datos[$campo];

			$campos_si_no = ['tipo_residencia', 'alu_cos_est_ap_fam', 'alu_cos_est_plsoc',
				'alu_cos_est_trab', 'alu_cos_est_beca', 'alu_cos_est_otra', 'tiene_beca_univ',
				'tiene_beca_inter', 'tiene_beca_nacio', 'alu_beca_prov', 'alu_beca_muni', 'alu_beca_otra',
				'alu_beca_tipo_eco', 'alu_beca_tipo_ser', 'alu_beca_tipo_inv', 'alu_beca_eco_tran',
				'alu_beca_eco_efec', 'alu_beca_eco_foto', 'alu_beca_eco_habi', 'alu_beca_eco_come',
				'alu_trab_desjub', 'alu_trab_fami', 'remuneracion', 'alu_est_civil_uh', 'alu_tec_pc_casa',
				'alu_tec_pc_trab', 'alu_tec_pc_univ', 'alu_tec_pc_otro', 'alu_tec_int_casa', 'alu_tec_int_trab',
				'alu_tec_int_univ', 'alu_tec_int_movil', 'alu_tec_int_otro', 'practica_deportes', 
				'hace_dep_gim_priv', 'hace_dep_univ', 'hace_dep_partic', 'hace_dep_otros', 'prac_dep_futbol', 
				'prac_dep_basquet', 'prac_dep_voley', 'prac_dep_gimnasia', 'prac_dep_tenis', 'prac_dep_natacion', 
				'prac_dep_handball', 'prac_dep_otros', 'insc_otra_inst', 'mot_inst_econom', 'mot_inst_prestigio', 
				'mot_inst_difusion', 'mot_inst_rec_estud', 'mot_inst_rec_amigos', 'mot_inst_sist_ing', 
				'mot_inst_ubicacion', 'mot_inst_otros', 'mot_prop_vocacion', 'mot_prop_sug_fam', 'mot_prop_rec_social', 
				'mot_prop_ins_lab', 'mot_prop_perf_prof', 'mot_prop_mejora_ec', 'mot_prop_util_soc', 'mot_prop_otros', 
				'adeuda_materias', 'insc_otra_inst', 'es_celiaco','es_mayor_25_sin_secundario',
				'tiene_cud', 'disc_auditiva', 'disc_visual', 'disc_motora', 'disc_cond_psicosocial', 'disc_otra',
				'aud_lenguaje_senias', 'aud_lenguaje_labial', 'aud_req_interprete_lengua_senias', 'aud_req_aro_magnetico', 'aud_req_otros_apoyos',
				'vis_req_archivo_audio', 'vis_req_texto_digital', 'vis_req_texto_braile', 'vis_req_otros_apoyos',
				'mot_req_apoyo_aula', 'mot_req_otros_apoyos',
				'psi_req_otros_apoyos',
				'otra_req_apoyo_vida_diaria'];

			if (in_array($campo, $campos_si_no)) {
				//---- Campos de selecci�n S� / No -----------------------------
				if ($salida == preinscripcion::si) {
					$salida = kernel::traductor()->trans('si');
				} elseif ($salida == preinscripcion::no) {
					$salida = kernel::traductor()->trans('no');
				}
			} else {
				//---- Control de datos obtenidos de selecci�n m�ltiple --------
				switch ($campo) :
					//---- Carreras --------------------------------------------
					case 'turno_preferido' :
						$opciones = util::opciones()->get_turnos();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Datos principales -----------------------------------
					case 'sexo' :
						$opciones = util::opciones()->get_generos();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
                                        case 'identidad_genero' :
						$opciones = util::opciones()->get_identidad_genero();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'ent_emisora_ci' :
						$opciones = util::opciones()->get_entidades_emisoras_ci();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'pais_emisor_pasap' :
						$opciones = util::opciones()->get_paises();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'tipo_visa' :
						$opciones = util::opciones()->get_tipos_visa();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'residencia' :
						$opciones = util::opciones()->get_residencia();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'pertenece_pueblo_originario' :
						$opciones = util::opciones()->get_si_no_no_desea_contestar();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'pueblo_originario' :
						$opciones = util::opciones()->get_pueblos_originarios();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						if (($datos[$campo] == preinscripcion::pueblo_originario_OTRO) && (!empty($datos['pueblo_originario_otro']))) {
							$salida = $datos['pueblo_originario_otro'];
						}
						break;
					//---- Datos personales -----------------------------------
					case 'obra_social_alu' :
						$opciones = util::opciones()->get_obra_social();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'tipo_res_per_lect' :
						$opciones = util::opciones()->get_tipos_vivienda();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'vive_actual_con' :
						$opciones = util::opciones()->get_vive_con();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'tipo_allegado' :
						$opciones = util::opciones()->get_tipos_allegado();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Datos laborales -------------------------------------
					case 'existe_trab_alum' :
						$opciones = util::opciones()->get_existe_trab_alu();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_trab_hace' :
						$opciones = util::opciones()->get_alu_trab_hace();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_trab_ocup' :
						$opciones = util::opciones()->get_trab_ocup();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'hora_sem_trab_alum' :
						$opciones = util::opciones()->get_hora_sem_trab_alum();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'rel_trab_propuesta' :
						$opciones = util::opciones()->get_rel_trab_propuesta();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Datos familiares ------------------------------------
					case 'estado_civil' :
						$opciones = util::opciones()->get_estados_civiles();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'cant_hijos_alum' :
						$opciones = util::opciones()->get_cant_hijos_o_fam_a_cargo();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'cant_fami_cargo' :
						$opciones = util::opciones()->get_cant_hijos_o_fam_a_cargo();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_vive' :
						$opciones = util::opciones()->get_si_no_desconoce(true);
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'ult_est_cur_padre' :
						$opciones = util::opciones()->get_niveles_estudio();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'sit_laboral_padre' :
						$opciones = util::opciones()->get_sit_laboral_padres();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_trab_hace' :
						$opciones = util::opciones()->get_padres_trab_hace();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_trab_ocup' :
						$opciones = util::opciones()->get_trab_ocup(true);
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'padre_trab_otro' :
						$opciones = util::opciones()->get_otra_ocupacion();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_vive' :
						$opciones = util::opciones()->get_si_no_desconoce(true);
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'ult_est_cur_madre' :
						$opciones = util::opciones()->get_niveles_estudio();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'sit_laboral_madre' :
						$opciones = util::opciones()->get_sit_laboral_padres();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_trab_hace' :
						$opciones = util::opciones()->get_padres_trab_hace();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_trab_ocup' :
						$opciones = util::opciones()->get_trab_ocup(true);
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'madre_trab_otro' :
						$opciones = util::opciones()->get_otra_ocupacion();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Datos estudios --------------------------------------
					case 'orientacion_recibida' :
						$opciones = util::opciones()->get_orientacion_vocacional();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_tipo' :
						$opciones = util::opciones()->get_tipos_estudios_superiores();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_esta' :
						$opciones = util::opciones()->get_estados_estudios_sup();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_area' :
						$opciones = util::opciones()->get_areas_estudios_sup();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_niv' :
						$opciones = util::opciones()->get_niveles_otestsup();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_otestsup_razon_abandono' :
						$opciones = util::opciones()->get_razones_abandono_estudio();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						if (($datos[$campo] == preinscripcion::razon_abandono_OTRO) && (!empty($datos['alu_otestsup_razon_abandono_otros']))) {
							$salida = $datos['alu_otestsup_razon_abandono_otros'];
						}
						break;
					//---- Idiomas ---------------------------------------------
					case 'alu_idioma_ingl' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_alem' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_fran' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_ital' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_port' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_chino' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'alu_idioma_otro_nivel' :
						$opciones = util::opciones()->get_niveles_idiomas();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					//---- Discapacidad ---------------------------------------------
					case 'condicion_discapacidad' :
						$opciones = util::opciones()->get_condicion_discapacidad();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'aud_tipo' :
						$opciones = util::opciones()->get_dificultades_discapacidad();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
					case 'vis_dificultad_para_ver' :
						$opciones = util::opciones()->get_dificultades_discapacidad();
						$salida = (!empty($opciones[$salida])) ? $opciones[$salida] : '--';
						break;
				endswitch;
			}
			return $salida;
		}
		return '--';
	}
	
	//--------------------------------------------------------------------------
	//---- Requisitos
	//--------------------------------------------------------------------------
	
	/**
	 * Obtiene el conjunto de requisitos de ingreso que debe presentar el aspirante
	 * en funci�n de las propuestas en las que est� inscripto.
	 * 
	 * @return array
	 */
	function get_requisitos_a_presentar()
	{
		$propuestas = array();
		$inscripciones = $this->get_datos_inscripciones();
		foreach ($inscripciones as $inscripcion) {
			$propuestas[] = $inscripcion['propuesta'];
		}
		if (!empty($propuestas)) {
			$requisitos = g3::requisitos()->requisitos_a_solicitar($propuestas);
			$nuevo = array();
			foreach ($requisitos as $dato) {
				//---- ID encriptado -------------------------------------------
				$id = util::catalogo()->generar_id($dato['requisito']);
				$nuevo[$id] = $dato;
				$nuevo[$id][catalogo::id] = $id;
			}
			$requisitos = $nuevo;
		} else {
			$requisitos = array();
		}
		return $requisitos;
	}
/**
	 * Obtiene el conjunto de requisitos obligatorios de ingreso que debe presentar el aspirante
	 * en funci�n de las propuestas en las que est� inscripto.
	 * 
	 * @return array
	 */
	function get_requisitos_obligatorios_a_presentar()
	{
		$propuestas = array();
		$inscripciones = $this->get_datos_inscripciones();
		foreach ($inscripciones as $inscripcion) {
			$propuestas[] = $inscripcion['propuesta'];
		}
		if (!empty($propuestas)) {
			$requisitos = g3::requisitos()->requisitos_a_solicitar($propuestas);
			$nuevo = array();
			foreach ($requisitos as $key => $dato) {
				if ($dato['doc_digital_obligatorio_preinscripcion']=='S'){
				//---- ID encriptado -------------------------------------------
				$id = util::catalogo()->generar_id($dato['requisito']);
				$nuevo[$id] = $dato;
				$nuevo[$id][catalogo::id] = $id;
				}
			}
			$requisitos = $nuevo;
		} else {
			$requisitos = array();
		}
		return $requisitos;
	}
	
	/**
	 * Obtiene los requisitos digitales que ya fueron cargados por el aspirante
	 * 
	 * @return array
	 */
	function get_requisitos_aspirante()
	{
		if (!isset($this->requisitos)) {
			$datos = util::consultas()->get_requisitos_digitales($this->id_preinscripcion);
			$nuevo = array();
			foreach ($datos as $dato) {
				//---- ID encriptado -------------------------------------------
				$id = util::catalogo()->generar_id($dato['requisito']);
				$nuevo[$id] = $dato;
				$nuevo[$id][catalogo::id] = $id;
			}
			$this->requisitos = $nuevo;
		}
		return $this->requisitos;
	}
	
	function set_requisito($datos)
	{
		$datos['id_preinscripcion'] = $this->id_preinscripcion;
		abm::alta('sga_requisitos_digitales', $datos);
		unset($this->requisitos);
	}
	
	function eliminar_requisito($datos)
	{
		$datos['id_preinscripcion'] = $this->id_preinscripcion;
		abm::baja('sga_requisitos_digitales', $datos);
		unset($this->requisitos);
	}

}