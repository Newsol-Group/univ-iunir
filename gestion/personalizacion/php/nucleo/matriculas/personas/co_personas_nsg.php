<?php
/**
 *
 *
 * @package Matrícula
 * @subpackage Personas
 */
class co_personas_nsg extends co_personas
{
	//Newsol - Ficha del Alumno: Se agregaron los campos de fechas (solicitud, proceso y entrega), el estado y las observaciones (de los alumnos y de la institución)	
	function get_constancias_solicitadas($persona)
	{
		$sql = "SELECT (select distinct observaciones from negocio_auditoria.logs_sga_constancias_solicitud where solicitud = sga_constancias_solicitud.solicitud and estado = 'Pendiente') AS observaciones_alu,
					observaciones, 
					sga_constancias_solicitud.fecha_solicitud,
					sga_constancias_solicitud.fecha_proceso,
					sga_constancias_solicitud.fecha_entrega,
					sga_constancias_solicitud.cant_copias_solicitadas,
					sga_constancias_solicitud.estado,
					sga_constancias.nombre as constancia_nombre,
					sga_propuestas.nombre as propuesta_nombre,
					sga_propuestas.propuesta as propuesta					
				FROM 
					sga_constancias_solicitud,
					sga_constancias,
					sga_alumnos,
					sga_propuestas
				WHERE
					sga_constancias_solicitud.constancia = sga_constancias.constancia
					AND sga_constancias_solicitud.alumno = sga_alumnos.alumno
					AND sga_propuestas.propuesta = sga_alumnos.propuesta
					AND sga_alumnos.persona = $persona

				";
		return guarani::db()->consultar($sql);
	}

	/** Newsol (01/2025) - Administrar Personas - Se cambió el campo trabajo_ocupacion para reflejar la situación de IVA de los docentes
        Obtiene la lista de las situaciones de IVA de una persona.
    	*/
	function get_cond_iva()
	{
	   	$sql = 'SELECT		nsg_cond_iva.cond_iva as valor,
	   						nsg_cond_iva.descripcion as descr
				FROM 		negocio_nsg.nsg_cond_iva as nsg_cond_iva
		';
		return guarani_db::consultar($sql);
	}

}
?>