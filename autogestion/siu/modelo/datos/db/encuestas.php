<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use \siu\modelo\datos\catalogo;

class encuestas
{
	/**
	 * parametros: alcance
	 * cache: no
	 */
	function definicion_alcance($parametros) 
	{
		// @TODO = FALTA ESTE MODULO
		$sql = "SELECT	a.encuesta,
						a.titulo,
						a.alcance,
						i.item,
						i.titulo
				FROM gde_encues_alcance as a,
				     gde_items as i
				WHERE a.alcance = i.alcance
				AND a.alcance = {$parametros['alcance']}";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		return $datos;				
	}	

	/**
	 * parametros: 
	 * cache: no
	 */
	function alcances() 
	{
		// @TODO = FALTA ESTE MODULO
		$sql = "SELECT	a.titulo,
						a.alcance
				FROM gde_encues_alcance as a";
		$datos = kernel::db()->consultar($sql, db::FETCH_NUM);
		
		return $datos;
	}	
	
	/**
	 * parametros: persona, perfil
	 * no_quote: perfil
	 * cache: memoria
	 */
	function encuestas_pendientes($parametros) 
	{
		$si = kernel::db()->quote(\comunes::si);
		$estado_activo = kernel::db()->quote(\comunes::estado_activo);
		$encuestado_alumno = kernel::db()->quote(\encuesta::encuestado_alumno);
		$encuestado_docente = kernel::db()->quote(\encuesta::encuestado_docente);
		
		$where_encuestado = "";
		if ($parametros['perfil'] == \persona::perfil_docente) {
		    // Si se trata de un DOCENTE
			$where_encuestado = " AND h.encuestado = $encuestado_docente";
		} elseif ($parametros['perfil'] == \persona::perfil_alumno) {
			// Si se trata de un ALUMNO
			$where_encuestado = " AND h.encuestado = $encuestado_alumno";
		}

		
		$sql = "SELECT 
				p.hash as id, -- id para luego marcar que fue contestado el formulario de encuestas.
				p.formulario as formulario,
				COALESCE(f.formulario_g2, p.formulario) as formulario_externo,
				h.kolla_id_habilitacion as kolla_id_habilitacion,
				h.kolla_password as kolla_password,
				f.titulo as titulo_formulario,
				COALESCE(cast(p.respuesta_g2 as text), p.hash) as id_externo, -- id que se registra en Kolla si NO es anonima
				h.tipo_relevamiento
		   FROM gde_encuestas_pendientes	as p
				JOIN gde_formularios		as f	ON (f.formulario = p.formulario)
				JOIN gde_habilitaciones		as h	ON (h.habilitacion = f.habilitacion)
				LEFT JOIN gde_becas_reapertura as r ON (r.respuesta = p.respuesta)
		  WHERE p.persona = {$parametros['persona']}
			AND h.activo = $si  -- Habilitacion activa para contestar
			AND p.fecha_respuesta IS NULL
		    AND CURRENT_DATE BETWEEN h.fecha_desde AND COALESCE(r.fecha_hasta, h.fecha_hasta)
			AND f.estado = $estado_activo -- formulario activo
			AND h.kolla_id_habilitacion IS NOT NULL 
			AND f.generado_en_kolla = $si
			$where_encuestado";
		  
		return kernel::db()->consultar($sql, db::FETCH_NUM);
	}	
	
	/**
	 * parametros: hash, fecha_respuesta
     * param_null: fecha_respuesta
	 * cache: no
	 */
	function marcar_encuesta_respondida ($parametros) 
	{
		$hash = $parametros['hash'];
		//agrego hmsu a la fecha devuelta por kolla para que cuando consulte los datos censales sean los anteriores a responder la encuesta
		$fecha=str_replace("'",'',$parametros['fecha_respuesta']);  
		$fecha = $fecha .' '. date('H:i:s.u');
		$fecha_respuesta = ($parametros['fecha_respuesta'] == "''")? 'CURRENT_TIMESTAMP' : kernel::db()->quote($fecha);
		
        //$fecha_respuesta = ($parametros['fecha_respuesta'] == "''")? 'CURRENT_TIMESTAMP' : $parametros['fecha_respuesta'];
		$sql = "UPDATE gde_encuestas_pendientes
			  SET fecha_respuesta = $fecha_respuesta,
			  	  sync_status = 'PENDIENTE'
			  WHERE hash = $hash
			  AND fecha_respuesta IS NULL"; // no la marco 2 veces
		$datos = kernel::db()->ejecutar($sql);
		return $datos > 0 ;
	}
	
