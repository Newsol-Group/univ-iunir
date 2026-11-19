<?php
namespace siu\modelo\datos\db;

use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo\entidades; //parametro importarlo asi entidades\parametro
use siu\modelo\entidades\alumno_foto;
use siu\modelo\entidades\parametro;

class carga_notas_cursada
{
	/**
	 * Lista de actas de cursadas y promociones abiertas (normales o rectificativas)
	 * parametros: persona, comision
	 * param_null: comision
	 * cache: no
	 * filas: n
	 */
	function lista_actas_abiertas_docente($parametros)
	{
		$filtro_comision = (isset($parametros['comision'])) ? " AND sga_actas.comision = {$parametros['comision']}" : '';

		$estado_abierto = kernel::db()->quote(comunes::estado_abierto);
		$tipo_normal 	= kernel::db()->quote(\acta::tipo_normal);
		$tipo_rectificativa	= kernel::db()->quote(\acta::tipo_rectificativa);
		

		//para el link a carga de notas el id es comision si no tiene subco, o comision_subcomision en otro caso
		$sql = "SELECT		vw_comisiones.comision as id,
							sga_actas.id_acta,
							sga_actas.nro_acta,
							sga_actas.comision,
							sga_actas.version,
							sga_actas.renglones_folio,
							vw_comisiones.comision_nombre as comision_nombre,
							vw_comisiones.elemento as actividad,
							vw_comisiones.elemento_codigo as actividad_codigo,
							vw_comisiones.entidad, 
							vw_comisiones.elemento_nombre as actividad_nombre,
							vw_comisiones.anio_academico as anio_academico,
							vw_comisiones.periodo_lectivo_nombre as periodo_lectivo_nombre,
							sga_actas_instancias.escala_nota,
							sga_actas.origen,
							sga_actas.tipo_acta, 
							CASE sga_actas.tipo_acta 
								WHEN $tipo_normal THEN 'Normal'
								WHEN $tipo_rectificativa THEN 'Rectificativa'
							END as tipo_acta_desc,
							CASE  
								WHEN sga_actas.tipo_acta = $tipo_normal THEN ''
								WHEN sga_actas.tipo_acta = $tipo_rectificativa AND sga_actas.acta_referencia IS NOT NULL THEN (SELECT ao.nro_acta FROM sga_actas as ao WHERE ao.id_acta = sga_actas.acta_referencia)
							END as rectifica_a,
							CASE sga_actas.origen
								WHEN '" . \acta::origen_regulares . "' THEN 
									(SELECT	COUNT(1)
									FROM	sga_eval_detalle_cursadas 
									WHERE	sga_eval_detalle_cursadas.id_acta_cursada = sga_actas.id_acta AND
											sga_eval_detalle_cursadas.acta_cursada_cerrada = 0)
								WHEN '" . \acta::origen_promociones ."' THEN
									(SELECT	COUNT(1)
									FROM	sga_eval_detalle_cursadas 
									WHERE	sga_eval_detalle_cursadas.id_acta_promocion = sga_actas.id_acta AND
											sga_eval_detalle_cursadas.acta_promocion_cerrada = 0)
								ELSE 0	
							END as cant_alumnos
				FROM	sga_actas
				JOIN	vw_comisiones ON vw_comisiones.comision = sga_actas.comision
				JOIN	sga_actas_instancias ON sga_actas_instancias.id_acta = sga_actas.id_acta
				JOIN	sga_docentes_comision ON sga_docentes_comision.comision = vw_comisiones.comision
				JOIN	sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
				WHERE	
					-- Sólo actas ABIERTAS.
					sga_actas.estado = $estado_abierto
					-- Sólo actas de comisiones del docente.
					AND	sga_docentes.persona = {$parametros['persona']}
					$filtro_comision
				ORDER BY	vw_comisiones.elemento_nombre,
							vw_comisiones.anio_academico DESC,
							vw_comisiones.fecha_inicio DESC
		";
		$actas = kernel::db()->consultar($sql);
		foreach ($actas as $key => $acta) {
			$parametros_porcentaje_carga_notas = array('acta' => $acta['id_acta'], 'comision' => $acta['comision']);
			$actas[$key]['porcentaje_carga'] = ($acta['origen'] == \acta::origen_regulares) ? $this->porcentaje_carga_notas_regulares($parametros_porcentaje_carga_notas) : $this->porcentaje_carga_notas_promocionales($parametros_porcentaje_carga_notas);
			$actas[$key]['origen_descr'] = ($acta['origen'] == \acta::origen_regulares) ? kernel::traductor()->trans('notas_cursada_comision.acta_cursada') : kernel::traductor()->trans('notas_cursada_comision.acta_promocion');
		}
		return $actas;
	}

