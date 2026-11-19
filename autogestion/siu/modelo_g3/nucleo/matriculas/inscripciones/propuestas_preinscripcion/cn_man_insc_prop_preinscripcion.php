<?php
/**
 * 
 * 
 * @package 	Matrícula
 * @subpackage 	Inscripciones
 *
 */
class cn_man_insc_prop_preinscripcion extends guarani_pers_cn
{
	// Listas de plan_version y periodo_insc, utilizados para obtener los requisitos 
	// correspondientes para la lista de la última pantalla.
	protected $s__planes;
	protected $s__periodos_inscripcion;
	protected $s__propuesta_aspira;
	protected $s__datos_inscripciones;

	function cargar_atributos_parametros($propuestas, $planes)
	{
		$operacion = guarani::get_id_operacion();        
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, $planes);
	}

	function resetear()
	{
		unset($this->s__planes);
		unset($this->s__periodos_inscripcion);
		unset($this->s__propuesta_aspira);
		unset($this->s__datos_inscripciones);
	}
    
    function get_listado($filtro)
    {
        $datos = [];
        //Voy a buscar la query de preinscriptos de autogestión
        if ($filtro['origen'] == preinscripcion::origen_ambos || $filtro['origen'] == preinscripcion::origen_autogestion) {
            $datos = toba::consulta_php('co_preinscripciones')->get_datos_desde_autogestion($filtro['where_3wdatos'], $filtro['where_3wpropuesta']);
		}        
        //Voy a buscar la query de preinscriptos de preinscripcion
        if ($filtro['origen'] == preinscripcion::origen_ambos || $filtro['origen'] == preinscripcion::origen_preinscripcion) {
                   
            //Si la base no está seleccionada, voy a hacer la query en las n bases.
            $bases = [];
            if (isset($filtro['base'])) {
                $bases[] = $filtro['base'];
            } else {
                $bases = $this->get_bases_preinscripcion(preinscripcion::origen_preinscripcion);
                $bases = array_column($bases, 'fuente_de_datos');            
            }

            foreach($bases as $base) {
                $this->validar_conexion_preinscripcion($base);
                $datos = array_merge($datos, toba::consulta_php('co_preinscripciones')->
                            get_datos_desde_preinscripcion($filtro['where_preinscripciondatos'], $filtro['where_preinscripcionpropuesta'], $base));
            }
            
        }

        //Ordeno por origen y luego por apellido y nombre.
        return rs_ordenar_por_columnas($datos, ['origen', 'apellido_nombres']);
    }
    
    public function get_bases_preinscripcion($origen)
    {   
        $bases = [];
        //si el origen es preinscripcion listo las bases que tenga.
        if ($origen == preinscripcion::origen_preinscripcion) {
            $bases = toba::consulta_php('co_preinscripciones')->get_fuentes_datos_preinscripcion();
        }        
        return $bases;
    }

	public function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_preinscripcion_propuesta');
		} else {
			parent::etiquetas($dep);
		} 
	}

    /*
     * Armado de filtro
     */
    function armar_filtro_condicion($datos)
    {
         //Traigo los periodos de inscripcion al anio academico para luego filtrar
        $filtro_anio = '';
        if (isset($datos['anio_academico'])) {
            $periodosInsc = array_column(toba::consulta_php('co_periodos_de_inscripcion')->get_periodos_insc_fechas_preinsc_de_anio_academico($datos['anio_academico']['valor']), 'periodo_insc');
            $filtro_anio .= " AND ".guarani_sql::armar_condicion_in($periodosInsc, 'tabla', 'periodo_insc');
        }
        
        $filtro_apyn = '';
        if (isset($datos['apellido_nombre'])) {            
            $filtro_apyn .= " AND tabla.apellido || ' ' || tabla.nombre ILIKE '%".$datos['apellido_nombre']['valor']."%'";            
        }
        
        $filtro_identificacion = '';
        if (isset($datos['identificacion'])) {            
            $filtro_identificacion .= " AND tabla.nro_documento ILIKE '%".$datos['identificacion']['valor']."%'";            
        }
        
        $filtro_propuesta = '';
        if (isset($datos['propuesta'])) {            
            $filtro_propuesta .= " AND tabla.propuesta = ".$datos['propuesta']['valor'];
        }
        
        $filtro_ubicacion = '';
        if (isset($datos['ubicacion'])) {            
            $filtro_ubicacion .= " AND tabla.ubicacion = ".$datos['ubicacion']['valor'];
        }
        
        $filtro_tramite = '';
        if (isset($datos['tramite'])) {            
            $filtro_tramite .= " AND tabla.id_preinscripcion || '/' || tabla.version_impresa = '".$datos['tramite']['valor']."'";
        }
        
        $filtro_tiene_atendedor = '';
        if (isset($datos['tiene_atendedor'])) {            
            $filtro_tiene_atendedor .= ($datos['tiene_atendedor']['valor']=="S")? " AND tabla.atiende <> NULL ": " AND tabla.atiende is NULL";
        }
        
        $filtro_atendido_por = '';
        if (isset($datos['atendido_por'])) {            
            $filtro_atendido_por .= " AND tabla.atendido_por = '".$datos['atendido_por']['valor']."'";
        }

		$filtro_duplicada_preinscripcion = '';
		$filtro_duplicada_3w = '';
		if (isset($datos['duplicada'])) {
			$filtro_duplicada_preinscripcion .= " AND sga_preinscripcion.duplicada = '{$datos['duplicada']['valor']}'";
			if ($datos['duplicada']['valor'] == comunes::si) {
				$filtro_duplicada_3w .= ' AND false'; // No existen cuentas duplicadas en Autogestión.
			}
		}

        $filtro_origen = '';
        //Si solo hacer la querys desde presincripcion voy a guardar que base 
        // que eligió si hay alguna elegida
        $origen = preinscripcion::origen_ambos;
        $base = null;
        if (isset($datos['origen'])) {
            $origen = $datos['origen']['valor'];
            if ($datos['origen']['valor'] == preinscripcion::origen_preinscripcion) {
                if (isset($datos['base'])) {                
                    $base = $datos['base']['valor'];
                }                
            }
        }
        $filtro = [];
        $filtro['where_preinscripciondatos'] = $this->get_where_preinscripciondatos($filtro_apyn.$filtro_identificacion.$filtro_tramite.$filtro_duplicada_preinscripcion);
        $filtro['where_preinscripcionpropuesta'] = $this->get_where_preinscripcionpropuesta($filtro_anio.$filtro_propuesta.$filtro_ubicacion.$filtro_tiene_atendedor.$filtro_atendido_por);
        $filtro['where_3wdatos'] = $this->get_where_3wdatos($filtro_apyn.$filtro_identificacion.$filtro_duplicada_3w);
        $filtro['where_3wpropuesta'] = $this->get_where_3wpropuesta($filtro_anio.$filtro_propuesta.$filtro_ubicacion.$filtro_tiene_atendedor.$filtro_atendido_por);
        $filtro['origen'] = $origen;
        $filtro['base'] = $base;
        return $filtro;
    }
    
    /*
     * Funciones que arman los where para las fuentes.
     */    
    function get_where_preinscripciondatos($where)
    {   
        $where = str_replace("tabla.apellido || ' ' || tabla.nombre", "COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) || ' ' || COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres)", $where);
        $where = str_replace('tabla.nro_documento', 'sga_preinscripcion.nro_documento', $where);
		$where = str_replace("tabla.id_preinscripcion || '/' || tabla.version_impresa", "sga_preinscripcion.id_preinscripcion || '/' || sga_preinscripcion.version_impresa", $where);
       return $where;
    }
    
    function get_where_preinscripcionpropuesta($where)
    {
        $where = str_replace('tabla.propuesta', 'sga_preinscripcion_propuestas.propuesta', $where);
        $where = str_replace('tabla.ubicacion', 'sga_preinscripcion_propuestas.ubicacion', $where);
        $where = str_replace('tabla.periodo_insc', 'sga_preinscripcion_propuestas.periodo_insc', $where);
		$where = str_replace('tabla.atiende', 'sga_preinscripcion_propuestas.atiende', $where);
		$where = str_replace('tabla.atendido_por', 'sga_preinscripcion_propuestas.atiende', $where);
		
        return $where;
    }

    function get_where_3wdatos($where)
    {
        $where = str_replace("tabla.apellido || ' ' || tabla.nombre", "vw_personas.apellido_nombres", $where);
        $where = str_replace('tabla.nro_documento', 'vw_personas.nro_documento', $where); 
        return $where;
    }
    
    function get_where_3wpropuesta($where)
    {
        $where = str_replace('tabla.propuesta', 'sga_preinscripcion_propuesta.propuesta', $where);
        $where = str_replace('tabla.ubicacion', 'sga_preinscripcion_propuesta.ubicacion', $where);
        $where = str_replace('tabla.periodo_insc', 'sga_preinscripcion_propuesta.periodo_insc', $where);
		$where = str_replace('tabla.atiende', 'sga_preinscripcion_propuesta.atiende', $where);        
		$where = str_replace('tabla.atendido_por', 'sga_preinscripcion_propuesta.atiende', $where);        
        return $where;
    }
    
	//-----------------------------------------------------------------------------------
	//---- Listados / Información
	//-----------------------------------------------------------------------------------
    
	/**
	 * Obtiene los datos personales y censales que el aspirante cargó en su ficha
	 * de Preinscripción.
	 * 
	 * @param int $preinscripcion
	 * @return array
	 */
	function get_datos_preinscripcion($id_preinscripcion, $version_impresa)
	{
		return toba::consulta_php('co_preinscripciones')->get_datos_preinscripcion_version_impresa($id_preinscripcion, $version_impresa);
	}

	/**
	 * Obtiene las preinscripciones que un aspirante efectó mediante el módulo de Preinscripción
	 * 
	 * @param int $preinscripcion
	 * @return array
	 */
	function get_preinscripciones($preinscripcion)
	{
		return toba::consulta_php('co_preinscripciones')->get_preinscripciones_preparadas_insc_por_id($preinscripcion['id']);
	}

	/**
	 * Obtiene las preinscripciones que un alumno de la Institución efectó mediante
	 * el módulo de Autogestión
	 * 
	 * @param int $persona
	 * @return array
	 */
	function get_preinscripciones_desde_autogestion($persona)
	{
		return toba::consulta_php('co_preinscripciones')->get_preinscripciones_desde_autogestion($persona['id']);
	}

	function get_datos_ev_info($persona)
	{
		$datos_persona = toba::consulta_php('co_personas')->get_datos_persona($persona);
		$datos_mail = current(toba::consulta_php('co_personas')->get_datos_persona_mail($persona));

		$datos = array();
		$datos['apellido_nombres'] = $datos_persona['nombre_completo'];
		$datos['identificacion'] = $datos_persona['documento'];
		$datos['mail'] = $datos_mail['email'];

		return $datos;
	}

	function get_estado_propuesta_aspira($persona, $propuesta)
	{
		$propuesta_aspira = toba::consulta_php('co_inscripciones')->get_id_ultima_propuesta_aspira($persona, $propuesta);
		return toba::consulta_php('co_inscripciones')->get_descr_estado_propuesta_aspira($propuesta_aspira);
	}
	
	function get_inscripciones_procesadas()
	{
		if (empty($this->s__datos_inscripciones)) {
			$this->s__datos_inscripciones = toba::consulta_php('co_inscripciones')->get_datos_inscripcion($this->s__propuesta_aspira);
		}
		return $this->s__datos_inscripciones;
	}
	
	function get_propuestas_aspira()
	{
		return $this->s__propuesta_aspira;
	}
	
	/**
	 * Función para restaurar las propuestas aspira al regreso de la navegación
	 * desde Administrar Requisitos de una Persona
	 * 
	 * @param array $propuestas_aspira
	 */
	function set_propuestas_procesadas($propuestas_aspira)
	{
		$this->s__propuesta_aspira = $propuestas_aspira;
	}

	//-----------------------------------------------------------------------------------
	//---- Validaciones
	//-----------------------------------------------------------------------------------

	function validar_propuestas_preinscripcion($id_preinscripcion)
	{        
		$datos_propuestas_preinsc = toba::consulta_php('co_preinscripciones')->get_preinscripciones_preparadas_insc_por_id($id_preinscripcion);
		if (empty($datos_propuestas_preinsc)) {
			$mje = guarani_mensajes::get('800MAT_codigo_sin_preinscripciones');
			throw guarani::error($mje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Procesamiento desde PREINSCRIPCIÓN/AUTOGESTIÓN
	//-----------------------------------------------------------------------------------

	function procesar_persona($datos)
	{
		try {
			toba::db()->abrir_transaccion();

			$persona = preinscripcion::procesar_persona($datos);

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}

		return $persona;
	}

	/**
	 * 
	 * 
	 * @param array $datos
	 * @param int $persona
	 * @param int $id_preinscripcion
	 * @throws toba_error
	 */
	function procesar_inscripciones($datos, $persona, $id_preinscripcion, $origen)
	{
		$this->validar_cant_inscripciones_periodo($persona, $datos);

		$this->s__planes = array();
		$this->s__periodos_inscripcion = array();
		$this->s__propuesta_aspira = array();

		try {
			toba::db()->abrir_transaccion();

			foreach ($datos as $inscripcion) {
				if ($inscripcion['procesar']) {
					$anio_periodo = explode('|', $inscripcion['anio_periodo_insc']);
					$anio_academico = $anio_periodo[0];
					$periodo_inscripcion = $anio_periodo[1];
					$periodo_insc = $anio_periodo[2];

					$this->s__propuesta_aspira = array_merge($this->s__propuesta_aspira, preinscripcion::procesar_inscripcion($persona, $periodo_inscripcion, $periodo_insc, $anio_academico, $inscripcion['propuesta'], $inscripcion['plan_version'], $inscripcion['modalidad'], $inscripcion['ubicacion'], 'now()', $inscripcion['tipo_ingreso']));
					
					guarani::act('act_preinscripciones')->marcar_inscripcion_propuesta($inscripcion,$origen);
					
					// Se agrega el plan a la lista de planes.
					$this->s__planes[] = array('plan_version' => $inscripcion['plan_version']);
					$this->s__periodos_inscripcion[] = array('periodo_inscripcion' => $periodo_inscripcion);
				}
			}
			if ($origen == preinscripcion::origen_preinscripcion) {
				// Cerrar la preinscripción (a partir de este momento, gestionará todo a través de 3w)
				guarani::act('act_preinscripciones')->cerrar_preinscripcion($id_preinscripcion, $persona);
			}

			toba::db()->cerrar_transaccion();
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			throw $e;
		}
	}

    /*
     * Voy armar los datos que le voy a pasar al notificador para que envie el mail.
     */
    function preparar_envio_mail($persona, $propuestas, $origen)
    {
        // Busco los dato de la persona y de la propuesta para pasarselo al notificador
        $datos_persona_mail = current(toba::consulta_php('co_personas')->get_datos_persona_mail($persona));
        //Busco los datos de las propuestas.        
        $where_propuestas = 'sga_propuestas.propuesta IN (' . implode(',', $propuestas).')';
        $datos_propuestas = toba::consulta_php('co_propuestas')->get_listado($where_propuestas);
        $nombres_propuestas = implode(', ',array_column($datos_propuestas, 'propuesta_nombre'));
                
        $parametros = [];
        $parametros['persona'] = $persona;
        $parametros['nombre_alumno'] = $datos_persona_mail['nombre_completo'];
        $parametros['nombre_propuesta'] = $nombres_propuestas;
        $parametros['usuario'] = $datos_persona_mail['usuario'];
        $parametros['link_autogestion'] = guarani_instalacion_conf::get_parametro_instalacion('url3w',true);
        $solo_reciben_mail = ($origen == preinscripcion::origen_autogestion);
		$parametros['email'] = toba::consulta_php('co_personas')->get_emails_personas([$persona], $solo_reciben_mail);
        guarani::notificador()->enviar(notificador::inscripcion_propuesta, $parametros, false);
    }

    function get_tiene_requisitos_digitales($id, $origen, $fuente)
    {
        //Verifica si tiene requisitos digitales
        if ($origen == preinscripcion::origen_autogestion) {
            $requisitos = toba::consulta_php('co_requisitos')->get_requisitos_digitales_preinscripcion($id);
        } else {
            $requisitos = toba::consulta_php('co_preinscripciones')->get_requisitos_digitales_preinscripcion($id, $fuente);
        }
        return count($requisitos) > 0;
    }
    
	//--------------------------------------------------------------------------
	//---- Validaciones de inscripciones
	//--------------------------------------------------------------------------

    function validar_conexion_preinscripcion($fuente)
	{
		$instancia = toba::instancia()->get_id();
		$proyecto = toba_proyecto::get_id();		

		$res = toba_dba::get_bases_definidas();
		$nombre = $instancia . ' ' . $proyecto . ' ' . $fuente;

		if (empty($res[$nombre])) {
			$mje = guarani_mensajes::get('800MAT_error_conexion_preinscripcion');
            toba::logger()->error($mje. " La conexión a la fuente de datos $fuente en instalacion/bases.ini no existe");
			throw guarani::error($mje);
		}
	}

	function validar_seleccion_insc($datos_inscripciones)
	{
		if (!empty($datos_inscripciones)) {
			foreach ($datos_inscripciones as $inscripcion) {
				if ($inscripcion['procesar']) {
					return true;
				}
			}
		}

		throw guarani::error(toba::mensajes()->get('800_no_seleccion', ['una inscripción']));
	}

	function validar_cant_inscripciones_periodo($persona, $datos_inscripciones)
	{
		foreach ($datos_inscripciones as $inscripcion) {
			if ($inscripcion['procesar']) {
				$anio_periodo = explode('|', $inscripcion['anio_periodo_insc']);
				$periodo_insc = $anio_periodo[2];
				$filtro = "	sga_situacion_aspirante.resultado_asp = '" . propuesta::inscripcion_rechazada . "' AND
							sga_propuestas_aspira.periodo_insc = $periodo_insc";
				$cant_inscripciones = toba::consulta_php('co_inscripciones')->get_cant_inscripciones($persona, $inscripcion['propuesta'], null, $filtro);
				if ($cant_inscripciones != 0) {
					$datos_propuesta = toba::consulta_php('co_propuestas')->get_datos_propuesta($inscripcion['propuesta']);
					$mje = guarani_mensajes::get('800MAT_persona_rechazada_periodo', array($datos_propuesta['nombre_abreviado']));
					throw guarani::error($mje);
				}
			}
		}
	}

	// cambio estado de p a s por solicitud de informacion
	function cambio_estado_preinscripcion($preinscripcion_propuesta, $id_preinscripcion, $fuente, $usuario_gestion, $mensaje)
	{//fuente para determina si es de preinscripcion o g3
		guarani::act('act_preinscripciones')->cambio_estado_inscripcion_propuesta($preinscripcion_propuesta,$id_preinscripcion, $mensaje, $fuente);
		//si cambio el estado cammbia atendedor
		$this->cambio_atendedor_preinscripcion($id_preinscripcion, $fuente, $usuario_gestion);
		//registro log de cambio #34402
		$this->registro_preinscripcion_log($preinscripcion_propuesta, $id_preinscripcion, $mensaje, preinscripcion::insc_solicitud_informacion, $fuente);
	}

	// cambio atendedor
	function cambio_atendedor_preinscripcion($id_preinscripcion, $fuente, $usuario_gestion)
	{
		guarani::act('act_preinscripciones')->cambio_atendedor($id_preinscripcion, $fuente, $usuario_gestion);
	}

	// cambio estado de p a s por solicitud de informacion
	function get_historial_preinscripcion($preinscripcion)
	{
		return toba::consulta_php('co_preinscripciones')->get_historial_preinscripcion($preinscripcion);
	}

	function registro_preinscripcion_log($preinscripcion_propuesta, $id_preinscripcion, $mensaje, $estado, $fuente)
	{
		guarani::act('act_preinscripciones')->registro_preinscripcion_log($preinscripcion_propuesta, $id_preinscripcion, $mensaje, $estado, $fuente);
	}

}