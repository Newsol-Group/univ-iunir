<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Alumnos
 */
class cn_pro_vencimiento_regularidad extends cn_procesos_bk
{
	protected $s__filtro;

	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_vencimiento_regularidad::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function set_filtro($filtro)
	{
		$this->s__filtro = $filtro;
	}
	
	function etiquetas($dep=null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_comisiones');
		} else {
			parent::etiquetas($dep);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas
	//-----------------------------------------------------------------------------------
	
	function get_listado($filtro)
	{
		// Sólo deben listarse las comisiones que tienen Acta de Regularidad Cerrada, sin excepción.
		$where = "sga_comisiones.comision IN
					(	
						SELECT 	sga_actas.comision
						FROM 	sga_actas
						WHERE	sga_actas.origen = '".acta::origen_regulares."'
								AND sga_actas.estado = '".acta::estado_cerrada."'
					)";
		
		if (!empty($filtro)) {
			$where .= ' AND '.$filtro;
		}
		
		return toba::consulta_php('co_comisiones')->get_listado($where);
	}
	
	function get_regularidades()
	{
		$filtro = $this->s__filtro;

		// Regularidades de la propuesta de cada alumno.
		$filtro .= " AND vw_regularidades.origen_otra_propuesta = false";

		// Regularidades por actas de origen REGULAR en estado CERRADA.
		$filtro .= " AND vw_regularidades.origen = '".acta::origen_regulares."'";

		// Resultado APROBADO.
		$filtro .= " AND vw_regularidades.resultado = '".escalas_notas::resultado_aprobado."'";

		// Actividad NO APROBADA.
		$filtro .= " AND vw_regularidades.elemento NOT IN (	SELECT	vw_hist_academica_basica.elemento
															FROM	vw_hist_academica_basica
															WHERE	vw_hist_academica_basica.alumno = vw_regularidades.alumno AND
																	vw_hist_academica_basica.resultado = '".comunes::resultado_nota_aprobado."' AND
																	vw_hist_academica_basica.origen_otra_propuesta = false)";
		
		/* 
		   Se saca esta condicion, porque el alumno puede haber cursado y a aprobado una actividad. Luego la recursa pero desaprueba, 
		   con lo cual sigue vigente la anterior, y si se vence y es necesario extender la vigencia; entonces debería poder ver 
		   esta regularidad para modificar y extender su vigencia. GDS 48323
		  
		// Última regularidad del alumno por actividad.
		$filtro .= " AND vw_regularidades.fecha = (	SELECT 	MAX(r.fecha) 
													FROM 	vw_regularidades_basica as r 
													WHERE 	r.alumno = vw_regularidades.alumno AND
															r.origen_otra_propuesta = false AND
															r.elemento = vw_regularidades.elemento)";
		*/
		
		$datos = toba::consulta_php('co_alumnos')->get_regularidades($filtro);
		return rs_ordenar_por_columnas($datos, array('actividad', 'comision'));
	}
}
?>