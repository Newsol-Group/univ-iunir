<?php
/**
 *
 *
 * @package Matrï¿½cula
 * @subpackage Personas/Alumnos
 */
class co_facturacion_nsg
{
	/**
	 * Obtiene una lista de los tipos de convenios para facturaciï¿½n
   	  */
	function get_tipos_convenio_fact()
	{
		$sql = "SELECT	mtc.tipo_convenio_fact  AS valor,
						mtc.descripcion	 			AS descr
				FROM 	negocio_nsg.nsg_tipo_convenio_fact mtc
			   	";
		
		return guarani_db::consultar($sql);
	}
		
	static function get_descripcion_tipo_convenio_fact($tipo_convenio_fact)
	{
		$sql = "SELECT
				mtc.tipo_convenio_fact  AS valor,
				mtc.descripcion	 			AS descr
				FROM 	negocio_nsg.nsg_tipo_convenio_fact mtc
				WHERE	mtc.tipo_convenio_fact = $tipo_convenio_fact
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}

	/**
        Obtiene una lista de los tipos de convenios segï¿½n el filtro recibido
    	*/
	function get_listado_tipos_convenios_fact($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	mtc.tipo_convenio_fact,
						mtc.descripcion
				FROM 	negocio_nsg.nsg_tipo_convenio_fact mtc
				$where
				ORDER BY mtc.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_tipo_convenio_fact($nombre, $tipo_convenio_fact=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mtc.tipo_convenio_fact) as cant 	
                FROM 	negocio_nsg.nsg_tipo_convenio_fact mtc
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('mtc.descripcion',$nombre);

		if (! is_null($tipo_convenio_fact)) {
			$tipo_convenio_fact = toba::db()->quote($tipo_convenio_fact);
			$sql .= " AND mtc.tipo_convenio_fact <> $tipo_convenio_fact ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
	 * Obtiene una lista de los tipos de bonificaciones 
     	*/
	function get_tipos_bonif()
	{
		$sql = "SELECT	mtb.tipo_bonif  AS valor,
						mtb.descripcion	 			AS descr
				FROM 	negocio_nsg.nsg_tipo_bonif mtb
			   	";
		
		return guarani_db::consultar($sql);
	}
		
	static function get_descripcion_tipo_bonif($tipo_bonif)
	{
		$sql = "SELECT
				mtb.tipo_bonif as valor,
				mtb.descripcion	as descr			
			FROM 	negocio_nsg.nsg_tipo_bonif mtb
				WHERE	mtb.tipo_bonif = $tipo_bonif
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}


	/**
        Obtiene una lista de los tipos de bonificaciones segï¿½n el filtro recibido
    	*/
	function get_listado_tipos_bonif($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	mtb.tipo_bonif,
				mtb.descripcion,
				mtb.cant_desde,
				mtb.cant_hasta
			FROM 	negocio_nsg.nsg_tipo_bonif mtb
				$where
				ORDER BY mtb.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_tipo_bonif($nombre, $tipo_bonif=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mtb.tipo_bonif) as cant 	
                FROM 	negocio_nsg.nsg_tipo_bonif mtb
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('mtb.descripcion',$nombre);

		if (! is_null($tipo_bonif)) {
			$tipo_bonif = toba::db()->quote($tipo_bonif);
			$sql .= " AND mtb.tipo_bonif <> $tipo_bonif ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de los medios de pago segï¿½n el filtro recibido
    	*/
	function get_listado_medios_pago($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	mmb.medio_pago,
				mmb.descripcion,
				mmb.disponible_autogestion,
				mmb.nombre_abreviado
				FROM 	negocio_nsg.nsg_medio_pago mmb
				$where
				ORDER BY mmb.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de los medios de pago segï¿½n el filtro recibido
    	*/
	function get_medios_pago()
	{
		$sql = "SELECT 	mmb.medio_pago as valor,
						mmb.descripcion as descr
				FROM 	negocio_nsg.nsg_medio_pago mmb
				ORDER BY mmb.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de los medios de pago segï¿½n el filtro recibido
    	*/
	function get_medios_pago_autogestion()
	{
		$sql = "SELECT 	mmb.medio_pago as valor,
						mmb.descripcion as descr
				FROM 	negocio_nsg.nsg_medio_pago mmb
				WHERE 	mmb.disponible_autogestion = 'S'
				ORDER BY mmb.descripcion";
		
		return guarani_db::consultar($sql);	
	}



	static function get_descripcion_medio_pago($medio_pago)
	{
		$sql = "SELECT
				mmb.descripcion	 			AS descr
				FROM 	negocio_nsg.nsg_medio_pago mmb
				WHERE	mmb.medio_pago = $medio_pago
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}


	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_medios_pago($nombre, $medio_pago=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mmb.medio_pago) as cant 	
                FROM 	negocio_nsg.nsg_medio_pago mmb
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('mmb.descripcion',$nombre);

		if (! is_null($medio_pago)) {
			$medio_pago = toba::db()->quote($medio_pago);
			$sql .= " AND mmb.medio_pago <> $medio_pago ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de las formas de pago segï¿½n el filtro recibido
    	*/
	function get_listado_formas_pago($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	mfp.forma_pago,
				mfp.descripcion,
				mfp.cant_cuotas
				FROM 	negocio_nsg.nsg_forma_pago mfp
				$where
				ORDER BY mfp.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de las formas de pago segï¿½n el filtro recibido
    	*/
	function get_formas_pago()
	{
		$sql = "SELECT 	mfp.forma_pago as valor,
						mfp.descripcion as descr
				FROM 	negocio_nsg.nsg_forma_pago mfp
				ORDER BY mfp.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de las formas de pago segï¿½n el filtro recibido
    	*/
	function get_formas_pago_certificacion()
	{
		$sql = "SELECT 	mfp.forma_pago as valor,
						mfp.descripcion as descr
				FROM 	negocio_nsg.nsg_forma_pago mfp
				WHERE   mfp.forma_pago in (1,2)
				ORDER BY mfp.descripcion";
		
		return guarani_db::consultar($sql);	
	}


	static function get_descripcion_forma_pago($forma_pago)
	{
		$sql = "SELECT
				mfp.descripcion	 			AS descr
				FROM 	negocio_nsg.nsg_forma_pago mfp
				WHERE	mfp.forma_pago = $forma_pago
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}


	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_formas_pago($nombre, $forma_pago=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mfp.forma_pago) as cant 	
                FROM 	negocio_nsg.nsg_forma_pago mfp
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('mfp.descripcion',$nombre);

		if (! is_null($forma_pago)) {
			$forma_pago = toba::db()->quote($forma_pago);
			$sql .= " AND mfp.forma_pago <> $forma_pago ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de las formas de pago por propuesta segï¿½n el filtro recibido
    	*/
	function get_listado_formas_pago_x_propuesta($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	mfpp.forma_pago_x_propuesta, 
				sga_propuestas.propuesta, 
				sga_propuestas.nombre as propuesta_nombre, 
				mfp.forma_pago, 
				mfp.descripcion as forma_pago_nombre,
				mfpp.corresponde_arancel_inscrp
			FROM 	negocio_nsg.nsg_forma_pago_x_propuesta mfpp,
				sga_propuestas,
				negocio_nsg.nsg_forma_pago mfp
			WHERE 	mfpp.propuesta = sga_propuestas.propuesta
			AND	mfpp.forma_pago = mfp.forma_pago
				$where
				ORDER BY mfpp.forma_pago_x_propuesta";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de las formas de pago por propuesta segï¿½n el filtro recibido
    	*/
	function get_formas_pago_x_propuesta($where='')
	{
		$sql = "SELECT 	mfpp.forma_pago_x_propuesta, 
				sp.nombre,
				mfpp.corresponde_arancel_inscrp
			FROM 	negocio_nsg.nsg_forma_pago_x_propuesta mfpp,
				sga_propuestas sp,
				negocio_nsg.nsg_forma_pago mfp
			WHERE 	mfpp.propuesta = sp.propuesta
			AND	mfpp.forma_pago = mfp.forma_pago
				$where";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_formas_pago_x_propuesta($propuesta, $forma_pago, $corresp_arancel)
	{
		if ((!isset($propuesta)) || (!isset($forma_pago))) {
			return false;
		}
			
		$corresp_arancel = toba::db()->quote($corresp_arancel);

		$sql = "SELECT 	count(*) as cant 	
	                FROM 	negocio_nsg.nsg_forma_pago_x_propuesta mfpp
			WHERE 	mfpp.propuesta = $propuesta
			AND	mfpp.forma_pago = $forma_pago
			AND	mfpp.corresponde_arancel_inscrp = $corresp_arancel
			";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de las formas de pago del alumno segï¿½n el filtro recibido
    	*/
	function get_formas_pago_alumno($alumno)
	{
		if (!is_null($alumno)) {
              		$alumno = toba::db()->quote($alumno);
              		$where = " AND sa.alumno = $alumno";
          	}

		$sql = "SELECT 	mfp.forma_pago as valor, 
			mfp.descripcion as descr 
		FROM 	negocio_nsg.nsg_forma_pago_x_propuesta mfpp,
			sga_alumnos sa,
			negocio_nsg.nsg_forma_pago mfp
		WHERE 	mfpp.propuesta = sa.propuesta
		AND	mfpp.forma_pago = mfp.forma_pago
		$where
		";

		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de las formas de pago del alumno segï¿½n el filtro recibido
    	*/
	function get_formas_pago_alumno_gestion($alumno)
	{
		if (!is_null($alumno)) {
              		$alumno = toba::db()->quote($alumno);
              		$where = " AND sa.alumno = $alumno";
          	}

		$sql = "SELECT 	mfp.forma_pago as valor, 
			mfp.descripcion as descr 
		FROM 	negocio_nsg.nsg_forma_pago mfp
		WHERE 	mfp.forma_pago in (2, 9)
		UNION ALL
			SELECT 	mfp.forma_pago as valor, 
			mfp.descripcion as descr 
		FROM 	negocio_nsg.nsg_forma_pago_x_propuesta mfpp,
			sga_alumnos sa,
			negocio_nsg.nsg_forma_pago mfp
		WHERE 	mfpp.propuesta = sa.propuesta
		AND	mfpp.forma_pago = mfp.forma_pago
		$where
		";

		return guarani_db::consultar($sql);	
	}

	
	/**
	 * Obtiene una lista de los tipos de beneficios aplicables a alumnos
   	  */
	function get_tipos_beneficio()
	{
		$sql = "SELECT	mtb.tipo_beneficio as valor,
				mtb.descripcion as descr,
				mtb.es_beca_o_bonif,
				mtb.porcentaje,
				mtb.disponible_autogestion
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
			   	";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de los tipos de becas aplicables a alumnos
   	  */
	function get_tipos_beca_alumno()
	{
		$sql = "SELECT	mtb.tipo_beneficio as valor,
				mtb.descripcion as descr
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
			WHERE 	mtb.es_beca_o_bonif = 'Beca'
			   	";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene una lista de los tipos de bonificaciones aplicables a alumnos
   	  */
	function get_tipos_bonif_alumno()
	{
		$sql = "SELECT	mtb.tipo_beneficio as valor,
				mtb.descripcion as descr
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
			WHERE 	mtb.es_beca_o_bonif = 'Bonif'
			   	";
		
		return guarani_db::consultar($sql);
	}
	
	/**
	 * Obtiene una lista de los tipos de beneficios que pueden solicitar los alumnos por autogestiï¿½n
   	  */
	function get_tipos_beneficio_alumno_autogestion()
	{
		$sql = "SELECT	mtb.tipo_beneficio,
				mtb.descripcion as nombre
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
			WHERE	mtb.disponible_autogestion = 'S'	
			   	";
		
		return guarani_db::consultar($sql);
	}

	/**
	 * Obtiene una lista de los tipos de beneficios aplicables a alumnos
   	  */
	function get_beneficios_tipo()
	{
		$sql = "SELECT	mtb.tipo_beneficio,
				mtb.descripcion,
				mtb.es_beca_o_bonif,
				mtb.porcentaje,
				mtb.disponible_autogestion
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
			   	";
		
		return guarani_db::consultar($sql);
	}

	static function get_descripcion_tipo_beneficio($tipo_beneficio)
	{
		$sql = "SELECT
				mtb.tipo_beneficio  AS valor,
				mtb.descripcion	 			AS descr
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
			WHERE	mtb.tipo_beneficio = $tipo_beneficio
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}

	/**
        Obtiene una lista de los tipos de convenios segï¿½n el filtro recibido
    	*/
	function get_listado_tipos_beneficio($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	mtb.tipo_beneficio,
				mtb.descripcion,
				mtb.es_beca_o_bonif,
				mtb.porcentaje,
				mtb.disponible_autogestion
			FROM 	negocio_nsg.nsg_tipo_beneficio mtb
				$where
			ORDER BY mtb.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_tipo_beneficio($nombre, $tipo_beneficio=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mtb.tipo_beneficio) as cant 	
                FROM 	negocio_nsg.nsg_tipo_beneficio mtb
				WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('mtb.descripcion',$nombre);

		if (! is_null($tipo_beneficio)) {
			$tipo_beneficio = toba::db()->quote($tipo_beneficio);
			$sql .= " AND mtb.tipo_beneficio <> $tipo_beneficio ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene una lista de las bonificaciones por convenio segï¿½n el filtro recibido
    	*/
	function get_listado_bonif_x_convenio($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}
		
		$sql = "SELECT 	mbc.bonif_x_convenio, 
				mbc.tipo_convenio_fact, 
				mtc.descripcion as tipo_convenio_nombre, 
				mbc.tipo_bonif, 
				mtf.descripcion as tipo_bonif_nombre,
				mbc.bonif_matricula, 
				mbc.bonif_cuota
			FROM 	negocio_nsg.nsg_bonif_x_convenio mbc,
				negocio_nsg.nsg_tipo_convenio_fact mtc,
				negocio_nsg.nsg_tipo_bonif mtf
			WHERE 	mbc.tipo_convenio_fact = mtc.tipo_convenio_fact
			AND	mbc.tipo_bonif = mtf.tipo_bonif
				$where
				ORDER BY mbc.bonif_x_convenio";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de las bonificaciones por convenio segï¿½n el filtro recibido
    	*/
	function get_bonif_x_convenio($where='')
	{
		$sql = "SELECT 	mbc.bonif_x_convenio, 
				mtc.descripcion 
			FROM 	negocio_nsg.nsg_bonif_x_convenio mbc,
				negocio_nsg.nsg_tipo_convenio_fact mtc,
				negocio_nsg.nsg_tipo_bonif mtf
			WHERE 	mbc.tipo_convenio_fact = mtc.tipo_convenio_fact
			AND	mbc.tipo_bonif = mtf.tipo_bonif
				$where";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_bonif_x_convenio($tipo_convenio, $tipo_bonif)
	{
		if ((!isset($tipo_convenio)) || (!isset($tipo_bonif))) {
			return false;
		}
			
		$sql = "SELECT 	count(*) as cant 	
	                FROM 	negocio_nsg.nsg_bonif_x_convenio mbc
			WHERE 	mbc.tipo_convenio_fact = $tipo_convenio
			AND	mbc.tipo_bonif = $tipo_bonif
			";

		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	function get_listado_datos_beneficio($filtro='')
	{
		if ($filtro) {
			$filtro = ' AND '.$filtro.' ';
		}

		$sql = "SELECT dato_beneficio, 
				alumno,
				tipo_beneficio, 
				beca_fecha_vigencia_desde, 
				beca_fecha_vigencia_hasta, 
				institucion, 
				beca_matricula,
				beca_cuota,
				bonificacion_matricula, 
				bonificacion_cuota
			FROM negocio_nsg.nsg_datos_beneficios
			$filtro
			";
		
		return guarani_db::consultar($sql);
	}

/**
        Obtiene una lista de los conceptos de facturaciï¿½n segï¿½n el filtro recibido
    	*/
	function get_listado_conceptos_fact($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
		$sql = "SELECT 	mcf.concepto,
				mcf.descripcion
			FROM 	negocio_nsg.nsg_conceptos_fact mcf
				$where
			ORDER BY mcf.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	/**
        Obtiene una lista de los conceptos de facturaciï¿½n segï¿½n el filtro recibido
    	*/
	function get_conceptos_fact()
	{
		$sql = "SELECT 	mcf.concepto as valor,
				mcf.descripcion as descr
			FROM 	negocio_nsg.nsg_conceptos_fact mcf
			ORDER BY mcf.descripcion";
		
		return guarani_db::consultar($sql);	
	}

	static function get_descripcion_concepto_fact($concepto_fact)
	{
		$sql = "SELECT	mcf.descripcion	AS descr
			FROM 	negocio_nsg.nsg_conceptos_fact  mcf
			WHERE	mcf.concepto = $concepto_fact
				";
		$result = guarani_db::consultar_fila($sql);
		return $result['descr'];
	}


	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_concepto_fact($nombre, $concepto_fact=null)
	{
		if (!isset($nombre)) {
			return false;
		}
		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(mcf.concepto) as cant 	
                	FROM 	negocio_nsg.nsg_conceptos_fact mcf
			WHERE 	' . guarani_sql::armar_condicion_compara_cadenas('mcf.descripcion',$nombre);

		if (! is_null($concepto_fact)) {
			$concepto_fact = toba::db()->quote($concepto_fact);
			$sql .= " AND mcf.concepto <> $concepto_fact ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}

	/**
        Obtiene nombres de tipos de beneficios relacionados a un alumno, segï¿½n el filtro recibido.
    	*/
	function get_descr_tipo_beneficio($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		
	   	$sql = 'SELECT	tipo_beneficio, 
				descripcion as tipo_beneficio_nombre 
				FROM 	negocio_nsg.nsg_tipo_beneficio 
				' . $where;
			        
		return guarani_db::consultar($sql);
	}

	/**
 	  *  Retorna el nombre de un Tipo de beca en formato RecordSet, en caso de que el parï¿½metro sea un array retorna 
 	  *  los nombres de los Tipos de beneficios segï¿½n la condiciï¿½n del array (para la carga masiva).
       *@param integer/array	tipo_beneficio	id o arreglo de ids del o de los Tipos de becas para los cuales se consultan los nombres.
    	*/
	function get_nombre_tipo_beneficio($tipo_beneficio)
	{
		if (is_array($tipo_beneficio)) {
			$filtro = guarani_sql::armar_condicion_in($tipo_beneficio, 'negocio_nsg.nsg_tipo_beneficio', 'tipo_beneficio');
			$datos = $this->get_descr_tipo_beneficio($filtro);
			return $datos;
		} 
		$tipo_beneficio = toba::db()->quote($tipo_beneficio);
		$datos = $this->get_descr_tipo_beneficio("negocio_nsg.nsg_tipo_beneficio.tipo_beneficio = $tipo_beneficio");
		return $datos[0];
		
	}

	/**
        Valida si un nombre se puede usar o no
	*/
	function validar_nombre_datos_becas_bonif_alumno($nombre, $alumno)
	{
		if (!isset($nombre)) {
			return false;
		}

		$nombre = toba::db()->quote($nombre);
		$sql = 'SELECT 	count(sdb.tipo_beneficio) as cant 	
                	FROM 	negocio_nsg.nsg_datos_beneficios sdb LEFT JOIN negocio_nsg.nsg_tipo_beneficio mtb ON mtb.tipo_beneficio = sdb.tipo_beneficio
				WHERE ' . guarani_sql::armar_condicion_compara_cadenas('mtb.descripcion',$nombre);

		if (! is_null($alumno)) {
			$alumno = toba::db()->quote($alumno);
			$sql .= " AND sdb.alumno = $alumno ";
		}	
		$res = guarani_db::consultar_fila($sql);
		return ($res['cant'] == 0);
	}


	function get_datos_beneficios($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		
		$sql = "SELECT dato_beneficio, 
				alumno,
				tipo_beneficio, 
				mtb.descripcion as tipo_beneficio_nombre, 
				beca_fecha_vigencia_desde, 
				beca_fecha_vigencia_hasta, 
				institucion, 
				beca_matricula,
				beca_cuota,
				bonificacion_matricula, 
				bonificacion_cuota
			FROM negocio_nsg.nsg_datos_beneficios
			LEFT JOIN negocio_nsg.nsg_tipo_beneficio as mtb ON mtb.tipo_beneficio = tipo_beneficio
			LEFT JOIN sga_instituciones ON sga_instituciones.institucion = institucion
			WHERE 	negocio_nsg.nsg_datos_beneficios.alumno = $alumno
			";
		
		return guarani_db::consultar($sql);
	}

	function get_dato_facturacion($alumno)
	{
		$alumno = toba::db()->quote($alumno);
		$sql = "SELECT coalesce(negocio_nsg.get_ultimo_dato_facturacion($alumno), 0) AS dato_facturacion;";
		return guarani_db::consultar_fila($sql);
	}

	function get_cabecera_dato_facturacion($dato_facturacion)
	{
		$dato_facturacion = toba::db()->quote($dato_facturacion);

		$sql = "SELECT	sdf.dato_facturacion,
				sdf.alumno
				FROM	negocio_nsg.nsg_datos_facturacion sdf
				WHERE	sdf.dato_facturacion = $dato_facturacion
				;";

		return guarani_db::consultar_fila($sql);
	}

	function get_listado_datos_facturacion($filtro='')
	{
		if ($filtro) {
			$filtro = ' AND '.$filtro.' ';
		}

		$sql = "SELECT alumno, 
				forma_pago,
				pago_a_cargo_empresa,
				porcentaje_facturacion_alumno, 
				porcentaje_facturacion_empresa, 
				medio_pago_alumno_mat, 
				medio_pago_empresa_mat, 
				medio_pago_alumno_cuota, 
				medio_pago_empresa_cuota,
				nro_tarjeta_alumno_mat, 
				cbu_alumno_mat, 
				nro_tarjeta_alumno_cuota, 
				cbu_alumno_cuota, 
				nro_tarjeta_empresa_mat, 
				cbu_empresa_mat, 
				nro_tarjeta_empresa_cuota, 
				cbu_empresa_cuota, 
				empresa,
				email_empresa,
				observaciones

			FROM negocio_nsg.nsg_datos_facturacion
				;";
		
		return guarani_db::consultar($sql);
	}

	function get_datos_facturacion_alumno($alumno)
	{
		$sql = "SELECT	dato_facturacion,
				alumno, 
				forma_pago,
				pago_a_cargo_empresa,
				porcentaje_facturacion_alumno, 
				porcentaje_facturacion_empresa, 
				medio_pago_alumno_mat, 
				nro_tarjeta_alumno_mat, 
				cbu_alumno_mat, 
				medio_pago_alumno_cuota, 
				nro_tarjeta_alumno_cuota, 
				cbu_alumno_cuota, 
				medio_pago_empresa_mat, 
				nro_tarjeta_empresa_mat,
				cbu_empresa_mat, 
				medio_pago_empresa_cuota, 
				nro_tarjeta_empresa_cuota, 
				cbu_empresa_cuota, 
				empresa,
				email_empresa,
				observaciones
			FROM negocio_nsg.nsg_datos_facturacion
			WHERE alumno = $alumno
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_facturacion_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}
		$sql = "SELECT		vw_personas.nombres,
					vw_personas.apellido,
					vw_personas.tipo_nro_documento as persona_nro_documento,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr,
					mfp.descripcion as forma_pago,
					sdf.pago_a_cargo_empresa,
					sdf.porcentaje_facturacion_alumno, 
					sdf.porcentaje_facturacion_empresa, 
					mmpam.descripcion as medio_pago_matricula_alumno, 
					sdf.nro_tarjeta_alumno_mat, 
					sdf.cbu_alumno_mat, 
					mmpac.descripcion as medio_pago_cuota_alumno,
					sdf.nro_tarjeta_alumno_cuota, 
					sdf.cbu_alumno_cuota, 
					mmpem.descripcion as medio_pago_matricula_empresa,
					sdf.nro_tarjeta_empresa_mat,
					sdf.cbu_empresa_mat, 
					mmpec.descripcion as medio_pago_cuota_empresa, 
					sdf.nro_tarjeta_empresa_cuota, 
					sdf.cbu_empresa_cuota, 
					sga_instituciones.nombre as nombre_empresa,
					sdf.email_empresa,
					sdf.observaciones

			FROM negocio_nsg.nsg_datos_facturacion sdf
						LEFT JOIN negocio_nsg.nsg_medio_pago as mmpam ON sdf.medio_pago_alumno_mat = mmpam.medio_pago
						LEFT JOIN negocio_nsg.nsg_medio_pago as mmpac ON sdf.medio_pago_alumno_cuota = mmpac.medio_pago
						LEFT JOIN negocio_nsg.nsg_medio_pago as mmpem ON sdf.medio_pago_empresa_mat = mmpem.medio_pago
						LEFT JOIN negocio_nsg.nsg_medio_pago as mmpec ON sdf.medio_pago_empresa_cuota = mmpec.medio_pago
						LEFT JOIN sga_instituciones ON sdf.empresa = sga_instituciones.institucion
						LEFT JOIN negocio_nsg.nsg_forma_pago as mfp ON sdf.forma_pago = mfp.forma_pago,
						sga_propuestas,
						sga_alumnos, 
						vw_personas
				WHERE		sdf.alumno = sga_alumnos.alumno AND
						sga_alumnos.persona = vw_personas.persona AND
						sga_alumnos.propuesta = sga_propuestas.propuesta
						$where
				ORDER BY	vw_personas.apellido,
						vw_personas.nombres
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_alumnos_sin_datos_facturacion_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}
		$sql = "SELECT		vw_personas.nombres,
					vw_personas.apellido,
					vw_personas.tipo_nro_documento as persona_nro_documento,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr
				FROM 		sga_propuestas,
						sga_alumnos, 
						vw_personas
				WHERE	(NOT EXISTS (select * from negocio_nsg.nsg_datos_facturacion sdf where sdf.alumno = sga_alumnos.alumno) OR
					EXISTS (select * from negocio_nsg.nsg_datos_facturacion sdf where sdf.alumno = sga_alumnos.alumno 
					and ((sdf.porcentaje_facturacion_alumno > 0 and ((sdf.medio_pago_alumno_mat is null or sdf.medio_pago_alumno_cuota is null)
						 or (sdf.medio_pago_alumno_mat = 1 and sdf.nro_tarjeta_alumno_mat is null) 
						 or (sdf.medio_pago_alumno_cuota = 1 and sdf.nro_tarjeta_alumno_cuota is null)) 
						 or (sdf.medio_pago_alumno_mat = 4 and sdf.cbu_alumno_mat is null) 
						 or (sdf.medio_pago_alumno_cuota = 4 and sdf.cbu_alumno_cuota is null)
						 or sdf.forma_pago is null)
					  or (sdf.porcentaje_facturacion_empresa > 0 and ((sdf.medio_pago_empresa_mat is null or sdf.medio_pago_empresa_cuota is null)
						 or (sdf.medio_pago_empresa_mat = 1 and sdf.nro_tarjeta_empresa_mat is null) 
						 or (sdf.medio_pago_empresa_cuota = 1 and sdf.nro_tarjeta_empresa_cuota is null)) 
						 or (sdf.medio_pago_empresa_mat = 4 and sdf.cbu_empresa_mat is null) 
						 or (sdf.medio_pago_empresa_cuota = 4 and sdf.cbu_empresa_cuota is null)
						or sdf.forma_pago is null)))) AND 
 						sga_alumnos.persona = vw_personas.persona AND
						sga_alumnos.propuesta = sga_propuestas.propuesta AND
						sga_alumnos.calidad = 'A' AND
						vw_personas.nro_documento <> '99999999'
						$where
				ORDER BY	vw_personas.apellido,
						vw_personas.nombres
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_beneficios_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}
		$sql = "SELECT	DISTINCT sga_alumnos.alumno, 
					vw_personas.nombres,
					vw_personas.apellido,
					vw_personas.tipo_nro_documento as persona_nro_documento,
					sga_propuestas.propuesta, 
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr,
					mtb.descripcion as tipo_beneficio_nombre,
					sdb.tipo_beneficio,
					sdb.beca_fecha_vigencia_desde,
					sdb.beca_fecha_vigencia_hasta,
					mtb.porcentaje as beca,					
					0 as bonificacion,
					'' as institucion_nombre
				FROM	negocio_nsg.nsg_datos_beneficios sdb
						LEFT JOIN sga_instituciones ON sdb.institucion = sga_instituciones.institucion
						LEFT JOIN negocio_nsg.nsg_tipo_beneficio as mtb ON sdb.tipo_beneficio = mtb.tipo_beneficio
						LEFT JOIN sga_alumnos ON sdb.alumno = sga_alumnos.alumno
						LEFT JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						LEFT JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				WHERE	mtb.es_beca_o_bonif = 'Beca'
				$where
				UNION ALL
				SELECT DISTINCT	sga_alumnos.alumno,
					vw_personas.nombres,
					vw_personas.apellido,
					vw_personas.tipo_nro_documento as persona_nro_documento,
					sga_propuestas.propuesta,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr,
					mtb.descripcion as tipo_beneficio_nombre,
					sdb.tipo_beneficio,
					sdb.beca_fecha_vigencia_desde,
					sdb.beca_fecha_vigencia_hasta,
					0 as beca,
					((case when sdb.institucion is not null then coalesce((select coalesce(sdb.bonificacion_cuota,0) + f.bonif_cuota from negocio_nsg.sga_instituciones_ext i, negocio_nsg.nsg_bonif_x_convenio f 
							where sdb.institucion is not null and sdb.institucion = i.id_institucion and i.tipo_convenio_fact = f.tipo_convenio_fact and f.tipo_bonif = 1),0) else coalesce(mtb.porcentaje,0) end)) as bonificacion,							
					(select sga_instituciones.nombre from sga_instituciones where sdb.institucion = sga_instituciones.institucion limit 1) as institucion_nombre
				FROM
					negocio_nsg.nsg_datos_beneficios sdb
						LEFT JOIN negocio_nsg.nsg_tipo_beneficio as mtb ON sdb.tipo_beneficio = mtb.tipo_beneficio
						LEFT JOIN sga_alumnos ON sdb.alumno = sga_alumnos.alumno
						LEFT JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
						LEFT JOIN vw_personas ON vw_personas.persona = sga_alumnos.persona
				WHERE	mtb.es_beca_o_bonif = 'Bonif'
						$where
				ORDER BY 5,3,2
	
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_morosos_perfil_de_datos($where='')
	{

		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}
		$sql = "SELECT		vw_personas.nombres,
					vw_personas.apellido,
					vw_personas.tipo_nro_documento as persona_nro_documento,
					'(' || sga_propuestas.codigo || ') ' || sga_propuestas.nombre_abreviado as propuesta_descr,
					sqe.deuda
				FROM		int_sq_morosos sqm 
						LEFT JOIN negocio_nsg.int_sq_morosos_ext sqe ON sqe.id_moroso_ext = sqm.id_moroso,
						sga_propuestas,
						sga_alumnos, 
						vw_personas
				WHERE		sqm.alumno = sga_alumnos.alumno AND
						sga_alumnos.persona = vw_personas.persona AND
						sga_alumnos.propuesta = sga_propuestas.propuesta
						$where
				ORDER BY	vw_personas.apellido,
						vw_personas.nombres
		";
		return guarani_db::consultar($sql, null, false);
	}


	function get_listado_morosos($where='')
	{
		if ($where) {
			$where = ' AND '.$where.' ';
		}

		$sql = "SELECT		sqm.alumno,
					vw_personas.nombres,
					vw_personas.apellido,
					vw_personas.tipo_nro_documento as persona_nro_documento,
					sga_propuestas.nombre as propuesta_descr,
					sga_propuestas.propuesta,
					sqe.deuda,
					sqm.id_moroso
				FROM	int_sq_morosos sqm
					left join negocio_nsg.int_sq_morosos_ext sqe ON sqe.id_moroso_ext = sqm.id_moroso,
					sga_propuestas,
					sga_alumnos, 
					vw_personas
				WHERE	sqm.alumno = sga_alumnos.alumno AND
					sga_alumnos.persona = vw_personas.persona AND
					sga_alumnos.propuesta = sga_propuestas.propuesta
					$where

				ORDER BY	vw_personas.apellido,
						vw_personas.nombres
		";
		return guarani_db::consultar($sql);
	}

	static function get_alumno_moroso_apellido($moroso)
	{
		$sql = "SELECT	mdp_personas.apellido		
			FROM	int_sq_morosos,
				mdp_personas,
				sga_alumnos
			WHERE	int_sq_morosos.alumno = sga_alumnos.alumno AND
				sga_alumnos.persona = mdp_personas.persona AND
				int_sq_morosos.id_moroso = $moroso
				";

		$result = guarani_db::consultar_fila($sql);
		return $result['apellido'];
	}

	static function get_alumno_moroso_nombres($moroso)
	{
		$sql = "SELECT	mdp_personas.nombres		
			FROM	int_sq_morosos,
				mdp_personas,
				sga_alumnos
			WHERE	int_sq_morosos.alumno = sga_alumnos.alumno AND
				sga_alumnos.persona = mdp_personas.persona AND
				int_sq_morosos.id_moroso = $moroso
				";

		$result = guarani_db::consultar_fila($sql);
		return $result['nombres'];
	}

	static function get_alumno_moroso_nro_documento($moroso)
	{
		$sql = "SELECT	mdp_personas_documentos.nro_documento		
			FROM	int_sq_morosos,
				mdp_personas_documentos,
				sga_alumnos
			WHERE	int_sq_morosos.alumno = sga_alumnos.alumno AND
				sga_alumnos.persona = mdp_personas_documentos.persona AND
				int_sq_morosos.id_moroso = $moroso
				";

		$result = guarani_db::consultar_fila($sql);
		return $result['nro_documento'];
	}

	static function get_alumno_moroso_propuesta($moroso)
	{
		$sql = "SELECT	sga_propuestas.nombre as propuesta_descr		
			FROM	int_sq_morosos,
				sga_propuestas,
				sga_alumnos
			WHERE	int_sq_morosos.alumno = sga_alumnos.alumno AND
				sga_alumnos.propuesta = sga_propuestas.propuesta AND
				int_sq_morosos.id_moroso = $moroso
				";

		$result = guarani_db::consultar_fila($sql);
		return $result['propuesta_descr'];
	}

	function get_transacciones_grado_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$where = $where." ORDER BY alumno";
 
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_generar_transacciones_grado()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas 
			FROM 		negocio_nsg.nsg_facturacion_grado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_grado.procesado = 'N'	
			$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_transacciones_diplomaturas_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$where = $where." ORDER BY alumno";

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_generar_transacciones_diplo()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas 
			FROM 		negocio_nsg.nsg_facturacion_diplomatura
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_diplomatura.procesado = 'N'
		
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_transacciones_posgrado_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		
		$where = $where." ORDER BY alumno";

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_generar_transacciones_posgrado()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit, 
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas 
			FROM 		negocio_nsg.nsg_facturacion_posgrado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_posgrado.procesado = 'N'
			$where
			";
		return guarani_db::consultar($sql, null, false);
	}


	function get_transacciones_constancias_perfil_de_datos($where)
	{
		if ($where) {
			$where = " AND $where";
		}

		$where = $where." ORDER BY alumno";

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_generar_transacciones_constancias()";
		guarani_db::consultar($sql);
		
		$sql = "SELECT alumno, 
					sede,
					propuesta, 
					concepto,
					solicitud, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas 
			FROM 		negocio_nsg.nsg_facturacion_constancias
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_constancias.procesado = 'N'
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_transacciones_examenes_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$where = $where." ORDER BY alumno";

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_generar_transacciones_examenes()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas 
			FROM 		negocio_nsg.nsg_facturacion_examenes
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_examenes.procesado = 'N'
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_reporte_constancias_a_facturar_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_constancias_a_facturar()";
		guarani_db::consultar($sql);

		$sql = "SELECT alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_constancias
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_reporte_facturacion_constancias.fecha = (select max(fecha) from negocio_nsg.nsg_reporte_facturacion_constancias)

					$where
			";
		return guarani_db::consultar($sql, null, false);
	}


	function get_reporte_examenes_a_facturar_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_examenes_a_facturar()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_examenes
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_reporte_cursadas_a_facturar_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_cursadas_a_facturar()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_grado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_reporte_cursadas_a_facturar_futuro_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_cursadas_a_facturar_futuro()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_grado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}
	
	function get_reporte_diplos_a_facturar_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_diplos_a_facturar()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_diplomatura
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_reporte_diplos_a_facturar_futuro_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_diplos_a_facturar_futuro()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_diplomatura
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_reporte_posgrados_a_facturar_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_posgrados_a_facturar()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_posgrado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_reporte_posgrados_a_facturar_futuro_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_reporte_posgrados_a_facturar_futuro()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT	alumno, 
					apellido, 
					nombres, 
					nro_documento,
					sede,
					nombre_sede,
					propuesta, 
					nombre_propuesta, 
					concepto, 
					nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_reporte_facturacion_posgrado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
					$where
			";
		return guarani_db::consultar($sql, null, false);
	}

	function get_historico_facturacion_cursadas_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT	a.alumno, 
					p.apellido, 
					p.nombres, 
					pd.nro_documento,
					sede,
					u.nombre as nombre_sede,
					b.propuesta as propuesta, 
					b.nombre as nombre_propuesta, 
					t.concepto, 
					f.descripcion as nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas,
					fecha
			FROM 		negocio_nsg.nsg_facturacion_grado t,
					sga_alumnos a, 
					sga_propuestas b, 
					sga_ubicaciones u,
					mdp_personas p,
					mdp_personas_documentos pd,
					negocio_nsg.nsg_conceptos_fact f
			WHERE		a.propuesta = b.propuesta AND 
					a.persona = p.persona AND
					p.persona = pd.persona AND
					t.alumno = a.alumno AND
					u.ubicacion = t.sede AND
					t.concepto = f.concepto AND
					t.alumno not in (select a.alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_historico_facturacion_examenes_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT	a.alumno, 
					p.apellido, 
					p.nombres, 
					pd.nro_documento,
					sede,
					u.nombre as nombre_sede,
					b.propuesta as propuesta, 
					b.nombre as nombre_propuesta, 
					t.concepto, 
					f.descripcion as nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas,
					fecha
			FROM 		negocio_nsg.nsg_facturacion_examenes t,
					sga_alumnos a, 
					sga_propuestas b, 
					sga_ubicaciones u,
					mdp_personas p,
					mdp_personas_documentos pd,
					negocio_nsg.nsg_conceptos_fact f
			WHERE		a.propuesta = b.propuesta AND 
					a.persona = p.persona AND
					p.persona = pd.persona AND
					t.alumno = a.alumno AND
					u.ubicacion = t.sede AND
					t.concepto = f.concepto AND
					t.alumno not in (select a.alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')

			$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_historico_facturacion_constancias_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT	a.alumno, 
					p.apellido, 
					p.nombres, 
					pd.nro_documento,
					sede,
					u.nombre as nombre_sede,
					b.propuesta as propuesta, 
					b.nombre as nombre_propuesta, 
					t.concepto, 
					f.descripcion as nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas,
					fecha
			FROM 		negocio_nsg.nsg_facturacion_constancias t,
					sga_alumnos a, 
					sga_propuestas b, 
					sga_ubicaciones u,
					mdp_personas p,
					mdp_personas_documentos pd,
					negocio_nsg.nsg_conceptos_fact f
			WHERE		a.propuesta = b.propuesta AND 
					a.persona = p.persona AND
					p.persona = pd.persona AND
					t.alumno = a.alumno AND
					u.ubicacion = t.sede AND
					t.concepto = f.concepto AND
					t.alumno not in (select a.alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_historico_facturacion_diplo_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT	a.alumno, 
					p.apellido, 
					p.nombres, 
					pd.nro_documento,
					sede,
					u.nombre as nombre_sede,
					b.propuesta as propuesta, 
					b.nombre as nombre_propuesta, 
					t.concepto, 
					f.descripcion as nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas,
					fecha
			FROM 		negocio_nsg.nsg_facturacion_diplomatura t,
					sga_alumnos a, 
					sga_propuestas b, 
					sga_ubicaciones u,
					mdp_personas p,
					mdp_personas_documentos pd,
					negocio_nsg.nsg_conceptos_fact f
			WHERE		a.propuesta = b.propuesta AND 
					a.persona = p.persona AND
					p.persona = pd.persona AND
					t.alumno = a.alumno AND
					u.ubicacion = t.sede AND
					t.concepto = f.concepto AND
					t.alumno not in (select a.alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')

			$where
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_historico_facturacion_posgrado_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT	a.alumno, 
					p.apellido, 
					p.nombres, 
					pd.nro_documento,
					sede,
					u.nombre as nombre_sede,
					b.propuesta as propuesta, 
					b.nombre as nombre_propuesta, 
					t.concepto, 
					f.descripcion as nombre_concepto,
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					porcentaje_facturacion_emp, 
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					email_emp,
					cuit,
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					total_cuotas,
					nro_cuota,
					agrupa_facturas,
					fecha
			FROM 		negocio_nsg.nsg_facturacion_posgrado t,
					sga_alumnos a, 
					sga_propuestas b, 
					sga_ubicaciones u,
					mdp_personas p,
					mdp_personas_documentos pd,
					negocio_nsg.nsg_conceptos_fact f
			WHERE		a.propuesta = b.propuesta AND 
					a.persona = p.persona AND
					p.persona = pd.persona AND
					t.alumno = a.alumno AND
					u.ubicacion = t.sede AND
					t.concepto = f.concepto AND
					t.alumno not in (select a.alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_posgrado.procesado = 'N'

			$where
		";
		return guarani_db::consultar($sql, null, false);
	}


	function get_deuda($persona)
	{

		$persona = toba::db()->quote($persona);
		$sql = "	
				SELECT
					sga_propuestas.propuesta as propuesta,
					sga_propuestas.codigo as propuesta_codigo,
					sga_propuestas.nombre as propuesta_nombre,
					COALESCE(cast(negocio_nsg.int_sq_morosos_ext.deuda as varchar),'No posee deuda') as deuda
				FROM sga_alumnos
					JOIN sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_planes_versiones ON sga_alumnos.plan_version = sga_planes_versiones.plan_version
					LEFT JOIN int_sq_morosos ON sga_alumnos.alumno = int_sq_morosos.alumno
					LEFT JOIN negocio_nsg.int_sq_morosos_ext ON int_sq_morosos.id_moroso = int_sq_morosos_ext.id_moroso_ext
				WHERE sga_alumnos.persona = $persona
				ORDER BY 1,4
				";
		return guarani_db::consultar($sql);
	}

	function get_alumnos_inscriptos_vs_facturados_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " AND $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}
		$sql = "SELECT      sga_propuestas.propuesta        as propuesta, 
					'(' || sga_propuestas.codigo || ') - ' || sga_propuestas.nombre        as nombre_propuesta,
                    			count(distinct sga_alumnos.alumno) as cant_inscriptos,
					count(distinct negocio_nsg.nsg_datos_facturacion.alumno) as cant_facturados
                    	FROM sga_propuestas,
					sga_planes,
					sga_alumnos
					JOIN sga_propuestas_aspira ON sga_alumnos.persona = sga_propuestas_aspira.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta 					
					LEFT JOIN negocio_nsg.nsg_datos_facturacion ON nsg_datos_facturacion.alumno = sga_alumnos.alumno
                	WHERE  sga_alumnos.propuesta = sga_propuestas.propuesta
					AND sga_planes.propuesta = sga_propuestas.propuesta
					AND sga_alumnos.plan_version = sga_planes.version_actual
					AND sga_alumnos.calidad = 'A'
			$where
                	GROUP BY    sga_propuestas.propuesta, sga_propuestas.codigo, sga_propuestas.nombre
		";
		return guarani_db::consultar($sql, null, false);
	}

	function get_ctacte_persona($persona)
	{

		$sql = "SELECT 	alumno, sga_propuestas.propuesta, sga_propuestas.nombre as nombre_propuesta
				FROM 	sga_alumnos
				JOIN 	sga_propuestas ON sga_propuestas.propuesta = sga_alumnos.propuesta
				WHERE 	persona = $persona";
		
		$alumno = guarani_db::consultar($sql, null, false);
		
		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));
		$result = array();

		foreach($alumno as $fila) 
		{
			$sql = "exec dbo.STR_SEL_COMPSALDOALUMNOS ".$fila["alumno"];
		
			$stmt = sqlsrv_query( $conn, $sql );
			if( $stmt === false) {
    				die( print_r( sqlsrv_errors(), true) );
			}

			while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
				$result[] = array("propuesta"=> $fila['propuesta'],
				"nombre_propuesta"=> $fila['nombre_propuesta'],
				"vtrmvc_codapl"=> $row['VTRMVC_CODAPL'],
				"grcfor_descrp"=> $row['GRCFOR_DESCRP'],
				"vtrmvc_nroapl"=> $row['VTRMVC_NROAPL'],
				"vtrmvc_fchvnc"=> substr($row['VTRMVC_FCHVNC'],6,4).'-'.substr($row['VTRMVC_FCHVNC'],3,2).'-'.substr($row['VTRMVC_FCHVNC'],0,2),
				"saldo"=> $row['Saldo']);  
    			}	
 
		}
		sqlsrv_free_stmt($stmt);
		sqlsrv_close($conn);
		return $result;
	}
	
	function get_ctacte_alumno($alumno)
	{

		$result = array();
		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		$sql = "exec dbo.STR_SEL_COMPSALDOALUMNOS ".$alumno;
		
		$stmt = sqlsrv_query( $conn, $sql );

		if( $stmt === false) {
    			die( print_r( sqlsrv_errors(), true) );
		}

		while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){

			$result[] = array("vtrmvc_nrocta" => $row['VTRMVC_NROCTA'],
				"vtmclh_nombre"=> $row['VTMCLH_NOMBRE'],
				"vtrmvc_codapl"=> $row['VTRMVC_CODAPL'],
				"grcfor_descrp"=> $row['GRCFOR_DESCRP'],
				"vtrmvc_nroapl"=> $row['VTRMVC_NROAPL'],
				"vtrmvc_fchvnc"=> $row['VTRMVC_FCHVNC'],
				"usr_vtmclh_nocrsa"=> $row['USR_VTMCLH_NOCRSA'],
				"alumno"=> $row['Alumno'],
				"saldo"=> $row['Saldo'],
				"link"=> $row['LINK']);  
 
    		}		

		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);
		return $result;

	}

	function get_softland_conn()
	{
		// Connect using Windows Authentication.
		$conn = new conexion_con_softland_nsg();
		return $conn->conectar();
	}
	function get_estado_pago_descripcion($estado_pago) {
		if (is_null($estado_pago)) return null;

		$sql = "SELECT descripcion
				FROM negocio_nsg.nsg_estados_pagos
				WHERE estado_pago = $estado_pago";
		$descripcion = guarani_db::consultar_fila($sql);	
		return $descripcion['descripcion'];
	}
	function get_datos_pagos_guarani($id, $origen) {
		
		$sql = ($origen == 'g3w3') ?
			   "SELECT codapl, nroapl, estado_pago, to_char(fecha, 'DD/MM/YYYY') as fecha_pago
				FROM negocio_nsg.nsg_referencias_pagos
				WHERE alumno = $id" :
			   "SELECT preinscripcion, codapl, nroapl, estado_pago, to_char(fecha, 'DD/MM/YYYY') as fecha_pago
				FROM negocio_nsg.nsg_referencias_pagos_preinsc
				WHERE preinscripcion = $id";
		return guarani_db::consultar($sql, null, false);
	}

	function get_datos_docentes_perfil_de_datos($where='')
	{
		if ($where) {
			$where = " WHERE $where";
		}
		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "select negocio_nsg.f_generar_informacion_docentes()";
		guarani_db::consultar($sql);

		$sql = "SELECT DISTINCT docente, 
			nombreyapellido,  
			tipo_documento,
			nro_documento, 
			cond_iva,
			cbu,
			domicilio,
			pais_domicilio,
			cp, 
			email
			FROM		negocio_nsg.nsg_liquidacion_docentes

			$where

		";
		return guarani_db::consultar($sql, null, false);
	}

	/**
	 * Inserta un nuevo registro en la tabla de morosos.
	 * 
	 */
	static function insertar_morosos()
	{

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		$param = "-2";
		$sql = "exec dbo.STR_SEL_SALDOALUMNOS ".$param;
		
		$stmt = sqlsrv_query( $conn, $sql );

		if( $stmt === false) {
    			die( print_r( sqlsrv_errors(), true) );
		}

		$p3Count = 0;
     		while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
            	{
			$nro_transaccion = $p3Count+1;
   
			$deuda =  $row['Saldo'];
			$alumno = $row['IDAlumno'];
			$sql = "select negocio_nsg.f_actualizar_morosos($alumno, $nro_transaccion, $deuda)";
			guarani_db::consultar($sql);
               		$p3Count++;
            	}
 
        	sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);

		toba::logger()->info('************ Se insertï¿½ el registro de morosos con ï¿½xito. ************');
	}

	/**
	 * Elimina los datos de la tabla de morosos.
	 * 
	 */
	static function eliminar_morosos()
	{
		$sql = "select negocio_nsg.f_borrar_morosos()";
		guarani_db::consultar($sql);

		toba::logger()->info('************ Se eliminaron los registros de morosos con ï¿½xito. ************');
	}


	/**
	 * Inserta los registros de alumnos de grado en la tabla de alumnos de Softland.
	 * 
	 */

	static function insertar_alumnos_grado()
	{


		$sql = "DELETE FROM negocio_nsg.tmp_alu_grado ";
		guarani_db::consultar($sql);


		$sql = "INSERT INTO negocio_nsg.tmp_alu_grado
			SELECT DISTINCT (case a.propuesta when 32 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 1),a.alumno) when 29 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 1),a.alumno) when 26 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 3),a.alumno) when 31 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 4),a.alumno) when 30 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 5),a.alumno) when 33 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 2),a.alumno) when 35 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 2),a.alumno) when 36 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 34),a.alumno) else a.alumno end) as alumno,  
			unaccent(upper(trim(c.nombres) || ' ' || trim(replace(c.apellido, '''', '')))) as nombreyapellido,  
			(select b.tipo_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as tipo_documento,
			(select b.nro_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as nro_documento,
			unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
			right('000' || j.pais,3) as pais_domicilio,  
			(case c.nacionalidad when 1 then coalesce(e.periodo_lectivo_codigo_postal, '2000') else '2000' end ) as cp, 					
			f.nombre as localidad, 
			h.nombre as provincia, 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MP' limit 1), '') as email1 , 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MI' limit 1), '') as email2,
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MS' limit 1), '') as email3,
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'T' limit 1), '') as tel1, 
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'C' limit 1), '') as tel2,
			'' as tel3,
			'03-01-'||(select cast(s.anio_academico as text) from sga_propuestas_aspira s where s.persona = a.persona AND s.propuesta = (case p.propuesta when 32 then 1 when 29 then 1 when 26 then 3 when 31 then 4 when 30 then 5 when 33 then 2 when 35 then 2 when 36 then 34 else a.propuesta end) limit 1) as fecha_inscripcion,
			(case p.propuesta when 32 then 1 when 29 then 1 when 26 then 3 when 31 then 4 when 30 then 5 when 33 then 2 when 35 then 2 when 36 then 34 else p.propuesta end) as propuesta	
			FROM	sga_alumnos a, 
					sga_propuestas p, 
					mdp_personas_documentos pd,
					mdp_personas c
					LEFT JOIN mdp_datos_censales d ON d.persona = c.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 	a.persona = c.persona AND
					p.propuesta = a.propuesta AND
					p.propuesta_tipo in (200,204) AND
					pd.persona = c.persona AND
					pd.tipo_documento <> 3 AND
					c.usuario <> 'newsol'			
			order by 1
			
		";
		$res = guarani_db::consultar($sql, null, false);

		$sql = "SELECT DISTINCT alumno, nombreyapellido, tipo_documento, nro_documento, domicilio, pais_domicilio, cp, localidad, provincia, email1, email2, email3, tel1, 
			tel2, tel3, fecha_inscripcion, propuesta 
			FROM  negocio_nsg.tmp_alu_grado			
			order by 1
		";

		$res = guarani_db::consultar($sql, null, false);

		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INMALU (USR_INMALU_IDALUM,USR_INMALU_NOMBRE,USR_INMALU_TIPDOC,USR_INMALU_NRODOC,USR_INMALU_DIRECC,USR_INMALU_CODPAI,USR_INMALU_CODPOS,
				USR_INMALU_DESLOC,USR_INMALU_DESPRO,USR_INMALU_DIREM1,USR_INMALU_DIREM2,USR_INMALU_DIREM3,USR_INMALU_TELEF1,USR_INMALU_TELEF2,
				USR_INMALU_TELEF3, USR_INMALU_FCHING, USR_INMALU_PROPUE, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
				VALUES (".$row["alumno"].",'".$row["nombreyapellido"]."',".$row["tipo_documento"].",'".$row["nro_documento"]."','".
				$row["domicilio"]."','".$row["pais_domicilio"]."','".$row["cp"]."','".$row["localidad"]."','".$row["provincia"]."','".$row["email1"]."','". 
				$row["email2"]."','".$row["email3"]."','".$row["tel1"]."','".$row["tel2"]."','".$row["tel3"]."','".$row["fecha_inscripcion"]."',".$row["propuesta"].",'".
				$fecha["fecha"]."','".$fecha["fecha"]."','".'SIU'."','".'A'."','".'N'."','".'USR_INMALU'."')";

			$stmt3 = sqlsrv_query($conn, $tsql3);

        		if ($stmt3 == FALSE)
			{ 
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn );  
			}
		 
		}

		sqlsrv_commit( $conn );
	    	sqlsrv_free_stmt($stmt3);
      		sqlsrv_close($conn);
		
		//toba::logger()->info('************ Se insertï¿½ el registro de alumnos de grado con ï¿½xito. ************');
	}


	/**
	 * Reporte de alumnos de grado a facturar.
	 * 
	 */
	function get_reporte_alumnos_grado_a_facturar($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT (case a.propuesta when 32 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 1),a.alumno) when 29 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 1),a.alumno) when 26 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 3),a.alumno) when 31 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 4),a.alumno) when 30 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 5),a.alumno) when 33 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 2),a.alumno) when 35 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 2),a.alumno) when 36 then coalesce((select alumno from sga_alumnos where persona = a.persona and propuesta = 34),a.alumno) else a.alumno end) as alumno,  
			unaccent(upper(trim(c.nombres) || ' ' || trim(replace(c.apellido, '''', '')))) as nombreyapellido,  
			(select t.desc_abreviada from mdp_personas_documentos b, mdp_tipo_documento t where t.tipo_documento = b.tipo_documento and b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as tipo_documento,
			(select b.nro_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as nro_documento,
			unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
			right('000' || j.pais,3) as pais_domicilio,  
			(case c.nacionalidad when 1 then coalesce(e.periodo_lectivo_codigo_postal, '2000') else '2000' end ) as cp, 					
			f.nombre as localidad, 
			h.nombre as provincia, 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MP' limit 1), '') as email1 , 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MI' limit 1), '') as email2,
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MS' limit 1), '') as email3,
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'T' limit 1), '') as tel1, 
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'C' limit 1), '') as tel2,
			'' as tel3,
			'03-01-'||(select cast(s.anio_academico as text) from sga_propuestas_aspira s where s.persona = a.persona AND s.propuesta = (case p.propuesta when 32 then 1 when 29 then 1 when 26 then 3 when 31 then 4 when 30 then 5 when 33 then 2 when 35 then 2 when 36 then 34 else a.propuesta end) limit 1) as fecha_inscripcion,
			(case p.propuesta when 32 then 1 when 29 then 1 when 26 then 3 when 31 then 4 when 30 then 5 when 33 then 2 when 35 then 2 when 36 then 34 else p.propuesta end) as propuesta,	
			(case p.propuesta when 32 then 'Licenciatura en Enfermería' when 29 then 'Licenciatura en Enfermería' when 26 then 'Medicina' when 31 then 'Odontología' when 30 then 'Licenciatura en Psicología' when 33 then 'Licenciatura en Instrumentación Quirúrgica' when 35 then 'Licenciatura en Instrumentación Quirúrgica' when 36 then 'Licenciatura en Kinesiología y Fisiatría' else p.nombre end) as nombre_propuesta
			FROM	sga_alumnos a, 
					sga_propuestas p, 
					mdp_personas_documentos pd,
					mdp_personas c
					LEFT JOIN mdp_datos_censales d ON d.persona = c.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 	a.persona = c.persona AND
					p.propuesta = a.propuesta AND
					p.propuesta_tipo in (200,204) AND
					pd.persona = c.persona AND
					pd.tipo_documento <> 3 AND
					c.usuario <> 'newsol'	
			$where		
			ORDER BY 	1
			
		";
		return guarani_db::consultar($sql, null, false);

	}


	/**
	 * Inserta los registros de alumnos de posgrado en la tabla de alumnos de Softland.
	 * 
	 */

	static function insertar_alumnos_posgrado()
	{

		$sql = "SELECT DISTINCT a.alumno, 
			unaccent(upper(trim(c.nombres) || ' ' || trim(replace(c.apellido, '''', '')))) as nombreyapellido,			
			(select b.tipo_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as tipo_documento,
			(select b.nro_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as nro_documento,
			unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
			right('000' || j.pais,3) as pais_domicilio,  
			(case c.nacionalidad when 1 then coalesce(e.periodo_lectivo_codigo_postal, '2000') else '2000' end ) as cp, 					
			f.nombre as localidad, 
			h.nombre as provincia, 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MP' limit 1), '') as email1 , 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MI' limit 1), '') as email2,
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MS' limit 1), '') as email3,
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'T' limit 1), '') as tel1, 
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'C' limit 1), '') as tel2,
			'' as tel3,
			'03-01-'||(select cast(s.anio_academico as text) from sga_propuestas_aspira s where s.persona = a.persona AND s.propuesta = a.propuesta limit 1) as fecha_inscripcion,
			p.propuesta	
			FROM		sga_alumnos a, 
					sga_propuestas p, 
					mdp_personas_documentos pd,
					mdp_personas c
					LEFT JOIN mdp_datos_censales d ON d.persona = c.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 		a.persona = c.persona AND
					p.propuesta = a.propuesta AND
					pd.persona = c.persona AND
					p.propuesta_tipo in (202,205) AND
					pd.tipo_documento <> 3 AND
					c.usuario <> 'newsol'
			ORDER BY 	a.alumno
		";
		$res = guarani_db::consultar($sql, null, false);

		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INMALU (USR_INMALU_IDALUM,USR_INMALU_NOMBRE,USR_INMALU_TIPDOC,USR_INMALU_NRODOC,USR_INMALU_DIRECC,USR_INMALU_CODPAI,USR_INMALU_CODPOS,
				USR_INMALU_DESLOC,USR_INMALU_DESPRO,USR_INMALU_DIREM1,USR_INMALU_DIREM2,USR_INMALU_DIREM3,USR_INMALU_TELEF1,USR_INMALU_TELEF2,
				USR_INMALU_TELEF3, USR_INMALU_FCHING, USR_INMALU_PROPUE, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
				VALUES (".$row["alumno"].",'".$row["nombreyapellido"]."',".$row["tipo_documento"].",'".$row["nro_documento"]."','".
				$row["domicilio"]."','".$row["pais_domicilio"]."','".$row["cp"]."','".$row["localidad"]."','".$row["provincia"]."','".$row["email1"]."','". 
				$row["email2"]."','".$row["email3"]."','".$row["tel1"]."','".$row["tel2"]."','".$row["tel3"]."','".$row["fecha_inscripcion"]."',".$row["propuesta"].",'".
				$fecha["fecha"]."','".$fecha["fecha"]."','".'SIU'."','".'A'."','".'N'."','".'USR_INMALU'."')";

			$stmt3 = sqlsrv_query($conn, $tsql3);

        		if ($stmt3 == FALSE)
			{ 
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn );  
			}
		 
		}
		sqlsrv_commit( $conn );
	    	sqlsrv_free_stmt($stmt3);
      		sqlsrv_close($conn);
		
		//toba::logger()->info('************ Se insertï¿½ el registro de alumnos de grado con ï¿½xito. ************');
	}


	/**
	 * Reporte de alumnos de posgrado a facturar.
	 * 
	 */
	function get_reporte_alumnos_posgrado_a_facturar($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT a.alumno, 
			unaccent(upper(trim(c.nombres) || ' ' || trim(replace(c.apellido, '''', '')))) as nombreyapellido,  
			(select t.desc_abreviada from mdp_personas_documentos b, mdp_tipo_documento t where t.tipo_documento = b.tipo_documento and b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as tipo_documento,
			(select b.nro_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as nro_documento,
			unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
			right('000' || j.pais,3) as pais_domicilio,  
			(case c.nacionalidad when 1 then coalesce(e.periodo_lectivo_codigo_postal, '2000') else '2000' end ) as cp, 					
			f.nombre as localidad, 
			h.nombre as provincia, 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MP' limit 1), '') as email1 , 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MI' limit 1), '') as email2,
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MS' limit 1), '') as email3,
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'T' limit 1), '') as tel1, 
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'C' limit 1), '') as tel2,
			'' as tel3,
			'03-01-'||(select cast(s.anio_academico as text) from sga_propuestas_aspira s where s.persona = a.persona AND s.propuesta = a.propuesta limit 1) as fecha_inscripcion,
			p.propuesta, 
			p.nombre as nombre_propuesta
			FROM	sga_alumnos a, 
					sga_propuestas p, 
					mdp_personas_documentos pd,
					mdp_personas c
					LEFT JOIN mdp_datos_censales d ON d.persona = c.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 		a.persona = c.persona AND
					p.propuesta = a.propuesta AND
					pd.persona = c.persona AND
					p.propuesta_tipo in (202,205) AND
					pd.tipo_documento <> 3 AND
					c.usuario <> 'newsol'
			$where		
			ORDER BY 	a.alumno
			
		";
		return guarani_db::consultar($sql, null, false);

	}


	/**
	 * Inserta los registros de alumnos de diplomatura en la tabla de alumnos de Softland.
	 * 
	 */

	static function insertar_alumnos_diplomaturas()
	{

		$sql = "SELECT DISTINCT	a.alumno, 
					unaccent(upper(trim(c.nombres) || ' ' || trim(replace(c.apellido, '''', '')))) as nombreyapellido, 
					(select b.tipo_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as tipo_documento,
					(select b.nro_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as nro_documento,
					unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
					coalesce(right('000' || j.pais,3),'054') as pais_domicilio,  
					(case c.nacionalidad when 1 then coalesce(e.periodo_lectivo_codigo_postal, '2000') else '2000' end ) as cp, 					
					f.nombre as localidad, 
					h.nombre as provincia, 
					coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MP' limit 1), '') as email1 , 
					coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MI' limit 1), '') as email2,
					coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MS' limit 1), '') as email3,
					coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'T' limit 1), '') as tel1, 
					coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'C' limit 1), '') as tel2,
					'' as tel3,
					'03-01-'||(select cast(s.anio_academico as text) from sga_propuestas_aspira s where s.persona = a.persona AND s.propuesta = a.propuesta limit 1) as fecha_inscripcion,
					p.propuesta
			FROM		sga_alumnos a, 
					sga_propuestas p, 
					mdp_personas c
					LEFT JOIN mug_localidades ml ON c.localidad_nacimiento = ml.localidad
					LEFT JOIN mug_dptos_partidos mdp ON ml.dpto_partido = mdp.dpto_partido
					LEFT JOIN mug_provincias mpr ON mpr.provincia = mdp.provincia
					LEFT JOIN mug_paises mp ON c.pais_origen = mp.pais
					LEFT JOIN mdp_datos_censales d ON d.persona = c.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 		a.persona = c.persona AND
					p.propuesta = a.propuesta AND
					p.propuesta_tipo in (208) AND
					c.usuario <> 'newsol' 
			ORDER BY 	a.alumno 
		";
		$res = guarani_db::consultar($sql, null, false);

		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INMALU (USR_INMALU_IDALUM,USR_INMALU_NOMBRE,USR_INMALU_TIPDOC,USR_INMALU_NRODOC,USR_INMALU_DIRECC,USR_INMALU_CODPAI,USR_INMALU_CODPOS,
				USR_INMALU_DESLOC,USR_INMALU_DESPRO,USR_INMALU_DIREM1,USR_INMALU_DIREM2,USR_INMALU_DIREM3,USR_INMALU_TELEF1,USR_INMALU_TELEF2,
				USR_INMALU_TELEF3, USR_INMALU_FCHING, USR_INMALU_PROPUE, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
				VALUES (".$row["alumno"].",'".$row["nombreyapellido"]."',".$row["tipo_documento"].",'".$row["nro_documento"]."','".
				$row["domicilio"]."','".$row["pais_domicilio"]."','".$row["cp"]."','".$row["localidad"]."','".$row["provincia"]."','".$row["email1"]."','". 
				$row["email2"]."','".$row["email3"]."','".$row["tel1"]."','".$row["tel2"]."','".$row["tel3"]."','".$row["fecha_inscripcion"]."',".$row["propuesta"].",'".
				$fecha["fecha"]."','".$fecha["fecha"]."','".'SIU'."','".'A'."','".'N'."','".'USR_INMALU'."')";

			$stmt3 = sqlsrv_query($conn, $tsql3);

        		if ($stmt3 == FALSE)
			{ 
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn );  
			}
			
		}	
		sqlsrv_commit( $conn );
        	sqlsrv_free_stmt($stmt3);
        	sqlsrv_close($conn);

		//toba::logger()->info('************ Se insertï¿½ el registro de morosos con ï¿½xito. ************');
	}

	/**
	 * Reporte de alumnos de diplomatura a facturar.
	 * 
	 */
	function get_reporte_alumnos_diplomatura_a_facturar($where='')
	{
		if ($where) {
			$where = " AND $where";
		}

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}

		$sql = "SELECT DISTINCT a.alumno, 
			unaccent(upper(trim(c.nombres) || ' ' || trim(replace(c.apellido, '''', '')))) as nombreyapellido,  
			(select t.desc_abreviada from mdp_personas_documentos b, mdp_tipo_documento t where t.tipo_documento = b.tipo_documento and b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as tipo_documento,
			(select b.nro_documento from mdp_personas_documentos b where b.persona = c.persona and b.tipo_documento <> 3 order by b.tipo_documento limit 1) as nro_documento,
			unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
			coalesce(right('000' || j.pais,3), '054') as pais_domicilio,  
			(case c.nacionalidad when 1 then coalesce(e.periodo_lectivo_codigo_postal, '2000') else '2000' end ) as cp, 					
			f.nombre as localidad, 
			h.nombre as provincia, 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MP' limit 1), '') as email1 , 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MI' limit 1), '') as email2,
			coalesce((select i.email from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'MS' limit 1), '') as email3,
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'T' limit 1), '') as tel1, 
			coalesce((select coalesce(i.telefono_codigo_area,'') || '-' ||  i.telefono_numero from mdp_personas_contactos i where i.persona = c.persona and i.contacto_tipo = 'C' limit 1), '') as tel2,
			'' as tel3,
			'03-01-'||(select cast(s.anio_academico as text) from sga_propuestas_aspira s where s.persona = a.persona AND s.propuesta = a.propuesta limit 1) as fecha_inscripcion,
			p.propuesta, 
			p.nombre as nombre_propuesta
			FROM		sga_alumnos a, 
					sga_propuestas p, 
					mdp_personas c
					LEFT JOIN mug_localidades ml ON c.localidad_nacimiento = ml.localidad
					LEFT JOIN mug_dptos_partidos mdp ON ml.dpto_partido = mdp.dpto_partido
					LEFT JOIN mug_provincias mpr ON mpr.provincia = mdp.provincia
					LEFT JOIN mug_paises mp ON c.pais_origen = mp.pais
					LEFT JOIN mdp_datos_censales d ON d.persona = c.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 		a.persona = c.persona AND
					p.propuesta = a.propuesta AND
					p.propuesta_tipo in (208) AND
					c.usuario <> 'newsol'
			$where		
			ORDER BY 	a.alumno
			
		";
		return guarani_db::consultar($sql, null, false);

	}

	/**
	 * Elimina los datos de la tabla temporal de alumnos.
	 * 
	 */

	static function borrar_tabla_temporal()
	{

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

  		$sql = "Exec dbo.STR_PRO_INTERFAZ_ALUMNOS";
		
		$stmt = sqlsrv_prepare($conn, $sql);
		if ($stmt == false)  
    			die(print_r(sqlsrv_errors(), true)); 

		$stmt = sqlsrv_execute( $stmt );
		if ($stmt == false)  
    			die(print_r(sqlsrv_errors(), true));  
			
  		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);

		//toba::logger()->info('************ Se eliminaron los registros de morosos con ï¿½xito. ************');
	}

	/**
	 * Inserta los registros en la tabla de docentes de Softland.
	 * 
	 */

	static function insertar_docentes_tabla_softland()
	{
		$sql = "SELECT DISTINCT a.docente, 
			unaccent(upper(trim(p.nombres) || ' ' || trim(replace(p.apellido, '''', '')))) as nombreyapellido,  
			(case b.tipo_documento when 3 then 80 end) as tipo_documento,
			b.nro_documento, 
			coalesce((select i.codigo from mdp_datos_economicos e, mdp_datos_censales c, negocio_nsg.nsg_cond_iva i where c.persona = p.persona and c.dato_censal = e.dato_censal and e.trabajo_ocupacion = i.cond_iva), '') as cond_iva,
			coalesce((select e.trabajo_tarea_descripcion from mdp_datos_economicos e, mdp_datos_censales c where c.persona = p.persona and c.dato_censal = e.dato_censal), '') as cbu,
			unaccent(upper(coalesce(trim(coalesce(replace(e.periodo_lectivo_calle, '''', ''),'') || ' ' || coalesce(e.periodo_lectivo_numero,'') || ' ' || coalesce(e.periodo_lectivo_piso,'') || ' ' || coalesce(e.periodo_lectivo_departamento, '')),'.'))) as domicilio,
			coalesce(right('000' || j.pais,3), '054') as pais_domicilio,
			coalesce(e.periodo_lectivo_codigo_postal, '2000') as cp, 
			coalesce((select i.email from mdp_personas_contactos i where i.persona = p.persona and i.contacto_tipo = 'MP' limit 1), '') as email
			FROM		sga_docentes a, 
					mdp_personas_documentos b, 
					mdp_personas p
					LEFT JOIN mdp_datos_censales d ON d.persona = p.persona
					LEFT JOIN mdp_datos_personales e ON e.dato_censal = d.dato_censal
					LEFT JOIN mug_localidades f ON f.localidad = e.periodo_lectivo_localidad 
					LEFT JOIN mug_dptos_partidos g ON f.dpto_partido = g.dpto_partido
					LEFT JOIN mug_provincias h ON g.provincia = h.provincia
					LEFT JOIN mug_paises j ON h.pais = j.pais
			WHERE 		a.persona = p.persona AND
					p.persona = b.persona AND
					b.tipo_documento = 3 AND
					a.estado = 'A'
			ORDER BY 	a.docente
		";
		$res = guarani_db::consultar($sql, null, false);

		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INMDOC (USR_INMDOC_IDDOCE, USR_INMDOC_NOMBRE, USR_INMDOC_TIPDOC, USR_INMDOC_NRODOC,USR_INMDOC_DIRECC, USR_INMDOC_CODPAI,
				USR_INMDOC_CODPOS, USR_INMDOC_DIREML, USR_INMDOC_CNDIVA, USR_INMDOC_NROCBU, USR_IN_FECALT, USR_IN_FECMOD, 
				USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
				VALUES (".$row["docente"].",'".$row["nombreyapellido"]."','".$row["tipo_documento"]."','".$row["nro_documento"]."','".$row["domicilio"]."','".
				$row["pais_domicilio"]."','".$row["cp"]."','".$row["email"]."','".$row["cond_iva"]."','".$row["cbu"]."','".$fecha["fecha"]."','".
				$fecha["fecha"]."','".'SIU'."','".'A'."','".'N'."','".'USR_INMDOC'."')";

			$stmt3 = sqlsrv_query($conn, $tsql3);

        		if ($stmt3 == FALSE)
			{ 
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn );  
			}
		 
		}
		sqlsrv_commit( $conn );
	    	sqlsrv_free_stmt($stmt3);
      		sqlsrv_close($conn);
		
		//toba::logger()->info('************ Se insertï¿½ el registro de docentes con ï¿½xito. ************');
	}

	/**
	 * Elimina los datos de la tabla temporal de docentes.
	 * 
	 */

	static function procesar_en_softland_y_borrar()
	{

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

  		$sql = "Exec dbo.STR_PRO_INTERFAZ_DOCENTES";
		
		$stmt = sqlsrv_prepare($conn, $sql);
		if ($stmt == false)  
    			die(print_r(sqlsrv_errors(), true)); 

		$stmt = sqlsrv_execute( $stmt );
		if ($stmt == false)  
    			die(print_r(sqlsrv_errors(), true));  
			
  		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);

		//toba::logger()->info('************ Se eliminaron los registros de docentes con ï¿½xito. ************');
	}

	/**
	 * Inserta los registros en la tabla de transacciones de Softland.
	 * 
	 */

	static function insertar_transacciones_diplomaturas()
	{

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu as nro_tarjeta_alu, 
					nro_cbu_alu as nro_cbu_alu, 
					cuit, 
					porcentaje_facturacion_emp, 
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					email_emp,
					medio_pago_emp, 
					nro_tarjeta_emp as nro_tarjeta_emp, 
					nro_cbu_emp as nro_cbu_emp, 
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_facturacion_diplomatura
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_diplomatura.procesado = 'N'		
		";
		$res = guarani_db::consultar($sql, null, false);

		$nro_reg = 1;
		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INRSIU (USR_INRSIU_NROREG, USR_INRSIU_IDALUM, USR_INRSIU_IDSEDE, USR_INRSIU_IDCARR, USR_INRSIU_IDCONC, 
					USR_INRSIU_CANTID, USR_INRSIU_TOTMAT, USR_INRSIU_NROMAT, USR_INRSIU_ANOMAT, USR_INRSIU_MEDPAG, USR_INRSIU_NROTAR, 
					USR_INRSIU_NROCBU, USR_INRSIU_NRODOC, USR_INRSIU_POREMP, USR_INRSIU_PORBE1, USR_INRSIU_PORBE2, USR_INRSIU_PORBE3, 
					USR_INRSIU_PORBE4, USR_INRSIU_MAIEMP, USR_INRSIU_MEDPAE, USR_INRSIU_NROTAE, USR_INRSIU_NROCBE, USR_INRSIU_TOTCUO, 
					USR_INRSIU_NROCUO, USR_INRSIU_AGRUPA, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
					VALUES (" . $nro_reg.",".$row["alumno"].",".$row["sede"].",".$row["propuesta"].",".$row["concepto"].",".
					$row["cantidad"].",".$row["tot_mat"].",".$row["nro_mat"].",".$row["anio"].",'".$row["medio_pago_alu"]."','".$row["nro_tarjeta_alu"]."','".
					$row["nro_cbu_alu"]."','".$row["cuit"]."',".$row["porcentaje_facturacion_emp"].",".$row["beca_mat"].",".$row["beca_cuota"].",".
					$row["bonif_mat"].",".$row["bonif_cuota"].",'".$row["email_emp"]."','".$row["medio_pago_emp"]."','".$row["nro_tarjeta_emp"]."','".
					$row["nro_cbu_emp"]."',".$row["total_cuotas"].",".$row["nro_cuota"].",'".$row["agrupa_facturas"]."','".$fecha["fecha"]."','".$fecha["fecha"]."','".
					'SIU'."','".'A'."','".'N'."','".'USR_INRSIU'."')";
			
			$stmt3 = sqlsrv_query($conn, $tsql3);
			
        		if ($stmt3 == FALSE) 
			{
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn ); 
			}			
			
			$nro_reg++;
		}	
		sqlsrv_commit( $conn );
        	sqlsrv_free_stmt($stmt3);
        	sqlsrv_close($conn);

		$sql = "select negocio_nsg.f_actualizar_transacciones('negocio_nsg.nsg_facturacion_diplomatura')";
		guarani_db::consultar($sql);
	}

	/**
	 * Inserta los registros en la tabla de transacciones de Softland.
	 * 
	 */

	static function insertar_transacciones_constancias()
	{

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					solicitud,
					cantidad, 			
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu as nro_tarjeta_alu, 
					nro_cbu_alu as nro_cbu_alu, 
					cuit, 
					porcentaje_facturacion_emp, 
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					email_emp,
					medio_pago_emp, 
					nro_tarjeta_emp as nro_tarjeta_emp, 
					nro_cbu_emp as nro_cbu_emp, 
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_facturacion_constancias
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_constancias.procesado = 'N'	
		";
		$res = guarani_db::consultar($sql, null, false);

		$nro_reg = 1;
		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INRSIU (USR_INRSIU_NROREG, USR_INRSIU_IDALUM, USR_INRSIU_IDSEDE, USR_INRSIU_IDCARR, USR_INRSIU_IDCONC, 
					USR_INRSIU_CANTID, USR_INRSIU_TOTMAT, USR_INRSIU_NROMAT, USR_INRSIU_ANOMAT, USR_INRSIU_MEDPAG, USR_INRSIU_NROTAR, 
					USR_INRSIU_NROCBU, USR_INRSIU_NRODOC, USR_INRSIU_POREMP, USR_INRSIU_PORBE1, USR_INRSIU_PORBE2, USR_INRSIU_PORBE3, 
					USR_INRSIU_PORBE4, USR_INRSIU_MAIEMP, USR_INRSIU_MEDPAE, USR_INRSIU_NROTAE, USR_INRSIU_NROCBE, USR_INRSIU_TOTCUO, 
					USR_INRSIU_NROCUO, USR_INRSIU_AGRUPA, USR_INRSIU_NROSOL, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
					VALUES (" . $nro_reg.",".$row["alumno"].",".$row["sede"].",".$row["propuesta"].",".$row["concepto"].",".
					$row["cantidad"].",".$row["tot_mat"].",".$row["nro_mat"].",".$row["anio"].",'".$row["medio_pago_alu"]."','".$row["nro_tarjeta_alu"]."','".
					$row["nro_cbu_alu"]."','".$row["cuit"]."',".$row["porcentaje_facturacion_emp"].",".$row["beca_mat"].",".$row["beca_cuota"].",".
					$row["bonif_mat"].",".$row["bonif_cuota"].",'".$row["email_emp"]."','".$row["medio_pago_emp"]."','".$row["nro_tarjeta_emp"]."','".
					$row["nro_cbu_emp"]."',".$row["total_cuotas"].",".$row["nro_cuota"].",'".$row["agrupa_facturas"]."',".$row["solicitud"].",'".$fecha["fecha"]."','".$fecha["fecha"]."','".
					'SIU'."','".'A'."','".'N'."','".'USR_INRSIU'."')";
			$stmt3 = sqlsrv_query($conn, $tsql3);
			
        		if ($stmt3 == FALSE) 
			{
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn ); 
			}			
			  
			$nro_reg++;
		}	
		sqlsrv_commit( $conn ); 
        	sqlsrv_free_stmt($stmt3);
        	sqlsrv_close($conn);

		$sql = "select negocio_nsg.f_actualizar_transacciones('negocio_nsg.nsg_facturacion_constancias')";
		guarani_db::consultar($sql);
	}

	/**
	 * Inserta los registros en la tabla de transacciones de Softland.
	 * 
	 */

	static function insertar_transacciones_examenes()
	{

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu as nro_tarjeta_alu, 
					nro_cbu_alu as nro_cbu_alu, 
					cuit, 
					porcentaje_facturacion_emp, 
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					email_emp,
					medio_pago_emp, 
					nro_tarjeta_emp as nro_tarjeta_emp, 
					nro_cbu_emp as nro_cbu_emp, 
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_facturacion_examenes
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_examenes.procesado = 'N'		
		";
		$res = guarani_db::consultar($sql, null, false);

		$nro_reg = 1;
		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INRSIU (USR_INRSIU_NROREG, USR_INRSIU_IDALUM, USR_INRSIU_IDSEDE, USR_INRSIU_IDCARR, USR_INRSIU_IDCONC, 
					USR_INRSIU_CANTID, USR_INRSIU_TOTMAT, USR_INRSIU_NROMAT, USR_INRSIU_ANOMAT, USR_INRSIU_MEDPAG, USR_INRSIU_NROTAR, 
					USR_INRSIU_NROCBU, USR_INRSIU_NRODOC, USR_INRSIU_POREMP, USR_INRSIU_PORBE1, USR_INRSIU_PORBE2, USR_INRSIU_PORBE3, 
					USR_INRSIU_PORBE4, USR_INRSIU_MAIEMP, USR_INRSIU_MEDPAE, USR_INRSIU_NROTAE, USR_INRSIU_NROCBE, USR_INRSIU_TOTCUO, 
					USR_INRSIU_NROCUO, USR_INRSIU_AGRUPA, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
					VALUES (" . $nro_reg.",".$row["alumno"].",".$row["sede"].",".$row["propuesta"].",".$row["concepto"].",".
					$row["cantidad"].",".$row["tot_mat"].",".$row["nro_mat"].",".$row["anio"].",'".$row["medio_pago_alu"]."','".$row["nro_tarjeta_alu"]."','".
					$row["nro_cbu_alu"]."','".$row["cuit"]."',".$row["porcentaje_facturacion_emp"].",".$row["beca_mat"].",".$row["beca_cuota"].",".
					$row["bonif_mat"].",".$row["bonif_cuota"].",'".$row["email_emp"]."','".$row["medio_pago_emp"]."','".$row["nro_tarjeta_emp"]."','".
					$row["nro_cbu_emp"]."',".$row["total_cuotas"].",".$row["nro_cuota"].",'".$row["agrupa_facturas"]."','".$fecha["fecha"]."','".$fecha["fecha"]."','".
					'SIU'."','".'A'."','".'N'."','".'USR_INRSIU'."')";
			
			$stmt3 = sqlsrv_query($conn, $tsql3);
			
        		if ($stmt3 == FALSE) 
			{
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn ); 
			}			
			
			$nro_reg++;
		}	
		sqlsrv_commit( $conn );
        	sqlsrv_free_stmt($stmt3);
        	sqlsrv_close($conn);

		$sql = "select negocio_nsg.f_actualizar_transacciones('negocio_nsg.nsg_facturacion_examenes')";
		guarani_db::consultar($sql);

	}

	/**
	 * Inserta los registros en la tabla de transacciones de Softland.
	 * 
	 */

	static function insertar_transacciones_grado()
	{

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu, 
					nro_cbu_alu, 
					cuit, 
					porcentaje_facturacion_emp, 
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					email_emp,
					medio_pago_emp, 
					nro_tarjeta_emp, 
					nro_cbu_emp, 
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_facturacion_grado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_grado.procesado = 'N'		
		";
		$res = guarani_db::consultar($sql, null, false);

		$nro_reg = 1;
		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INRSIU (USR_INRSIU_NROREG, USR_INRSIU_IDALUM, USR_INRSIU_IDSEDE, USR_INRSIU_IDCARR, USR_INRSIU_IDCONC, 
					USR_INRSIU_CANTID, USR_INRSIU_TOTMAT, USR_INRSIU_NROMAT, USR_INRSIU_ANOMAT, USR_INRSIU_MEDPAG, USR_INRSIU_NROTAR, 
					USR_INRSIU_NROCBU, USR_INRSIU_NRODOC, USR_INRSIU_POREMP, USR_INRSIU_PORBE1, USR_INRSIU_PORBE2, USR_INRSIU_PORBE3, 
					USR_INRSIU_PORBE4, USR_INRSIU_MAIEMP, USR_INRSIU_MEDPAE, USR_INRSIU_NROTAE, USR_INRSIU_NROCBE, USR_INRSIU_TOTCUO, 
					USR_INRSIU_NROCUO, USR_INRSIU_AGRUPA, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
					VALUES (" . $nro_reg.",".$row["alumno"].",".$row["sede"].",".$row["propuesta"].",".$row["concepto"].",".
					$row["cantidad"].",".$row["tot_mat"].",".$row["nro_mat"].",".$row["anio"].",'".$row["medio_pago_alu"]."','".$row["nro_tarjeta_alu"]."','".
					$row["nro_cbu_alu"]."','".$row["cuit"]."',".$row["porcentaje_facturacion_emp"].",".$row["beca_mat"].",".$row["beca_cuota"].",".
					$row["bonif_mat"].",".$row["bonif_cuota"].",'".$row["email_emp"]."','".$row["medio_pago_emp"]."','".$row["nro_tarjeta_emp"]."','".
					$row["nro_cbu_emp"]."',".$row["total_cuotas"].",".$row["nro_cuota"].",'".$row["agrupa_facturas"]."','".$fecha["fecha"]."','".$fecha["fecha"]."','".
					'SIU'."','".'A'."','".'N'."','".'USR_INRSIU'."')";
			
			$stmt3 = sqlsrv_query($conn, $tsql3);
			
        		if ($stmt3 == FALSE) 
			{
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn ); 
			}			
			  
			$nro_reg++;
		}	
		sqlsrv_commit( $conn );
        	sqlsrv_free_stmt($stmt3);
        	sqlsrv_close($conn);

		$sql = "select negocio_nsg.f_actualizar_transacciones('negocio_nsg.nsg_facturacion_grado')";
		guarani_db::consultar($sql);

	}

	/**
	 * Inserta los registros en la tabla de transacciones de Softland.
	 * 
	 */

	static function insertar_transacciones_posgrado()
	{

		$sql = "SELECT DISTINCT	alumno, 
					sede,
					propuesta, 
					concepto, 
					cantidad, 
					tot_mat,
					nro_mat,
					anio,
					medio_pago_alu,
					nro_tarjeta_alu as nro_tarjeta_alu, 
					nro_cbu_alu as nro_cbu_alu, 
					cuit, 
					porcentaje_facturacion_emp, 
					beca_mat, 
					beca_cuota, 
					bonif_mat, 
					bonif_cuota,
					email_emp,
					medio_pago_emp, 
					nro_tarjeta_emp as nro_tarjeta_emp, 
					nro_cbu_emp as nro_cbu_emp, 
					total_cuotas,
					nro_cuota,
					agrupa_facturas
			FROM 		negocio_nsg.nsg_facturacion_posgrado
			WHERE		alumno not in (select alumno from sga_alumnos a, mdp_personas_documentos b 
								where a.persona = b.persona and b.nro_documento = '99999999')
			AND 		negocio_nsg.nsg_facturacion_posgrado.procesado = 'N'		
		";
		$res = guarani_db::consultar($sql, null, false);

		$nro_reg = 1;
		$sql = "select date(now()) as fecha";
		$fecha = guarani_db::consultar_fila($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$tsql3 = "INSERT INTO dbo.USR_INRSIU (USR_INRSIU_NROREG, USR_INRSIU_IDALUM, USR_INRSIU_IDSEDE, USR_INRSIU_IDCARR, USR_INRSIU_IDCONC, 
					USR_INRSIU_CANTID, USR_INRSIU_TOTMAT, USR_INRSIU_NROMAT, USR_INRSIU_ANOMAT, USR_INRSIU_MEDPAG, USR_INRSIU_NROTAR, 
					USR_INRSIU_NROCBU, USR_INRSIU_NRODOC, USR_INRSIU_POREMP, USR_INRSIU_PORBE1, USR_INRSIU_PORBE2, USR_INRSIU_PORBE3, 
					USR_INRSIU_PORBE4, USR_INRSIU_MAIEMP, USR_INRSIU_MEDPAE, USR_INRSIU_NROTAE, USR_INRSIU_NROCBE, USR_INRSIU_TOTCUO, 
					USR_INRSIU_NROCUO, USR_INRSIU_AGRUPA, USR_IN_FECALT, USR_IN_FECMOD, USR_IN_USERID, USR_IN_ULTOPR, USR_IN_DEBAJA, USR_IN_OALIAS) 
					VALUES (" . $nro_reg.",".$row["alumno"].",".$row["sede"].",".$row["propuesta"].",".$row["concepto"].",".
					$row["cantidad"].",".$row["tot_mat"].",".$row["nro_mat"].",".$row["anio"].",'".$row["medio_pago_alu"]."','".$row["nro_tarjeta_alu"]."','".
					$row["nro_cbu_alu"]."','".$row["cuit"]."',".$row["porcentaje_facturacion_emp"].",".$row["beca_mat"].",".$row["beca_cuota"].",".
					$row["bonif_mat"].",".$row["bonif_cuota"].",'".$row["email_emp"]."','".$row["medio_pago_emp"]."','".$row["nro_tarjeta_emp"]."','".
					$row["nro_cbu_emp"]."',".$row["total_cuotas"].",".$row["nro_cuota"].",'".$row["agrupa_facturas"]."','".$fecha["fecha"]."','".$fecha["fecha"]."','".
					'SIU'."','".'A'."','".'N'."','".'USR_INRSIU'."')";
			
			$stmt3 = sqlsrv_query($conn, $tsql3);
			
        		if ($stmt3 == FALSE) 
			{
				die(print_r( sqlsrv_errors(), true));
				sqlsrv_rollback( $conn ); 
			}			
			 
			$nro_reg++;
		}	
		sqlsrv_commit( $conn ); 
        	sqlsrv_free_stmt($stmt3);
        	sqlsrv_close($conn);

		$sql = "select negocio_nsg.f_actualizar_transacciones('negocio_nsg.nsg_facturacion_posgrado')";
		guarani_db::consultar($sql);

	}

	/**
	 * Chequea si la tabla temporal de Softlan estï¿½ vacï¿½a, en cuyo caso puede proceder a insertar los registros.
	 * 
	 */

	static function chequear_tabla_temporal()
	{

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

		$sql = "SELECT COUNT(*) as cantidad FROM dbo.USR_INRSIU";

		$stmt = sqlsrv_query($conn, $sql);

        	if ($stmt == FALSE)
               		die(print_r( sqlsrv_errors(), true));
		
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		$cant_reg = $row['cantidad'];

		if ($cant_reg == 0)
			return 1;
		else 
			return 0;

  		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);

		//toba::logger()->info('************ Se eliminaron los registros de morosos con ï¿½xito. ************');
	}

	/**
	 * Retorna el listado de nombres de Alumnos que no pagaron la rematriculacion
	 * 
	 */
	function get_estado_pago_alumno_rematriculacion_agostinos($alumno, $sede, $propuesta, $concepto, $anio, $nrosol)
	{
		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

		$sql = "exec dbo.STR_SEL_CONCEPTOPAGADO ".$alumno.",".$sede.",".$propuesta.",".$concepto.",".$anio.",".$nrosol;		
		$stmt = sqlsrv_query($conn, $sql);

        	if ($stmt == FALSE)
               		die(print_r( sqlsrv_errors(), true));
		
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		$pago = $row['PAGO'];

		return $pago;

  		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);

		//toba::logger()->info('************ Se eliminaron los registros de morosos con ï¿½xito. ************');
	}	

	/**
	 * Retorna el listado de nombres de Alumnos que no pagaron la rematriculacion
	 * 
	 */
	function get_estado_pago_alumno_rematriculacion($alumno, $sede, $propuesta, $concepto, $anio)
	{
		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
            		die(print_r( sqlsrv_errors(), true));

		$sql = "exec dbo.STR_SEL_COBRANZACONCEPTO ".$alumno.",".$sede.",".$propuesta.",".$concepto.",".$anio;		
		$stmt = sqlsrv_query($conn, $sql);

        	if ($stmt == FALSE)
               		die(print_r( sqlsrv_errors(), true));
		
		$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
		
		return $row;

  		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);

		//toba::logger()->info('************ Se eliminaron los registros de morosos con ï¿½xito. ************');
	}	

	/**
	 * Actualiza la tabla de certificados de egreso con la informaciï¿½n de pago del mismo
	 * 
	 */
	function actualizar_estado_pago_certificacion_egreso()
	{

		$sql = "SELECT DISTINCT nro_solicitud as nrosol, 
				sga_certificados_otorg.alumno,
				propuesta,
				ubicacion as sede,	
				5 as concepto,
				coalesce((select max(ep.anio_de_cursada) from sga_insc_cursada ic, sga_comisiones sc, sga_periodos_lectivos pl, sga_periodos p, sga_elementos_revision er, sga_elementos_plan ep
					 	where ic.alumno = sga_alumnos.alumno and ic.comision = sc.comision and sc.periodo_lectivo = pl.periodo_lectivo and ic.plan_version = ep.plan_version 
					 	and ep.elemento_revision = er.elemento_revision and er.elemento = sc.elemento and p.periodo = pl.periodo 
					 	and now() between p.fecha_inicio and p.fecha_fin),1) as anio

			FROM sga_certificados_otorg
			LEFT JOIN negocio_nsg.sga_certificados_otorg_ext ON negocio_nsg.sga_certificados_otorg_ext.id_solicitud = sga_certificados_otorg.nro_solicitud
			JOIN sga_alumnos ON sga_certificados_otorg.alumno = sga_alumnos.alumno
		";

		$res = guarani_db::consultar($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$solicitud = $row['nrosol'];

			$sql = "exec dbo.STR_SEL_CONCEPTOPAGADO ".$row['alumno'].",".$row['sede'].",".$row['propuesta'].",".$row['concepto'].",".$row['anio'].",".$row['nrosol'];		
			$stmt = sqlsrv_query($conn, $sql);
			
			if ($stmt == FALSE)
        			die(print_r( sqlsrv_errors(), true));
		
			$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
			$pago = $result['PAGO'];
			$pago = toba::db()->quote($pago);

			$sql = "update negocio_nsg.sga_certificados_otorg_ext set pago = $pago where id_solicitud = $solicitud ";
			guarani_db::ejecutar($sql);	
		
  		}
		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);
	}	


	/**
	 * Actualiza la tabla de solicitudes de constancia con la informaciï¿½n de pago de la misma
	 * 
	 */
	function actualizar_estado_pago_constancias()
	{

		$sql = "SELECT DISTINCT solicitud as nrosol, 
				sga_constancias_solicitud.alumno,
				propuesta,
				ubicacion as sede,	
				negocio_nsg.sga_constancias_ext.concepto,
				coalesce((select max(ep.anio_de_cursada) from sga_insc_cursada ic, sga_comisiones sc, sga_periodos_lectivos pl, sga_periodos p, sga_elementos_revision er, sga_elementos_plan ep
					 	where ic.alumno = sga_alumnos.alumno and ic.comision = sc.comision and sc.periodo_lectivo = pl.periodo_lectivo and ic.plan_version = ep.plan_version 
					 	and ep.elemento_revision = er.elemento_revision and er.elemento = sc.elemento and p.periodo = pl.periodo 
					 	and now() between p.fecha_inicio and p.fecha_fin),1) as anio
			FROM sga_constancias_solicitud
			JOIN negocio_nsg.sga_constancias_solicitud_ext ON negocio_nsg.sga_constancias_solicitud_ext.id_solicitud = sga_constancias_solicitud.solicitud
			JOIN sga_constancias ON sga_constancias_solicitud.constancia = sga_constancias.constancia
			JOIN negocio_nsg.sga_constancias_ext ON negocio_nsg.sga_constancias_ext.id_constancia = sga_constancias.constancia
			JOIN sga_alumnos ON sga_constancias_solicitud.alumno = sga_alumnos.alumno
			WHERE negocio_nsg.sga_constancias_ext.concepto is not null		
		";

		$res = guarani_db::consultar($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$solicitud = $row['nrosol'];

			$sql = "exec dbo.STR_SEL_CONCEPTOPAGADO ".$row['alumno'].",".$row['sede'].",".$row['propuesta'].",".$row['concepto'].",".$row['anio'].",".$row['nrosol'];		
			$stmt = sqlsrv_query($conn, $sql);
			
			if ($stmt == FALSE)
        			die(print_r( sqlsrv_errors(), true));
		
			$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
			$pago = $result['PAGO'];
			$pago = toba::db()->quote($pago);

			$sql = "update negocio_nsg.sga_constancias_solicitud_ext set pago = $pago where id_solicitud = $solicitud ";
			guarani_db::ejecutar($sql);	
  		}
		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);
	}	




	function actualizar_estado_pago_constancias2($where_base = '')
	{
		$where = "WHERE negocio_nsg.sga_constancias_ext.concepto is not null";

		if ($where_base != '')
		{
			$where .= " AND ".$where_base;
		}

		$sql = "SELECT DISTINCT solicitud as nrosol, 
				sga_constancias_solicitud.alumno,
				propuesta,
				ubicacion as sede,	
				negocio_nsg.sga_constancias_ext.concepto,
				coalesce((select max(ep.anio_de_cursada) from sga_insc_cursada ic, sga_comisiones sc, sga_periodos_lectivos pl, sga_periodos p, sga_elementos_revision er, sga_elementos_plan ep
					 	where ic.alumno = sga_alumnos.alumno and ic.comision = sc.comision and sc.periodo_lectivo = pl.periodo_lectivo and ic.plan_version = ep.plan_version 
					 	and ep.elemento_revision = er.elemento_revision and er.elemento = sc.elemento and p.periodo = pl.periodo 
					 	and now() between p.fecha_inicio and p.fecha_fin),1) as anio
			FROM sga_constancias_solicitud
			JOIN negocio_nsg.sga_constancias_solicitud_ext ON negocio_nsg.sga_constancias_solicitud_ext.id_solicitud = sga_constancias_solicitud.solicitud
			JOIN sga_constancias ON sga_constancias_solicitud.constancia = sga_constancias.constancia
			JOIN negocio_nsg.sga_constancias_ext ON negocio_nsg.sga_constancias_ext.id_constancia = sga_constancias.constancia
			JOIN sga_alumnos ON sga_constancias_solicitud.alumno = sga_alumnos.alumno
			$where
			--WHERE negocio_nsg.sga_constancias_ext.concepto is not null		
			--AND (sga_constancias_solicitud.estado = 'Pendiente' OR sga_constancias_solicitud.estado = 'Aceptado')
		";

		$res = guarani_db::consultar($sql, null, false);

		// Connect using Windows Authentication. 
		$conn = new conexion_con_softland_nsg();
		$conn = $conn->conectar(); 

		if($conn == false)
         		die(print_r( sqlsrv_errors(), true));

        	if (sqlsrv_begin_transaction($conn) == FALSE)
            		die(print_r( sqlsrv_errors(), true));

		foreach($res as $row) 
		{
			$solicitud = $row['nrosol'];

			$sql = "exec dbo.STR_SEL_CONCEPTOPAGADO ".$row['alumno'].",".$row['sede'].",".$row['propuesta'].",".$row['concepto'].",".$row['anio'].",".$row['nrosol'];		
			$stmt = sqlsrv_query($conn, $sql);
			
			if ($stmt == FALSE)
        			die(print_r( sqlsrv_errors(), true));
		
			$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
			$pago = $result['PAGO'];
			$pago = toba::db()->quote($pago);

			$sql = "update negocio_nsg.sga_constancias_solicitud_ext set pago = $pago where id_solicitud = $solicitud ";
			guarani_db::ejecutar($sql);	
  		}
		sqlsrv_free_stmt($stmt);
        	sqlsrv_close($conn);
	}	

}

?>