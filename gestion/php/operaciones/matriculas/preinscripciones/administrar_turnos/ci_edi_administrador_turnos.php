<?php
class ci_edi_administrador_turnos extends guarani_pers_ci
{
	function conf()
	{
		if ($this->controlador()->es_edicion()) {
			$this->dep('formulario')->set_solo_lectura(['turno_config']);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(formulario $form)
	{
		if ($this->controlador()->es_edicion()) {
			$datos = $this->cn()->entidad()->get_datos_config();
			if (!empty($datos)) {
				$form->set_datos($datos);
			}
		}
	}

	function evt__formulario__modificacion($datos)
	{
		if ($this->controlador()->es_edicion()) {
			$this->cn()->entidad()->set_datos_config($datos);
		} else {
			$this->cn()->entidad()->set_datos_agregar($datos);
		}
	}

}