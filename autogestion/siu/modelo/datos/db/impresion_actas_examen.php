<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use siu\modelo\entidades;
use siu\modelo\datos\catalogo;

class impresion_actas_examen
{
	/**
	 * parametros: persona, llamado_mesa
	 * cache: no
	 * filas: n
	 */
	function lista_actas_docente($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT		sga_actas.id_acta,
							sga_actas.nro_acta,
							sga_actas.version,
							sga_actas.version_impresa,
							sga_actas.renglones_folio,
							sga_actas.estado,
							sga_elementos.nombre as actividad_nombre,
							vw_mesas_examen.anio_academico,
							vw_mesas_examen.llamado_mesa,
							vw_mesas_examen.mesa_examen,
							vw_mesas_examen.llamado_mesa_entidad as entidad,
							to_char(vw_mesas_examen.mesa_examen_fecha,$formato_fecha_visual) as fecha,
							vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
							CASE
								WHEN (sga_actas.estado = '" . \acta::estado_abierta . "') THEN
									(	SELECT 	count(1)
										FROM	sga_eval_detalle_examenes
										WHERE 	sga_eval_detalle_examenes.id_acta = sga_actas.id_acta
									)
								ELSE
									(	SELECT 	COUNT(1)  
										FROM 	sga_actas_detalle			
										WHERE 	sga_actas_detalle.id_acta = sga_actas.id_acta
									)
							END as cant_alumnos,
							-- Porcentaje de carga del acta
							CASE WHEN sga_actas.estado = '" . \acta::estado_abierta . "'
							THEN
								(	SELECT CAST(SUM(CASE
											WHEN (	
												sga_eval_detalle_examenes.fecha IS NULL
												OR (sga_eval_detalle_examenes.resultado IN ('".\escalas_notas::resultado_aprobado."', '".\escalas_notas::resultado_reprobado."') AND sga_eval_detalle_examenes.nota IS NULL)
												OR sga_eval_detalle_examenes.resultado IS NULL 
											) THEN 0
											ELSE 1
										END) * 100.00 / COUNT(*) AS decimal(5,2)) 
									FROM	sga_eval_detalle_examenes
									WHERE	sga_eval_detalle_examenes.id_acta = sga_actas.id_acta 
								) 
							ELSE 100
							END AS porcentaje_carga
				FROM		sga_actas,
							vw_mesas_examen,
							sga_elementos,
							sga_docentes_mesa_llamado,
							sga_docentes
				WHERE		sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa AND
							vw_mesas_examen.mesa_examen_elemento = sga_elementos.elemento AND
							vw_mesas_examen.llamado_mesa = sga_docentes_mesa_llamado.llamado_mesa AND
							sga_docentes_mesa_llamado.docente = sga_docentes.docente AND
							-- Sólo actas de mesas del docente.
							sga_docentes.persona = {$parametros['persona']}
							AND sga_actas.llamado_mesa = {$parametros['llamado_mesa']}
				ORDER BY 	sga_actas.nro_acta
		";
		return kernel::db()->consultar($sql);
	}
	
	/**
	 * parametros: id_acta
	 * cache: no
	 * filas: 1
	 */
	function marcar_cerrada_por_docente($parametros)
	{
		$id_acta = $parametros['id_acta'];
		
		$sql = "UPDATE	sga_actas  
				SET		cerrada_por_docente = '" . \comunes::si . "'
				WHERE	id_acta = $id_acta
				;";
		
		kernel::db()->ejecutar($sql);
	}
	
	/**
	 * parametros: instancias
	 * no_quote: instancias
	 * cache: no
	 * filas: n
	 */
	function get_puntos_de_control_instancias($parametros)
	{
		$instancias = $parametros['instancias'];
		$sql = "SELECT	sga_instancias_puntos_ctrl.instancia,
						sga_instancias_puntos_ctrl.pto_control
				FROM	sga_instancias_puntos_ctrl
				WHERE	sga_instancias_puntos_ctrl.instancia IN ($instancias) 
		";
		$rs = kernel::db()->consultar($sql);
		$puntos_de_control = array();
		foreach ($rs as $r) {
			$puntos_de_control[$r['instancia']][] = $r['pto_control'];
		}
		return $puntos_de_control;
	}

	/**
	 * parametros: acta
	 * cache: no
	 * filas: 1
	 */
	function acta_cabecera($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT	sga_actas.id_acta,
						sga_actas.nro_acta,
						sga_actas.origen,
						sga_actas.tipo_acta,
						sga_actas.llamado_mesa,
						sga_actas.version,
						sga_actas.version_impresa,
						sga_actas.renglones_folio,
						sga_actas.acta_referencia,
						sga_actas.observaciones,
						sga_actas.estado,
						sga_actas.id_documento_digital,
						sga_actas.estado_documento_digital,
						sga_actas.digital,
						vw_mesas_examen.anio_academico,
						vw_mesas_examen.mesa_examen_nombre as mesa_nombre,
						vw_mesas_examen.turno_examen_nombre as turno_nombre,
						vw_mesas_examen.llamado_nombre as llamado_nombre,
						vw_mesas_examen.mesa_examen_elemento	AS elemento,
						to_char(vw_mesas_examen.mesa_examen_fecha,$formato_fecha_visual) as fecha_formato_visual,
						'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre as cod_nombre_actividad,
						sga_ubicaciones.nombre as ubicacion_nombre
				FROM	sga_actas,
						vw_mesas_examen,
						sga_elementos,
						sga_ubicaciones
				WHERE	sga_actas.llamado_mesa = vw_mesas_examen.llamado_mesa AND
						vw_mesas_examen.mesa_examen_elemento = sga_elementos.elemento AND
						vw_mesas_examen.mesa_examen_ubicacion = sga_ubicaciones.ubicacion AND
						sga_actas.id_acta = {$parametros['acta']}
		";
		return kernel::db()->consultar_fila($sql);
	}

}