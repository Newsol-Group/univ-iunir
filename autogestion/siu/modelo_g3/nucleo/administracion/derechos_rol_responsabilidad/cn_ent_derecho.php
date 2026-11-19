<?php
/**
 * 
 * @package Administración
 */
class cn_ent_derecho extends cn_entidad
{
	function hay_cambios()
	{
		return ($this->dep('derechos_comision')->hay_cambios() || $this->dep('derechos_mesa_examen')->hay_cambios());
	}

	//------------------------------------------------------------------------
	//-- Derechos por Responsabilidad para Comisiones
	//------------------------------------------------------------------------

	function get_datos_derechos_comision($condiciones=null)
	{
		return $this->dep('derechos_comision')->get_filas($condiciones);
	}

	function set_datos_derechos_comision($datos, $condiciones=null)
	{
		$derechos = $this->dep('derechos_comision')->get_filas($condiciones);
		$derechos_aplanados = aplanar_matriz($derechos, 'derecho');
		$derechos_a_borrar = array_diff($derechos_aplanados, $datos);
		$derechos_a_insertar = array_diff($datos, $derechos_aplanados);

		$derechos_asociativos = rs_convertir_asociativo($derechos, array('derecho'), apex_datos_clave_fila);
		foreach ($derechos_a_borrar as $derecho) {
			$this->dep('derechos_comision')->eliminar_fila($derechos_asociativos[$derecho]);
		}

		foreach ($derechos_a_insertar as $derecho) {
			$this->dep('derechos_comision')->nueva_fila(array('derecho' => $derecho, 'responsabilidad' => $condiciones['responsabilidad']));
		}
	}

	function sincronizar_derechos_comision()
	{
		$this->dep('derechos_comision')->sincronizar();
	}

	//------------------------------------------------------------------------
	//-- Derechos por Rol para Mesas de Examen
	//------------------------------------------------------------------------

	function get_datos_derechos_mesa_examen($condiciones=null)
	{
        return $this->dep('derechos_mesa_examen')->get_filas($condiciones);
	}

	function set_datos_derechos_mesa_examen($datos, $condiciones=null)
	{
		$derechos = $this->dep('derechos_mesa_examen')->get_filas($condiciones);
		$derechos_aplanados = aplanar_matriz($derechos, 'derecho');
		$derechos_a_borrar = array_diff($derechos_aplanados, $datos);
		$derechos_a_insertar = array_diff($datos, $derechos_aplanados);

		$derechos_asociativos = rs_convertir_asociativo($derechos, array('derecho'), apex_datos_clave_fila);
		foreach ($derechos_a_borrar as $derecho) {
			$this->dep('derechos_mesa_examen')->eliminar_fila($derechos_asociativos[$derecho]);
		}

		foreach ($derechos_a_insertar as $derecho) {
			$this->dep('derechos_mesa_examen')->nueva_fila(array('derecho' => $derecho, 'rol' => $condiciones['rol']));
		}
	}

	function sincronizar_derechos_mesa_examen()
	{
		$this->dep('derechos_mesa_examen')->sincronizar();
	}
}
?>
