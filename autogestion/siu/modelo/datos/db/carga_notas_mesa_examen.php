<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\entidades\alumno_foto;

class carga_notas_mesa_examen
{
	const alumnos_pagina = 20;

	/**
	 *
	 * parametros: persona
	 * cache: no
	 * filas: n
	 */
	function lista_mesas_docente($parametros)
	{
		$persona = $parametros['persona'];
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);

		$sql = "SELECT		sga_evaluaciones.evaluacion,
							vw_mesas_examen.mesa_examen, 
							vw_mesas_examen.llamado_mesa, 
							sga_elementos.elemento,
							sga_elementos.codigo,
							sga_elementos.nombre as nombre_actividad,
							COALESCE('(' || sga_elementos.codigo || ') ' , '') || sga_elementos.nombre AS actividad_descr,
							vw_mesas_examen.mesa_examen_nombre as nombre_mesa, 
							vw_mesas_examen.mesa_examen_ubicacion, 
							sga_ubicaciones.nombre as nombre_ubicacion,
							vw_mesas_examen.llamado_nombre as nombre_llamado,
							vw_mesas_examen.mesa_examen_fecha as fecha,
							to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_fecha_visual) as fecha_formato_visual
				FROM 		vw_mesas_examen,
							sga_ubicaciones, 
							sga_elementos,
							sga_evaluaciones,
							sga_evaluaciones_tipos,
							sga_docentes_mesa_llamado, 
							sga_docentes
				WHERE 		sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
							AND sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad 
							AND sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
							AND sga_evaluaciones_tipos.automatica = '" . \comunes::si . "'
							AND sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
							-- Mesas del docente 
							AND sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa 
							AND sga_docentes_mesa_llamado.docente = sga_docentes.docente
							AND sga_docentes.persona = $persona
							-- Mesas con fecha menor o igual a hoy
							AND vw_mesas_examen.mesa_examen_fecha <= CURRENT_DATE
							AND vw_mesas_examen.llamado_mesa_estado = 'A'
				ORDER BY 	vw_mesas_examen.mesa_examen_fecha
		";
		
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 *
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: no
	 * filas: 1
	 */
	function get_datos_encabezado_mesa($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);

		$sql = "SELECT	vw_actividades.elemento as elemento,
						vw_actividades.codigo as actividad_codigo,
						vw_actividades.nombre as actividad_nombre,

						vw_mesas_examen.anio_academico as anio_academico,
						vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
						vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
						vw_mesas_examen.llamado_nombre as llamado_nombre,

						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) as fecha_formato_visual,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_hora_visual) as fecha_formato_visual_sin_segundos,
						sga_ubicaciones.nombre as ubicacion_nombre,
						get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa, 1) as espacio_nombre,
						(SELECT count(*)  FROM sga_evaluaciones, sga_eval_detalle_examenes
						 WHERE sga_evaluaciones.evaluacion = sga_eval_detalle_examenes.evaluacion
						 AND sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad) as cant_alumnos

				FROM	vw_mesas_examen
						LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra,
						sga_ubicaciones,
						vw_actividades
				WHERE	vw_mesas_examen.llamado_mesa = {$parametros['llamado_mesa']}
						AND sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
						AND vw_actividades.elemento = vw_mesas_examen.mesa_examen_elemento";

		$datos = kernel::db()->consultar_fila($sql);
		$datos['paginas'] = ceil($datos['cant_alumnos'] / static::alumnos_pagina);
		return $datos;
	}

	protected function get_datos_mesa_llamados($llamado_mesa, $select = '')
	{
		$llamado_mesa = \toba::db()->quote($llamado_mesa);
		$datos = \toba::consulta_php('co_mesas_de_examen')->get_listado("sga_llamados_mesa.llamado_mesa = $llamado_mesa", $select);
		if (!empty($datos)) {
			return current($datos);
		} else {
			return array();
		}
	}

	/**
	 *
	 * parametros: evaluacion
	 * cache: no
	 * filas: n
	 */
	function get_alumnos_mesa($parametros)
	{
		$evaluacion = $parametros['evaluacion'];
		$alumnos_pagina = static::alumnos_pagina;
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		// Recupera alumnos inscriptos en la mesa y alumnos agregados a la mesa que no tienen inscripcion.
		$sql = "SELECT	vw_personas.apellido_nombres_elegido AS apellido_y_nombre,
						vw_personas.tipo_nro_documento AS documento_principal,
						sga_alumnos.alumno,
						sga_alumnos.alumno as renglon,
						sga_alumnos.legajo,
						sga_propuestas.nombre_abreviado AS propuesta_nombre_abreviado,
						sga_eval_detalle_examenes.instancia,
						sga_instancias.nombre AS instancia_nombre,
						" . \guarani_sql::get_case_si_no('sga_eval_detalle_examenes', 'inscripto', 'inscripto') . ",
						sga_actas.nro_acta,
						vw_personas.id_imagen,
						sga_llamados_mesa.llamado_mesa,
						to_char(sga_eval_detalle_examenes.fecha, $formato_fecha_visual) as fecha,
						sga_eval_detalle_examenes.evaluacion,
						sga_eval_detalle_examenes.nota,
						sga_eval_detalle_examenes.resultado,
						sga_eval_detalle_examenes.escala_nota,
						sga_eval_detalle_examenes.id_acta,
						sga_actas.version,
						sga_actas.version_impresa,
						sga_eval_detalle_examenes.ultimo_cambio

				FROM	sga_eval_detalle_examenes
						JOIN sga_evaluaciones				ON (sga_evaluaciones.evaluacion				= sga_eval_detalle_examenes.evaluacion)
						JOIN sga_evaluaciones_tipos			ON (sga_evaluaciones.evaluacion_tipo		= sga_evaluaciones_tipos.evaluacion_tipo)
						JOIN sga_llamados_mesa				ON (sga_llamados_mesa.entidad				= sga_evaluaciones.entidad)
						LEFT JOIN sga_insc_examen			ON (sga_insc_examen.alumno					= sga_eval_detalle_examenes.alumno
																AND sga_insc_examen.llamado_mesa		= sga_llamados_mesa.llamado_mesa)
						LEFT JOIN sga_actas					ON (sga_actas.id_acta						= sga_eval_detalle_examenes.id_acta)
						JOIN sga_alumnos					ON (sga_alumnos.alumno						= sga_eval_detalle_examenes.alumno)
						JOIN vw_personas					ON (vw_personas.persona						= sga_alumnos.persona)
						JOIN sga_propuestas					ON (sga_propuestas.propuesta				= sga_alumnos.propuesta)
						JOIN sga_instancias					ON (sga_instancias.instancia				= sga_eval_detalle_examenes.instancia)

				WHERE 	sga_evaluaciones.evaluacion	= $evaluacion
						AND (sga_eval_detalle_examenes.inscripto = 'N' OR 
							 (sga_eval_detalle_examenes.inscripto = 'S' AND sga_insc_examen.estado	= '" . \inscripcion_examen::estado_aceptada . "')
							) 

				ORDER BY 
						vw_personas.apellido_nombres_elegido

				LIMIT	$alumnos_pagina
				OFFSET	$alumnos_pagina * ({$parametros['pagina']} - 1)
				;";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $dato) {
			$dato['url_imagen'] = empty($dato['id_imagen']) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm.png') : alumno_foto::url_imagen($dato['id_imagen']);
			$nuevo[$dato['renglon']] = $dato;
		}
		return $nuevo;
	}

	/**
	 * Obtiene el listado de resultados disponibles para las distintas instancias
	 * en que una mesa ha sido habilitada.
	 *
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: memoria
	 * filas: n
	 */
	function resultados($parametros)
	{
		$datos_instancias = $this->get_instancias_llamado_mesa($parametros['llamado_mesa']);
		$salida = array();
		foreach ($datos_instancias as $dato_instancia) {
			$salida[$dato_instancia['instancia']] = \toba::consulta_php('co_escalas_notas')->get_resultados_posibles_instancia_escala_nota($dato_instancia['instancia'], $dato_instancia['escala_nota']);
		}
		return $salida;
	}

	/**
	 * Obtiene el listado de instancias habilitadas para un llamado_mesa
	 *
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: memoria
	 * filas: n
	 */
	function get_instancias_llamado_mesa($parametros)
	{
		if (is_array($parametros)) {
			$llamado_mesa = kernel::db()->quote($parametros['llamado_mesa']);
		} else {
			$llamado_mesa = kernel::db()->quote($parametros);
		}

		$sql = "SELECT 	sga_mesas_examen_instancias.instancia,
						sga_instancias.nombre as instancia_nombre,
						sga_mesas_examen_instancias.escala_nota,
						sga_escalas_notas.nombre as escala_nota_nombre
				FROM 	sga_llamados_mesa,
						sga_mesas_examen_instancias,
						sga_instancias,
						sga_escalas_notas
				WHERE 	sga_llamados_mesa.mesa_examen = sga_mesas_examen_instancias.mesa_examen
						AND sga_instancias.instancia = sga_mesas_examen_instancias.instancia
						AND sga_mesas_examen_instancias.escala_nota = sga_escalas_notas.escala_nota
						AND sga_llamados_mesa.llamado_mesa = $llamado_mesa
				";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Obtiene el listado de LAS DISTINTAS instancias habilitadas para un llamado_mesa
	 *
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: memoria
	 * filas: n
	 */
	function escalas_usadas($parametros)
	{
		$llamado_mesa = kernel::db()->quote($parametros['llamado_mesa']);

		$sql = "SELECT 	DISTINCT sga_mesas_examen_instancias.escala_nota
				FROM 	sga_llamados_mesa,
						sga_mesas_examen_instancias
				WHERE 	sga_llamados_mesa.mesa_examen = sga_mesas_examen_instancias.mesa_examen
						AND sga_llamados_mesa.llamado_mesa = $llamado_mesa
				";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Obtiene los detalles de escala de notas para las distintas instancias
	 * habilitadas para un llamado_mesa
	 *
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: memoria
	 * filas: n
	 */
	function escala_notas($parametros)
	{
		$datos_instancias = $this->get_instancias_llamado_mesa($parametros['llamado_mesa']);

		$salida = array();
		foreach ($datos_instancias as $dato_instancia) {
			$salida[$dato_instancia['instancia']] = $this->get_detalles_escala_nota($dato_instancia['escala_nota']);
		}
		return $salida;
	}

	/**
	 * Obtiene los detalles de una escala de notas dada
	 *
	 * parametros: escala_nota
	 * no_quote: escala_nota
	 * cache: memoria
	 * filas: n
	 */
	function get_detalles_escala_nota($escala_nota)
	{
		if (is_array($escala_nota)) {
			$escala_nota = $escala_nota['escala_nota'];
		}
		$escala_nota = kernel::db()->quote($escala_nota);

		$sql = "SELECT		sga_escalas_notas_det.nota,
							sga_escalas_notas_det.descripcion,
							sga_escalas_notas_resultado.descripcion as resultado,
							sga_escalas_notas_det.valor_numerico,
							sga_escalas_notas_concepto.descripcion as concepto
				FROM		sga_escalas_notas_det
							LEFT JOIN sga_escalas_notas_concepto ON sga_escalas_notas_det.concepto = sga_escalas_notas_concepto.concepto,
					 		sga_escalas_notas_resultado
				WHERE		sga_escalas_notas_det.escala_nota = $escala_nota
							AND	sga_escalas_notas_resultado.resultado = sga_escalas_notas_det.resultado
				ORDER BY	sga_escalas_notas_det.valor_numerico ASC
		";

		return kernel::db()->consultar($sql);
	}

	/**
	 * Obtiene las cabeceras de las escalas de notas vinculadas a las instancias
	 * del llamado_mesa
	 *
	 * parametros: llamado_mesa
	 * no_quote: llamado_mesa
	 * cache: no
	 * filas: n
	 */
	function datos_escalas_notas($parametros)
	{
		$datos_instancias = $this->get_instancias_llamado_mesa($parametros['llamado_mesa']);

		$salida = array();
		foreach ($datos_instancias as $dato_instancia) {
			$salida[$dato_instancia['instancia']] = current(\toba::consulta_php('co_escalas_notas')->get_info_escalas_notas($dato_instancia['escala_nota']));
		}
		return $salida;
	}

	/**
	 * parametros: evaluacion, alumno, fecha, nota, resultado, ultimo_cambio
	 * param_null: fecha, nota, resultado
	 * param_tipo: int, int, str/10, str/10, str/1, str/100
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
		$set = "fecha = $fecha";

		$nota = is_null($parametros['nota']) ? 'null' : $parametros['nota'];
		$set .= ", nota = $nota";

		$resultado = is_null($parametros['resultado']) ? 'null' : $parametros['resultado'];
		$set .= ", resultado = $resultado";

		$sql = "UPDATE	sga_eval_detalle_examenes
				SET		$set
				WHERE	evaluacion = {$parametros['evaluacion']} 
						AND	alumno = {$parametros['alumno']} 
						AND	ultimo_cambio = {$parametros['ultimo_cambio']}
		";
		$filas_afectadas = kernel::db()->ejecutar($sql);
		return $filas_afectadas;
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
	 * parametros: llamado_mesa, term
	 * cache: no
	 * filas: n
	 */
	function busqueda_alumno($parametros)
	{
		$alumnos_pagina = static::alumnos_pagina;

		// El ordenamiento de los alumnos es por el Apellido y Nombre (parámetro: exa_orden_detalle_actas)
		$orden = 2;
		
		$sql = "SELECT	alumno,
						alumno as renglon,
						pagina,
						apellido || ', ' || nombres as apellido_nombres
				FROM	f_buscar_alumnos_mesa_examen({$parametros['llamado_mesa']}, $alumnos_pagina, $orden, {$parametros['term']})
		";
		
		return kernel::db()->consultar($sql);
	}

}
?>