	/**
	 * parametros: acta, comision
	 * cache: no
	 * filas: 1
	 */
	function porcentaje_carga_notas_regulares($parametros)
	{
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $parametros['comision']));
		$asentar_notas = entidades\parametro::get_valor(\acta::cur_asentar_notas_actas_regulares, $propuestas);
		
		$sql = "SELECT	CAST(SUM(CASE
								WHEN (	sga_eval_detalle_cursadas.cond_regularidad IS NULL OR
										sga_eval_detalle_cursadas.fecha_regular IS NULL OR
										('$asentar_notas' = '" . \acta::ingreso_notas_obligatorio . "' AND sga_eval_detalle_cursadas.resultado_cursada IN ('" . \escalas_notas::resultado_aprobado . "', '" . \escalas_notas::resultado_reprobado . "') AND sga_eval_detalle_cursadas.nota_cursada IS NULL) OR
										sga_eval_detalle_cursadas.resultado_cursada IS NULL) THEN 0
                				ELSE 1
							END) * 100.00 / COUNT(*) AS decimal(5,2)) AS porcentaje
				FROM	sga_eval_detalle_cursadas
				WHERE	sga_eval_detalle_cursadas.id_acta_cursada = {$parametros['acta']}
		";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['porcentaje'];
	}

    /**
     * parametros: acta, comision
     * cache: no
     * filas: 1
     */
    function porcentaje_carga_notas_promocionales($parametros)
    {
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $parametros['comision']));
        $asentar_notas = entidades\parametro::get_valor(\acta::cur_asentar_notas_actas_promocion, $propuestas);
		
        $sql = "SELECT	CAST(SUM(CASE
								WHEN (sga_eval_detalle_cursadas.fecha_promocion IS NULL OR
										('$asentar_notas' = '" . \acta::ingreso_notas_obligatorio . "' AND sga_eval_detalle_cursadas.resultado_promocion IN ('" . \escalas_notas::resultado_aprobado . "', '" . \escalas_notas::resultado_reprobado . "') AND sga_eval_detalle_cursadas.nota_promocion IS NULL) OR
										sga_eval_detalle_cursadas.resultado_promocion IS NULL) THEN 0
                				ELSE 1
							END) * 100.00 / COUNT(*) AS decimal(5,2)) AS porcentaje
				FROM	sga_eval_detalle_cursadas
				WHERE	sga_eval_detalle_cursadas.id_acta_promocion = {$parametros['acta']}
		";
        $datos = kernel::db()->consultar_fila($sql);
        return $datos['porcentaje'];
    }

	/**
	 * parametros:
	 * cache: no
	 */
	function condiciones_activas()
	{
		return \toba::consulta_php('co_condiciones_regularidad')->get_condiciones_activas();
	}

	/**
	 * parametros:
	 * cache: no
	 */
	function resultados_escala_nota()
	{
		return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_resultados();
	}

	/**
	 * parametros: tipo
	 * cache: no
	 */
	function lista_escala_notas($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_listado("tipo={$parametros['tipo']}");
	}

	/**
	 * parametros: escala
	 * no_quote: escala
	 * cache: no
	 */
	function rango_escala_notas($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_rangos_resultados($parametros['escala']);
	}

	/**
	 * parametros: escala
	 * no_quote: escala
	 * cache: no
	 */
	function detalle_escala_notas($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_escalas_notas_detalles($parametros['escala']);
	}

	/**
	 * parametros: acta
	 * no_quote: acta
	 * cache: no
	 * filas: 1
	 */
	function acta_cabecera($parametros)
	{
		$datos = \toba::consulta_php('co_actas')->get_datos_acta($parametros['acta']);
		$datos[catalogo::id] = catalogo::generar_id($datos['id_acta']);
		$datos['url_comision'] = catalogo::generar_id($datos['comision']);
		$datos['folios'] = ($datos['renglones_folio'] == 0) ? 0 : ceil($datos['cant_alumnos'] / $datos['renglones_folio']);
		return $datos;
	}

	/**
	 * parametros: acta
	 * cache: no
	 * filas: 1
	 */
	function acta_estado($parametros)
	{
		$sql = "SELECT	sga_actas.estado
				FROM	sga_actas
				WHERE	sga_actas.id_acta = {$parametros['acta']}
		";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['estado'];
	}

	/**
	 * parametros: acta, renglones_folio, folio
	 * no_quote: acta, renglones_folio, folio
	 * cache: no
	 * filas: 1
	 */
	function acta_folio($parametros)
	{
		$datos_acta = \toba::consulta_php('co_actas')->get_datos_acta($parametros['acta']);
		
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $datos_acta['comision']));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));
		$params_sistema = array(
			'cur_inserta_alumnos_no_promocionales_en_acta' => parametro::get_valor('cur_inserta_alumnos_no_promocionales_en_acta', $propuestas),
			'cur_orden_detalle_actas' => parametro::get_valor('cur_orden_detalle_actas', $ras),
		);
		
		$limit = "LIMIT {$parametros['renglones_folio']}";
		$offset = "OFFSET {$parametros['renglones_folio']} * ({$parametros['folio']} - 1)";
		$datos = \toba::consulta_php('co_actas')->get_alumnos_acta_cursadas($parametros['acta'], comunes::instancia_regularidad, '', $limit, $offset, false, $params_sistema);

		$nuevo = array();
		foreach ($datos as $dato) {
			$dato['renglon'] = $dato['alumno'];
			$dato['fecha'] = $dato['fecha_display'];
			$dato['url_imagen'] = empty($dato['id_imagen']) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm.png') : alumno_foto::url_imagen($dato['id_imagen']);
			$nuevo[$dato['renglon']] = $dato;
		}
		
		return $nuevo;
	}

	/**
	 * parametros: evaluacion, alumno, fecha, cond_regularidad, nota, resultado
	 * param_null: fecha, cond_regularidad, nota, resultado
	 * param_tipo: int, int, str/10, int, str/10, str/1
	 * cache: no
	 * filas: 1
	 */
	function guardar_renglon($parametros)
	{
		if (!is_null($parametros['fecha'])) {
			$fecha = date_parse_from_format('d/m/Y', $parametros['fecha']);
			$fecha = "'{$fecha['year']}-{$fecha['month']}-{$fecha['day']}'";
		} else {
			$fecha = 'null';
		}
		$set = "fecha_regular = $fecha";

		$cond_regularidad = is_null($parametros['cond_regularidad']) ? 'null' : $parametros['cond_regularidad'];
		$set .= ", cond_regularidad = $cond_regularidad";

		$nota = is_null($parametros['nota']) ? 'null' : $parametros['nota'];
		$set .= ", nota_cursada = $nota";

		$resultado = is_null($parametros['resultado']) ? 'null' : $parametros['resultado'];
		$set .= ", resultado_cursada = $resultado";

		$sql = "UPDATE	sga_eval_detalle_cursadas
				SET		$set
				WHERE	evaluacion = {$parametros['evaluacion']} AND
						alumno = {$parametros['alumno']}
		";
		$datos = kernel::db()->ejecutar($sql);
		return $datos;
	}

	/**
	 * parametros: acta, version, version_impresa
	 * cache: no
	 * filas: 1
	 */
	function actualizar_acta($parametros)
	{
		if ($parametros['version'] == $parametros['version_impresa']) {
			$sql = "UPDATE	sga_actas
					SET		version = ({$parametros['version']} + 1)
					WHERE	id_acta = {$parametros['acta']}";
			$datos = kernel::db()->ejecutar($sql);
			return $datos;
		}
	}

	/**
	 * parametros: escala_notas
	 * no_quote: escala_notas
	 * cache: memoria
	 * filas: n
	 */
	function resultados($parametros)
	{
		return \toba::consulta_php('co_escalas_notas')->get_resultados_posibles_instancia_escala_nota(comunes::instancia_regularidad, $parametros['escala_notas']);
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function condiciones_regularidad($parametros)
	{
		return \toba::consulta_php('co_condiciones_regularidad')->get_condiciones_activas();
	}

	/**
	 * parametros: alumno, comision
	 * no_quote: alumno, comision
	 * cache: no
	 * filas: n
	 */
	function get_datos_parciales($parametros)
	{
		return \toba::consulta_php('co_evaluaciones')->get_resultados_evaluaciones_alumno_comision($parametros['alumno'], $parametros['comision']);
	}

}