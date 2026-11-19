<?php
/**
 *
 *
 * @package Responsables_Academicas
 * @subpackage Documentos
 */
class co_documentos
{
    
	/**
        Obtiene una lista de documentos según el filtro recibido
    */	
	function get_listado($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}

	   	$sql = "SELECT	sga_documentos.documento,
	   					sga_documentos.documento_numero,
	   					sga_documentos_tipos.nombre || ' ' || sga_documentos.documento_numero AS documento_descripcion,
	   					sga_documentos.documento_tipo,
	   					sga_documentos.fecha,
	   					to_char(sga_documentos.fecha,'".comunes::formato_fecha_visual."') AS fecha_formato_visual,
	   					sga_documentos.observaciones,
						".guarani_sql::get_case('sga_documentos','estado','estado',toba::consulta_php('co_comunes')->get_estado_activo_inactivo()).",
	   					sga_documentos_tipos.nombre AS tipo_nombre,
	   					sga_documentos.descripcion
				FROM 	sga_documentos
				JOIN    sga_documentos_tipos ON sga_documentos.documento_tipo = sga_documentos_tipos.documento_tipo
				$where
	   			ORDER BY sga_documentos.documento_numero";
 
	   	return guarani_db::consultar($sql);
	}
	
	function get_documentos()
	{
    	$sql = 'SELECT	documento as valor,
    					documento_numero as descr
    			FROM 	sga_documentos';
    			
    	return guarani_db::consultar($sql);
    	
	}

	/**
 	  * Retorna los datos de un documento en formato RecordSet, en caso de que el parámetro sea un array retorna 
 	  * los datos de los documentos según la condición del array (para la carga masiva).
      *	@param integer/array	documento	id o arreglo de ids del o de los documentos para los cuales se consultan los datos.
    */
	function get_datos_documentos($documento)
	{
		if (is_array($documento)) {
			$filtro = guarani_sql::armar_condicion_in($documento, 'sga_documentos', 'documento');
			$datos = $this->get_listado($filtro);
			return $datos;
		} 
		$documento = toba::db()->quote($documento);
		$datos = $this->get_listado("sga_documentos.documento = $documento");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	/**
        Obtiene una lista de los Tipos de Documentos Respaldatorios segun el filtro recibido.
    */
	function get_listado_tipos_documentos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_documentos_tipos.documento_tipo,
						sga_documentos_tipos.nombre,
						sga_documentos_tipos.descripcion
				FROM 	sga_documentos_tipos
				$where
				ORDER BY sga_documentos_tipos.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de documentos, agrupadas según su tipo y ordenadas ascendentemente por fecha, según el filtro recibido
    */
	function get_listado_reporte($where='')
	{
		if ($where) {
	   	$where = ' WHERE '.$where.' ';
		}
    
	   	$sql = "SELECT	sga_documentos.documento,
	   					sga_documentos.documento_numero,
	   					sga_documentos.documento_tipo,
	   					sga_documentos.fecha,
	   					sga_documentos.observaciones,
	   					CASE
	   							WHEN sga_documentos.estado = 'A' THEN 'Activo'
	   							WHEN sga_documentos.estado = 'I' THEN 'No Activo'
	   							ELSE 'No Activo'
	   					END as estado,
	   					sga_documentos_tipos.nombre || ' ' || '-' || ' ' ||sga_documentos_tipos.descripcion as descripcion			
				FROM sga_documentos
				JOIN sga_documentos_tipos ON sga_documentos.documento_tipo = sga_documentos_tipos.documento_tipo
				$where 
	   			ORDER BY sga_documentos.fecha
	   		";

		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los todos los tipos de documentos existentes en la base de datos
    */
	function get_documentos_tipos()
	{
	   	$sql = 'SELECT	sga_documentos_tipos.documento_tipo as valor,
	   					sga_documentos_tipos.nombre as descr  
				FROM 	sga_documentos_tipos
				ORDER BY descr
		';

	   	return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un nombre de Tipo de Documento Respaldatorio se puede usar o no.
     * @param string   $nombre	Nombre del Tipo de Documento a validar.
     * @param integer	$tipo_doc	Id de un Tipo de Documento para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_tipo_documento($nombre, $tipo_doc=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	COUNT(sga_documentos_tipos.documento_tipo) as cant 	
                FROM 	sga_documentos_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_documentos_tipos.nombre', $nombre).'
				';
		if (!is_null($tipo_doc)) {
			$tipo_doc = toba::db()->quote($tipo_doc);
			$sql .= " AND sga_documentos_tipos.documento_tipo <> $tipo_doc ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 *  Obtiene la clave (PK) del documento a partir del numero y el tipo del mismo.
     * @param integer   $nro 	Numero del Documento
     * @param integer 	$tipo	Tipo del Documento 
     * @return integer 	Clave del documento (sga_documentos.documento)
    */
	function get_documento_clave($nro, $tipo)
	{
		
		$nro = toba::db()->quote($nro);
		$tipo = toba::db()->quote($tipo);
	   	$sql = "SELECT	sga_documentos.documento
				FROM 	sga_documentos
				WHERE 	sga_documentos.documento_numero = $nro AND 
						sga_documentos.documento_tipo = $tipo ; 
		";

	   	return guarani_db::consultar($sql);
	}
	
	/**
	 *  Obtiene los datos del documento a partir de la clave (PK) del mismo.
     * @param integer   $key 	Clave del Documento
     * @return array 	Datos del documento
    */
	function get_documento_datos($key)
	{		
		$key = toba::db()->quote($key);
	   	$sql = "SELECT	sga_documentos.documento_numero,
	   					sga_documentos.documento_tipo,
	   					extract(year from sga_documentos.fecha) as documento_anio
				FROM 	sga_documentos
				WHERE 	sga_documentos.documento = $key
		";

	   	return guarani_db::consultar($sql);
	}

	static function get_documento($id)
	{
	    $id = toba::db()->quote($id);
	    $sql = "SELECT	sga_documentos.documento as valor,
						sga_documentos_tipos.nombre || ' ' || sga_documentos.documento_numero as descr
				FROM 	sga_documentos
				JOIN	sga_documentos_tipos ON sga_documentos.documento_tipo = sga_documentos_tipos.documento_tipo
				WHERE	sga_documentos.documento = $id
		";
		$rs = guarani_db::consultar_fila($sql);
		return $rs['descr'];
	}

    /**
        Valida si una combinacion de tipo + numero documento + fecha se puede usar o no.
    */
	function validar_combinacion_nrodoc_tipo_numero_fecha($nro, $tipo, $nro_doc=null ,$fecha)
	{

		$nro = toba::db()->quote($nro);
		$tipo = toba::db()->quote($tipo);
	   	$sql = "SELECT	count(sga_documentos.documento_numero) as cant
				FROM 	sga_documentos
				WHERE 	".guarani_sql::armar_condicion_compara_cadenas('sga_documentos.documento_numero', $nro)." AND 
						sga_documentos.documento_tipo = $tipo  
		";

		if (!is_null($nro_doc)) {
			$nro_doc = toba::db()->quote($nro_doc);
			$sql .= " AND sga_documentos.documento <> $nro_doc ";
		}
		if (!is_null($fecha)) {
			$fecha = toba::db()->quote($fecha);
                        $sql .= " AND sga_documentos.fecha = TO_DATE($fecha , 'YYYY-MM-DD')";
		}
		$res = guarani_db::consultar_fila($sql);	
		return ($res['cant'] == 0);
		
	}
	
    /**
        Valida si una combinacion de pais + tipo + numero documento se puede usar o no.
    */
	function validar_combinacion_pais_tipo_numero($pais, $tipo_documento, $nro_documento, $nro_expulsion=null)
	{
		if (!(isset($pais) && isset($tipo_documento) && isset($nro_documento))) {
			return false;
		}
		
		$pais = toba::db()->quote($pais);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$nro_documento = toba::db()->quote($nro_documento);		
		
		$sql = "SELECT	count(sga_alumnos_expulsados.nro_expulsion) as cant
				FROM 	sga_alumnos_expulsados
				WHERE	sga_alumnos_expulsados.nro_documento = $nro_documento
				AND		sga_alumnos_expulsados.tipo_documento = $tipo_documento
				AND		sga_alumnos_expulsados.pais = $pais
				";
	
		if (!is_null($nro_expulsion)) {
			$nro_expulsion = toba::db()->quote($nro_expulsion);
			$sql .= " AND sga_alumnos_expulsados.nro_expulsion <> $nro_expulsion ";
		}
		
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);
	}
	
	/**
        Valida si una fecha inicial es valida o no.
    */
	function validar_fecha_desde($fecha_desde)
	{
		if (!isset($fecha_desde)) {
			return false;
		}
		return guarani_fecha::es_mayor($fecha_desde, guarani_fecha::get_hoy()) ? false : true;
	}

	/**
        Valida si una fecha final es valida o no.
    */
	function validar_fecha_hasta($fecha_hasta=null)
	{
		if (!isset($fecha_hasta)) {
			return false;
		}
		return guarani_fecha::es_mayor($fecha_hasta, guarani_fecha::get_hoy()) ? true : false;
	}

	/**
        Valida si un documento tiene datos asociados.
    */
	function validar_docs_rel($nro_doc)
	{

		$nro_doc = toba::db()->quote($nro_doc);		
		
		$sql = "SELECT	count(sga_documentos.documento) as cant
				FROM 	sga_documentos
				LEFT JOIN sga_convenios ON sga_documentos.documento = sga_convenios.documento
				LEFT JOIN sga_planes_versiones ON ((sga_documentos.documento = sga_planes_versiones.documento_alta) OR (sga_documentos.documento = sga_planes_versiones.documento_baja))
				LEFT JOIN sga_propuestas ON ((sga_documentos.documento = sga_propuestas.documento_alta) OR (sga_documentos.documento =  sga_propuestas.documento_baja))
				LEFT JOIN sga_requisitos_aspirante ON ((sga_documentos.documento = sga_requisitos_aspirante.documento_excepcion) OR (sga_documentos.documento = sga_requisitos_aspirante.documento_prorroga))
				WHERE	sga_documentos.documento = $nro_doc;";
	
		$res = guarani_db::consultar_fila($sql);		
		return ($res['cant'] == 0);

	}	

	function get_documento_digital($documento)
	{
		$sql = "SELECT	sga_documentos_digital.archivo,
						sga_documentos_digital.nombre_archivo
				FROM	sga_documentos_digital
				WHERE	sga_documentos_digital.documento = $documento";
		return guarani_db::consultar_fila($sql);
	}
}
?>