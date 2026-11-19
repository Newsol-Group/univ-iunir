<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage EscalaDeNotas
 */
class co_escalas_notas
{
	function get_listado($where=' ')
	{
		if (trim($where) != '' ) $where = ' where '.$where.' ';

		$sql = "SELECT 	sga_escalas_notas.escala_nota,
						sga_escalas_notas.nombre,
						sga_escalas_notas.descripcion,
						sga_escalas_notas.tipo,
						sga_escalas_notas.cantidad_decimales,
						sga_escalas_notas.separador_decimal,
						sga_escalas_notas.nota_inicial,
						sga_escalas_notas.nota_final,
						".guarani_sql::get_case('sga_escalas_notas','estado').",
						sga_escalas_notas.es_numerica
    		    FROM sga_escalas_notas     	
  				 ".$where." ORDER BY 2"; 
  				 
		return guarani_db::consultar($sql);
	}

	/**
	 * Método utilizado para la carga del campos externo escala_nota_nombre de los Datos Tabla que lo necesiten
	 *
	 * @param <type> $escala_nota
	 * @return <type>
	 */
	function get_ext_escala_nota_nombre($escala_nota)
	{
		if (is_array($escala_nota)) {
			$where = guarani_sql::armar_condicion_in($escala_nota, 'sga_escalas_notas', 'escala_nota');
		} else {
			$escala_nota = toba::db()->quote($escala_nota);
			$where = "sga_escalas_notas.escala_nota = $escala_nota";
		}

		$sql = "SELECT 	sga_escalas_notas.escala_nota,
						sga_escalas_notas.nombre as escala_nota_nombre
    		    FROM	sga_escalas_notas
  				WHERE   $where;
				";
		return guarani_db::consultar($sql);;

	}

	/**
	 * Retorna el encabezado de las escalas de notas pasadas como parámetro.
	 *
	 * @param <type> $escala_nota
	 * @return <type>
	 */
	function get_info_escalas_notas($escala_nota)
	{
		if (is_array($escala_nota)) {
			$where = guarani_sql::armar_condicion_in($escala_nota, 'sga_escalas_notas', 'escala_nota');
		} else {
			$escala_nota = toba::db()->quote($escala_nota);
			$where = "sga_escalas_notas.escala_nota = $escala_nota";
		}

		return $this->get_listado($where);
	}

	/**
	 * Retorna los rangos de resultados de las escalas de notas pasadas como parámetro.
	 *
	 * @param <type> $escalas_notas
	 * @return <type>
	 */
	function get_rangos_resultados($escalas_notas)
	{
		if (is_array($escalas_notas)) {
			$where = guarani_sql::armar_condicion_in($escalas_notas, 'sga_escalas_notas_rangos_resultados', 'escala_nota');
		} else {
			$escala_nota = toba::db()->quote($escalas_notas);
			$where = "sga_escalas_notas_rangos_resultados.escala_nota = $escala_nota";
		}

		$sql = "SELECT	sga_escalas_notas_rangos_resultados.escala_nota,
						sga_escalas_notas_rangos_resultados.nota_inicial,
						sga_escalas_notas_rangos_resultados.nota_final,
						sga_escalas_notas_rangos_resultados.resultado
				FROM	sga_escalas_notas_rangos_resultados
				WHERE 
				$where
				";

		return guarani_db::consultar($sql);
	}



	function get_listado_reporte($where=' ')
	{
		if ($where) {
			$where = ' AND ' . $where;
		}

		$sql = "SELECT 		sga_escalas_notas.escala_nota as escala_nota,
							sga_escalas_notas.nombre as nombre_escala,
							COALESCE(sga_escalas_notas.descripcion,'Sin descripción') as descripcion_escala, 
							sga_escalas_notas.estado as estado_escala,
							(CASE	WHEN sga_escalas_notas.estado = 'A' THEN 'Activo' 
									WHEN sga_escalas_notas.estado = 'B' THEN 'Baja' END) as escala_estado_descr,
							sga_escalas_notas_det.nota as nota,
							sga_escalas_notas_det.descripcion as descripcion_nota,
							sga_escalas_notas_det.resultado as resultado_id,
							sga_escalas_notas_resultado.descripcion as resultado_valor,
							sga_escalas_notas_det.valor_numerico as valor_numerico,
							sga_escalas_notas_det.concepto as concepto,
							sga_escalas_notas_concepto.nombre as concepto_nombre
    		    FROM		sga_escalas_notas,
    		    			sga_escalas_notas_det
								LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_det.concepto = sga_escalas_notas_concepto.concepto,
    		    			sga_escalas_notas_resultado
				WHERE		sga_escalas_notas.escala_nota = sga_escalas_notas_det.escala_nota AND
				  			sga_escalas_notas_det.resultado = sga_escalas_notas_resultado.resultado
				  			$where
				ORDER BY	sga_escalas_notas.nombre, sga_escalas_notas_det.orden
  				;";
				 
		return guarani_db::consultar($sql);
	}
	
