<?php
namespace nsg01\modelo\datos\db;

use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use SIU\Chulupi\kernel;
use siu\modelo\entidades\alumno_foto;
use siu\modelo\entidades\parametro;

class carga_notas_cursada_comision extends \siu\modelo\datos\db\carga_notas_cursada_comision
{

	// Newsol - 06/2025 Se agregaron los % de asistencia a cursadas y promoción de un alumno
	/**
	 * parametros: comision, id_acta
	 * param_null: id_acta
	 * no_quote: comision, id_acta
	 * cache: no
	 * filas: 1
	 */
	function comision_cabecera($parametros)
	{
	$sql_id_acta = "";
		
		if (!empty($parametros['id_acta'])) {
			$sql_id_acta = " AND ".$parametros['id_acta']."in (sga_eval_detalle_cursadas.id_acta_cursada,
							sga_eval_detalle_cursadas.id_acta_promocion)";
		}
		$sql = "SELECT	sga_comisiones.comision as id,
						sga_comisiones.comision,
						sga_comisiones.nombre as comision_nombre,
						sga_elementos.codigo as actividad_codigo,
						sga_elementos.nombre as actividad_nombre,
						sga_periodos.anio_academico as anio_academico_periodo,
						sga_periodos.nombre as periodo_lectivo_nombre,
						sga_periodos.fecha_inicio as fecha_inicio_periodo,
						to_char(sga_periodos.fecha_inicio, '" . comunes::formato_fecha_visual . "') as fecha_inicio_periodo_visual,
						comisiones_instancias_regularidad.instancia as instancia_regularidad,
						comisiones_instancias_regularidad.escala_nota as escala_nota_regularidad,
						escalas_notas_regularidad.tipo as escala_nota_tipo_regularidad,
						comisiones_instancias_promocion.instancia as instancia_promocion,
						comisiones_instancias_promocion.escala_nota as escala_nota_promocion,
						escalas_notas_promocion.tipo as escala_nota_tipo_promocion,
						comisiones_instancias_regularidad.porc_asistencia as porc_asistencia_cursada,
						comisiones_instancias_promocion.porc_asistencia as porc_asistencia_promocion,

