<?php
namespace nsg01\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use siu\modelo\datos\db\solicitudes as solicitudes_nsg;

class solicitudes extends solicitudes_nsg
{
	// Newsol 03-2022 - Se agreg� el campo observaciones como respuesta al alumno de lo solicitado por autogesti�n
	/** 
	 * parametros: alumno
	 * cache: no
	 * filas: n
	 */
	function get_solicitudes_activas($parametros)
	{
		// Lista las solicitudes de constancias realizadas por el alumno que no estan rechazadas ni entregadas 
		// Estado de las solicitudes: Pendiente, Impreso, Rechazado, Entregado
		$sql = "
			SELECT
				sc.constancia AS constancia,
				sc.nombre AS nombre_constancia,
				scs.solicitud AS solicitud,
				scs.fecha_solicitud,
				scs.presentar_a,
				scs.interfaz,
				scs.cant_copias_solicitadas,
				scs.estado,
				scs.fecha_fin_vigencia,
				scs.observaciones,
				sc.permite_descargar
			FROM sga_constancias_solicitud AS scs,
			     sga_constancias AS sc
			WHERE scs.alumno = {$parametros['alumno']}
			  AND scs.constancia = sc.constancia
			  AND scs.estado NOT IN ('Rechazado', 'Entregado')
			ORDER BY scs.fecha_solicitud desc
		";

        	$datos = kernel::db()->consultar($sql);
        	$nuevo = array();

        	foreach($datos as $key => $dato) {
            		$nuevo[$key] = $dato;
            	$nuevo[$key][catalogo::id] = catalogo::generar_id($dato['solicitud']);
        	}
		return $nuevo;
	}

	// Newsol 03-2022 - Se agreg� el campo observaciones como respuesta al alumno de lo solicitado por autogesti�n
	/**
	 * parametros: alumno
	 * cache: no
	 * filas: n
	 */
	function get_solicitudes_historicas($parametros)
	{
		// Lista las solicitudes de constancias realizadas por el alumno que estan rechazadas o entregadas 
		// Estado de las solicitudes: Pendiente, Impreso, Rechazado, Entregado
		$sql = "
			SELECT
				sc.constancia AS constancia,
				sc.nombre AS nombre_constancia,
				scs.solicitud AS solicitud,
				scs.fecha_solicitud,
				scs.presentar_a,
				scs.interfaz,
				scs.cant_copias_solicitadas,
				scs.estado,
				scs.fecha_fin_vigencia,
				scs.observaciones,
				sc.permite_descargar
			FROM sga_constancias_solicitud AS scs,
			     sga_constancias AS sc
			WHERE scs.alumno = {$parametros['alumno']}
			  AND scs.constancia = sc.constancia
			  AND scs.estado IN ('Rechazado', 'Entregado')
			ORDER BY scs.fecha_solicitud desc		
		";

        	$datos = kernel::db()->consultar($sql);
        	$nuevo = array();

        	foreach($datos as $key => $dato) {
            		$nuevo[$key] = $dato;
            		$nuevo[$key][catalogo::id] = catalogo::generar_id($dato['solicitud']);
       		}
      	
		return $nuevo;
	}

	// Newsol 05-2025 - Se agreg� el motivo de baja de la carrera a las solicitudes del alumno
	/**
   	* cache: memoria
    	* filas: n
    	*/
    	function get_motivos_baja()
    	{
        	return \toba::consulta_php('co_inscripciones')->get_motivos_baja();
    	}

	// Newsol 05-2025 - Se agreg� el tipo de beneficio que puede solicitar el alumno desde autogesti�n
	/**
   	* cache: memoria
    	* filas: n
    	*/
    	function get_tipos_beneficios()
    	{
        	return \toba::consulta_php('co_facturacion')->get_tipos_beneficio_alumno_autogestion();
    	}

	// Newsol 05-2025 - Se agreg� la materia a la que se quiere dar de baja por autogestion
	/**
     	* parametros: alumno
	* no_quote: alumno
     	* cache: no
     	*/
   	function get_materias_inscripto($parametros)
    	{
        	return \toba::consulta_php('co_inscripciones_cursadas')->get_inscripciones_actividad_alumno($parametros['alumno'], '');
    	}

	// Newsol 03-2022 - Se agreg� el join con la tabla extendida de constancias para considerar el campo cobrable
	/**
	 * parametros: propuesta
	 * param_null: propuesta
	 * no_quote: propuesta
	 * cache: memoria
	 * filas: n
	 */

