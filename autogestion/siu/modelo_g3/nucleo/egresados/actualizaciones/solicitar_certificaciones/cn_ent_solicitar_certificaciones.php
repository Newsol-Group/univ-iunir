<?php
/**
 *
 *
 * @package		Egresados
 * @subpackage	Actualizaciones
 * 
 */
class cn_ent_solicitar_certificaciones extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar = true)
	{
		$this->validar_datos($datos);
		$this->tabla('certificado_otorg')->set($datos);
	}

	function get_datos($tabla = null)
	{
		return $this->tabla('certificado_otorg')->get();
	}

	function get_columna($columna)
	{
		return $this->tabla('certificado_otorg')->get_columna($columna);
	}

	//--------------------------------------------------------------------------
	//---- Menciones -----------------------------------------------------------
	//--------------------------------------------------------------------------

	function set_datos_menciones($datos)
	{
		$menciones = $this->tabla('menciones')->get_filas();
		$menciones_aplanadas = aplanar_matriz($menciones, 'mencion');
		$menciones_a_borrar = array_diff($menciones_aplanadas, $datos);
		$menciones_a_insertar = array_diff($datos, $menciones_aplanadas);
		$menciones_asociativas = rs_convertir_asociativo($menciones, array('mencion'), apex_datos_clave_fila);

		foreach ($menciones_a_borrar as $mencion) {
			$this->tabla('menciones')->eliminar_fila($menciones_asociativas[$mencion]);
		}

		foreach ($menciones_a_insertar as $mencion) {
			$this->tabla('menciones')->nueva_fila(array('mencion' => $mencion));
		}
	}

	function get_datos_menciones()
	{
		return $this->tabla('menciones')->get_filas();
	}

	//--------------------------------------------------------------------------

	function validar_datos($datos)
	{
		$this->validar_fecha_inicio_tramite($datos['fecha_inicio_tramite']);

		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}

	function validar_fecha_inicio_tramite($fecha)
	{
		if (guarani_fecha::es_mayor($fecha, guarani_fecha::get_hoy())) {
			$this->set_error('800_control_fechas', array('Inicio Trámite', 'mayor', 'actual'));
		}
	}

}
?>