						(	SELECT	COUNT(1)
							FROM	sga_evaluaciones
									JOIN sga_evaluaciones_tipos ON (sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND sga_evaluaciones_tipos.aplica_a = '" . \acta::evaluacion_tipo_aplica_comision . "')
									JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
       								LEFT JOIN sga_insc_cursada ON (sga_comisiones.comision = sga_insc_cursada.comision AND sga_eval_detalle_cursadas.alumno = sga_insc_cursada.alumno)
							WHERE	sga_comisiones.entidad = sga_evaluaciones.entidad AND
									(sga_insc_cursada.estado IS NULL OR sga_insc_cursada.estado = '" . \inscripcion_cursada::estado_aceptada . "')
									$sql_id_acta						
									) as cant_alumnos
				FROM	sga_comisiones
						  LEFT JOIN sga_comisiones_instancias as comisiones_instancias_regularidad ON 
						           (comisiones_instancias_regularidad.comision = sga_comisiones.comision AND
						            comisiones_instancias_regularidad.instancia = " . \comision::inst_cursar . ")
						  LEFT JOIN sga_comisiones_instancias as comisiones_instancias_promocion ON             
						           (sga_comisiones.comision = comisiones_instancias_promocion.comision AND
						            comisiones_instancias_promocion.instancia = " . \comision::inst_promocionar . ")
						  LEFT JOIN sga_escalas_notas as escalas_notas_regularidad ON escalas_notas_regularidad.escala_nota = comisiones_instancias_regularidad.escala_nota
						  LEFT JOIN sga_escalas_notas as escalas_notas_promocion ON escalas_notas_promocion.escala_nota = comisiones_instancias_promocion.escala_nota, 
						sga_elementos,
						sga_periodos_lectivos,
						sga_periodos
				WHERE	sga_comisiones.elemento = sga_elementos.elemento AND
						sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo AND
						sga_periodos_lectivos.periodo = sga_periodos.periodo AND
						sga_comisiones.comision = {$parametros['comision']}
		";
		$datos = kernel::db()->consultar_fila($sql);
		$datos['paginas'] = ceil($datos['cant_alumnos'] / carga_notas_cursada_comision::alumnos_pagina);
		return $datos;
	}

	// Newsol - 06/2025 Se agregaron los % de asistencia a cursadas y promoción de un alumno
	/** 
	 * parametros: comision, subcomision, id_acta, pagina
	 * param_null: subcomision, pagina, id_acta
	 * no_quote: comision, subcomision, id_acta, pagina
	 * cache: no
	 * filas: n
	 */
	function alumnos_comision($parametros)
	{
		$propuestas = catalogo::consultar('atributos_params', 'get_propuestas_comision', array('comision' => $parametros['comision']));
		$ras = catalogo::consultar('atributos_params', 'get_propuestas_ras', array('propuestas' => $propuestas));

		$param_ordenamiento = parametro::get_valor('cur_orden_detalle_actas', $ras);
		$campo_ordenamiento = \toba::consulta_php('co_actas')->get_campo_ordenamiento($param_ordenamiento);

		$sql_limit = "";
		$sql_id_acta = "";
		if ($parametros['pagina']) {
			$alumnos_pagina = carga_notas_cursada_comision::alumnos_pagina;
			$sql_limit = " LIMIT $alumnos_pagina OFFSET $alumnos_pagina * ({$parametros['pagina']} - 1)";
		}
		if (!empty($parametros['id_acta'])) {
			$sql_id_acta = "AND ".$parametros['id_acta']."in (sga_eval_detalle_cursadas.id_acta_cursada,
							sga_eval_detalle_cursadas.id_acta_promocion)";
		}

                $filtro_subcomision = "";
                // Si es una subcomisión
                if (!empty($parametros['subcomision'])) {
                    $filtro_subcomision = static::filtro_subcomision('sga_alumnos.alumno', $parametros['subcomision']);
                }

		$sql = "SELECT		sga_comisiones.comision,
							sga_evaluaciones.evaluacion,
							sga_eval_detalle_cursadas.instancia_cursada,
							sga_eval_detalle_cursadas.instancia_promocion,
							sga_eval_detalle_cursadas.cond_regularidad,
							to_char(sga_eval_detalle_cursadas.fecha_regular, '" . comunes::formato_fecha_visual . "') as fecha_regular,
							to_char(sga_eval_detalle_cursadas.fecha_promocion, '" . comunes::formato_fecha_visual . "') as fecha_promocion,
							sga_eval_detalle_cursadas.escala_nota_cursada,
							sga_eval_detalle_cursadas.escala_nota_promocion,
							sga_eval_detalle_cursadas.nota_cursada,
							sga_eval_detalle_cursadas.nota_promocion,
							sga_eval_detalle_cursadas.resultado_cursada,
							sga_eval_detalle_cursadas.resultado_promocion,
							sga_eval_detalle_cursadas.id_acta_cursada,
							sga_eval_detalle_cursadas.id_acta_promocion,
							sga_eval_detalle_cursadas.acta_cursada_cerrada,
							sga_eval_detalle_cursadas.acta_promocion_cerrada,
							sga_eval_detalle_cursadas.observacion_cursada,
							sga_eval_detalle_cursadas.observacion_promocion,
							acta_regularidad.nro_acta as nro_acta_regularidad,
							acta_promocion.nro_acta as nro_acta_promocion,
							acta_regularidad.version as version_regularidad,
							acta_promocion.version as version_promocion,
							acta_regularidad.version_impresa as version_impresa_regularidad,
							acta_promocion.version_impresa as version_impresa_promocion,
							sga_alumnos.alumno,
							sga_alumnos.alumno as renglon,
							vw_personas.apellido,
							vw_personas.apellido_elegido,
							vw_personas.nombres,
							vw_personas.nombres_elegido,
							vw_personas.apellido_nombres_elegido,
							vw_personas.tipo_nro_documento as documento_principal,
							vw_personas.id_imagen,
							sga_eval_detalle_cursadas.ultimo_cambio,
							sga_clases_asistencia_acum.porc_asistencia,
							comisiones_instancias_regularidad.porc_asistencia as porc_asistencia_cursada,
							comisiones_instancias_promocion.porc_asistencia as porc_asistencia_promocion

				FROM		sga_comisiones
							LEFT JOIN sga_comisiones_instancias as comisiones_instancias_regularidad ON 
						           (comisiones_instancias_regularidad.comision = sga_comisiones.comision AND
						            comisiones_instancias_regularidad.instancia = " . \comision::inst_cursar . ")
						  	LEFT JOIN sga_comisiones_instancias as comisiones_instancias_promocion ON             
						           (sga_comisiones.comision = comisiones_instancias_promocion.comision AND
						            comisiones_instancias_promocion.instancia = " . \comision::inst_promocionar . ")
							JOIN sga_evaluaciones ON sga_comisiones.entidad = sga_evaluaciones.entidad
							LEFT JOIN sga_evaluaciones_tipos ON (sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo AND sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND sga_evaluaciones_tipos.aplica_a = '" . \acta::evaluacion_tipo_aplica_comision . "')
							JOIN sga_eval_detalle_cursadas ON sga_evaluaciones.evaluacion = sga_eval_detalle_cursadas.evaluacion
							JOIN sga_alumnos ON (sga_eval_detalle_cursadas.alumno = sga_alumnos.alumno)
							JOIN vw_personas ON (sga_alumnos.persona = vw_personas.persona)
							LEFT JOIN sga_actas as acta_regularidad ON sga_eval_detalle_cursadas.id_acta_cursada = acta_regularidad.id_acta
							LEFT JOIN sga_actas as acta_promocion ON sga_eval_detalle_cursadas.id_acta_promocion = acta_promocion.id_acta
							LEFT JOIN sga_insc_cursada ON (sga_comisiones.comision = sga_insc_cursada.comision AND sga_eval_detalle_cursadas.alumno = sga_insc_cursada.alumno)
							LEFT JOIN sga_clases_asistencia_acum ON (sga_comisiones.comision = sga_clases_asistencia_acum.comision AND sga_eval_detalle_cursadas.alumno = sga_clases_asistencia_acum.alumno)

				WHERE		sga_comisiones.comision = {$parametros['comision']}
                                                AND (sga_insc_cursada.estado IS NULL OR sga_insc_cursada.estado = '" . \inscripcion_cursada::estado_aceptada . "')
						$sql_id_acta
                                                $filtro_subcomision
                                -- https://stackoverflow.com/questions/13580826/postgresql-repeating-rows-from-limit-offset
				ORDER BY $campo_ordenamiento, sga_alumnos.alumno
				$sql_limit";

		$datos = kernel::db()->consultar($sql);

		$nuevo = array();
		foreach ($datos as $dato) {
			$dato['url_imagen'] = empty($dato['id_imagen']) ? kernel::vinculador()->vinculo_recurso('img/iconos/mm.png') : alumno_foto::url_imagen($dato['id_imagen']);
			$nuevo[$dato['renglon']] = $dato;
		}
		return $nuevo;
	}
}
?>