	/**
	 * parametros: habilitacion
	 * cache: no
	 */
	function password_habilitacion($parametros){
		$sql = "SELECT	eh.kolla_password,
						eh.habilitacion,
						eh.tipo_relevamiento
				FROM gde_habilitaciones as eh
				WHERE eh.kolla_id_habilitacion = {$parametros['habilitacion']}";
		return kernel::db()->consultar($sql);
	}


    /**
     * parametros: docente, anio_academico, periodo_lectivo, actividad, comision
     * param_null: periodo_lectivo, actividad, comision
     * cache: no
     */
     function reporte_encuestas_actuacion_docente($parametros)
     { // Visualiza el listado de encuestas donde el docente forma parte del objeto de la encuesta.
	   // o tambien encuestas de actuacion academica donde el docente existe en la comision.
       // Usado en la operacion "Ver resultados de encuestas" de actuación docente.

		$si = kernel::db()->quote(\comunes::si);
		$estado_activo = kernel::db()->quote(\comunes::estado_activo);
		
		$subtipo_academica = kernel::db()->quote(\encuesta::subtipo_academica);
		$subtipo_docente = kernel::db()->quote(\encuesta::subtipo_docente);
		
		$tipo_docente = \encuesta::tipo_docente;
		$tipo_academica = \encuesta::tipo_academica;
		$tipo_docente_academica = \encuesta::tipo_docente_academica;
		$tipo_pares = \encuesta::tipo_pares;
	   
        $filtro_periodo_lectivo = "";
        $filtro_actividad = "";
        $filtro_comision = "";

        if($parametros['periodo_lectivo'] != NULL) {
            $filtro_periodo_lectivo   = " AND sga_comisiones.periodo_lectivo = {$parametros['periodo_lectivo']} ";
        }
        
        if($parametros['actividad'] != NULL) {
            // Solo habilitaciones para esta actividad.	
            $filtro_actividad = " AND sga_comisiones.elemento = {$parametros['actividad']} ";
        }

        if ($parametros['comision'])
            $filtro_comision = " AND sga_comisiones.comision = {$parametros['comision']} ";

        // Recupera las encuestas segun filtro ingresado y donde el docente es objeto de la encuesta
		// o evaluaciones academicas donde el docente existe en la comision.
		// En el caso de habilitaciones de tipo 3, recupera el item relacionado con la cursada y el item relacionado con el docente.
        // Tipo Habilitacion:  1 = Evaluación docentes en Comisiones  - 
		//                     2 = Evaluación academica de las comisiones (consultas sobre la cursada)
		//                     3 = Evaluación académica y docente en comisiones
		//                     4 = Evaluación de pareces en las cursadas
        $sql = "SELECT 
                cast(h.habilitacion as text) || '-' || cast(f.formulario as text) as id,
                e.encuesta as encuesta,
                e.nombre as encuesta_titulo,
                e.kolla_unidad_gestion as kolla_unidad_gestion,
                h.habilitacion as habilitacion, 
                h.titulo as habilitacion_titulo,
                f.formulario as formulario,
                COALESCE(f.formulario_g2, f.formulario) as formulario_externo,
                f.titulo as formulario_titulo, 
                i.item as elemento,
                COALESCE(i.elemento_g2, i.item) as elemento_externo,
                e.kolla_id_encuesta as kolla_id_encuesta,
                h.kolla_id_habilitacion as kolla_id_habilitacion,
                h.kolla_password as kolla_password,
                (SELECT COUNT(*) FROM gde_encuestas_pendientes as ep WHERE ep.formulario = f.formulario) as cant_encuestados,
                (SELECT COUNT(*) FROM gde_encuestas_pendientes as ep WHERE ep.formulario = f.formulario AND ep.fecha_respuesta IS NOT NULL) as cant_respondidas,
                sga_elementos.nombre as actividad_nombre,
                p_pl.nombre as periodo_lectivo_nombre,
                sga_comisiones.nombre as comision_nombre,
                sga_comisiones.comision,
				e.subtipo as subtipo_encuesta,
				st.nombre as subtipo_encuesta_desc
                
                FROM gde_habilitaciones as h
                JOIN gde_grupos as g ON g.habilitacion = h.habilitacion
                JOIN gde_encuestas as e ON e.encuesta = g.encuesta
				JOIN gde_subtipos as st ON st.subtipo = e.subtipo
                JOIN gde_formularios as f ON f.habilitacion = h.habilitacion
                JOIN gde_formulario_items as fi ON fi.formulario = f.formulario 
                JOIN gde_items as i ON (i.item = fi.item AND i.habilitacion = g.habilitacion AND i.encuesta = g.encuesta)
                LEFT JOIN sga_comisiones ON sga_comisiones.comision = f.comision
                LEFT JOIN sga_periodos_lectivos as pl ON pl.periodo_lectivo = sga_comisiones.periodo_lectivo
                LEFT JOIN sga_periodos as p_pl ON p_pl.periodo = pl.periodo
                LEFT JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
                
				WHERE h.anio_academico = {$parametros['anio_academico']}
                  AND h.kolla_id_habilitacion IS NOT NULL
                  AND h.tipo IN ($tipo_docente, $tipo_academica, $tipo_docente_academica, $tipo_pares)     
                  AND f.estado = $estado_activo   -- Formularios activos
                  AND f.generado_en_kolla = $si   -- Formularios generados en Kolla
                  
                  $filtro_actividad
                  $filtro_periodo_lectivo
                  $filtro_comision
                  
                  -- formularios que tengan como elemento al docente
                  AND ((e.subtipo = $subtipo_docente AND h.tipo IN ($tipo_docente, $tipo_docente_academica, $tipo_pares) AND i.docente = {$parametros['docente']})  -- Evaluacion al docente / Evaluacion de Pares.
				        OR  
                       (e.subtipo = $subtipo_academica AND h.tipo IN ($tipo_academica, $tipo_docente_academica) AND i.docente IS NULL AND i.comision = f.comision
					    AND EXISTS (SELECT 1 FROM sga_docentes_comision as dc 
						                    WHERE dc.comision = f.comision AND dc.docente = {$parametros['docente']}
									)
						) -- Evaluacion academica de la cursada donde se encuentra el docente
                      )
                  AND i.estado = $estado_activo
                  AND i.generado_en_kolla = $si
                                      
                 ORDER BY habilitacion_titulo, encuesta_titulo, actividad_nombre, comision_nombre, periodo_lectivo_nombre";

            $encuestas = kernel::db()->consultar($sql);
                  
            foreach ($encuestas as $key => $encuesta){
            
                    $encuestas[$key]['kolla_id_encuesta_hash'] = catalogo::generar_id($encuesta['kolla_id_encuesta']);
                    $encuestas[$key]['elemento_externo_hash'] = catalogo::generar_id($encuesta['elemento']);
                    $encuestas[$key]['formulario_hash'] = catalogo::generar_id($encuesta['formulario']);
                    $encuestas[$key]['kolla_id_habilitacion_hash'] = catalogo::generar_id($encuesta['kolla_id_habilitacion']);
                    //En caso de no tener ninguna unidad de gestion asignada la encuesta, se solicita el valor para toda la institucion.
                    if (empty($encuesta['kolla_unidad_gestion']))
                        $encuestas[$key]['kolla_unidad_gestion'] = \guarani::param()->get_default('enc_kolla_unidad_gestion');
					
					$encuestas[$key]['elemento_externo']   = catalogo::generar_id($encuesta['elemento_externo']);
                    $encuestas[$key]['formulario_externo'] = catalogo::generar_id($encuesta['formulario_externo']);
            }
            
            return $encuestas;
    }

