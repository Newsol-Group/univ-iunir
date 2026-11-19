<?php
/**
 *	
 *
 * @package Unidades de Gestión
 * @subpackage Calendario Académico
 */
class co_periodos_lectivos
{
	function get_listado($where=' ')
	{
		if (trim($where) != '' ) {
			$where = ' AND '.$where.' ';
		}

		$sql = "SELECT 		sga_periodos_lectivos.periodo_lectivo,
							sga_periodos_lectivos.periodo,
							sga_periodos_lectivos.fecha_inicio_dictado,
							to_char(sga_periodos_lectivos.fecha_inicio_dictado,'".comunes::formato_fecha_visual."') as fecha_inicio_dictado_visual,
       						sga_periodos_lectivos.fecha_fin_dictado,
       						to_char(sga_periodos_lectivos.fecha_fin_dictado,'".comunes::formato_fecha_visual."') as fecha_fin_dictado_visual,
       						sga_periodos_lectivos.fecha_tope_movimientos, 
       						sga_periodos_lectivos.fecha_inactivacion, 
       						sga_periodos_lectivos.entidad,
       						sga_periodos.periodo_generico,
       						sga_periodos.nombre 					as periodo_nombre,
       						sga_periodos.anio_academico 			as periodo_anio_academico,
       						sga_periodos.fecha_inicio 				as periodo_fecha_inicio,
       						to_char(sga_periodos.fecha_inicio,'".comunes::formato_fecha_visual."') as periodo_fecha_inicio_visual,
       						sga_periodos.fecha_fin 					as periodo_fecha_fin,
       						to_char(sga_periodos.fecha_fin,'".comunes::formato_fecha_visual."') as periodo_fecha_fin_visual,
       						sga_periodos_genericos.nombre			as periodo_generico_nombre,
							sga_periodos_genericos.periodo_generico_tipo as periodo_generico_tipo,
							sga_periodos_genericos.periodo_lectivo_tipo as periodo_lectivo_tipo,
							(SELECT	count(sga_comisiones.comision)
						 	 FROM	sga_comisiones
						 	 WHERE	sga_comisiones.periodo_lectivo = sga_periodos_lectivos.periodo_lectivo
							) as cant_comisiones
       						
  				FROM 		sga_periodos_lectivos,
  							sga_periodos,
  							sga_periodos_genericos
  							
  				WHERE		sga_periodos_lectivos.periodo = sga_periodos.periodo
  				AND			sga_periodos.periodo_generico	= sga_periodos_genericos.periodo_generico
							$where

				ORDER BY 	sga_periodos.anio_academico,
							sga_periodos.nombre
				;"; 
  				 
		return guarani_db::consultar($sql);
	}
	
	//Obtiene los datos correspondientes al período lectivo dado como parámetro.
	function get_datos_periodo_lectivo($p_lectivo)
	{
		if (!is_null($p_lectivo)) {
			$p_lectivo = toba::db()->quote($p_lectivo);
			$datos = $this->get_listado(" sga_periodos_lectivos.periodo_lectivo = $p_lectivo ");
			if (!empty($datos)) {
				return current($datos);
			}
		}
		
	}
	
	//Obtiene el nombre correspondiente al período lectivo dado como parámetro.
	function get_nombre_periodo_lectivo($p_lectivo)
	{
		if (!is_null($p_lectivo)) {
			$p_lectivo = toba::db()->quote($p_lectivo);
			
			$sql = "SELECT	sga_periodos.nombre	
					FROM	sga_periodos_lectivos,
							sga_periodos
					WHERE	sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							sga_periodos_lectivos.periodo_lectivo = $p_lectivo
					;";
			
			$datos = guarani_db::consultar_fila($sql);
			
			if (!empty($datos)) {
				return current($datos);
			}
		}
		
	}
	
	
	//Obtiene el nombre y año académico correspondiente al período lectivo dado como parámetro.
	function get_nombre_y_anio_periodo_lectivo($p_lectivo)
	{
		if (!is_null($p_lectivo)) {
			$p_lectivo = toba::db()->quote($p_lectivo);
			
			$sql = "SELECT	sga_periodos.nombre			AS periodo_nombre,
							sga_periodos.anio_academico	AS periodo_anio_academico
					FROM	sga_periodos_lectivos,
							sga_periodos
					WHERE	sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							sga_periodos_lectivos.periodo_lectivo = $p_lectivo
					;";
			
			return guarani_db::consultar_fila($sql);
		}
		
	}
	