	function lista_constancias_activas($parametros)
	{   
		// Listado de constancias disponibles para solicitud por el alumno en autogestion.
		$constancia_alumno_regular = \constancias::constancia_de_alumno_regular;
		$si = kernel::db()->quote(\comunes::si);
		
		$where_ra = '';
		if (!is_null($parametros['propuesta'])) {
			$propuesta = kernel::db()->quote($parametros['propuesta']);
			$where_ra = "AND constancia IN (SELECT DISTINCT sga_constancias_ra.constancia
											FROM sga_constancias_ra
											JOIN sga_propuestas_ra ON (sga_constancias_ra.responsable_academica = sga_propuestas_ra.responsable_academica)
											WHERE sga_propuestas_ra.propuesta = $propuesta)";
		}
		
		$sql = "SELECT constancia, (nombre || (case c.cobrable when 'S' then '-- Arancelado' else '' end)) as nombre, objeto 
				FROM sga_constancias 
				LEFT JOIN negocio_nsg.sga_constancias_ext c ON sga_constancias.constancia = c.id_constancia
		        WHERE activo = $si 
			AND disponible_autogestion = $si
					$where_ra
		        ORDER BY nombre
				";
		return kernel::db()->consultar($sql);
	}

	// Newsol 05-2025 - Se agreg� el insert en la tabla de constancias_solicitud extendida para incluir el campo cobrable y motivo de baja	 
	/**
	 * parametros: alumno, constancia, copias, presentar, observaciones, interfaz, estado, motivo_baja, tipo_beneficio, comision, codigo_verificacion, formulario, formulario_campo, formulario_valor
     	 * param_null: codigo_verificacion, formulario, formulario_campo, formulario_valor
	 * no_quote: formulario_campo
	 * cache: no
	 */
	function nueva_solicitud($parametros)
	{
        	$codigo_verificacion = ($parametros['codigo_verificacion'])? $parametros['codigo_verificacion'] : 'NULL';

		$insert_formulario = "";
		if (!empty($parametros['formulario_campo'])) {
			$formulario_campo = kernel::db()->quote($parametros['formulario_campo']);
			$insert_formulario = "INSERT INTO sga_constancias_solicitud_param (
				solicitud,
				campo,
				valor
			) VALUES (
				currval('sga_constancias_solicitud_seq'),
				{$formulario_campo},
				{$parametros['formulario_valor']}
		);";
		} else {			

			$insert_formulario = "";
		}

		$sql = "
			INSERT INTO sga_constancias_solicitud (
				alumno,
				constancia,
				cant_copias_solicitadas,
				presentar_a,
				observaciones,
				interfaz,
				estado,
				codigo_verificacion
			) VALUES (
				{$parametros['alumno']},
				{$parametros['constancia']},
				{$parametros['copias']},
				{$parametros['presentar']},
				{$parametros['observaciones']},
				{$parametros['interfaz']},
				{$parametros['estado']},
				{$codigo_verificacion}
			); ".$insert_formulario ;

		$datos = kernel::db()->ejecutar($sql);
		$ret_datos['datos'] = $datos;

		if ($datos > 0) {
			
			$sql = "SELECT max(sga_constancias_solicitud.solicitud) as solicitud, 
					sga_constancias_ext.cobrable as cobrable
				FROM sga_constancias_solicitud
					left join sga_constancias ON sga_constancias_solicitud.constancia = sga_constancias.constancia
					left join negocio_nsg.sga_constancias_ext ON sga_constancias.constancia = negocio_nsg.sga_constancias_ext.id_constancia	
				WHERE sga_constancias_solicitud.alumno = {$parametros['alumno']}
				AND sga_constancias_solicitud.constancia = {$parametros['constancia']}
				AND sga_constancias_solicitud.interfaz = {$parametros['interfaz']}
				AND date_part('day', sga_constancias_solicitud.fecha_solicitud) = date_part('day', now())
				GROUP BY sga_constancias_ext.cobrable
				";

			$datos_solicitud = kernel::db()->consultar($sql);
			$datos_solicitud[0]['cobrable'] = kernel::db()->quote($datos_solicitud[0]['cobrable']);

			$sql = "INSERT INTO negocio_nsg.sga_constancias_solicitud_ext (
					id_solicitud,
					cobrable, 
					motivo_baja, tipo_beneficio, comision, pago) VALUES ({$datos_solicitud[0]['solicitud']}, {$datos_solicitud[0]['cobrable']}, {$parametros['motivo_baja']}, {$parametros['tipo_beneficio']}, {$parametros['comision']}, 'N')";

				kernel::db()->ejecutar($sql);
			
			$ret_datos['solicitud'] = $datos_solicitud[0]['solicitud'];
		} 
		return $ret_datos;
	}
}
?>