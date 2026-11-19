<?php
namespace siu\modelo\datos\db;

use siu\modelo\_comun\comunes;
use SIU\Chulupi\kernel;

class derechos_comision
{
	const alumnos_pagina = 20;
	const derecho_para_comision = 'C';

	/**
	 * parametros: persona, actividad
	 * param_null: actividad
	 * cache: no
	 * filas: n
	 */
	function lista_comisiones_docente($parametros)
	{
		$filtro = "";
		if(isset($parametros['actividad'])){
			$filtro = "AND vw_comisiones.elemento = ". $parametros['actividad'];
		}

        $estado_activo = kernel::db()->quote(comunes::estado_activo);

		$sql = "SELECT
				vw_comisiones.elemento as actividad,
				vw_comisiones.elemento_nombre as actividad_nombre,
				vw_comisiones.elemento_codigo as actividad_codigo,
				vw_comisiones.anio_academico as anio_academico,
				vw_comisiones.periodo_generico_nombre as periodo_lectivo_nombre,
				vw_comisiones.comision as comision,
				vw_comisiones.entidad as entidad,
				vw_comisiones.comision_nombre as comision_nombre,
				sga_turnos_cursadas.nombre  as turno ,
				f_horario_comision (vw_comisiones.comision) as horario,
				f_modalidad_cursada_comision (vw_comisiones.comision) as modalidad

				FROM vw_comisiones
				JOIN sga_comisiones ON (vw_comisiones.comision = sga_comisiones.comision)
				JOIN sga_docentes_comision ON sga_docentes_comision.comision = vw_comisiones.comision
				JOIN sga_docentes ON sga_docentes_comision.docente = sga_docentes.docente
				LEFT JOIN sga_turnos_cursadas ON sga_turnos_cursadas.turno = vw_comisiones.turno_cursada
					
				WHERE vw_comisiones.fecha_inactivacion >= CURRENT_DATE
				  AND COALESCE(vw_comisiones.fecha_publicacion_comisiones_docente,CURRENT_DATE) <= CURRENT_DATE
				  AND sga_docentes.persona  =  {$parametros['persona']}
				  AND sga_comisiones.estado = $estado_activo
				 $filtro
				ORDER BY 1";
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
		if (!empty($parametros['term'])) {
			//Agrego los % %
			$term = kernel::db()->quote_like($parametros['term'], '%', '%');
			$where = "AND (f_limpiar_acentos(vw_comisiones.elemento_codigo) ILIKE $term OR
					      f_limpiar_acentos(vw_comisiones.elemento_nombre)	ILIKE $term)";
		} else {
			//$where = "AND sga_elementos.elemento = {$parametros['actividad']}";
		}

		//todo: sacar el distinct
		$sql = "SELECT  DISTINCT
					vw_comisiones.elemento,
					--vw_comisiones.elemento_nombre as actividad_nombre,
					--vw_comisiones.elemento_codigo as actividad_codigo
					'(' || vw_comisiones.elemento_codigo || ') ' || vw_comisiones.elemento_nombre as descripcion