	/**
        Obtiene una lista de tipos de periodos lectivos según el filtro recibido
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}

		$sql = "SELECT 	sga_periodos_lectivos_tipos.periodo_lectivo_tipo,
						sga_periodos_lectivos_tipos.nombre,
						sga_periodos_lectivos_tipos.duracion_en_meses,
						sga_periodos_lectivos_tipos.duracion_en_semanas,
						sga_periodos_lectivos_tipos.orden
  				FROM 	sga_periodos_lectivos_tipos
				$where
				ORDER BY 2
				;";	
		return guarani_db::consultar($sql);
	}	
		
	/**
	 * Retorna todos los Periodos Lectivos existentes asociados a un período genérico cuyo año académico es el que se pasa
	 * como parámetro.
	 *
	 * @param integer $anio id del año academico para el cual se quieren buscar los períodos lectivos asociados.
	 */
	function get_periodos_lectivos_anio_academico($anio)
	{
		$anio = toba::db()->quote($anio);
		$where = "sga_periodos.anio_academico = $anio";
		return $this->get_periodos_lectivos($where);
	}
	
	function get_periodos_lectivos_anio_academico_tipo($periodo_lectivo_tipo, $anio)
	{
		$anio = toba::db()->quote($anio);
		$periodo_lectivo_tipo = toba::db()->quote($periodo_lectivo_tipo);
		$where = "	sga_periodos.anio_academico = $anio AND
					sga_periodos_genericos.periodo_lectivo_tipo = $periodo_lectivo_tipo";
		return $this->get_periodos_lectivos($where);
	}
	
