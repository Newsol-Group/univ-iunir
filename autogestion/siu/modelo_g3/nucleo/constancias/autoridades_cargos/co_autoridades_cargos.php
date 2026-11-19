<?php
/**
 *
 * 
 * @package 	Constancias y Certificados
 * @subpackage 	Autoridades - Cargos
 * 
 */
class co_autoridades_cargos
{
	/**
	 * Obtiene un listado de constancias, según el filtro recibidos
	 */
	function get_listado_cargos($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where;
		}

		$sql = "SELECT		sga_cargos.cargo,
							sga_cargos.nombre,
							sga_cargos.nombre_femenino,
							sga_cargos.nombre_no_binario,
							" . guarani_sql::get_case_si_no('sga_cargos', 'activo') . "
				FROM		sga_cargos
				$where
				ORDER BY	sga_cargos.nombre
				";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene un listado de autoridades, según el filtro recibidos
	 */
	function get_listado_autoridades($where='')
	{
		if ($where) {
			$where = ' AND '.$where;
		}

		$sql = "SELECT		sga_autoridades.autoridad,
							 COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido) as apellido,
							COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres) as nombres,
							 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as autoridad_nombre_completo,
							mdp_personas.sexo,
							mdp_personas_sexo.descripcion as sexo_descr,
							sga_autoridades.titulo_persona,
							mdp_personas_titulos.nombre as titulo_nombre,
							sga_autoridades.cargo,
							sga_cargos.nombre as cargo_nombre,
							".guarani_sql::get_case_si_no('sga_autoridades', 'activo').",
							".guarani_sql::get_case_si_no('sga_autoridades', 'firma_digital').",
							".guarani_sql::get_case_si_no('sga_autoridades', 'firma_digital_analitico').",
							".guarani_sql::get_case_si_no('sga_autoridades', 'firma_digital_diploma').",
							".guarani_sql::get_case_si_no('sga_autoridades', 'firma_egreso')."
				FROM		sga_autoridades
							LEFT JOIN mdp_personas_titulos ON sga_autoridades.titulo_persona = mdp_personas_titulos.titulo_persona,
							mdp_personas,
							mdp_personas_sexo,
							sga_cargos
				WHERE		sga_autoridades.persona = mdp_personas.persona AND
							mdp_personas.sexo = mdp_personas_sexo.sexo AND
							sga_autoridades.cargo = sga_cargos.cargo
							$where
				ORDER BY	 COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)
				";

		return guarani_db::consultar($sql);
	}
	
	// Obtiene el listado de las autoridades activas existentes con sus datos.
	function get_listado_autoridades_activas($where='')
	{
		$filtro = "sga_autoridades.activo = '" . comunes::si . "'";
		if ($where) {
			$filtro .= ' AND ' . $where;
		}
		return $this->get_listado_autoridades($filtro);
	}

	/**
	 * Obtiene los datos basicos de la autoridad indicada
	 *
	 * @param str $autoridad ID de la autoridad a obtener los datos basicos
	 *
	 */
	function get_datos_basicos_autoridad($autoridad)
	{
		$autoridad = toba::db()->quote($autoridad);
		$sql = "SELECT	COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)|| ' ' ||  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)AS autoridad_nombre,
						CASE mdp_personas.sexo
							WHEN '".persona::sexo_femenino."' THEN mdp_personas_titulos.nombre_femenino_abreviado
							WHEN '".persona::sexo_no_binario."' THEN COALESCE(mdp_personas_titulos.nombre_no_binario_abreviado, '')
							ELSE mdp_personas_titulos.nombre_abreviado
						END AS autoridad_titulo,
						CASE mdp_personas.sexo 
							WHEN '".persona::sexo_femenino."' THEN sga_cargos.nombre_femenino
							WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_cargos.nombre_no_binario, '')
							ELSE sga_cargos.nombre
						END AS autoridad_cargo
				FROM	sga_autoridades
						LEFT JOIN mdp_personas_titulos ON sga_autoridades.titulo_persona = mdp_personas_titulos.titulo_persona,
						mdp_personas,
						sga_cargos
				WHERE	sga_autoridades.persona = mdp_personas.persona AND
						sga_autoridades.cargo = sga_cargos.cargo AND
						sga_autoridades.autoridad = $autoridad
	   ";
		return current(guarani_db::consultar($sql));
	}
	
	/**
	 * Obtiene un listado de ids y nombres de los Cargos Institucionales activos para la carga de un combo.
	 *
	 */
	static function get_listado_nombres_cargos_activos($where='')
	{
		if ($where) {
			$where = ' AND ' . $where . ' ';
		}
		
		$sql = "SELECT 		sga_cargos.cargo		as valor,
							sga_cargos.nombre		as descr
				FROM 		sga_cargos
				WHERE		sga_cargos.activo = '" . comunes::si . "'
							$where
				ORDER BY 	sga_cargos.nombre		
			   ";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los ids y descripciones de todos los Cargos Institucionales, cuyos nombres contengan al string 
	 * ingresado como filtro (mediante un combo editable).
	 *
	 * @param str $filtro porción del nombre del o los Cargos ingresada por el usuario.
	 */
	static function get_cargos_descr($filtro='')
	{
		$condicion = '';
		if (isset($filtro) && trim($filtro) != '') {
			$filtro = guarani_texto::limpiar_acentos($filtro);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$condicion = $filtro_condicion->get_sql('f_limpiar_acentos(sga_cargos.nombre::varchar)', $filtro);
		}
		return static::get_listado_nombres_cargos_activos($condicion);
	}
	
	/**
	 * Obtiene el nombre de un Cargo Institucional cuyo id se da como parámetro, retornando el string para el 
	 * método descripción de un combo editable.
	 *
	 * @param integer $cargo id del cargo institucional para el cual se desea recuperar la descripción.
	 */
	static function get_descripcion_cargo($cargo=null)
	{
		if (!isset($cargo)) {
			return '';
		}
		$cargo = toba::db()->quote($cargo);
		$result = static::get_listado_nombres_cargos_activos("sga_cargos.cargo = $cargo");
		return empty($result) ? '' : $result[0]['descr'];
	}

	/**
	 * Obtiene el conjunto de autoridades asociados a un tipo de Constancia
	 *
	 * @param integer $tipo_const id del tipo de la constancia
	 */
	function get_autoridades_constancia($constancia)
	{
		$constancia = toba::db()->quote($constancia);

		$sql = "SELECT 		
						sga_constancias_autoridades.autoridad,
						sga_constancias_autoridades.en_calidad_de,
						 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) as autoridad_nombre,
						CASE sga_constancias_autoridades.en_calidad_de
	   						WHEN '" . constancias::autoridad_titular . "' 
	   							THEN 'TITULAR'
	   						WHEN '" . constancias::autoridad_suplente . "' 
	   							THEN 'SUPLENTE'
	   					END as autoridad_calidad
				FROM 
						sga_constancias_autoridades,
						sga_autoridades,
						mdp_personas
				WHERE		
						sga_constancias_autoridades.autoridad = sga_autoridades.autoridad AND 
						sga_autoridades.persona = mdp_personas.persona AND
						sga_constancias_autoridades.constancia = $constancia
				ORDER BY 	
						en_calidad_de DESC
			   ";

		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene los datos de la/s autoridad/es dadas para la carga externa masiva del DT de autoridades 
	 * firmantes asociadas a una constancia.
	 *
	 * @param array/integer $autoridad arreglo de ids o id de la/s autoridad/es para las que se desea 
	 * recuperar los datos.
	 */
	function get_datos_autoridades_firmantes($autoridad)
	{
		if (is_array($autoridad)) {
			$filtro = guarani_sql::armar_condicion_in($autoridad, 'sga_autoridades', 'autoridad');
			$datos = $this->get_listado_autoridades($filtro);
			return $datos;
		}
		$autoridad = toba::db()->quote($autoridad);
		$datos = $this->get_listado_autoridades("sga_autoridades.autoridad = $autoridad");
		if (!empty($datos)) {
			return $datos[0];
		}
	}
	
	// Obtiene las opciones para la Calidad de una Autoridad Firmante, para la carga de un combo.
	function get_calidades_autoridad()
	{
		return array (	array('valor' => constancias::autoridad_titular, 'descr' => 'Titular'),
						array('valor' => constancias::autoridad_suplente, 'descr' => 'Suplente')
						);
	}
	
	/**
	 *  Valida si un nombre de Cargo se puede usar o no.
     * @param string	$nombre		Nombre del Cargo a validar.
     * @param integer	$cargo		Id del Cargo para excluir en la validación, si es distinto de nulo.
     * @return boolean	Si se puede usar ese nombre porque no todavía no existe, o no
    */
	function validar_nombre_cargo($nombre, $cargo=null)
	{
		if (is_null($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(sga_cargos.cargo) as cant 	
                FROM 	sga_cargos
				WHERE 	'.guarani_sql::armar_condicion_compara_cadenas('sga_cargos.nombre', $nombre).'
				';
		if (!is_null($cargo)) {
			$cargo = toba::db()->quote($cargo);
			$sql .= " AND sga_cargos.cargo <> $cargo ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}
        
        
        /**
	 * Obtiene las autoridades_firmantes de un conjunto de RAs dadas para enviar a Araí-Documentos.
	 * @see https://redmine.siu.edu.ar/redmine/issues/30921 Ticket #30921
         * @param int[]     $ras    Array con los ids de las responsables académicas.
         * @return array    Array de autoridades firmantes para un conjunto de RAs dadas.
	 */
	function get_autoridades_firmantes_arai_documentos($ras = [])
	{

            $si = toba::db()->quote(comunes::si);
            $filtro_ras = "";
            if (!empty($ras)) {
                $ras = implode(", ", $ras);
                $filtro_ras = "AND sga_autoridades.autoridad IN (SELECT DISTINCT autoridad FROM sga_autoridades_ra WHERE responsable_academica IN ($ras))";
            }
            
            
            $sql = "SELECT 
                    mdp_personas.persona,
                    mdp_personas.arai_identificador_sso
                    FROM sga_autoridades
                    JOIN mdp_personas ON (sga_autoridades.persona = mdp_personas.persona)
                    WHERE sga_autoridades.activo = $si
                    AND sga_autoridades.firma_digital = $si
                    $filtro_ras
                    ORDER BY  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)";

            return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene las autoridades_firmantes por propuesta y tipo de documento que puede firmar.
	 * @see https://redmine.siu.edu.ar/redmine/issues/32726 Ticket #32726
	 * @param int		$propuesta	ID de propuesta.
	 * @param bool		$diploma	Si firma diploma.
	 * @param bool		$analitico  Si firma analítico.
	 * @param bool		$egreso		Si firma egreso.
	 * @return array    Array de autoridades firmantes.
	 */
	function get_autoridades_firmantes_diploma_analitico_digital($propuesta, $diploma = false, $analitico = false, $egreso = false)
	{
		$si = toba::db()->quote(comunes::si);

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($propuesta);
		$ras = implode(", ", $ras);
		$filtro_ras = "AND sga_autoridades.autoridad IN (SELECT DISTINCT autoridad FROM sga_autoridades_ra WHERE responsable_academica IN ($ras))";

		$filtro_diploma = "";
		if ($diploma) {
			$filtro_diploma = "AND sga_autoridades.firma_digital_diploma = $si";
		}
		
		$filtro_analitico = "";
		if ($analitico) {
			$filtro_analitico = "AND sga_autoridades.firma_digital_analitico = $si";
		}
		
		$filtro_egreso = "";
		if ($egreso) {
			$filtro_egreso = "AND sga_autoridades.firma_egreso = $si";
		}

		$sql = "SELECT 
                    mdp_personas.persona AS persona,
                    CASE WHEN mdp_personas.arai_identificador_sso IS NULL
                    THEN
                        ".quote(arai_usuarios::SIU_USUARIO_ARAI)." || '|' || COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres )  --Se agrega este string para validar que tiene usuario Arai
                    ELSE
                        mdp_personas.arai_identificador_sso 
                    END AS valor,
					 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) || ' (' || CASE mdp_personas.sexo WHEN '".persona::sexo_femenino."' THEN sga_cargos.nombre_femenino WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_cargos.nombre_no_binario, '') ELSE sga_cargos.nombre END || ')' AS descr,
					 COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido)AS apellido,
					COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)AS nombres,
					 COALESCE( mdp_personas.apellido_elegido, mdp_personas.apellido ) || ', ' || COALESCE( mdp_personas.nombres_elegido, mdp_personas.nombres ) AS apellido_nombre,
					CASE mdp_personas.sexo
						WHEN '".persona::sexo_femenino."' THEN sga_cargos.nombre_femenino
						WHEN '".persona::sexo_no_binario."' THEN COALESCE(sga_cargos.nombre_no_binario, '')
						ELSE sga_cargos.nombre
					END AS cargo
				FROM sga_autoridades
				JOIN mdp_personas ON (sga_autoridades.persona = mdp_personas.persona)
				JOIN sga_cargos ON (sga_autoridades.cargo = sga_cargos.cargo)
				WHERE sga_autoridades.activo = $si
				$filtro_ras $filtro_diploma $filtro_analitico $filtro_egreso
                                ORDER BY  COALESCE(mdp_personas.apellido_elegido, mdp_personas.apellido),
                            COALESCE(mdp_personas.nombres_elegido, mdp_personas.nombres)";
		
		return guarani_db::consultar($sql);
	}

}