    function get_escalas_notas()
    {
        $sql = "SELECT 		sga_escalas_notas.escala_nota 	AS valor,
				        	sga_escalas_notas.nombre 		AS descr
				FROM 		sga_escalas_notas
				ORDER BY	sga_escalas_notas.nombre";
                
        return guarani_db::consultar($sql);
    }

	function get_tipos()
    {
        return escalas_notas::get_tipos();
    }

	function get_escalas_notas_activas($escala_asignada=null)
	{
		$union = '';
		if ($escala_asignada) {
			$escala_asignada = toba::db()->quote($escala_asignada);
			$union = "	UNION
						SELECT	sga_escalas_notas.escala_nota as valor,
								sga_escalas_notas.nombre as descr
						FROM	sga_escalas_notas
						WHERE	sga_escalas_notas.escala_nota = $escala_asignada";
		}
        $sql = "SELECT		escala_nota as valor,
							nombre as descr
				FROM		sga_escalas_notas
				WHERE		estado = '".comunes::estado_activo."'
				$union
				ORDER BY	descr
		";
		return guarani_db::consultar($sql);
	}

    function get_ids_escalas_notas_activas()
    {
        $sql = "SELECT 	sga_escalas_notas.escala_nota
                FROM	sga_escalas_notas
                WHERE	sga_escalas_notas.estado = '".comunes::estado_activo."'
                ";
               
        return guarani_db::consultar($sql);
    }

	function get_escalas_notas_resultados_solo_ausente()
	{
		$where = "sga_escalas_notas_resultado.resultado = '".escalas_notas::resultado_ausente."'";
		return $this->get_escalas_notas_resultados($where);
	}
    
	function get_escalas_notas_resultados_sin_ausente()
	{
		$where = "sga_escalas_notas_resultado.resultado <> '".escalas_notas::resultado_ausente."'";
		return $this->get_escalas_notas_resultados($where);
	}

	function get_escalas_notas_resultados($filtro='')
	{
		$where = '';
		if ($filtro != '') {
			$where = "WHERE $filtro";
		}
		$sql = "SELECT	sga_escalas_notas_resultado.resultado as valor,
						sga_escalas_notas_resultado.descripcion as descr
				FROM	sga_escalas_notas_resultado
				$where
				ORDER BY	descripcion";
        return guarani_db::consultar($sql);
	}

	function get_escalas_notas_detalles($escalas_notas)
	{
		if (is_array($escalas_notas)) {
			$where = guarani_sql::armar_condicion_in($escalas_notas, 'sga_escalas_notas_det', 'escala_nota');
		} else {
			$escala_nota = toba::db()->quote($escalas_notas);
			$where = "sga_escalas_notas_det.escala_nota = $escala_nota";
		}

		$sql = "SELECT	sga_escalas_notas_det.escala_nota,
						sga_escalas_notas_det.nota,
						sga_escalas_notas_det.descripcion,
						sga_escalas_notas_det.concepto,
						sga_escalas_notas_det.resultado,
						sga_escalas_notas_det.valor_numerico
				FROM	sga_escalas_notas_det
				WHERE
				$where
				";

		return guarani_db::consultar($sql);
	}

	/**
        Retorna los distintos separadores posibles para las escalas contínuas
    */
    function get_separadores()
    {
		return array(array('valor' => '.','descr' => 'Punto'),
		             array('valor' => ',','descr' => 'Coma')
		            );
    }

