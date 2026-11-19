<?php
/**
 *
 *
 * @package Unidad de Gestion
 * @subpackage Instituciones
 */
class co_instituciones
{
	/**
        Obtiene una lista de instituciones según el filtro recibido
    */
	function get_listado($where='')
	{
		if ($where) {
	    	$where = ' WHERE '.$where;
		}
    
	   	$sql = "SELECT	sga_instituciones.institucion,
						sga_instituciones.nombre,
						sga_instituciones.nombre as entidad_descripcion,
						sga_instituciones.nombre_abreviado,
						sga_instituciones.tipo_institucion,
						sga_instituciones_tipos.nombre as tipo_institucion_nombre,
						sga_instituciones.localidad,
						mug_localidades.nombre as localidad_nombre,
						CASE
							WHEN mug_provincias.pais = ".persona::pais_argentina." THEN mug_localidades.nombre
							ELSE mug_localidades.nombre || ' (' || mug_paises.nombre || ')'
						END as localidad_descripcion,
						sga_instituciones.calle,
						sga_instituciones.numero,
						sga_instituciones.codigo_postal,
						sga_instituciones.telefono,
						sga_instituciones.fax,
						sga_instituciones.email,
						sga_instituciones.entidad
				FROM 	sga_instituciones
				JOIN	sga_instituciones_tipos ON sga_instituciones_tipos.tipo_institucion = sga_instituciones.tipo_institucion
				JOIN    mug_localidades ON mug_localidades.localidad = sga_instituciones.localidad
				JOIN	mug_dptos_partidos ON mug_dptos_partidos.dpto_partido = mug_localidades.dpto_partido
				JOIN	mug_provincias ON mug_provincias.provincia = mug_dptos_partidos.provincia
				JOIN	mug_paises ON mug_paises.pais = mug_provincias.pais 
				$where
				ORDER BY sga_instituciones.nombre  
				;";
			        
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los tipos de instituciones segun el filtro recibido.
    */
	function get_listado_tipos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_instituciones_tipos.tipo_institucion,
						sga_instituciones_tipos.nombre,
						sga_instituciones_tipos.descripcion
				FROM 	sga_instituciones_tipos
				$where
				ORDER BY sga_instituciones_tipos.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los nombres de instituciones segun el filtro recibido.
    */
	function get_listado_nombres($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_instituciones.nombre as descr,
						sga_instituciones.institucion as valor
				FROM 	sga_instituciones
				$where
				ORDER BY sga_instituciones.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los nombres de todas las instituciones externas 
    */
    function get_instituciones_externas($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}
		$sql = "SELECT	sga_instituciones.nombre as descr,
						sga_instituciones.institucion as valor
				FROM 	sga_instituciones
				WHERE 	sga_instituciones.institucion <> '" . guarani_configuracion::instancia()->get_valor_config('institucion_instalacion') . "' 
				$where 
				ORDER BY sga_instituciones.nombre
			   ;";
		
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los nombres de todas las instituciones donde pueden haber realizado sus estudios las personas.
    */
    function get_instituciones($filtro='')
	{
		$condicion = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = $filtro_condicion->get_sql('f_limpiar_acentos(sga_instituciones.nombre::varchar)', $filtro);
		}
		return $this->get_listado_nombres($condicion);	
	}

	/**
        Retorna datos de instituciones.
    */
	function get_datos_instituciones($instit)
	{
		$filtro = toba::db()->quote($instit);
		$sql = "SELECT	sga_instituciones_tipos.nombre as tipo_institucion,
						sga_instituciones.nombre as nombre_institucion,
						mug_localidades.nombre as localidad
  				FROM 	sga_instituciones
				JOIN	sga_instituciones_tipos ON sga_instituciones.tipo_institucion = sga_instituciones_tipos.tipo_institucion
				JOIN	mug_localidades ON sga_instituciones.localidad = mug_localidades.localidad
  				WHERE 	sga_instituciones.institucion = $filtro 
				;";				

		return guarani_db::consultar($sql);
	}
	
	// Retorna el origen de una institucion según el país asociado a la localidad: N o E (nacional o extranjera).
	function get_origen_institucion($institucion)
	{
		$institucion = toba::db()->quote($institucion);
		$sql = "SELECT	vw_localidades.pais
  				FROM 	sga_instituciones
				JOIN	vw_localidades ON vw_localidades.localidad = sga_instituciones.localidad
  				WHERE 	sga_instituciones.institucion = $institucion
				;";				

		$datos = guarani_db::consultar($sql);
		if (!empty($datos)) {
			$pais = $datos[0]['pais'];
			if ($pais == persona::pais_argentina) {
				return 'N';
			} else {
				return 'E';
			}
		}
	}
	
	// Retorna el origen de un colegio según el país asociado a la localidad: N o E (nacional o extranjera).
	function get_origen_colegio($colegio)
	{
		$colegio = toba::db()->quote($colegio);
		$sql = "SELECT	vw_localidades.pais
  				FROM 	sga_colegios_secundarios
				JOIN	vw_localidades ON vw_localidades.localidad = sga_colegios_secundarios.localidad
  				WHERE	sga_colegios_secundarios.colegio = $colegio
				;";				

		$datos = guarani_db::consultar($sql);
		if (!empty($datos)) {
			$pais = $datos[0]['pais'];
			if ($pais == persona::pais_argentina) {
				return 'N';
			} else {
				return 'E';
			}
		}
	}
	
	/**
        Retorna los tipos de instituciones.
    */
	function get_instituciones_tipos($filtro='')
	{
		$filtro = toba::db()->quote("%$filtro%");
		$sql = "SELECT		sga_instituciones_tipos.tipo_institucion as valor, 
							sga_instituciones_tipos.nombre as descr 
  				FROM 		sga_instituciones_tipos
  				WHERE 		sga_instituciones_tipos.nombre ILIKE $filtro OR
							sga_instituciones_tipos.descripcion ILIKE $filtro
				ORDER BY	sga_instituciones_tipos.nombre
				;";				

		return guarani_db::consultar($sql);
	}
	
	/**
        Retorna la institucion que realiza la gestión académica.
    */
	function get_institucion_gestionante()
	{
		$where = " sga_instituciones.institucion = '" . guarani_configuracion::instancia()->get_valor_config('institucion_instalacion') . "'";
		$resp = $this->get_listado($where);
		return $resp;
	}

	/**
        Retorna el nombre abreviado de una institucion.
    */
	function get_institucion_nombre($instit)
	{
		$instit = toba::db()->quote($instit);
		$sql = "SELECT	sga_instituciones.nombre_abreviado as nombre_abreviado
				FROM 	sga_instituciones
  				WHERE 	sga_instituciones.institucion = $instit
				ORDER BY nombre_abreviado;";				
		return guarani_db::consultar($sql);
	}
	
	/**
        Obtiene una lista de los tipos de institución con su descripción
    */
	function get_descripciones()
	{
		$sql = 'SELECT tipo_institucion, nombre 
		        FROM sga_instituciones_tipos 
		        ORDER BY tipo_institucion';
		return guarani_db::consultar($sql);
	}

	/**
	 * Valida si un nombre de Tipo de Institución se puede usar o no.
     * @param string   $nombre	Nombre del Tipo de Institución a validar.
     * @param integer	$tipo_institucion	Id de un Tipo de Institución para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_tipo_institucion($nombre, $tipo_institucion=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	count(sga_instituciones_tipos.tipo_institucion) as cant 	
                FROM 	sga_instituciones_tipos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_instituciones_tipos.nombre', $nombre).'
				';
		if (!is_null($tipo_institucion)) {
			$tipo_institucion = toba::db()->quote($tipo_institucion);
			$sql .= " AND sga_instituciones_tipos.tipo_institucion <> $tipo_institucion ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Valida si un nombre de Institución se puede usar o no.
     * @param string   $nombre	Nombre de la Institución a validar.
     * @param integer	$tipo_institucion	Id de una Institución para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_institucion($nombre, $institucion=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		
		$sql = 'SELECT 	count(sga_instituciones.institucion) as cant 	
                FROM 	sga_instituciones
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_instituciones.nombre', $nombre).'
				';
		if (!is_null($institucion)) {
			$institucion = toba::db()->quote($institucion);
			$sql .= " AND sga_instituciones.institucion <> $institucion ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
	
	/**
	 * Obtiene el nombre de una Institución cuyo id se da como parámetro,
	 * retornando la string para el método descripción de un combo editable.
	 *
	 * @param integer $institucion id de la institución para la cual se desea recuperar la descripción.
	 */
	static function get_descripcion_institucion($institucion=null)
	{
		if (!isset($institucion)) {
			return '';
		}
		
		$institucion = toba::db()->quote($institucion);
		$result = toba::consulta_php('co_instituciones')->get_listado_nombres("sga_instituciones.institucion = $institucion");
		return empty($result) ? '' : $result[0]['descr'];
	}
	
	/**
	 * Obtiene una lista de los códigos araucanos segun el filtro recibido.
     */
	function get_int_araucano_instituciones($filtro='')
	{
		$filtro = toba::db()->quote("%$filtro%");		
		$sql = "SELECT	int_arau_instituciones.institucion_araucano AS valor,
						institucion_araucano || ' - ' || nombre || ' ' || COALESCE('(' || institucion_nombre || ')', '')	 			AS descr
				FROM 	int_arau_instituciones
				WHERE 	
						int_arau_instituciones.institucion_araucano::varchar ILIKE $filtro OR
						int_arau_instituciones.institucion_nombre ILIKE $filtro OR 
						int_arau_instituciones.nombre ILIKE $filtro
				ORDER BY int_arau_instituciones.nombre
			   	";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene el nombre de la inst araucano.
	 */
	static function get_descripcion_int_araucano($institucion_araucano)
	{
		$sql = "
				SELECT	int_arau_instituciones.institucion_araucano AS valor,
						institucion_araucano || ' - ' || nombre || ' ' || COALESCE('(' || institucion_nombre || ')', '')	 			AS descr
				FROM 	int_arau_instituciones
				WHERE	int_arau_instituciones.institucion_araucano = $institucion_araucano
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}
	
	//--------------------------------------------------------------------------
	//---- Colegios Secundarios ------------------------------------------------
	//--------------------------------------------------------------------------
	
	/**
     * Obtiene una lista de los colegios secundarios con sus datos de localidad y dirección,
	 * segun el filtro recibido.
     */
	function get_listado_colegios($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT	sga_colegios_secundarios.colegio,
				sga_colegios_secundarios.nombre,
				sga_colegios_secundarios.calle || ' ' || COALESCE(sga_colegios_secundarios.nro, ' ') as colegio_direccion,
				sga_colegios_secundarios.nombre || ' (' || sga_colegios_secundarios.cue::varchar || ')' as descripcion,
				vw_localidades.pais,
				vw_localidades.pais_nombre,
				vw_localidades.provincia,
				vw_localidades.provincia_nombre,
				vw_localidades.dpto_partido,
				vw_localidades.dpto_partido_nombre,
				vw_localidades.localidad,
				vw_localidades.localidad_nombre
			FROM sga_colegios_secundarios
			JOIN vw_localidades ON vw_localidades.localidad = sga_colegios_secundarios.localidad
			WHERE sga_colegios_secundarios.nivel = '".institucion::colegio_nivel_secundario."'
			$where
			ORDER BY	sga_colegios_secundarios.nombre
			;";
		return guarani_db::consultar($sql);
	}
	
	static function get_descripcion_colegio($colegio=null)
	{
		if (!isset($colegio)) {
			return '';
		}
		
		$colegio = toba::db()->quote($colegio);
		$result = toba::consulta_php('co_instituciones')->get_listado_nombres_colegios("sga_colegios_secundarios.colegio = $colegio");
		return empty($result) ? '' : $result[0]['descr'];
	}
	
	/**
     * Obtiene una lista de los nombres de colegios secundarios segun el filtro recibido.
     */
	function get_listado_nombres_colegios($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT	sga_colegios_secundarios.colegio as valor,
				sga_colegios_secundarios.nombre || ' (' || sga_colegios_secundarios.cue::varchar || ')' as descr
			FROM sga_colegios_secundarios
			WHERE sga_colegios_secundarios.nivel = '".institucion::colegio_nivel_secundario."'
			$where
			ORDER BY sga_colegios_secundarios.nombre
			;";
		return guarani_db::consultar($sql);
	}

	
	/*
		Obtiene el nombre del colegio Secundarios
	*/
	function get_nombre_colegio_por_id($colegio)
	{
		$colegio = toba::db()->quote($colegio);
		
		$sql = "SELECT	sga_colegios_secundarios.nombre || ' (' || sga_colegios_secundarios.cue::varchar || ')' as nombre
				FROM 	sga_colegios_secundarios
				WHERE	sga_colegios_secundarios.colegio = $colegio
			   ";
		
		$datos = guarani_db::consultar_fila($sql);
		if (!empty($datos['nombre'])) {
			return $datos['nombre'];
		} else {
			return '';
		}
	}
	
	/**
     *  Obtiene una lista de los nombres de colegios secundarios 
     */
    function get_colegios($filtro='')
	{
		$filtro = toba::db()->quote("%$filtro%");
		$where = " sga_colegios_secundarios.nombre ILIKE $filtro ";
		
		return $this->get_listado_nombres_colegios($where);	
	}
	
	function info_institucion()
	{
		$sql = "SELECT
					sga_instituciones.institucion, 
					sga_instituciones.nombre, 
					sga_instituciones.codigo, 
					'logo' as logo
				FROM par_configuraciones,
				     sga_instituciones
				WHERE par_configuraciones.nombre = 'institucion_instalacion'
				  AND sga_instituciones.institucion = cast(par_configuraciones.valor as integer)
				";

		$institucion = guarani_db::consultar_fila($sql);
		return $institucion;
	}
}
