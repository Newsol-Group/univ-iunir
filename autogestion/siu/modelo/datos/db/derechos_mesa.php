<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;

class derechos_mesa
{

	const derecho_para_mesa = 'M';
	
    function cant_dias_extras_para_mostrar_mesas_examenes(){
        return 30;
    }

	/**
	 * parametros: persona, actividad
	 * param_null: actividad
	 * cache: no
	 * filas: n
	 */
	function lista_mesas_docente($parametros)
	{
		// Lista mesas de examen donde se encuentra el docente.
		// Mesas de examen de turnos en curso y una actividad (opcional)
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
        
		$filtro = '';
		if(isset($parametros['actividad'])){
			$filtro = "AND mesa_examen_elemento = ". str_replace("'", "", $parametros['actividad']);
		}
		
		$persona = $parametros['persona'];
		
		$sql = "SELECT	vw_mesas_examen.mesa_examen_nombre as mesa_examen_nombre,
						vw_mesas_examen.mesa_examen as mesa_examen,
						vw_mesas_examen.llamado_mesa as llamado_mesa,
						vw_mesas_examen.mesa_examen_elemento as actividad,
						vw_mesas_examen.llamado_mesa_entidad as entidad,
						vw_actividades.codigo as actividad_codigo,
						vw_actividades.nombre as actividad_nombre,
						'(' || vw_actividades.codigo || ') ' || vw_actividades.nombre AS actividad_descr,
						vw_mesas_examen.mesa_examen_ubicacion as ubicacion,
						sga_ubicaciones.nombre as ubicacion_nombre,
						vw_mesas_examen.turno_examen_nombre as turno_examen_nombre,
						vw_mesas_examen.llamado_nombre as llamado_nombre,
						vw_mesas_examen.mesa_examen_fecha as fecha,
						to_char(vw_mesas_examen.mesa_examen_fecha, $formato_fecha_visual) || ' ' || to_char(cast(vw_mesas_examen.mesa_examen_hora_inicio as time), $formato_fecha_visual) as fecha_formato_visual,
						get_espacios_mesa_examen(vw_mesas_examen.llamado_mesa) as espacio_nombre,
						sga_evaluaciones.evaluacion as evaluacion,
						0 as solicitar_libro,
						(vw_mesas_examen.mesa_examen_fecha || ' ' || vw_mesas_examen.mesa_examen_hora_inicio)::timestamp as fecha_con_hora
				FROM 	vw_mesas_examen
				JOIN	sga_evaluaciones ON sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad
				JOIN	sga_evaluaciones_tipos ON sga_evaluaciones_tipos.evaluacion_tipo = sga_evaluaciones.evaluacion_tipo
				JOIN	sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
				JOIN	vw_actividades ON vw_actividades.elemento = vw_mesas_examen.mesa_examen_elemento
				JOIN	sga_docentes_mesa_llamado ON sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa
				JOIN	sga_docentes ON sga_docentes.docente = sga_docentes_mesa_llamado.docente

				WHERE	sga_docentes.persona = $persona
				  AND vw_mesas_examen.mesa_examen_fecha + ".$this->cant_dias_extras_para_mostrar_mesas_examenes()." >= CURRENT_DATE
				  AND vw_mesas_examen.llamado_mesa_estado = 'A'
				  AND sga_evaluaciones_tipos.automatica = 'S'
				  $filtro
				ORDER BY fecha_con_hora, actividad_descr
				";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: persona, term
	 * no_quote: term
	 * cache: no
	 * filas: n
	 */
	function lista_actividades_docente($parametros)
	{
		// Actividades de turnos en curso, un docente dado y que en su nombre se encuentra la cadena <term>
		if (!empty($parametros['term'])) {
			//Agrego los % %
			$term = kernel::db()->quote_like($parametros['term'], '%', '%');
			$where = "AND (f_limpiar_acentos(sga_elementos.codigo) ILIKE $term OR
					      f_limpiar_acentos(sga_elementos.nombre)	ILIKE $term)";
		}

		//todo: sacar el distinct
		$sql = "SELECT  DISTINCT
					vw_mesas_examen.mesa_examen_elemento as elemento,
					'(' || sga_elementos.codigo || ') ' || sga_elementos.nombre as descripcion
				FROM 
					vw_mesas_examen
					JOIN sga_elementos ON sga_elementos.elemento = vw_mesas_examen.mesa_examen_elemento
					JOIN sga_docentes_mesa_llamado ON sga_docentes_mesa_llamado.llamado_mesa = vw_mesas_examen.llamado_mesa
					JOIN sga_docentes ON sga_docentes.docente = sga_docentes_mesa_llamado.docente
				WHERE 
					vw_mesas_examen.mesa_examen_fecha + ".$this->cant_dias_extras_para_mostrar_mesas_examenes()." >= CURRENT_DATE
				AND sga_docentes.persona  =  {$parametros['persona']}
				$where
				ORDER BY 1";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: llamado_mesa
	 * cache: no
	 * filas: 1
	 */
	function mesa_cabecera($parametros)
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
						(SELECT count(*)  FROM sga_evaluaciones, sga_eval_detalle_examenes
						 WHERE sga_evaluaciones.evaluacion = sga_eval_detalle_examenes.evaluacion
						 AND sga_evaluaciones.entidad = vw_mesas_examen.llamado_mesa_entidad) as cant_alumnos

				FROM
					vw_mesas_examen
					JOIN sga_ubicaciones ON sga_ubicaciones.ubicacion = vw_mesas_examen.mesa_examen_ubicacion
					JOIN vw_actividades ON vw_actividades.elemento = vw_mesas_examen.mesa_examen_elemento
					LEFT JOIN sga_catedras ON sga_catedras.catedra = vw_mesas_examen.mesa_examen_catedra
				WHERE vw_mesas_examen.llamado_mesa = {$parametros['llamado_mesa']}
				";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

    /**
	 * parametros: llamado_mesa
	 * cache: no
	 * filas: n
	 */
	function docentes_mesa($parametros)
	{			
		$sql = "SELECT mdp_personas.apellido as apellido,
				       mdp_personas.nombres as nombre,
				       mdp_personas.persona,
				       r.nombre as responsabilidad,
				       sga_docentes.legajo,
				       sga_docentes.docente as id,
				       mdp_personas.id_imagen as id_imagen
				  FROM sga_docentes_mesa_llamado
				       JOIN sga_docentes ON sga_docentes.docente = sga_docentes_mesa_llamado.docente
				       JOIN mdp_personas ON mdp_personas.persona = sga_docentes.persona
				       LEFT JOIN sga_tribunal_roles as r ON r.rol = sga_docentes_mesa_llamado.rol
				 WHERE sga_docentes_mesa_llamado.llamado_mesa = {$parametros['llamado_mesa']}
				ORDER BY r.orden_acta, mdp_personas.apellido, mdp_personas.nombres
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: llamado_mesa
	 * cache: no
	 * filas: n
	 */
	function derechos_mesa($parametros)
	{
		
		$derecho_para_mesa = kernel::db()->quote(static::derecho_para_mesa);
		
		$sql = "SELECT		gdu_derechos_personas.persona,
							gdu_derechos.derecho as derecho,
							gdu_derechos.nombre as nombre,
							CASE 
								WHEN gdu_derechos_personas.derecho IS NULL THEN 0
								ELSE 1
							END as tiene_derecho
				FROM		sga_llamados_mesa
							LEFT JOIN gdu_derechos_personas ON gdu_derechos_personas.entidad = sga_llamados_mesa.entidad
							LEFT JOIN gdu_derechos ON gdu_derechos.derecho = gdu_derechos_personas.derecho
				WHERE		sga_llamados_mesa.llamado_mesa = {$parametros['llamado_mesa']}
							AND gdu_derechos.derecho <> 'otorgar_derechos_mesa_examen'
							AND gdu_derechos.derecho_para = $derecho_para_mesa
				ORDER BY	gdu_derechos.derecho";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function derechos_asignables()
	{
		
		$derecho_para_mesa = kernel::db()->quote(static::derecho_para_mesa);
		
		$sql = "
			SELECT
					gdu_derechos.derecho as derecho,
					gdu_derechos.nombre as nombre
			FROM
					gdu_derechos
			WHERE
					gdu_derechos.derecho_para = $derecho_para_mesa
					AND gdu_derechos.derecho <> 'otorgar_derechos_mesa_examen'
			ORDER BY
					derecho";
		$datos = kernel::db()->consultar($sql);

		return $datos;
	}



	/**
	 * parametros: persona_, derecho, entidad
	 * cache: no
	 * filas: 1
	 */
	function agregar_derecho($parametros)
	{
		$sql = "INSERT INTO gdu_derechos_personas(entidad, derecho, persona)
	            VALUES ({$parametros['entidad']}, {$parametros['derecho']}, {$parametros['persona_']});
		";
		$filas_afectadas = kernel::db()->ejecutar($sql);
		return $filas_afectadas;
	}

	/**
	 * parametros: persona_, derecho, entidad
	 * cache: no
	 * filas: 1
	 */
	function eliminar_derecho($parametros)
	{
		$sql = "DELETE FROM gdu_derechos_personas
					WHERE entidad = {$parametros['entidad']}
					 AND derecho = {$parametros['derecho']}
					 AND persona = {$parametros['persona_']};
		";
		$filas_afectadas = kernel::db()->ejecutar($sql);
		return $filas_afectadas;
	}

    /**
     * parametros: docente, llamado_mesa
     * cache: memoria
     * filas: 1
     */
    function rol_docente_mesa($parametros)
    {

        $sql = "SELECT  sga_tribunal_roles.rol,
                        sga_tribunal_roles.nombre,
                        sga_tribunal_roles.descripcion
                FROM sga_docentes_mesa_llamado
                JOIN sga_tribunal_roles ON (sga_docentes_mesa_llamado.rol = sga_tribunal_roles.rol)
                WHERE sga_docentes_mesa_llamado.docente = {$parametros['docente']}
                AND sga_docentes_mesa_llamado.llamado_mesa = {$parametros['llamado_mesa']}
				";

        return kernel::db()->consultar_fila($sql);
    }

    /**
     * parametros: rol
     * cache: memoria
     * filas: n
     */
    function derechos_mesa_rol($parametros)
    {
		
		$derecho_para_mesa = kernel::db()->quote(static::derecho_para_mesa);
		
        $sql = "SELECT gdu_derechos.derecho,
                       gdu_derechos.nombre,
                       gdu_derechos.descripcion
                FROM gdu_mesa_examen
                JOIN gdu_derechos ON (gdu_mesa_examen.derecho = gdu_derechos.derecho)
                WHERE gdu_mesa_examen.rol = {$parametros['rol']}
                AND gdu_derechos.derecho_para = $derecho_para_mesa
                AND gdu_derechos.derecho != 'otorgar_derechos_mesa_examen'
                ORDER BY gdu_derechos.derecho
				";

        return kernel::db()->consultar($sql);
    }

}
?>
