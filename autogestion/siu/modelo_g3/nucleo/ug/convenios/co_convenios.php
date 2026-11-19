<?php
/**
 *
 *
 * @package Unidades de Gestion
 * @subpackage Convenios
 */
class co_convenios
{
	/**
        Obtiene una lista de los convenios según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	sga_convenios.convenio,
						sga_convenios.nombre,
						sga_convenios_tipos.nombre as tipo_convenio,
						sga_convenios.anio_academico_vigencia,
						sga_convenios.estado,
						f_instituciones_convenio(sga_convenios.convenio) as instituciones
				FROM 	sga_convenios,
						sga_convenios_tipos
				WHERE	sga_convenios.convenio_tipo = sga_convenios_tipos.convenio_tipo" . $where;
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los convenios según el filtro recibido
    */
	function get_convenios($where='')
	{
		$sql = "SELECT 	sga_convenios.convenio as valor,
						sga_convenios.nombre as descr
				FROM 	sga_convenios
				$where";
		
		return guarani_db::consultar($sql);
	}
	
	function get_datos_convenio($convenio)
	{
		$convenio = toba::db()->quote($convenio);
		$sql = "SELECT 	sga_convenios.convenio,
						sga_convenios.nombre,
						sga_convenios.documento,
						sga_convenios.vigencia_desde,
						to_char(sga_convenios.vigencia_desde, '".comunes::formato_fecha_visual."') as vigencia_desde_visual,
						sga_convenios.vigencia_hasta,
						to_char(sga_convenios.vigencia_hasta, '".comunes::formato_fecha_visual."') as vigencia_hasta_visual,
						sga_convenios_tipos.nombre as tipo_convenio,
						sga_convenios.anio_academico_vigencia,
						sga_convenios.estado,
						sga_convenios.propuesta_base,
						'('  || sga_propuestas.codigo || ') ' || sga_propuestas.nombre AS prop_descr,
						sga_documentos_tipos.nombre || ' ' || sga_documentos.documento_numero as doc_desc,
						f_instituciones_convenio(sga_convenios.convenio) as instituciones
				FROM 	sga_convenios
				JOIN sga_convenios_tipos ON sga_convenios.convenio_tipo = sga_convenios_tipos.convenio_tipo
				LEFT JOIN sga_documentos ON sga_convenios.documento = sga_documentos.documento
				LEFT JOIN sga_documentos_tipos ON sga_documentos.documento_tipo = sga_documentos_tipos.documento_tipo
				LEFT JOIN sga_propuestas ON sga_propuestas.propuesta = sga_convenios.propuesta_base
				WHERE sga_convenios.convenio = $convenio
		";
		
		return guarani_db::consultar_fila($sql);
	}
	
	/**
        Obtiene una lista de los tipos de convenios según el filtro recibido
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	sga_convenios_tipos.convenio_tipo,
						sga_convenios_tipos.nombre
				FROM 	sga_convenios_tipos
				$where
				ORDER BY sga_convenios_tipos.nombre";
		
		return guarani_db::consultar($sql);	
	}

	/**
	 * Retorna la lista de estados para un convenio.
	 * 
	 * @return array
	 */
	function get_listado_estados()
	{
		return array(	array('valor' => convenio::estado_nuevo, 'descr' => convenio::estado_nuevo),
						array('valor' => convenio::estado_vigente, 'descr' => convenio::estado_vigente),
						array('valor' => convenio::estado_baja, 'descr' => convenio::estado_baja)
		);
	}

	/**
	 * Retorna la lista de tipos de instituciones para un convenio.
	 * 
	 * @return array
	 */
	function get_listado_instituciones_tipos()
	{
		return array(	array('valor' => convenio::realizado_con_nacionales, 'descr' => convenio::realizado_con_nacionales_desc),
						array('valor' => convenio::realizado_con_internacionales, 'descr' => convenio::realizado_con_internacionales_desc));
	}