    /**
     * Obtiene todos los datos y resultado del detalle de notas para la escala pasada como parámetro, según el filtro dado.
     *
     * @param integer	$escala	id de la escala de notas para la cual se quieren recuperar todo el detalle de notas que la 
     * componen y sus datos.
     * @param str		$where	filtro sobre las notas a recuperar.
     */
    function get_detalle_escala_notas($escala=null, $where='')
    {
    	$filtro = '';
    	if (!is_null($escala)) {
    		$escala = toba::db()->quote($escala);
    		$filtro .= " AND sga_escalas_notas.escala_nota = $escala "; 
    	}
    	if ($where) {
    		$filtro .= ' AND ' . $where;
    	}
    	
    	$sql = "SELECT		sga_escalas_notas_det.escala_nota,
    						sga_escalas_notas_det.nota,
    						sga_escalas_notas_det.descripcion,
    						sga_escalas_notas_det.valor_numerico,
    						sga_escalas_notas_det.concepto,
							sga_escalas_notas_concepto.nombre as concepto_nombre,
    						sga_escalas_notas_det.resultado,
    						sga_escalas_notas_resultado.descripcion as resultado_nombre,
							sga_escalas_notas_det.orden
    			FROM		sga_escalas_notas,
    						sga_escalas_notas_det
								LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_det.concepto = sga_escalas_notas_concepto.concepto,
    						sga_escalas_notas_resultado
    			WHERE		sga_escalas_notas.escala_nota = sga_escalas_notas_det.escala_nota AND
    						sga_escalas_notas_det.resultado = sga_escalas_notas_resultado.resultado
    						$filtro
				ORDER BY	sga_escalas_notas_det.orden
    			;";
    	
    	return guarani_db::consultar($sql);
    }
    
    /**
     * Obtiene los detalles de las escalas de notas para todas las instancias
     * definidas a una mesa de examen pasada como parámetro.
     *
     * @param integer	$mesa_examen id de la mesa para la cual se desea recuperar 
     * todos los detalles de las escalas de notas definidas para todas sus instancias
     */
	function get_detalle_escala_notas_por_mesa_examen($mesa_examen=null, $filtro='')
	{
		$where = '';
		if (!is_null($mesa_examen)) {
			$mesa_examen = toba::db()->quote($mesa_examen);
			$where = " AND sga_mesas_examen_instancias.mesa_examen = $mesa_examen ";
		}
		if ($filtro) {
			$where .= ' AND ' . $filtro;
		}
		
		$sql = "SELECT		sga_escalas_notas_det.escala_nota,
    						sga_escalas_notas_det.nota,
    						sga_escalas_notas_det.descripcion,
    						'Instancia: ' 		|| sga_instancias.nombre 	|| ' - ' || 
    						'Escala de Notas: ' || sga_escalas_notas.nombre	AS instancia_escala_nombre,
    						sga_escalas_notas_det.concepto,
							sga_escalas_notas_concepto.nombre AS concepto_nombre,
    						sga_escalas_notas_det.valor_numerico,
    						sga_escalas_notas_det.resultado,
    						sga_escalas_notas_resultado.descripcion AS resultado_nombre,
    						sga_instancias.instancia
    			FROM		sga_escalas_notas,
    						sga_escalas_notas_det
								LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_det.concepto = sga_escalas_notas_concepto.concepto,
    						sga_escalas_notas_resultado,
    						sga_mesas_examen_instancias,
    						sga_instancias
				WHERE		sga_escalas_notas_det.escala_nota		= sga_escalas_notas.escala_nota
				AND			sga_escalas_notas_resultado.resultado	= sga_escalas_notas_det.resultado
				AND			sga_mesas_examen_instancias.escala_nota	= sga_escalas_notas.escala_nota
    			AND			sga_instancias.instancia				= sga_mesas_examen_instancias.instancia
    						$where
    			;";

		return guarani_db::consultar($sql);
    }