    /**
     * parametros: comision
     * param_null:
     * cache: no
     */
    function reporte_encuestas_actuacion_otros_docentes_comision($parametros)
    { // TODO descripcion reporte

		$si = kernel::db()->quote(\comunes::si);
		$estado_activo = kernel::db()->quote(\comunes::estado_activo);
		
		$subtipo_docente = kernel::db()->quote(\encuesta::subtipo_docente);
		
		$tipo_docente = \encuesta::tipo_docente;
		$tipo_docente_academica = \encuesta::tipo_docente_academica;
		$tipo_pares = \encuesta::tipo_pares;
		
        $filtro_periodo_lectivo = "";
        $filtro_actividad = "";
        $filtro_comision = "";

        if ($parametros['comision'])
            $filtro_comision = " AND sga_comisiones.comision = {$parametros['comision']} ";

        // Recupera las encuestas segun filtro ingresado y donde el docente es objeto de la encuesta
        // o evaluaciones academicas donde el docente existe en la comision.
        // En el caso de habilitaciones de tipo 3, recupera el item relacionado con la cursada y el item relacionado con el docente.
        // Tipo Habilitacion:  1 = Evaluación docentes en Comisiones  -
        //                     2 = Evaluación academica de las comisiones (consultas sobre la cursada)
        //                     3 = Evaluación académica y docente en comisiones
        //                     3 = Evaluación de pares en las cursadas.
        $sql = "SELECT 
                    cast(h.habilitacion as text) || '-' || cast(f.formulario as text) as id,
                    e.encuesta as encuesta,
                    e.nombre as encuesta_titulo,
                    e.kolla_unidad_gestion as kolla_unidad_gestion,
                    h.kolla_id_habilitacion as kolla_id_habilitacion, 
                    h.titulo as habilitacion_titulo,
                    f.formulario as formulario,
                    f.titulo as formulario_titulo, 
                    e.kolla_id_encuesta as kolla_id_encuesta,            
                    sga_elementos.nombre as actividad_nombre,
                    sga_comisiones.nombre as comision_nombre,
                    sga_comisiones.comision,
                    e.subtipo as subtipo_encuesta,
                    st.nombre as subtipo_encuesta_desc,
                    (SELECT COUNT(*) FROM gde_encuestas_pendientes as ep WHERE ep.formulario = f.formulario) as cant_encuestados,
                    (SELECT COUNT(*) FROM gde_encuestas_pendientes as ep WHERE ep.formulario = f.formulario AND ep.fecha_respuesta IS NOT NULL) as cant_respondidas
					
                FROM sga_comisiones 
                JOIN sga_elementos ON sga_elementos.elemento = sga_comisiones.elemento
				JOIN gde_formularios as f ON f.comision = sga_comisiones.comision
				JOIN gde_habilitaciones as h ON h.habilitacion = f.habilitacion
                JOIN gde_grupos as g ON g.habilitacion = h.habilitacion
                JOIN gde_encuestas as e ON e.encuesta = g.encuesta
                JOIN gde_subtipos as st ON st.subtipo = e.subtipo
				
                WHERE sga_comisiones.comision = {$parametros['comision']}
				  AND h.kolla_id_habilitacion IS NOT NULL
                  AND h.tipo IN ($tipo_docente, $tipo_docente_academica, $tipo_pares)     
                  AND f.estado = $estado_activo   -- Formularios activos
                  AND f.generado_en_kolla = $si   -- Formularios generados en Kolla                 
                  AND e.subtipo = $subtipo_docente	-- Tipo de Formulario igual a Evaluación Docente
                ORDER BY habilitacion_titulo, encuesta_titulo";

        $encuestas = kernel::db()->consultar($sql);

        foreach ($encuestas as $key => $encuesta){

            $encuestas[$key]['kolla_id_encuesta_hash'] = catalogo::generar_id($encuesta['kolla_id_encuesta']);
            $encuestas[$key]['elemento_externo_hash'] = null;
            $encuestas[$key]['formulario_hash'] = catalogo::generar_id($encuesta['formulario']);
            $encuestas[$key]['kolla_id_habilitacion_hash'] = catalogo::generar_id($encuesta['kolla_id_habilitacion']);

            //En caso de no tener ninguna unidad de gestion asignada la encuesta, se asigna el valor para toda la institucion.
            if (empty($encuesta['kolla_unidad_gestion']))
                $encuestas[$key]['kolla_unidad_gestion'] = \guarani::param()->get_default('enc_kolla_unidad_gestion');
        }

        return $encuestas;
    }