	/**
	 * Retorna todos los Periodos Genéricos existentes de tipo Período Lectivo para un año académico dado.
	 */
	function get_periodos_lectivos_gen_anio_academico($anio)
	{
		$anio = toba::db()->quote($anio);
		$sql = "SELECT 		sga_periodos_genericos.periodo_generico as valor,
							sga_periodos_genericos.nombre 			as descr
							
  				FROM 		sga_periodos_genericos,
  							sga_periodos
  							
  				WHERE		sga_periodos_genericos.periodo_generico 		= sga_periodos.periodo_generico
  				AND			sga_periodos.anio_academico						= $anio
				AND			sga_periodos_genericos.periodo_generico_tipo	= '".periodo_generico::tipo_periodo_lectivo."'
		
				ORDER BY 	sga_periodos_genericos.nombre
				;";	
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de Períodos Genéricos según el filtro recibido
    */
	function get_periodos_genericos($where='')
	{
		if ($where) {
			$where = 'WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	periodo_generico, 
						nombre,
						periodo_generico_tipo,
						periodo_lectivo_tipo, 
						orden_en_su_tipo 
  				FROM 	sga_periodos_genericos
  				$where
  				ORDER BY 2,3
  				;"; 
  				 
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene todos los Períodos Genéricos de tipo Período Lectivo existentes según el filtro recibido
	 */
	function get_periodos_genericos_activos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT 		sga_periodos_genericos.periodo_generico,
							sga_periodos_genericos.nombre AS nombre_periodo_generico,
							sga_periodos_genericos.periodo_generico_tipo,
							sga_periodos_genericos.periodo_lectivo_tipo,
							sga_periodos_genericos.orden_en_su_tipo
  				FROM 		sga_periodos_genericos
  				WHERE		sga_periodos_genericos.periodo_generico_tipo = '".periodo_generico::tipo_periodo_lectivo."'
  							$where
  				ORDER BY	sga_periodos_genericos.nombre,
							sga_periodos_genericos.periodo_generico_tipo
  		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene los datos correspondientes al Período Genérico
	 */
	function get_datos_periodos_genericos_activos($p_generico)
	{
		if (!is_null($p_generico)) {
			$p_generico = toba::db()->quote($p_generico);
			$datos = $this->get_periodos_genericos_activos(" sga_periodos_genericos.periodo_generico = $p_generico ");
			if (!empty($datos)) {
				return current($datos);
			}
		}
		
	}
	
	/**
	 * Obtiene todos los periodos lectivos existentes con su id y descripción (para la carga de un combo.
	 *
	 */
	function get_periodos_lectivos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$sql = "SELECT		sga_periodos_lectivos.periodo_lectivo as valor,
							sga_periodos.nombre as descr
				FROM		sga_periodos_lectivos,
							sga_periodos,
							sga_periodos_genericos
				WHERE		sga_periodos_lectivos.periodo = sga_periodos.periodo AND
							sga_periodos.periodo_generico = sga_periodos_genericos.periodo_generico
							$where
				ORDER BY	sga_periodos.nombre
		";
		return guarani_db::consultar($sql);
	}
	
	/*
	*	Retorna el listado de tipos de períodos lectivos
	*/
	function get_periodos_lectivos_tipos()
	{
		$sql = "	SELECT 	sga_periodos_lectivos_tipos.periodo_lectivo_tipo as valor,
							sga_periodos_lectivos_tipos.nombre as descr,
							sga_periodos_lectivos_tipos.duracion_en_meses,
							sga_periodos_lectivos_tipos.duracion_en_semanas,
							sga_periodos_lectivos_tipos.orden
					FROM 	sga_periodos_lectivos_tipos
					ORDER BY 2
							;"; 
		return guarani_db::consultar($sql);
	}

	/**
        Valida si un tipo de periodo lectivo se puede usar o no.
    */
	function validar_periodo_lectivo_tipo($periodo_lectivo_tipo, $entidad_es_nueva)
	{
		if ($entidad_es_nueva) {
			if (!isset($periodo_lectivo_tipo)) {
				return false;
			}
			
			$periodo_lectivo_tipo = toba::db()->quote($periodo_lectivo_tipo);
			
			$sql = 'SELECT 	count(sga_periodos_lectivos_tipos.periodo_lectivo_tipo) as cant
					FROM 	sga_periodos_lectivos_tipos
					WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_periodos_lectivos_tipos.periodo_lectivo_tipo', $periodo_lectivo_tipo).'
					';
			
			$res = guarani_db::consultar_fila($sql);
			return ($res['cant'] == 0);
		} else {
			return true;
		}
	}
	
	/**
        Valida si un nombre a trasladar de periodo lectivo se puede usar o no.
    */
	function validar_periodo_lectivo_nombre($nombre, $periodo_lectivo_tipo, $entidad_es_nueva)
	{
		if (!isset($nombre)) {
			return false;
		}
		
		$nombre = toba::db()->quote($nombre);
				
		$sql = 'SELECT 	count(sga_periodos_lectivos_tipos.periodo_lectivo_tipo) as cant
				FROM 	sga_periodos_lectivos_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_periodos_lectivos_tipos.nombre', $nombre).'
				';
		if (!$entidad_es_nueva) {
			$periodo_lectivo_tipo = toba::db()->quote($periodo_lectivo_tipo);
			$sql .= " AND sga_periodos_lectivos_tipos.periodo_lectivo_tipo <> $periodo_lectivo_tipo ";
		}
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	function get_descripcion_entidad($entidad, $entidad_tipo=null) 
    {
		if (! $entidad_tipo) {
			$entidad_tipo = g3entidad::get_tipo_entidad($entidad);
		}
		$tabla_tipo = g3entidad::get_tabla_tipo($entidad_tipo);
		$datos_entidad = guarani::co('co_g3entidades')->get_listado_tipo_detallado($entidad_tipo, "$tabla_tipo.entidad = $entidad");
		return $datos_entidad[0];
	}

	/**
        Valida si un nombre de período lectivo se puede usar o no.
    */
	function validar_nombre_periodo_lectivo($nombre, $anio_academico, $periodo_lectivo=null)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$anio_academico = toba::db()->quote($anio_academico);

		$sql = "SELECT	count(sga_periodos.periodo) as cant
                FROM 	sga_periodos,
						sga_periodos_lectivos
				WHERE 	sga_periodos.periodo = sga_periodos_lectivos.periodo AND
						sga_periodos.anio_academico = $anio_academico AND ".
						guarani_sql::armar_condicion_compara_cadenas('sga_periodos.nombre', $nombre);

		if (!is_null($periodo_lectivo)) {
			$periodo_lectivo = toba::db()->quote($periodo_lectivo);
			$sql .= " AND sga_periodos_lectivos.periodo_lectivo <> $periodo_lectivo ";
		}
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}

	function tiene_excepcion_en_comision($periodo_inscripcion)
	{
		$periodo_inscripcion = toba::db()->quote($periodo_inscripcion);
		
		$sql = "SELECT 	sga_comisiones_excep_perinsc.periodo_insc_com
				FROM 	sga_comisiones_excep_perinsc
  				WHERE	sga_comisiones_excep_perinsc.periodo_insc = $periodo_inscripcion
				";	
		
		$excepciones = guarani_db::consultar($sql);
		
		if (empty($excepciones)) {
			return false;
		}
		return true;
	}
	
	function tramo_en_uso($id_tramo)
	{
		$id_tramo = toba::db()->quote($id_tramo);
		
		$sql = "SELECT 	1 as en_uso
				FROM 	sga_clases
				WHERE 	id_tramo = $id_tramo";
		
		$res = guarani_db::consultar_fila($sql);
		if (!empty($res['en_uso'])) {
			return $res['en_uso'];
		} else {
			return false;
		}
		
	}
	/*
	*	Retorna los datos de los tramos asociados a un periodo lectivo
	*/
	function get_tramos_por_periodos_lectivos($periodo)
	{
		$sql = "SELECT 	sga_periodos_lectivos_tramos.id_tramo,
                                sga_periodos_lectivos_tramos.periodo_lectivo,
                                sga_periodos_lectivos_tramos.nro_tramo,
                                sga_periodos_lectivos_tramos.fecha_inicio,
                                sga_periodos_lectivos_tramos.fecha_fin,
                                to_char(sga_periodos_lectivos_tramos.fecha_inicio, '".comunes::formato_fecha_visual."') || ' al ' || to_char(sga_periodos_lectivos_tramos.fecha_fin, '".comunes::formato_fecha_visual."') as desc_tramo
                                FROM 	sga_periodos_lectivos_tramos
                                WHERE sga_periodos_lectivos_tramos.periodo_lectivo = '$periodo' 
                                ORDER BY 1;"; 
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna el listado de períodos lectivos vigentes a hoy.
	 * 
	 * @return array
	 */
	function get_periodos_lectivos_vigentes()
	{
		$where = "CURRENT_DATE BETWEEN sga_periodos.fecha_inicio AND sga_periodos.fecha_fin";
		return $this->get_periodos_lectivos($where);
	}

	/**
	 * Retorna el listado de años académicos que tienen períodos lectivos vigentes a hoy.
	 * 
	 * @return array
	 */
	function get_anios_academicos_con_periodos_lectivos_vigentes()
	{
		$sql = "SELECT	sga_periodos.anio_academico as valor,
						sga_periodos.anio_academico as descr
				FROM	sga_periodos_lectivos
						JOIN sga_periodos ON sga_periodos_lectivos.periodo = sga_periodos.periodo
						JOIN sga_periodos_inscripcion ON sga_periodos_inscripcion.periodo = sga_periodos.periodo
						JOIN sga_periodos_inscripcion_fechas ON sga_periodos_inscripcion.periodo_inscripcion = sga_periodos_inscripcion_fechas.periodo_inscripcion
				WHERE	CURRENT_TIMESTAMP BETWEEN sga_periodos_inscripcion_fechas.fecha_inicio AND sga_periodos_inscripcion_fechas.fecha_fin
		";
		return guarani_db::consultar($sql);
	}

	/**
	 * Retorna la cantidad de períodos lectivos no finalizados para el año académico dado.
	 * 
	 * @return integer
	 */
	function get_cant_periodos_lectivos_no_finalizados_por_anio_academico($anio_academico)
	{
		$anio_academico = toba::db()->quote($anio_academico);
		$sql = "SELECT	COUNT(1) as cant
				FROM	vw_periodos_lectivos
				WHERE	vw_periodos_lectivos.anio_academico = $anio_academico AND
						vw_periodos_lectivos.fecha_fin >= CURRENT_DATE
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['cant'];
	}

	function get_opciones_tipo_alumno()
	{
		return array(array('valor' => periodo_de_inscripcion::tipo_alumno_todos, 'descr' => 'Todos'),
                     array('valor' => periodo_de_inscripcion::tipo_alumno_ingresantes, 'descr' => 'Ingresantes'),
                     array('valor' => periodo_de_inscripcion::tipo_alumno_reinscriptos, 'descr' => 'Reinscriptos'));
	}

	function get_opciones_tipo_cursado()
	{
		return array(array('valor' => periodo_de_inscripcion::tipo_cursado_todos, 'descr' => 'Todos'),
                     array('valor' => periodo_de_inscripcion::tipo_cursado_primera_vez, 'descr' => 'Por Primera Vez'),
                     array('valor' => periodo_de_inscripcion::tipo_cursado_recursantes, 'descr' => 'Recursantes'));
	}
}
?>