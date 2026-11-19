<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Autoridades Firmantes
 * 
 */
class cn_ent_autoridad extends cn_entidad
{
	//------------------------------------------------------------------------
	//-- Autoridades Firmantes
	//------------------------------------------------------------------------

	function get_datos($tabla=null)
	{
		return $this->tabla('autoridades')->get();
	}

	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->tabla('autoridades')->set($datos);
	}

	//------------------------------------------------------------------------
	//-- Responsables Académicas
	//------------------------------------------------------------------------

	function get_datos_responsables_academicas()
	{
        return $this->tabla('autoridades_ra')->get_filas();
	}

	function set_datos_responsables_academicas($datos)
	{
		$ras = $this->tabla('autoridades_ra')->get_filas();
		$ras_aplanadas = aplanar_matriz($ras, 'responsable_academica');
		$ras_a_borrar = array_diff($ras_aplanadas, $datos);
		$ras_a_insertar = array_diff($datos, $ras_aplanadas);

		$ras_asociativas = rs_convertir_asociativo($ras, array('responsable_academica'), apex_datos_clave_fila);
		foreach ($ras_a_borrar as $ra) {
			$this->tabla('autoridades_ra')->eliminar_fila($ras_asociativas[$ra]);
		}

		foreach ($ras_a_insertar as $ra) {
			$this->tabla('autoridades_ra')->nueva_fila(array('responsable_academica' => $ra));
		}
	}
}
?>