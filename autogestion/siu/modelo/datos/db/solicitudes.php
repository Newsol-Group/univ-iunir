<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;

class solicitudes
{
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
				sc.permite_descargar
			FROM sga_constancias_solicitud AS scs,
			     sga_constancias AS sc
			WHERE scs.alumno = {$parametros['alumno']}
			  AND scs.constancia = sc.constancia
			  AND scs.estado NOT IN ('Rechazado', 'Entregado')
			ORDER BY scs.solicitud
		";

        $datos = kernel::db()->consultar($sql);
        $nuevo = array();

        foreach($datos as $key => $dato) {
            $nuevo[$key] = $dato;
            $nuevo[$key][catalogo::id] = catalogo::generar_id($dato['solicitud']);
        }

        return $nuevo;
	}

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
				sc.permite_descargar
			FROM sga_constancias_solicitud AS scs,
			     sga_constancias AS sc
			WHERE scs.alumno = {$parametros['alumno']}
			  AND scs.constancia = sc.constancia
			  AND scs.estado IN ('Rechazado', 'Entregado')
			ORDER BY scs.fecha_solicitud, scs.solicitud
		";

        $datos = kernel::db()->consultar($sql);
        $nuevo = array();

        foreach($datos as $key => $dato) {
            $nuevo[$key] = $dato;
            $nuevo[$key][catalogo::id] = catalogo::generar_id($dato['solicitud']);
        }

        return $nuevo;
	}

	/**
	 * **********
	 * 
	 * 
	 * esto es para acordarme de cambiar al comitear
	 * 
	 * 
	 * 
	 * 
	 * 
	 * 
	 * **
	 * parametros: propuesta
	 * param_null: propuesta
	 * no_quote: propuesta
	 * cache: no
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
		
		$sql = "SELECT constancia, nombre ,objeto
				FROM sga_constancias 
		        WHERE activo = $si 
					--AND (objeto IS NULL OR constancia = $constancia_alumno_regular)  -- Esta condicion hay que sacarla cuando se implemente los formularios de entrada de datos
					AND disponible_autogestion = $si
					$where_ra
		        ORDER BY nombre
				";
		return kernel::db()->consultar($sql);
	}

	/**
	 * parametros: constancia
	 * cache: no
	 * filas: 1
	 */
	function get_max_cant_copias($parametros)
	{
		$sql = "SELECT max_cant_copias FROM sga_constancias WHERE constancia = {$parametros['constancia']}";
		$rs = kernel::db()->consultar_fila($sql);
		return $rs['max_cant_copias'];
	}

	/**
	 * parametros: constancia, alumno
	 * cache: no
	 * filas: 1
	 */
	function get_cantidades($parametros)
	{
		// Recupera la cantidad de constancias solicitadas por el alumno desde que ingreso a la propuesta.
		// Solicitudes de esa constancia en cualquier estado.
		// Usado para validar la cantidad maxima de solicitudes de dicha constancia que el alumno puede realizar.
		$sql = "
			SELECT
				max_cant_copias,
				max_cant_solicitudes,
				(SELECT count(*)
					FROM sga_constancias_solicitud
					WHERE
						constancia = {$parametros['constancia']} AND
						alumno = {$parametros['alumno']}
				) AS cant_solicitudes
			FROM sga_constancias as sc
			WHERE constancia = {$parametros['constancia']}";
		return kernel::db()->consultar_fila($sql);
	}

	/**
	 * parametros: alumno, constancia, copias, presentar, observaciones, interfaz, estado, codigo_verificacion, formulario, formulario_campo, formulario_valor
     * param_null: codigo_verificacion, formulario, formulario_campo, formulario_valor
	 * no_quote: formulario_campo
	 * cache: no
	 */
	function nueva_solicitud($parametros)
	{
        $codigo_verificacion = ($parametros['codigo_verificacion']) ? $parametros['codigo_verificacion'] : 'NULL';

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

		return kernel::db()->ejecutar($sql);
	}
}
?>