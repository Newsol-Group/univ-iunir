<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use siu\modelo\_comun\comunes;

class mesa 
{

	/**
	 * parametros: nombre, elemento, catedra, ubicacion, observaciones, mesa_en_turno_examen, anio_academico, division, fecha, hora_inicio, hora_finalizacion, espacio, instancias, modalidad, docentes
	 * param_null: catedra, observaciones, division, espacio, hora_finalizacion
	 * 	 
	 * cache: no
	 */
	public function insertar_mesa($parametros)
	{
		
		
		if (empty($parametros['catedra']) ) $parametros['catedra'] = 'null';
		if (empty($parametros['observaciones']) ) $parametros['observaciones'] = 'null';
		if (empty($parametros['division']) ) $parametros['division'] = 'null';
		if (empty($parametros['espacio']) ) $parametros['espacio'] = 'null';
		if (empty($parametros['hora_finalizacion']) ) $parametros['hora_finalizacion'] = 'null';
		
		
		$sql = "SELECT * FROM f_mesa_examen_alta (
						{$parametros['nombre']}, 
						{$parametros['elemento']},
						{$parametros['catedra']},
						{$parametros['ubicacion']},
						{$parametros['observaciones']},
						{$parametros['mesa_en_turno_examen']},
						{$parametros['anio_academico']},
						{$parametros['division']},
						{$parametros['fecha']},
						{$parametros['hora_inicio']},
						{$parametros['hora_finalizacion']},
						{$parametros['espacio']},
						{$parametros['instancias']},
						{$parametros['modalidad']},
						{$parametros['docentes']});";

						
		$resultado = kernel::db()->consultar_fila($sql);
		return $resultado;
		
	}
	
	
	/**
	 * parametros: persona, mesa_examen
	 * cache: no
	 * 
	 */
	public function info_link_mesa($parametros)
	{
		// Recupera mesas de examen en los llamados activos donde se encuentra el docente.
		// Mesas de examen con fecha menor o igual a hoy.
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		
		$persona = $parametros['persona'];
		$sql = "SELECT		
						sga_evaluaciones.evaluacion,
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
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_hora_visual) as fecha_formato_visual
			FROM 		vw_mesas_examen,
						sga_ubicaciones, 
						sga_elementos,
						sga_evaluaciones,
						sga_evaluaciones_tipos,
						sga_docentes_mesa_llamado, 
						sga_docentes
			WHERE 		vw_mesas_examen.mesa_examen = {$parametros['mesa_examen']}
						AND sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento 
						AND sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad 
						AND sga_evaluaciones.evaluacion_tipo = sga_evaluaciones_tipos.evaluacion_tipo
						AND sga_evaluaciones_tipos.automatica = '".\comunes::si."'
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
	
}

?>