	/**
        Obtiene una lista de los convenios asociados a una propuesta + los que no estan asociada a ninguna
    */
	function get_convenios_propuestas_all($propuesta)
	{
		
		$prop = quote($propuesta);
		$sql = "SELECT sga_convenios.convenio,
				       sga_convenios.nombre
				FROM   sga_convenios,
				       sga_convenios_propuestas
				WHERE  sga_convenios_propuestas.convenio = sga_convenios.convenio
				       AND sga_convenios_propuestas.propuesta = $prop
				UNION
				SELECT sga_convenios.convenio,
				       sga_convenios.nombre
				FROM   sga_convenios
				WHERE  sga_convenios.convenio NOT IN (SELECT DISTINCT convenio
				                                      FROM   sga_convenios_propuestas
				                                      WHERE  sga_convenios_propuestas.convenio = sga_convenios.convenio) 
				";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de las actividades asociadas a un convenio
    */
	function get_actividades_convenio($convenio)
	{
		$convenio = quote($convenio);
		$sql = "SELECT sga_convenios_actividades.elemento
				FROM   sga_convenios_actividades
				WHERE  sga_convenios_actividades.convenio = $convenio
				";
		
		return guarani_db::consultar($sql);	
	}
	
	/**
        Obtiene una lista de los convenios asociados con una institucion
    */
	function get_convenios_institucion($institucion)
	{
		$institucion = toba::db()->quote($institucion);
		$formato_anio_4_digitos_visual = toba::db()->quote(comunes::formato_anio_4_digitos_visual);
		
		$sql = "SELECT 		sga_convenios.convenio,
							sga_convenios.nombre,
							sga_convenios_tipos.nombre 		AS tipo_convenio,
							sga_documentos.documento_numero AS nro_doc,
							sga_documentos_tipos.nombre 	AS tipo_doc,
							to_char(sga_documentos.fecha, $formato_anio_4_digitos_visual) AS anio_doc
				FROM sga_convenios
				JOIN sga_convenios_tipos ON sga_convenios.convenio_tipo = sga_convenios_tipos.convenio_tipo
				JOIN sga_convenios_instituciones ON sga_convenios.convenio = sga_convenios_instituciones.convenio
				LEFT JOIN sga_documentos ON sga_convenios.documento = sga_documentos.documento
				LEFT JOIN sga_documentos_tipos ON sga_documentos.documento_tipo = sga_documentos_tipos.documento_tipo
				
				WHERE	sga_convenios_instituciones.institucion = $institucion
				ORDER BY 	sga_convenios.nombre
				";
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene la lista de Instituciones correspondientes al Convenio dado.
    */
	function get_instituciones_convenio($convenio)
	{
		$id = toba::db()->quote($convenio);
		$sql = "SELECT 	f_instituciones_convenio(sga_convenios.convenio) as instituciones
				FROM 	sga_convenios
				WHERE	sga_convenios.convenio = $id
				";
		return guarani_db::consultar($sql);
	}
	
	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_convenio_tipo($nombre, $convenio=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(sga_convenios_tipos.convenio_tipo) as cant 	
                FROM 	sga_convenios_tipos
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('sga_convenios_tipos.nombre',$nombre);
		if (! is_null($convenio)) {
			$convenio = toba::db()->quote($convenio);
			$sql .= " AND sga_convenios_tipos.convenio_tipo <> $convenio ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Valida si un nombre de Convenio se puede usar o no.
     * @param string   $nombre	Nombre del Convenio a validar.
     * @param integer	$convenio	Id de una Convenio para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_convenio($nombre, $convenio=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	count(sga_convenios.convenio) as cant 	
                FROM 	sga_convenios
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_convenios.nombre', $nombre).'
				';
		if (!is_null($convenio)) {
			$convenio = toba::db()->quote($convenio);
			$sql .= " AND sga_convenios.convenio <> $convenio ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/*
     * Retorna la descripcion de un convenio, para el ef popup.
     */
	static function get_convenio_nombre($id=null)
	{
		if (! isset($id) || trim($id) == '') {
			return array();
		}    	
		
	    $id = toba::db()->quote($id);

				
		$sql = "SELECT 	sga_convenios.convenio as valor, 
						sga_convenios.nombre as descr
				FROM	sga_convenios
				WHERE	sga_convenios.convenio = $id 
				";				
			
		$result = guarani_db::consultar($sql);

		if (! empty($result)) {
			return $result[0]['descr'];
		}
	}	
}
?>