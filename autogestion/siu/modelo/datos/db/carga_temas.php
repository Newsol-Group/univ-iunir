<?php
namespace siu\modelo\datos\db;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\alumno_foto;

class carga_temas
{
	/**
	 * parametros: comision, clase, subcomision
	 * param_null: subcomision
	 * cache: no
	 * filas: n
	 */
	function clase_detalle($parametros)
	{
		
		
		$where_clase  = '';
		
                if (!is_null($parametros['clase'])) {
			
			$where_clase = " where sga_clases.clase= {$parametros['clase']}  ";
		}
		$sql = "  select clase,tema_planificado, tema_dictado from sga_clases $where_clase ";
		$datos = kernel::db()->consultar($sql);

		return $datos;
	}


	/**
	 * parametros: clase
	 * cache: no
	 * filas: 1
	 */
	function clase_cabecera($parametros)
	{
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		$formato_hora_visual = kernel::db()->quote(\comunes::formato_hora_visual);
		
		$sql = "SELECT 
                    vw_clases.clase,
                    to_char(vw_clases.fecha, $formato_fecha_visual) as fecha_formateada,
                    vw_clases.cantidad_horas_dictadas,
                    vw_clases.tipo_clase_nombre as tipo_clase,
                    to_char(vw_clases.hora_inicio, $formato_hora_visual) AS hora_inicio_formateada,
                    to_char(vw_clases.hora_finalizacion, $formato_hora_visual) AS hora_finalizacion_formateada,
                    vw_clases.comision_nombre,
                    vw_clases.comision,
                    vw_clases.dia_semana,
                    vw_comisiones.elemento_nombre as actividad_nombre,
                    vw_comisiones.elemento_codigo as actividad_codigo
                FROM 
                    vw_clases,
                    vw_comisiones
                WHERE 
                        vw_clases.comision = vw_comisiones.comision 
                    AND vw_clases.clase = {$parametros['clase']}";


		$datos = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
		return $datos;
	}

	/**
	 * parametros: clase, tema_dictado, tema_planificado
	 * param_null: tema_dictado, tema_planificado
	 * cache: no
	 * filas: 1
	 */
	function guardar($parametros)
	{
		// update
			$sql = "UPDATE 
							sga_clases
						SET
							tema_planificado = {$parametros['tema_planificado']},
							tema_dictado = {$parametros['tema_dictado']}
						WHERE
							clase = {$parametros['clase']}";

				$consulta = kernel::db()->ejecutar($sql);
				return $consulta;
			}
		
	}

	

?>