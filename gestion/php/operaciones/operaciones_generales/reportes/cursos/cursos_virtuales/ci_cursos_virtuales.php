<?php
class ci_cursos_virtuales extends ci_impresion_popup
{
	//Componentes Visuales de información de selecciones previas
	protected $ev_info;
	protected $s__titulo;
				
	function ini()
	{
		parent::ini();

		// El reporte muestra las comisiones creadas con observaciones
		$this->s__datos = guarani_datos_impresion::instancia()->get_datos();
	}
	
	function conf__pant_reporte(toba_ei_pantalla $pantalla)
	{
		if ($this->s__titulo == 'Reporte cursos') {
			$pantalla->agregar_dep('cuadro_cursos');
		}
		if ($this->s__titulo == 'Reporte alumnos') {
			$pantalla->agregar_dep('cuadros_alumnos');
		}
		if ($this->s__titulo == 'Reporte docentes') {
			$pantalla->agregar_dep('cuadro_docentes');
		}
		//$this->ev_info()->set_datos($this->s__datos_conf);
	}
	
	function cuadro() 
	{
		if ($this->s__titulo == 'Reporte cursos') {
			return $this->dep('cuadro_cursos');
		}
		if ($this->s__titulo == 'Reporte alumnos') {
			return $this->dep('cuadros_alumnos');
		}
		if ($this->s__titulo == 'Reporte docentes') {
			return $this->dep('cuadro_docentes');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_cursos ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_cursos(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
		$cuadro->set_titulo($this->s__titulo);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_docentes(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
		$cuadro->set_titulo($this->s__titulo);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadros_alumnos --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadros_alumnos(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->s__datos);
		$cuadro->set_titulo($this->s__titulo);
	}

	

	//---- Componente visual para mostrar Información -----------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_columnas_a_mostrar(array('codigo_comision', 'actividad', 'curso_virtual', 'accion', 'Apellido_Nombre', 'identificacion', 'usuario', 'rol', 'error'));
			//	$this->ev_info->set_columnas_fila(array('codigo_comision' ,'actividad','curso_virtual','accion','Apellido_Nombre' ,'identificacion','usuario',	'rol','error'), 1);
			$this->ev_info->set_etiqueta('codigo_comision', 'codigo_comision');
			$this->ev_info->set_etiqueta('actividad', 'actividad');
			$this->ev_info->set_etiqueta('curso_virtual', 'curso_virtual');
			$this->ev_info->set_etiqueta('accion', 'Accion');
			$this->ev_info->set_etiqueta('Apellido_Nombre', 'Apellido y Nombre');
			$this->ev_info->set_etiqueta('identificacion', 'Identificacion');
			$this->ev_info->set_etiqueta('usuario', 'Usuario');
			$this->ev_info->set_etiqueta('rol', 'Rol');
			$this->ev_info->set_etiqueta('error', 'error');
		}
		return $this->ev_info;
	}
	
	

}
?>