<?php
/**
 *
 *
 * @package		Asistencias
 * @subpackage	-
 * 
 */
class ci_edi_registrar_asistencia_docentes extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- PANTALLA Inicial 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		// Si no debe indicarse la cantidad de inasistencias, se desactivan los campos correspondientes.
		if (!$this->controlador()->indica_cantidad_inasistencias()) {
			$form->ef('cant_inasistencias')->set_solo_lectura();
		}
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} elseif (!$this->cn()->entidad()->es_nueva()) {
			$form->set_datos($this->cn()->entidad()->get_datos());
		} else {
			// Valores defecto para cantidad de inasistencias y cantidad de justificadas.
			$valor = $this->controlador()->obtener_valor_defecto_cant_inasistencias();
			$form->ef('cant_inasistencias')->set_estado($valor);
			$form->ef('cant_justificadas')->set_estado(0);
		}
	}

}
?>