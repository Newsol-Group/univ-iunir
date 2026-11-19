<?php
/**
 *
 *
 * @package Unidad de Gestion
 * @subpackage Instituciones
 */
class co_instituciones_nsg extends co_instituciones
{
	/**Newsol - Se agrega el tipo de convenio para facturación de la tabla extendida de instituciones
     	*/
	function get_listado($where='')
	{
		if ($where) {
	    	$where = ' AND '.$where;
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
						sga_instituciones.entidad,
						sie.tipo_convenio_fact,
						sie.agrupa_alumnos
				FROM 	sga_instituciones
                			LEFT JOIN negocio_nsg.sga_instituciones_ext sie ON 
                            	sga_instituciones.institucion = sie.id_institucion,
						sga_instituciones_tipos,
				        mug_localidades,
						mug_dptos_partidos,
						mug_provincias,
						mug_paises
				WHERE   sga_instituciones.localidad = mug_localidades.localidad AND 
				        sga_instituciones.tipo_institucion = sga_instituciones_tipos.tipo_institucion  AND
						mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido AND
						mug_dptos_partidos.provincia = mug_provincias.provincia AND
						mug_provincias.pais = mug_paises.pais				        
				$where
				ORDER BY sga_instituciones.nombre  
				";
			        
		return guarani_db::consultar($sql);
	}

	/**Newsol - Se agrega el CUIT, para los casos en que las instituciones sean empresas.
   	*/
	function get_listado_nombres($where='')
	{
		if ($where) {
			$where = ' WHERE '.$where.' ';
		}
		$sql = "SELECT	sga_instituciones.nombre || ' (CUIT:' || coalesce(sga_instituciones.fax, '') || ')' as descr,
				sga_instituciones.institucion as valor
			FROM 	sga_instituciones			
				$where
				ORDER BY sga_instituciones.nombre
			   ";
		
		return guarani_db::consultar($sql);
	}
	
	/**Newsol (NUEVO) - Genera un reporte de instituciones
    	*/
	function get_reporte_instituciones($where='')
	{
		if ($where) {
	    	$where = ' AND '.$where;
		}

		$perfil_datos = toba::manejador_sesiones()->get_perfil_datos_activo();
		if (! empty($perfil_datos)) {
			$where_gatillo = toba::perfil_de_datos()->get_where_dimension_gatillo(toba::proyecto()->get_parametro('fuente_datos'), comunes::dimension_ug, 'sga_propuestas', 'sga_propuestas');
		}
    
	   	$sql = "SELECT	sga_instituciones.institucion,
						sga_instituciones.nombre as institucion_nombre,
						sga_instituciones_tipos.nombre as tipo_institucion_nombre,
						mug_localidades.nombre as localidad_nombre,
						CASE
							WHEN mug_provincias.pais = ".persona::pais_argentina." THEN mug_localidades.nombre
							ELSE mug_localidades.nombre || ' (' || mug_paises.nombre || ')'
						END as localidad_descripcion,
						sga_instituciones.calle,
						sga_instituciones.numero,
						sga_instituciones.codigo_postal,
						sga_instituciones.telefono,
						sga_instituciones.fax as cuit,
						sga_instituciones.email,
						tf.descripcion as nombre_convenio,
						tb.descripcion as nombre_bonif,
						coalesce(f.bonif_matricula,0) as bonif_matricula, 
						coalesce(f.bonif_cuota,0) as bonif_cuota,
						sie.agrupa_alumnos
				FROM 	sga_instituciones
					LEFT JOIN negocio_nsg.sga_instituciones_ext sie ON sga_instituciones.institucion = sie.id_institucion
					LEFT JOIN negocio_nsg.nsg_bonif_x_convenio f ON sie.tipo_convenio_fact = f.tipo_convenio_fact
					LEFT JOIN negocio_nsg.nsg_tipo_bonif tb ON f.tipo_bonif = tb.tipo_bonif
					LEFT JOIN negocio_nsg.nsg_tipo_convenio_fact tf ON f.tipo_convenio_fact = tf.tipo_convenio_fact,	
					sga_instituciones_tipos,
				        mug_localidades,
					mug_dptos_partidos,
					mug_provincias,
					mug_paises
				WHERE   sga_instituciones.localidad = mug_localidades.localidad AND 
				        sga_instituciones.tipo_institucion = sga_instituciones_tipos.tipo_institucion  AND
						mug_localidades.dpto_partido = mug_dptos_partidos.dpto_partido AND
						mug_dptos_partidos.provincia = mug_provincias.provincia AND
						mug_provincias.pais = mug_paises.pais
				        $where
				ORDER BY sga_instituciones.nombre  
				";
			        
		return guarani_db::consultar($sql);
	}

	
}
?>