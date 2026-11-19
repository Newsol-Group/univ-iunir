<?php
class regla_control_tope_actividades_a_cursar_modulo_generica extends regla
{
	function validar()
	{
		/*
		  Regla que verifica que el alumno no curse mas de n actividades de un módulo.
		  Se controlan actividades aprobadas por examen final, promocion, equivalencia total y regularidad, cursadas aprobadas vigentes e inscripciones a comisiones de  periodos lectivos vigentes.
		  Solo se controla si la actividad en la que se inscribe el alumno pertenece a un unico módulo en el plan de estudios (no es una genérica)
		  y que tenga definido un valor tope de cantidad de actividades a cursar.	
		*/
	
		// Parámetros de contexto
		$alumno = $this->get_parametro('alumno');
		$actividad = $this->get_parametro('elemento');
		$fecha = $this->get_parametro('fecha');

		// Se obtiene la versión del plan del alumno. Se obtiene del parametro y si no viene se obtiene la version de plan actual del alumno.
		$plan_version = $this->existe_parametro('plan_version') ? $this->get_parametro('plan_version') : guarani::alumno()->get_plan_version($alumno, $fecha);

		// Verifico en que módulo se encuentra la actividad. 
		// Solo módulos que tengan definido la cantidad maxima de actividades a cursar
		$sql = "SELECT e_mod.elemento as modulo, 
				ep_mod.elemento_revision as modulo_revision, 
				ep_mod.nombre as modulo_nombre,
				ep_mod.tope_cursado_tipo,
				ep_mod.tope_cursado_cantidad,
				est_mod.entidad_subtipo
			FROM sga_elementos_revision as er_act
			JOIN sga_elementos_plan as ep_act ON ep_act.elemento_revision = er_act.elemento_revision AND ep_act.plan_version = $plan_version 
			JOIN sga_elementos_comp as ec ON ec.elemento_hijo = er_act.elemento_revision
			JOIN sga_elementos_revision as er_mod ON er_mod.elemento_revision = ec.elemento_padre
			JOIN sga_elementos_plan as ep_mod ON ep_mod.elemento_revision = er_mod.elemento_revision AND ep_mod.plan_version = $plan_version
			JOIN sga_elementos as e_mod ON e_mod.elemento = er_mod.elemento
			JOIN sga_g3entidades_subtipos as est_mod ON est_mod.entidad_subtipo = e_mod.entidad_subtipo

			 WHERE er_act.elemento = $actividad
			   AND est_mod.entidad_tipo = 1 	-- La actividad esta dentro de un módulo 
			   -- AND est_mod.entidad_subtipo <> 2 	-- No es una materia genericaa
			   AND COALESCE(ep_mod.tope_cursado_tipo,0) > 0 	-- El modulo tiene definido tipo de tope de actividades a cursar.
			   AND COALESCE(ep_mod.tope_cursado_cantidad,0) > 0 	-- El modulo tiene definido tope de actividades a cursar.
			";
		$rs_modulos = guarani_db::consultar($sql);

		if (empty($rs_modulos)) {
		   // La actividad no se encuentra en un modulo/genérica que controle tope de actividades a cursar. Salgo.
		   $resultado = true;
		   
		} elseif (count($rs_modulos) == 1) {
			// La actividad se encuentra en un solo módulo/genérica en el plan, obtengo el modulo y cantidad de actividades
			$tipo_tope   = $rs_modulos[0]['tope_cursado_tipo'];
			$cant_maxima = $rs_modulos[0]['tope_cursado_cantidad'];
			$modulo      = $rs_modulos[0]['modulo'];
			$modulo_revision = $rs_modulos[0]['modulo_revision'];
			$modulo_nombre   = $rs_modulos[0]['modulo_nombre'];
			$entidad_subtipo = $rs_modulos[0]['entidad_subtipo'];
			
			// Verifico si el módulo es una genérica
			if ($entidad_subtipo == 2) {
			
			}
			
			
		} elseif (count($rs_modulos) > 1) {
		   // La actividad se encuentra en mas de un modulo, no puedo realizar el control. Salgo.
		   $resultado = true;
		   
		   $es_generica = false;
		   $es_modulo = false;
		   foreach ($rs_modulos as $mg) {
		      if ($mg['entidad_subtipo'] == 2) {
		         // Es una generica
		         $es_generica = true;
		      } else { 
		      	 // No es una generica. Es un módulo.
		         $es_modulo = true;  
		      }
		   }

		   if ($es_modulo) {
		      // No puedo evaluar porque la actividad se encuentra en mas de un módulo o en algun módulo y alguna genérica. Salgo sin poder controlar.
		      $resultado = true;
		      
		   } elseif ($es_generica and !$es_modulo) {
		      // Todos los módulos donde se encuentra la actividad son genericas.
		      // Como es una optativa compartida, busco la generica para la cual la cumple el alumno.
		      $sql_gen = "SELECT generica
		                      FROM sga_alumnos_optativas
			             WHERE alumno = $alumno
			               AND plan_version = $plan_version 
 			               AND optativa = $actividad
 			             LIMIT 1";

		      $rs_gen = guarani_db::consultar_fila($sql_gen);		      
		      if (empty($rs_gen)) {
		         // La optativa compartida no fue seleccionada para una generica. Salgo sin error 
		         // No puedo controlar porque debe primero elegir para que generica la cumple.
		         return true;
		      } else {
		         // El alumno eligió una genperica para la que se cumple la optativa.
		         // Busco la genérica en los modulos recuperados al comienzo y obtengo los datos para controlar.
		         foreach ($rs_modulos as $mg) {
		             if ($mg['elemento'] == $rs_gen['generica']) {
			        $tipo_tope   = $mg['tope_cursado_tipo'];
			        $cant_maxima = $mg['tope_cursado_cantidad'];
			        $modulo      = $mg['modulo'];
			        $modulo_revision = $mg['modulo_revision'];
			        $modulo_nombre   = $mg['modulo_nombre'];
			        $entidad_subtipo = $mg['entidad_subtipo'];
		                exit;
		             }   
		         }

		      }   
		      
		   } 
		}	
		
		// Si encontró un módulo o genérica, entonces recupero datos para controlar de las otras actividades del módulo/generica.
		if (!isset($resultado)) {

		   /* 
		    Recupero actividades del modulo que esten cursadas/aprobadas/inscripciones 
		    Actividades asignadas directamente al modulo/genérica
		    En el caso de créditos de la actividad, considera los de la version actual del plan. No recupera los créditos en la version del plan que realizada.
		   */ 
		   $sql = "SELECT CASE $tipo_tope
		                 WHEN 1 THEN COUNT(er_act.elemento)
		                 WHEN 2 THEN SUM(ep_act.creditos)
		                 WHEN 3 THEN SUM(ep_act.horas_totales)
		                 WHEN 4 THEN SUM(ec.puntaje)
		               END as cantidad
		          FROM sga_elementos_comp as ec 
		          JOIN sga_elementos_revision as er_act ON er_act.elemento_revision = ec.elemento_hijo
		          JOIN sga_elementos_plan as ep_act ON ep_act.plan_version = $plan_version AND ep_act.elemento_revision = er_act.elemento_revision    
 			  WHERE ec.elemento_padre = $modulo_revision
		           AND er_act.elemento <> $actividad 
 			    -- Aprobadas/regularizadas vigentes/ Inscripciones a cursadas
 			   AND er_act.elemento IN (-- Cursadas/equivalencias de regularidad aprobadas vigentes
		                                SELECT elemento FROM vw_regularidades_basica  WHERE alumno = $alumno AND resultado = 'A' AND es_vigente = 1
                      		                UNION 
 		                                -- Aprobadas por examen final, promocion, equivalencia, resolucion 
		                                SELECT DISTINCT elemento FROM vw_hist_academica_basica WHERE alumno = $alumno AND resultado = 'A'
		                                UNION 
		                                -- Inscripto a cursadas de periodos lectivos vigentes
                                               SELECT c.elemento 
                                               FROM sga_insc_cursada as i 
                                               JOIN vw_comisiones as c ON c.comision = i.comision WHERE i.alumno = $alumno 
		                                AND c.fecha_fin >= CURRENT_DATE				  		              
		                               )
                    
		        ";
		        
		  $rs_act = guarani_db::consultar_fila($sql);

		  // Recupero cantidad de actividades del modulo realizadas por el alumno
		  $cantidad = $rs_act['cantidad'];

		  // Esta OK si el alumno no llegó al maximo de actividades permitidas para cursar
		  $resultado = ($cantidad < $cant_maxima);
		}

		// Resultado final (por si esta negado)
  		$resultado_final = $this->resultado_final($resultado);
		
		// Si falló el control, entonces salgo con mensaje.
		if (!$resultado_final) {
			$nombre_alumno = guarani::alumno()->get_alumno_nombre($alumno);
			$datos_actividad = guarani::elementos()->get_datos_basicos_elemento_en_plan_version($actividad, $plan_version);
			$actividad_nombre = $datos_actividad['nombre'];
			
			if ($tipo_tope == 1) {
			   $cant_maxima_desc = intval($cant_maxima);
			} elseif ($tipo_tope == 2) {
			   $cant_maxima_desc = $cant_maxima . ' créditos de' ;
			} elseif ($tipo_tope == 3) {
			   $cant_maxima_desc = $cant_maxima . ' horas de' ;
			} elseif ($tipo_tope == 4) {
			   $cant_maxima_desc = $cant_maxima . ' puntos de' ;
			} else {
			   $cant_maxima_desc = $cant_maxima;
			}   
			$this->set_parametros_mensaje_validacion(array($nombre_alumno, $actividad_nombre, $modulo_nombre, $cant_maxima_desc), true);
		}
		return $resultado_final;
	}
}

