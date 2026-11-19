<?php
class ci_nav_asignaciones_espacios extends guarani_pers_ci
{
	protected $s__filtros;

	//-----------------------------------------------------------------------------------
	//---- form_filtro_asignaciones_espacios --------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_filtro_asignaciones_espacios(form_filtro_asignaciones_espacios $form)
	{
		$datos_defecto = array();

		if (isset($this->s__filtros['fecha'])) {
			$datos_defecto['fecha'] = $this->s__filtros['fecha'];
		} else {
			$datos_defecto['fecha'] = guarani_fecha::get_hoy();
		}

		if (isset($this->s__filtros['ubicacion'])) {
			$datos_defecto['ubicacion'] = $this->s__filtros['ubicacion'];
		}
		
		if (isset($this->s__filtros['edificio'])) {
			$datos_defecto['edificio'] = $this->s__filtros['edificio'];
		}

		if (isset($this->s__filtros['asignar'])) {
			$datos_defecto['asignar'] = $this->s__filtros['asignar'];
		} else {
			$datos_defecto['asignar'] = asignacion_espacio::tipo_comision . "," . asignacion_espacio::tipo_examen;
		}

		$form->set_datos_defecto($datos_defecto);
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__form_filtro_asignaciones_espacios__modificacion($datos)
	{
		$this->s__filtros = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function datos_filtros()
	{
		if (isset($this->s__filtros) && isset($this->s__filtros['edificio'])) {
			$datos_filtros['fecha'] = $this->s__filtros['fecha'];
			$datos_filtros['edificio'] = $this->s__filtros['edificio'];
			$datos_filtros['asignar'] = explode(",", $this->s__filtros['asignar']);
			return $datos_filtros;
		}
		return null;
	}
}
?>