				FROM vw_comisiones,
					sga_docentes_comision,
					sga_docentes
				WHERE vw_comisiones.fecha_fin >= CURRENT_DATE
					AND sga_docentes_comision.comision = vw_comisiones.comision
					AND sga_docentes_comision.docente = sga_docentes.docente
					AND sga_docentes.persona  =  {$parametros['persona']}
					$where
				ORDER BY 1";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: comision
	 * cache: no
	 * filas: 1
	 */
	function comision_cabecera($parametros)
	{
		$sql = "SELECT
						vw_comisiones.elemento_nombre as actividad_nombre,
						vw_comisiones.elemento_codigo as actividad_codigo,
						vw_comisiones.comision_nombre as comision_nombre,
						f_horario_comision (vw_comisiones.comision) as horario

				FROM    vw_comisiones
				WHERE
						vw_comisiones.comision = {$parametros['comision']}
				";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

    /**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function docentes_comision($parametros)
	{
		$sql = "
				SELECT mdp_personas.apellido as apellido,
				       mdp_personas.nombres as nombre,
				       mdp_personas.persona,
				       r.nombre as responsabilidad,
				       sga_docentes.legajo,
				       sga_docentes.docente as id,
				       mdp_personas.id_imagen as id_imagen
				  FROM sga_docentes_comision
				  JOIN sga_docentes ON sga_docentes.docente = sga_docentes_comision.docente
				  JOIN mdp_personas ON mdp_personas.persona = sga_docentes.persona
				  LEFT JOIN sga_docentes_responsabilidades as r ON r.responsabilidad = sga_docentes_comision.responsabilidad
				 WHERE sga_docentes_comision.comision = {$parametros['comision']}
				ORDER BY r.orden_listado, mdp_personas.apellido, mdp_personas.nombres
		";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: comision
	 * cache: no
	 * filas: n
	 */
	function derechos_comision($parametros)
	{
		
		$derecho_para_comision = kernel::db()->quote(static::derecho_para_comision);
		
		$sql = "
			SELECT
					gdu_derechos_personas.persona,
					gdu_derechos.derecho as derecho,
					gdu_derechos.nombre as nombre
			FROM gdu_derechos
			LEFT JOIN gdu_derechos_personas	ON (gdu_derechos.derecho = gdu_derechos_personas.derecho)
			JOIN sga_comisiones				ON (gdu_derechos_personas.entidad = sga_comisiones.entidad)
			WHERE
					sga_comisiones.comision = {$parametros['comision']}
					AND gdu_derechos.derecho <> 'otorgar_derechos_comision'
					AND gdu_derechos.derecho_para = $derecho_para_comision
			ORDER BY gdu_derechos.derecho";
					
		return kernel::db()->consultar($sql);
	}

	/**
	 * cache: memoria
	 * filas: n
	 */
	function derechos_asignables()
	{
		
		$derecho_para_comision = kernel::db()->quote(static::derecho_para_comision);
		
		$sql = "
			SELECT
					gdu_derechos.derecho as derecho,
					gdu_derechos.nombre as nombre
			FROM
					gdu_derechos
			WHERE
					gdu_derechos.derecho_para = $derecho_para_comision
					AND gdu_derechos.derecho <> 'otorgar_derechos_comision'
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
		$sql = "INSERT INTO gdu_derechos_personas(
                    entidad, derecho, persona)
	            VALUES ({$parametros['entidad']},
	                    {$parametros['derecho']},
	                    {$parametros['persona_']});
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
     * parametros: docente, comision
     * cache: memoria
     * filas: 1
     */
    function responsabilidad_docente_comision($parametros)
    {

        $sql = "SELECT  sga_docentes_responsabilidades.responsabilidad,
                        sga_docentes_responsabilidades.nombre,
                        sga_docentes_responsabilidades.descripcion
                FROM sga_docentes_comision
                JOIN sga_docentes ON (sga_docentes_comision.docente = sga_docentes.docente)
                JOIN sga_docentes_responsabilidades ON (sga_docentes_responsabilidades.responsabilidad = sga_docentes_comision.responsabilidad)
                WHERE sga_docentes.docente = {$parametros['docente']}
                AND sga_docentes_comision.comision = {$parametros['comision']}
				";

        return kernel::db()->consultar_fila($sql);
    }

    /**
     * parametros: rol
     * cache: memoria
     * filas: n
     */
    function derechos_comision_rol($parametros)
    {
		
		$derecho_para_comision = kernel::db()->quote(static::derecho_para_comision);
		
        $sql = "SELECT gdu_derechos.derecho,
                       gdu_derechos.nombre,
                       gdu_derechos.descripcion
                FROM gdu_comision
                JOIN gdu_derechos ON (gdu_comision.derecho = gdu_derechos.derecho)
                WHERE responsabilidad = {$parametros['rol']}
                AND gdu_derechos.derecho_para = $derecho_para_comision
                AND gdu_derechos.derecho != 'otorgar_derechos_comision'
                ORDER BY gdu_derechos.derecho
				";

        return kernel::db()->consultar($sql);
    }

}
?>