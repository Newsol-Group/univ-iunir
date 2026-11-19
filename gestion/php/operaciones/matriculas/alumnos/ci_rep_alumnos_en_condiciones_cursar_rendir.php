<?php
class ci_rep_alumnos_en_condiciones_cursar_rendir extends ci_rep_sin_listado_inicial
{
    public $s__elemento         = "";
    public $s__datos_cuadro     = "";
    public $s__para             = "";
    public $s__materia          = " --- ";
    public $s__propuesta_nombre  = " --- ";//Guardo el nombre de la propuesta para el email
    
	function get_tabla_principal()
	{
		return 'sga_alumnos';
	}

	function get_datos($filtro)
	{
		try {
			$alumnos = guarani::co('co_alumnos')->get_listado_alumnos_en_condiciones_de_cursar_rendir_una_actividad($this->s__elemento, $filtro);
			$datos = array();
			$regla = ($this->s__para == alumno::cursar_rendir_cursar_valor) ? guarani::regla(regla::correlativas_cursada) : guarani::regla(regla::correlativas_aprobacion);
			foreach ($alumnos as $alumno) {
				$parametros = array();
				$parametros['alumno'] = $alumno['alumno'];
				$parametros['elemento'] = intval($this->s__elemento);
				$parametros['plan_version'] = $alumno['plan_version'];
				$parametros['fecha'] = guarani_fecha::get_hoy();
				$regla->set_parametros_contexto($parametros);
				$componente_aprobado = $regla->validar();
				if ($componente_aprobado) {
					$datos[] = $alumno;
				}
			}
			$this->s__datos_cuadro = $datos;
			return $datos;
		} catch (toba_error $e) {
			toba::logger()->error($e->get_mensaje());
			throw new guarani_error($e->getMessage());
		}
	}

    //---- filtro -----------------------------------------------------------------------