          /**
     * parametros: 
     * param_null: 
     * cache: memoria
     */
     //Devuelve todos los id de los elementos encuestados y agrega un campo con el hash de los mismos. 
     function get_id_elementos()
     {
         $sql = "SELECT item 
                    FROM gde_items";
         $items = kernel::db()->consultar($sql, db::FETCH_ASSOC);
         foreach ($items as $key => $item){
             $items[$key][catalogo::id] = catalogo::generar_id($item['item']);
             
         }
         return $items;
     }
	 
          /**
     * parametros: item
     * param_null: 
     * cache: memoria
     */
     //Devuelve todos los id de los elementos encuestados y agrega un campo con el hash de los mismos. 
     function get_id_elemento_externo($parametros)
     {
         $sql = "SELECT COALESCE(elemento_g2, item) as elemento_externo
                    FROM gde_items
					WHERE item = {$parametros['item']}";
         $items = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
         return $items['elemento_externo'];
     }
     
        /**
     * parametros: 
     * param_null: 
     * cache: memoria
     */
     //Devuelve todos los id de los formularios y agrega un campo con el hash de los mismos. 
     function get_id_formularios()
     {
         $sql = "SELECT formulario 
                    FROM gde_formularios";
         $formularios = kernel::db()->consultar($sql, db::FETCH_ASSOC);
         foreach ($formularios as $key => $formulario){
             $formularios[$key][catalogo::id] = catalogo::generar_id($formulario['formulario']); 
         }
         return $formularios;
     }
	 
