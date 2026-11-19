<?php
/**
 *
 *
 * @package 	Matrícula
 * @subpackage 	Alumnos
 * 
 */
class co_vencimientos_regularidad
{
	function get_listado($where='')
	{
		if ($where) {
			$where = "WHERE $where";
		}
		$sql = "SELECT 		sga_regularidades_venc.vencimiento,
							sga_regularidades_venc.fecha_alta,
					    	to_char(sga_regularidades_venc.fecha_alta,'".comunes::formato_fecha_visual."') AS fecha_modificacion,
							COALESCE(to_char(sga_regularidades_venc.fecha_vigencia_anterior,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') AS fecha_vigencia_anterior,
							COALESCE(to_char(sga_regularidades_venc.fecha_vigencia_nuevo,'".comunes::formato_fecha_visual."'), 'Sin Vencimiento') AS fecha_vigencia_nuevo,
							sga_regularidades_venc.fecha_vigencia_nuevo as fecha_vigencia, -- (Fecha de vigencia sin formato)
						    sga_documentos.documento_numero,
							sga_regularidades_venc.observaciones
				FROM 		sga_regularidades_venc
							LEFT JOIN sga_documentos ON sga_documentos.documento = sga_regularidades_venc.documento
				$where
				ORDER BY 	sga_regularidades_venc.vencimiento
		";
		return guarani_db::consultar($sql);
	}
	
	function get_max_fecha_vencimiento($alumno, $id_acta=null, $equivalencia=null)
	{
		$alumno = toba::db()->quote($alumno);
		if (!is_null($id_acta)) {
			$id_acta = toba::db()->quote($id_acta);
			$where = " AND sga_regularidades_venc.id_acta = $id_acta";
		} else {
			$equivalencia = toba::db()->quote($equivalencia);
			$where = " AND sga_regularidades_venc.equivalencia = $equivalencia";
		}
		$sql = "SELECT 	sga_regularidades_venc.fecha_vigencia_nuevo
				FROM 	sga_regularidades_venc
				WHERE	sga_regularidades_venc.vencimiento = (	SELECT	MAX(sga_regularidades_venc.vencimiento) AS vencimiento
																FROM	sga_regularidades_venc
																WHERE	sga_regularidades_venc.alumno = $alumno
																		$where)
		";
		// Consulto la tabla de movimientos de fecha de fin de vigencia.
		$rtn = guarani_db::consultar_fila($sql);
		
		// Consulto si ya tuvo alguna modificacion del vencimiento
		if (empty($rtn)) {
			// No tuvo, voy a buscar en el acta o equivalencia
			if (!is_null($id_acta)) {
				// Acta de Cursada.
				$sql = " SELECT fecha_vigencia as fecha_vigencia_nuevo 
				          FROM sga_actas_detalle
						  WHERE id_acta = $id_acta 
						    AND alumno = $alumno";
			} else {
				// Equivalencia de Regularidad o Equivalencia Parcial
				$sql = " SELECT fecha_vigencia as fecha_vigencia_nuevo
				           FROM sga_equiv_otorgada 
						  WHERE equivalencia = $equivalencia";
			}
			
			// Recupero la fecha de fin de vigencia actual.
			$rtn = guarani_db::consultar_fila($sql);
		}	
			
		
		// Retorno la fecha de fin de vigencia actual.
		return $rtn;
	}

	function get_alumnos_a_verificar()
	{
		return array(
			array('valor' => vencimiento_regularidad::alumnos_regulares_sin_readmision, 'descr' => vencimiento_regularidad::alumnos_regulares_sin_readmision_descr),
			array('valor' => vencimiento_regularidad::alumnos_regulares_con_readmision_vencida, 'descr' => vencimiento_regularidad::alumnos_regulares_con_readmision_vencida_descr),
			array('valor' => vencimiento_regularidad::alumnos_no_regulares, 'descr' => vencimiento_regularidad::alumnos_no_regulares_descr)
		);
	}

	function get_alumnos_a_verificar_nombres($alumnos)
	{
		$alumnos_a_verificar_descr = array();
		if (in_array(vencimiento_regularidad::alumnos_regulares_sin_readmision, $alumnos)) {
			$alumnos_a_verificar_descr[] = vencimiento_regularidad::alumnos_regulares_sin_readmision_descr;
		}
		if (in_array(vencimiento_regularidad::alumnos_regulares_con_readmision_vencida, $alumnos)) {
			$alumnos_a_verificar_descr[] = vencimiento_regularidad::alumnos_regulares_con_readmision_vencida_descr;
		}
		if (in_array(vencimiento_regularidad::alumnos_no_regulares, $alumnos)) {
			$alumnos_a_verificar_descr[] = vencimiento_regularidad::alumnos_no_regulares_descr;
		}
		return $alumnos_a_verificar_descr;
	}
}
?>