	function conf__filtro(\toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('responsable_academica')->set_condicion_fija('es_igual_a');
        $filtro->columna('propuesta')->set_condicion_fija('es_igual_a');
        $filtro->columna('actividad')->set_condicion_fija('es_igual_a');
        $filtro->columna('para')->set_condicion_fija('es_igual_a');
        $filtro->columna('alumno_cond')->set_condicion_fija('es_igual_a');
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos)) {
			
			$this->s__elemento = $datos['actividad']['valor'];
			$this->s__propuesta_nombre = guarani::co('co_propuestas')->get_datos_propuesta($datos['propuesta']['valor'])['nombre'];
			$this->s__materia = current(guarani::co('co_elementos')->get_actividad_nombre($this->s__elemento))['nombre_actividad'];

			$elemento = $this->s__elemento;
			$propuesta = $datos['propuesta']['valor'];

			// Filtro: La actividad se encuentra en la version del plan actual del alumno
			$filtro_actividad = " AND sga_alumnos.plan_version IN (SELECT DISTINCT plan_version FROM vw_actividades_plan WHERE propuesta = $propuesta AND elemento = $elemento)";
			
			// Filtro: Actividad se encuentra regularizada vigente.
			$regularizada = " 1 IN (SELECT 1 FROM vw_regularidades_basica as b WHERE b.alumno = sga_alumnos.alumno AND b.elemento = $elemento AND b.resultado = '".comunes::resultado_nota_aprobado."' AND b.es_vigente = 1)";
			
			// Filtro: Que este inscripto a cursada, exista en una comision o en acta de cursada cerrada.
			$insc_cursada = " 1 IN (-- Inscripción a cursada
			                          SELECT 1 
			                            FROM vw_insc_cursada as i 
			                            JOIN sga_comisiones as c ON c.comision = i.comision 
						   WHERE i.alumno = sga_alumnos.alumno 
						     AND c.elemento = $elemento
						  UNION 
						   -- Se encuentra en una comision de la misma actividad
						   SELECT 1 
						  FROM sga_eval_detalle_cursadas as d
						  JOIN sga_evaluaciones as e ON e.evaluacion = d.evaluacion
						  JOIN sga_evaluaciones_tipos as et ON et.evaluacion_tipo = e.evaluacion_tipo
						  JOIN sga_comisiones as c ON c.entidad = e.entidad
						   WHERE d.alumno = sga_alumnos.alumno 
						     AND c.elemento = $elemento
						     AND et.automatica = '".comunes::si."'
						  UNION 
						   -- Se encuentra en un acta de cursada cerrada de la actividad
						   SELECT 1 
			                           FROM sga_actas_detalle as d
						    JOIN sga_actas as a ON a.id_acta = d.id_acta
			                           JOIN sga_comisiones as c ON c.comision = a.comision 
						   WHERE d.alumno = sga_alumnos.alumno 
						     AND c.elemento = $elemento
						     AND a.estado = '".acta::estado_cerrada."'
						     AND d.estado = '". acta::detalle_estado_activo ."'
						     AND d.rectificado = '".comunes::no."' 
						  )
			
			";
			
			// Filtro: Que no este en un acta de examen cerrada con resultado aprobado/desaprobado. El ausente no se considera.
			$rinde_1ra_vez = " 1 NOT IN (	SELECT	1 
							FROM sga_actas_detalle as d 
							JOIN sga_actas as a ON a.id_acta = d.id_acta 
							JOIN sga_llamados_mesa as llm ON llm.llamado_mesa = a.llamado_mesa 
							JOIN sga_mesas_examen as me ON me.mesa_examen = llm.mesa_examen 
							WHERE	d.alumno = sga_alumnos.alumno AND
								d.estado = '". acta::detalle_estado_activo ."' AND
								d.rectificado = '".comunes::no."' AND
								d.resultado IN ('".escalas_notas::resultado_aprobado."','".escalas_notas::resultado_reprobado."') AND -- No considero si estuvo ausente.
								a.estado = '".acta::estado_cerrada."' AND -- Cerrada
								me.elemento = $elemento
							)";
								
			// Filtro:  Es recursante: La cursó y desaprobó 
			$es_recursante = " 1 IN (SELECT	1
						   FROM sga_actas_detalle as d
						   JOIN sga_actas as a ON a.id_acta = d.id_acta
					 	   JOIN sga_comisiones as c ON c.comision = a.comision
					 	  WHERE d.alumno = sga_alumnos.alumno AND
							d.estado = '". acta::detalle_estado_activo ."' AND
							d.rectificado = '".comunes::no."' AND
							d.resultado = '".escalas_notas::resultado_reprobado."' AND -- Desaprobado
							a.origen = '".acta::origen_regulares."' AND -- Acta de Cursada
							a.estado = '". acta::estado_cerrada ."' AND -- Cerrada
							c.elemento = $elemento
						)";

			$filtro_alumno = '';
			if ((isset($datos['para']))) {
				//PARA CURSAR
				if ($datos['para']['valor'] == alumno::cursar_rendir_cursar_valor) {
					$this->s__para = alumno::cursar_rendir_cursar_valor;      
					if (isset($datos['alumno_cond'])) {
						//NO TIENE LA MATERIA REGULARIZADA, para cursar no debe tener la materia regularizada
						$filtro_alumno .= ' AND NOT' . $regularizada;
						//CURSAR POR PRIMERA VEZ
						if ($datos['alumno_cond']['valor'] == alumno::cursar_rendir_c_primera_vez_valor) {
							$filtro_alumno .= ' AND NOT '. $insc_cursada;
						}	
						//CURSA - RECURSANTE
						if ($datos['alumno_cond']['valor'] == alumno::cursar_rendir_recursante_valor) {
							$filtro_alumno .=  ' AND '.$es_recursante;
						}	
					}
				//PARA RENDIR
				} else {                
					$this->s__para = alumno::cursar_rendir_rendir_valor;   
					             
					//RENDIR POR PRIMERA VEZ
					if ($datos['alumno_cond']['valor'] == alumno::cursar_rendir_r_primera_vez_valor) {
						$filtro_alumno .= ' AND '. $rinde_1ra_vez;
					}	
					//TIENE LA MATERIA REGULARIZADA, este filtro siempre lo pone al tener el filtro rendir
					$filtro_alumno .= ' AND ' . $regularizada;
				}            
			}

			$this->dep('filtro')->columna('responsable_academica')->resetear_estado();
			$this->dep('filtro')->columna('actividad')->resetear_estado();
			$this->dep('filtro')->columna('para')->resetear_estado();
			$this->dep('filtro')->columna('alumno_cond')->resetear_estado();

			$this->s__datos = $datos;
			$this->s__filtro = " {$this->dep('filtro')->get_sql_where()} $filtro_actividad $filtro_alumno";
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}
	}
    
    function evt__envio_mail(){
        if (isset($this->s__datos_cuadro)){
            
            $estado = ($this->s__para === alumno::cursar_rendir_cursar_valor) ? strtolower(alumno::cursar_rendir_cursar_descr) : strtolower(alumno::cursar_rendir_rendir_descr);
            foreach ($this->s__datos_cuadro as $alum) {
                try {
                    $parametros['nombre_actividad'] = $this->s__materia;
                    $parametros['estado'] = $estado;
                    $parametros['email'] = $alum['email'];					
                    $parametros['nombre_alumno'] = $alum['apellido_nombres'];
                    $parametros['persona'] = $alum['persona'];
                    $parametros['nombre_propuesta'] = $this->s__propuesta_nombre;	
                    guarani::notificador()->enviar(notificador::cursar_rendir, $parametros);
                } catch (toba_error $e) {
					// Falla el envio del email
					toba::logger()->error($e->get_mensaje());
					$mensaje = guarani_mensajes::get('800_no_se_pudo_enviar_email');
					throw guarani::error($mensaje);
				}
            }
            $msje = guarani_mensajes::get('800_envio_email_exitoso');
            toba::notificacion()->agregar($msje, comunes::error_informacion);
        }        
    }
}
