<?php
/**
 *
 *
 * @package Asistencias
 * @subpackage 
 */
class ci_edi_excepciones_asistencia extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		if ($this->cn()->entidad()->es_nueva()) {
			$this->controlador()->pantalla('edicion')->eliminar_evento('eliminar');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- Pantalla Inicial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- formulario -------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
	{
		$datos_comision = $this->controlador()->get_datos_comision();
		$instancias = toba::consulta_php('co_inscripciones_cursadas')->get_instancias_inscripcion($datos_comision['inscripcion']);
		$instancias_aplanado = aplanar_matriz($instancias);

		if (!in_array(comision::inst_cursar, $instancias_aplanado)) {
			$form->ef('porcentaje_regular')->set_solo_lectura();
		} else {
			$form->ef('porcentaje_regular')->set_obligatorio();
		}

		if (!in_array(comision::inst_promocionar, $instancias_aplanado)) {
			$form->ef('porcentaje_promocion')->set_solo_lectura();
		} else {
			$form->ef('porcentaje_promocion')->set_obligatorio();
		}

		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			$form->set_datos($datos);

			if (isset($datos['porcentaje_regular'])) {
				$cant_clases_regular = floor(($datos['porcentaje_regular'] * $datos_comision['cant_clases']) / 100);
				$form->set_datos_defecto(array('cant_clases_regular' => $cant_clases_regular));
			}
			if (isset($datos['porcentaje_promocion'])) {
				$cant_clases_promocion = floor(($datos['porcentaje_promocion'] * $datos_comision['cant_clases']) / 100);
				$form->set_datos_defecto(array('cant_clases_promocion' => $cant_clases_promocion));
			}
		}
	}

	function evt__formulario__modificacion($datos)
	{
		$datos['alumno'] = $this->controlador()->get_preseleccion();
		$datos['comision'] = $this->controlador()->get_comision();

		parent::evt__formulario__modificacion($datos);
	}
}
?>