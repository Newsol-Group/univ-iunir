<?php
/**
 *
 *
 * @package 	Matricula
 * @subpackage 	Alumnos
 * 
 */
class cn_ent_orientacion extends cn_entidad
{
	function set_datos_orientaciones($alumno, $datos, $plan)
	{
		$orientaciones = $this->tabla('orientaciones')->get_filas();
		$orientaciones_aplanadas = aplanar_matriz($orientaciones, 'orientacion');
		$orientaciones_a_borrar = array_diff($orientaciones_aplanadas, $datos['orientacion']);
		$orientaciones_a_insertar = array_diff($datos['orientacion'], $orientaciones_aplanadas);

		$orientaciones_asociativas = rs_convertir_asociativo($orientaciones, array('orientacion'), apex_datos_clave_fila);
		foreach ($orientaciones_a_borrar as $orientacion) {
			$this->tabla('orientaciones')->eliminar_fila($orientaciones_asociativas[$orientacion]);
		}

		foreach ($orientaciones_a_insertar as $orientacion) {
			$this->tabla('orientaciones')->nueva_fila(array('alumno' => $alumno, 'orientacion' => $orientacion, 'plan' => $plan));
		}
	}

	function get_datos($tabla=null)
	{
        return $this->tabla('orientaciones')->get_filas();
	}

	/**
	 * Se redefine la función sincronizar del cn_entidad ya que este DR tiene una único DT.
	 * No puede realizarse la sincronización de la relación por no tener un único registro en la tabla cabecera.
	 *
	 */
	function sincronizar()
	{
		$this->tabla('orientaciones')->sincronizar();
	}
}
?>