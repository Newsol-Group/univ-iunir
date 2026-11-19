<?php
/**
 *
 * Representa una propuesta curricular
 *
 * @package 	Propuestas Formativas
 * @subpackage 	-
 * 
 */
class cn_ent_propuestas extends cn_entidad
{
	function sincronizar()
	{
		// Se realiza la sincronización
		$salida = parent::sincronizar();
		// Se vacía la caché de parámetros, ya que los cambios originados pueden
		// llegar a cambiar los escenarios de resolución de parámetros (ej: cambia
		// el tipo de propuesta o las RA's asociadas).
		guarani::cache_parametros()->vaciar_cache_parametros();
		// Se retorna la salida original
		return $salida;
	}
	function verificar_editabilidad()
	{
		$estado = $this->get_columna('estado');
		if ($estado == propuesta::estado_baja) {
			$this->bloquear_edicion();
		} else {
			$this->habilitar_edicion();
		}
	}

	function validar_editabilidad()	{}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('propuestas')->set($datos);
		$this->set_datos_areas_tem($datos['areas_tematicas']);
	}

	function get_datos($tabla=null)
	{
		return $this->tabla('propuestas')->get();
	}

	function get_columna($columna)
	{
		return $this->tabla('propuestas')->get_columna($columna);
	}

	function posee_planes_activos()
	{
		$datos = $this->get_datos();
		return toba::consulta_php('co_planes')->propuesta_posee_plan_version_activo($datos['propuesta']);
	}

	function get_datos_areas_tem()
	{
        return $this->tabla('areas_tem')->get_filas();
	}

	function set_datos_areas_tem($datos)
	{
		$areas = $this->tabla('areas_tem')->get_filas();
		$areas_aplanadas = aplanar_matriz($areas, 'area_tematica');
		$areas_a_borrar = array_diff($areas_aplanadas, $datos);
		$areas_a_insertar = array_diff($datos, $areas_aplanadas);

		$areas_asociativas = rs_convertir_asociativo($areas, array('area_tematica'), apex_datos_clave_fila);
		foreach ($areas_a_borrar as $area) {
			$this->tabla('areas_tem')->eliminar_fila($areas_asociativas[$area]);
		}

		foreach ($areas_a_insertar as $area) {
			$this->tabla('areas_tem')->nueva_fila(array('area_tematica' => $area));
		}
	}

	function get_g3entidad_subtipo()
	{
		return g3entidad::subtipo_propuesta;
	}

	function get_datos_ras()
	{
		return $this->tabla('resp_academicas')->get_filas();
	}

	function agregar_ra($datos_ra)
	{
		$this->tabla('resp_academicas')->nueva_fila($datos_ra);
	}

	function eliminar_ra($id_fila_ra)
	{
		$this->tabla('resp_academicas')->eliminar_fila($id_fila_ra);
	}

	function get_datos_ofertas()
	{
        return $this->tabla('ofertas')->get_filas();
	}

	function set_datos_ofertas($datos)
	{
		$this->tabla('ofertas')->procesar_filas($datos);
	}
	
	
	//----------------------------------------------------------------------------

	function validar_datos($datos)
	{
		$propuesta = $this->tabla('propuestas')->get_columna('propuesta');
		$this->validar_codigo_propuesta($datos['codigo'], $propuesta);
		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_codigo_propuesta($codigo, $propuesta=null)
	{
		if (!toba::consulta_php('co_propuestas')->validar_codigo_propuesta($codigo, $propuesta)) {
			$this->set_error('dato_duplicado',array('código','certificado'));
		}
	}
	
}
?>