        /**
     * parametros: formulario
     * param_null: 
     * cache: memoria
     */
     //Devuelve todos los id de los formularios y agrega un campo con el hash de los mismos. 
     function get_id_formulario_externo($parametros)
     {
         $sql = "SELECT COALESCE(formulario_g2, formulario) as formulario_externo
                    FROM gde_formularios
					WHERE formulario = {$parametros['formulario']}";
         $formularios = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
         return $formularios['formulario_externo'];
     }

           /**
     * parametros: 
     * param_null: 
     * cache: memoria
     */
     //Devuelve todos los id de kolla y agrega un campo con el hash de los mismos. 
     function get_id_kolla_encuestas()
     {
         $sql = "SELECT kolla_id_encuesta 
                    FROM gde_encuestas";
         $encuestas = kernel::db()->consultar($sql, db::FETCH_ASSOC);
         foreach ($encuestas as $key => $encuesta){
             $encuestas[$key][catalogo::id] = catalogo::generar_id($encuesta['kolla_id_encuesta']); 
             
         }
         return $encuestas;
         
     }
     
     /**
     * parametros: 
     * param_null: 
     * cache: memoria
     */
     //Devuelve todos los id de los alcances y agrega un campo con el hash de los mismos. 
     function get_id_kolla_habilitacion()
     {
         $sql = "SELECT kolla_id_habilitacion 
                    FROM gde_habilitaciones
                    WHERE kolla_id_habilitacion IS NOT NULL";
         $alcances = kernel::db()->consultar($sql, db::FETCH_ASSOC);
         foreach ($alcances as $key => $alcance){
             $alcances[$key][catalogo::id] = catalogo::generar_id($alcance['kolla_id_habilitacion']); 
         }
         return $alcances;
     }

