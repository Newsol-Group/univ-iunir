<?php
class ci_nav_asignaciones_espacios_masiva extends guarani_pers_ci
{
	protected $s__filtros;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$pantalla->eliminar_dep('cuadro_disponibilidad');
		
		if (isset($this->s__filtros['edificio'])) {
			$pantalla->agregar_dep('cuadro_disponibilidad');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_filtro_asignaciones_espacios_masiva -------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__form_filtro_asignaciones_espacios_masiva(form_filtro_asignaciones_espacios_masiva $form)
	{
		$datos_defecto = array();
		
		if (isset($this->s__filtros['ubicacion'])) {
			$datos_defecto['ubicacion'] = $this->s__filtros['ubicacion'];
		}
		
		if (isset($this->s__filtros['edificio'])) {
			$datos_defecto['edificio'] = $this->s__filtros['edificio'];
		}
		
		if (isset($this->s__filtros['anio_academico'])) {
			$datos_defecto['anio_academico'] = $this->s__filtros['anio_academico'];
		}
		
		if (isset($this->s__filtros['periodo_lectivo'])) {
			$datos_defecto['periodo_lectivo'] = $this->s__filtros['periodo_lectivo'];
		}
		
		if (isset($this->s__filtros['elemento'])) {
			$datos_defecto['elemento'] = $this->s__filtros['elemento'];
		}
		
		$form->set_datos_defecto($datos_defecto);
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__cuadro_disponibilidad(toba_ei_cuadro $cuadro)
	{
		if (isset($this->s__filtros)) {
			$datos = toba::consulta_php('co_asignaciones_espacios')->get_distribucion_comisiones_en_edificacion($this->s__filtros['edificio'], $this->s__filtros['periodo_lectivo']);
			$cuadro->set_datos($datos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function evt__form_filtro_asignaciones_espacios_masiva__modificacion($datos)
	{
		$this->s__filtros = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function datos_filtros()
	{
		if (isset($this->s__filtros)) {
			$datos_filtros['ubicacion'] = $this->s__filtros['ubicacion'];
			$datos_filtros['edificio'] = $this->s__filtros['edificio'];
			$datos_filtros['anio_academico'] = $this->s__filtros['anio_academico'];
			$datos_filtros['periodo_lectivo'] = $this->s__filtros['periodo_lectivo'];
			$datos_filtros['elemento'] = $this->s__filtros['elemento'];
			
			return $datos_filtros;
		}
		
		return null;
	}
}
?>