    /**
     * Obtiene los resultados posibles para una instancia y una escala de notas dadas unido al resultado 'Ausente' que es posible 
     * para todas las escalas.
     *
     * @param integer $escala_nota
     */
    function get_resultados_posibles_instancia_escala_nota($instancia=null, $escala_nota=null)
    {
		$instancia = toba::db()->quote($instancia);
		$escala_nota = toba::db()->quote($escala_nota);

    	$sql = "SELECT	sga_escalas_notas_resultado.resultado as valor,
    					COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) as descr
    			FROM	sga_escalas_notas_det
    					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = $instancia AND sga_escalas_notas_det.resultado = sga_instancias_resultado.resultado),
    					sga_escalas_notas_resultado
    			WHERE	sga_escalas_notas_det.resultado = sga_escalas_notas_resultado.resultado AND
    					sga_escalas_notas_det.escala_nota = $escala_nota
    			
    			UNION	
    			
    			SELECT	sga_escalas_notas_resultado.resultado as valor,
    					COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) as descr
    			FROM	sga_escalas_notas_resultado
    					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = $instancia AND sga_escalas_notas_resultado.resultado = sga_instancias_resultado.resultado)
    			WHERE	sga_escalas_notas_resultado.resultado = '" . escalas_notas::resultado_ausente . "' 
    			;";

    	return guarani_db::consultar($sql);
    }

	function get_resultados_posibles_escala_nota($escala_nota=null)
	{
		$escala_nota = toba::db()->quote($escala_nota);
		$sql = "SELECT	sga_escalas_notas_resultado.resultado as valor,
						sga_escalas_notas_resultado.descripcion as descr
				FROM	sga_escalas_notas_det
						JOIN sga_escalas_notas_resultado ON sga_escalas_notas_det.resultado = sga_escalas_notas_resultado.resultado
				WHERE	sga_escalas_notas_det.escala_nota = $escala_nota
				UNION
				SELECT	sga_escalas_notas_resultado.resultado as valor,
						sga_escalas_notas_resultado.descripcion as descr
				FROM	sga_escalas_notas_resultado
				WHERE	sga_escalas_notas_resultado.resultado = '".escalas_notas::resultado_ausente."' 
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna los datos de los resultados de acuerdo a una instancia.
	 */
	function get_resultados_instancia($instancia, $where='')
	{
		$instancia = toba::db()->quote($instancia);
		if ($where) {
			$where = "WHERE $where";
		}
    	$sql = "SELECT	sga_escalas_notas_resultado.resultado as valor,
    					COALESCE(sga_instancias_resultado.descripcion, sga_escalas_notas_resultado.descripcion) as descr
    			FROM	sga_escalas_notas_resultado
    					LEFT JOIN sga_instancias_resultado ON (sga_instancias_resultado.instancia = $instancia AND sga_escalas_notas_resultado.resultado = sga_instancias_resultado.resultado)
    			$where
    	";
    	return guarani_db::consultar($sql);
	}
    
    /**
     * Obtiene los detalles de las escalas de notas para todas las instancias
     * definidas para una comisión dada.
     *
     * @param integer $comision
     */
    function get_detalle_escala_notas_por_comision($comision)
    {
    	$comision = toba::db()->quote($comision);
    	
    	$sql = "SELECT 		sga_escalas_notas_det.nota,
				    		sga_escalas_notas_det.descripcion,
				    		'Instancia: ' 		|| sga_instancias.nombre 	|| ' - ' || 
				    		'Escala de Notas: ' || sga_escalas_notas.nombre	AS instancia_escala_nombre,
				    		sga_escalas_notas_resultado.descripcion 		AS resultado_nombre,
				    		sga_instancias.instancia,
				    		sga_escalas_notas.nombre || ' ' ||  sga_instancias.nombre AS instancia_escala_nombre_imprimir 
				FROM 		sga_comisiones_instancias,
							sga_escalas_notas,
							sga_escalas_notas_det,
							sga_escalas_notas_resultado,
				            sga_instancias
				
				WHERE		sga_comisiones_instancias.escala_nota = sga_escalas_notas_det.escala_nota
							AND	sga_escalas_notas.escala_nota = sga_escalas_notas_det.escala_nota
				   			AND sga_escalas_notas_det.resultado = sga_escalas_notas_resultado.resultado
				            AND sga_comisiones_instancias.instancia = sga_instancias.instancia
				            AND sga_comisiones_instancias.comision = $comision
				            
				ORDER BY	sga_comisiones_instancias.instancia,
							sga_escalas_notas_det.orden";
    	
    	return guarani_db::consultar($sql);
    }

	/*
	 * Determina si una escala está siendo utilizada o no.
	 */
	function esta_en_uso($escala_nota)
	{
		$escala_nota = toba::db()->quote($escala_nota);

		$referencias = array('sga_comisiones_instancias',
							'sga_actas_instancias', 
							'sga_evaluaciones',
							'sga_mesas_examen_instancias',
							'sga_actas_detalle',
							'sga_eval_detalle',
							'sga_eval_detalle_examenes');

		foreach ($referencias as $tabla) {
			$sql = "SELECT	1 as en_uso
					WHERE	EXISTS (SELECT	1
									FROM 	$tabla
									WHERE 	$tabla.escala_nota = $escala_nota)
					";

			$res = guarani_db::consultar_fila($sql);

			if (isset($res['en_uso'])) {
				return true;
			}
		}

		$sql = "SELECT	1 as en_uso
				WHERE	EXISTS (SELECT	1
								FROM 	sga_elementos_atrib
								WHERE 	sga_elementos_atrib.escala_nota_examen = $escala_nota
								OR		sga_elementos_atrib.escala_nota_equivalencia = $escala_nota
								OR		sga_elementos_atrib.escala_nota_cursada_regular = $escala_nota
								OR		sga_elementos_atrib.escala_nota_cursada_promocion = $escala_nota)
				";

		$res = guarani_db::consultar_fila($sql);

		if (isset($res['en_uso'])) {
			return true;
		}

		$sql = "SELECT	1 as en_uso
				WHERE	EXISTS (SELECT	1
								FROM 	sga_elementos_plan
								WHERE 	sga_elementos_plan.escala_nota_examen = $escala_nota
								OR		sga_elementos_plan.escala_nota_equivalencia = $escala_nota
								OR		sga_elementos_plan.escala_nota_cursada_regular = $escala_nota
								OR		sga_elementos_plan.escala_nota_cursada_promocion = $escala_nota)
				";

		$res = guarani_db::consultar_fila($sql);

		if (isset($res['en_uso'])) {
			return true;
		}

		$sql = "SELECT	1 as en_uso
				WHERE	EXISTS (SELECT	1
								FROM 	sga_eval_detalle_cursadas
								WHERE 	sga_eval_detalle_cursadas.escala_nota_promocion = $escala_nota
								OR		sga_eval_detalle_cursadas.escala_nota_cursada = $escala_nota)
				";

		$res = guarani_db::consultar_fila($sql);

		if (isset($res['en_uso'])) {
			return true;
		}

		return false;
	}

    /**
     *  Valida si un nombre de escala de notas se puede usar o no.
     */
	function validar_nombre_escala_notas($nombre, $escala_nota)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT	count(sga_escalas_notas.escala_nota) as cant
                FROM 	sga_escalas_notas
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_escalas_notas.nombre', $nombre).'
				';
		
		if (!is_null($escala_nota)) {
			$escala_nota = toba::db()->quote($escala_nota);
			$sql .= " AND sga_escalas_notas.escala_nota <> $escala_nota ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/**
	 * Determina si una nota es válida dentro de una escala de notas dada.
	 *
	 */
	function existe_nota_en_escala($nota, $escala)
	{
		$nota = toba::db()->quote($nota);
		$escala = toba::db()->quote($escala);
		
		$sql = "SELECT	COUNT(sga_escalas_notas_det.nota) as cant
				FROM	sga_escalas_notas_det
				WHERE	sga_escalas_notas_det.escala_nota = $escala	AND
						sga_escalas_notas_det.nota = $nota";

		$res = guarani_db::consultar_fila($sql);	
		return ($res['cant'] > 0);
	}

	/**
	 * Determina si una nota existe dentro de la escala de notas asociada a la instancia y la mesa de exámen dadas.
	 *
	 * @param integer	$mesa_examen	id de la mesa de examen a la que corresponde la instancia para la cual se determina la escala.
	 * @param integer	$nota			nota para la cual se desea determinar si pertenece a la escala de la instancia.
	 * @param integer	$instancia		id de la instancia para la cual se determina la escala y la relación con la nota.
	 */
	function es_nota_valida_para_instancia_examen($mesa_examen, $nota, $instancia)
	{
		$mesa_examen = toba::db()->quote($mesa_examen);
		$nota = toba::db()->quote($nota);
		$instancia = toba::db()->quote($instancia);
		
		$sql = "SELECT	COUNT(sga_escalas_notas_det.nota) as cant
				FROM	sga_escalas_notas_det,
						sga_mesas_examen_instancias
				WHERE	sga_mesas_examen_instancias.escala_nota = sga_escalas_notas_det.escala_nota AND
						sga_mesas_examen_instancias.mesa_examen = $mesa_examen AND
						sga_mesas_examen_instancias.instancia = $instancia	AND
						sga_escalas_notas_det.nota = $nota
				;";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}
	
	/**
	 * Determina si un resultado es válido para una nota dentro de una escala de notas dadas.
	 *
	 */
	function resultado_valido_nota_escala($nota, $escala, $resultado)
	{
		$nota = toba::db()->quote($nota);
		$escala = toba::db()->quote($escala);
		$resultado = toba::db()->quote($resultado);

		$sql = "SELECT 	COUNT(sga_escalas_notas_det.nota) as cant
				FROM 	sga_escalas_notas_det
				WHERE	sga_escalas_notas_det.escala_nota = $escala AND
						sga_escalas_notas_det.nota = $nota AND
						sga_escalas_notas_det.resultado = $resultado
		";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] > 0);
	}

	function get_escalas_notas_resultados_nombre($resultado)
	{
		if (is_array($resultado)) {
			$filtro = guarani_sql::armar_condicion_in($resultado, 'sga_escalas_notas_resultado', 'resultado');
		} else {
			$resultado = toba::db()->quote($resultado);
			$filtro = "sga_escalas_notas_resultado.resultado = $resultado";
		}
		
		$sql = "SELECT	resultado,
						descripcion as resultado_nombre
				FROM	sga_escalas_notas_resultado
				WHERE 	$filtro";

        return guarani_db::consultar($sql);
	}

	/**
	 * Dado un valor, retorna la mayor nota más cercana al mismo en una escala de notas.
	 *
	 * @param string $valor
	 * @param integer $escala
	 * @return string
	 */
	function get_nota_equivalente_escala($valor, $escala)
	{
		$valor = toba::db()->quote($valor);
		$escala = toba::db()->quote($escala);
		$sql = "SELECT	MAX(CAST(replace(sga_escalas_notas_det.nota, ',', '.')  as NUMERIC)) as nota
				FROM 	sga_escalas_notas_det
				WHERE	sga_escalas_notas_det.escala_nota = $escala AND
						ABS(CAST($valor as NUMERIC) - sga_escalas_notas_det.valor_numerico) = (	SELECT	MIN(ABS(CAST($valor as NUMERIC) - det.valor_numerico))
																								FROM	sga_escalas_notas_det as det
																								WHERE	det.escala_nota = $escala)";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['nota'];
	}

	/**
	 * Retorna las notas inicial y final de una escala de notas dada.
	 *
	 * @param integer $escala_notas
	 * @return array
	 */
	function get_extremos_escala_notas($escala_notas)
	{
		$escala_notas = toba::db()->quote($escala_notas);
		$sql = "SELECT 	CASE 
							WHEN (sga_escalas_notas.tipo = '".escalas_notas::tipo_continua."') THEN sga_escalas_notas.nota_inicial 
							ELSE (	SELECT	MIN(sga_escalas_notas_det.valor_numerico) as nota_inicial
									FROM	sga_escalas_notas_det
									WHERE	sga_escalas_notas_det.escala_nota = sga_escalas_notas.escala_nota)
						END as nota_inicial,
						CASE 
							WHEN (sga_escalas_notas.tipo = '".escalas_notas::tipo_continua."') THEN sga_escalas_notas.nota_final
							ELSE (	SELECT	MAX(sga_escalas_notas_det.valor_numerico) as nota_final
									FROM 	sga_escalas_notas_det
									WHERE 	sga_escalas_notas_det.escala_nota = sga_escalas_notas.escala_nota)
						END as nota_final
				FROM 	sga_escalas_notas
				WHERE 	sga_escalas_notas.escala_nota = $escala_notas
		";
		return guarani_db::consultar_fila($sql);
	}
	
	/**
	 * Recupera las escalas de notas definidas para una instancia. 
	 * El filtro por responsables académicas de las propuestas es opcional.
	 * @param	int		$instancia			Instancia
	 * @param	array	$propuestas			Filtro por responsables académicas de las propuestas (Opcional)
	 * @param	int		$escala_asignada	Escala de nota ya asignada (Opcional)
	 * @return	array	Escalas de notas definidas para una instancia.
	 */
	function get_escalas_notas_por_instancia($instancia, $propuestas = [], $escala_asignada = 0)
	{
		
		$union = "";
		if ($escala_asignada) {
			$union = "
				UNION
				
				SELECT
        			sga_escalas_notas.escala_nota	as valor,
                    sga_escalas_notas.nombre		as descr
                FROM sga_escalas_notas
                WHERE sga_escalas_notas.escala_nota = $escala_asignada";
		}

		$from_ras = "";
		$where_ras = "";
		if (!empty($propuestas)) {
			// Filtro escalas de notas de las responsables academicas de las propuestas pasadas por parametro.
			$from_ras = "JOIN sga_escalas_notas_ra ON (sga_escalas_notas_ra.escala_nota = sga_escalas_notas.escala_nota)";
			$from_ras .= "JOIN sga_propuestas_ra ON (sga_propuestas_ra.responsable_academica = sga_escalas_notas_ra.responsable_academica)";
			$propuestas_in = implode(", ", $propuestas);
			$where_ras = " AND sga_propuestas_ra.propuesta IN ($propuestas_in)";
		}

		$sql = "SELECT DISTINCT
        			sga_escalas_notas.escala_nota	as valor,
                    sga_escalas_notas.nombre		as descr
                FROM sga_escalas_notas
				$from_ras
                WHERE sga_escalas_notas.estado = '" . comunes::estado_activo . "'
				  AND sga_escalas_notas.escala_nota IN (SELECT escala_nota FROM sga_escalas_notas_instancias WHERE instancia = $instancia)
				  $where_ras  
				  $union
                ORDER BY descr
				";
		return guarani_db::consultar($sql);
	}

	function get_escala_notas_cursar($propuestas = [], $escala_asignada = 0)
	{
		return $this->get_escalas_notas_por_instancia(comision::inst_cursar, $propuestas, $escala_asignada);
	}
	
	function get_escala_notas_promocion($propuestas = [], $escala_asignada = 0)
	{
		return $this->get_escalas_notas_por_instancia(comision::inst_promocionar, $propuestas, $escala_asignada);
	}
	
	function get_escala_notas_examen($propuestas = [], $escala_asignada = 0)
	{
		return $this->get_escalas_notas_por_instancia(comision::inst_exam_regular, $propuestas, $escala_asignada);
	}
	
	function get_escala_notas_equivalencia($propuestas = [], $escala_asignada = 0)
	{
		return $this->get_escalas_notas_por_instancia(comision::inst_equivalencia, $propuestas, $escala_asignada);
	}
	
	function get_escala_notas_evaluaciones_comision($propuestas = [], $escala_asignada = 0)
	{
		return $this->get_escalas_notas_por_instancia(comision::inst_eval_comision, $propuestas, $escala_asignada);
	}
	
	/*
	 * Listado de instancias con el módulo al que pertenece cada una. Usada en Escalas de Notas.
	*/
	function get_listado_instancias_escalas_notas($where="")
	{
		if ($where) {
			$where = ' WHERE ' . $where;
		}
		
		$sql = "SELECT 	sga_instancias.instancia,
						sga_instancias.nombre,
						'(' ||
						CASE sga_instancias.aplica_a 
						  WHEN 'C' THEN 'Cursada'
						  WHEN 'P' THEN 'Cursada'
						  WHEN 'M' THEN 'Examen'
						  WHEN 'Q' THEN 'Equivalencia'
						  WHEN 'A' THEN 'Resolución'
						END || ') ' ||  sga_instancias.nombre as display
				FROM 	sga_instancias
				$where
				ORDER BY display, nombre
				";
	
		return guarani_db::consultar($sql);
	}

}