    /**
     * parametros: persona, perfil, fecha_desde, fecha_hasta, docente, actividad
     * param_null: fecha_desde, fecha_hasta, docente, actividad
	 * no_quote: perfil
     * cache: no
     */
    function encuestas_respondidas($parametros)
    {
		/* Visualiza el listado de encuestas respondidas por el alumno o docente
		   Si el reporte esta en la interfaz del alumno, entonces solo ve las encuestas que respondió como alumno.
		   Si el reporte esta en la interfaz del docente, entonces solo ve las encuestas que respondió como docente.
		*/
		$encuestado_alumno = kernel::db()->quote(\encuesta::encuestado_alumno);
		$encuestado_docente = kernel::db()->quote(\encuesta::encuestado_docente);
		
		$where_encuestado = "";
		if ($parametros['perfil'] == \persona::perfil_docente) {
		    // Si se trata de un DOCENTE
			$where_encuestado = " AND h.encuestado = $encuestado_docente";
		} elseif ($parametros['perfil'] == \persona::perfil_alumno) {
			// Si se trata de un ALUMNO
			$where_encuestado = " AND h.encuestado = $encuestado_alumno";
		}
		
		
	 	$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$si = kernel::db()->quote(\comunes::si);
		$estado_activo = kernel::db()->quote(\comunes::estado_activo);
		
        $filtro_fecha_desde = "";
        $filtro_fecha_hasta = "";
        $filtro_actividad   = "";
        $filtro_docente     = "";

        if($parametros['fecha_desde'] != null) $filtro_fecha_desde = " AND date(p.fecha_respuesta) >= to_date({$parametros['fecha_desde']}, $formato_fecha_visual) ";
        if($parametros['fecha_hasta'] != null) $filtro_fecha_hasta = " AND date(p.fecha_respuesta) <= to_date({$parametros['fecha_hasta']}, $formato_fecha_visual) ";

        if($parametros['actividad'] != null) {
            $filtro_actividad = " AND ele.elemento = {$parametros['actividad']} ";
        }
        
        if($parametros['docente'] != null){
            $filtro_docente = " AND f.formulario IN (SELECT fi.formulario
                                                    FROM gde_items as i, gde_formulario_items as fi
                                                    WHERE i.docente = {$parametros['docente']}
                                                    AND fi.item = i.item
                                                    )";
        }
		// Codigo externo es el id de respuesta que en G2 es el campo gde_encues_persona.respuesta y en G3 gde_encuestas_pendientes.hash	
        $sql = "SELECT
                    p.hash as hash,
                    COALESCE(cast(p.respuesta_g2 as text), p.hash) as codigo_externo, 
                    p.fecha_respuesta as fecha_respuesta,
                    to_char(p.fecha_respuesta, $formato_fecha_visual) as sfecha_respuesta,
                    e.kolla_id_encuesta as kolla_id_encuesta,
                    h.kolla_id_habilitacion as kolla_id_habilitacion,
                    h.kolla_password as kolla_password,
                    h.anonima as anonima,
                    h.anio_academico as anio_academico,
                    pl.nombre as periodo_lectivo_nombre,
                    --f.formulario as formulario,
                    COALESCE(f.formulario_g2,f.formulario) as formulario,
                    e.nombre as encuesta_titulo,
                    f.titulo as formulario_titulo,
                    h.titulo as habilitacion_titulo,
                    ele.nombre as actividad_nombre,
                    h.kolla_unidad_gestion as unidad_gestion
                
                FROM gde_encuestas_pendientes as p
                JOIN mdp_personas as dp					ON (dp.persona = p.persona)
                JOIN gde_formularios as f				ON (f.formulario = p.formulario)
                JOIN gde_habilitaciones as h			ON (h.habilitacion = f.habilitacion)
                JOIN gde_grupos as g					ON (g.habilitacion = h.habilitacion)
                JOIN gde_encuestas as e					ON (e.encuesta = g.encuesta)
                LEFT JOIN sga_comisiones as c			ON (c.comision = f.comision)
                LEFT JOIN sga_elementos as ele			ON (ele.elemento = c.elemento)
                LEFT JOIN vw_periodos_lectivos as pl	ON (pl.periodo_lectivo = h.periodo_lectivo) 
                
                WHERE p.persona = {$parametros['persona']}
                  AND p.fecha_respuesta IS NOT NULL
                  $filtro_fecha_desde
                  $filtro_fecha_hasta
                  AND f.estado = $estado_activo                   
                  AND f.generado_en_kolla = $si        
                  AND h.kolla_id_habilitacion IS NOT NULL
                  $filtro_actividad
                  $filtro_docente
				  $where_encuestado
                    
                ORDER BY h.anio_academico desc, p.fecha_respuesta desc";
        
        return kernel::db()->consultar($sql, db::FETCH_ASSOC);

    }


    /**
     * parametros: periodo_lectivo, docente
     * param_null:
     * cache: no
     */
    function get_comision_ver_encuestas_docente ($parametros){
        $sql = "SELECT vw_comisiones.comision,
                  vw_comisiones.comision_nombre as comision_nombre,
                  vw_comisiones.elemento as actividad,
                  vw_comisiones.elemento_nombre as actividad_nombre,
                  vw_comisiones.elemento_codigo as actividad_codigo

                FROM vw_comisiones 
                  JOIN sga_docentes_comision ON sga_docentes_comision.comision = vw_comisiones.comision
                  JOIN sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
                  JOIN gdu_derechos_personas ON (gdu_derechos_personas.derecho = 'comision_ver_encuestas_docente' AND 
                                                  gdu_derechos_personas.persona = sga_docentes.persona AND 
                                                  gdu_derechos_personas.entidad = vw_comisiones.entidad)

                WHERE vw_comisiones.periodo_lectivo = {$parametros['periodo_lectivo']} 
                AND sga_docentes.docente = {$parametros['docente']}

              ORDER BY vw_comisiones.elemento_nombre, vw_comisiones.comision_nombre";

        return kernel::db()->consultar($sql, db::FETCH_ASSOC);
    }

}