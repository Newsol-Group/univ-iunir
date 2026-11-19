<?php
class ci_consultar_legajo extends ci_navegacion
{
	protected $s__filtro = null;
	
	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		if (!isset($this->s__filtro)) {
			$pantalla->eliminar_dep('cuadro_persona');
		} else {
			$persona = $this->cn()->get_datos_persona($this->s__filtro);
			if (!empty($persona)) {
				$this->ev_info()->set_datos($persona);
			} else {
				$pantalla->eliminar_dep('cuadro_persona');
				$pantalla->eliminar_dep('cuadro_rtf');
				$this->informar_msg('Los datos ingresados no corresponden a ningún alumno en la institución seleccionada');
			}
		}
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);

			$this->ev_info->set_columnas_a_mostrar(array('apellido', 'nombres', 'fecha_nacimiento', 'documento', 'genero', 'nacionalidad', 'mail', 'nombre_localidad_procedencia'));
			$this->ev_info->set_columnas_fila(array('apellido', 'nombres', 'genero'), 1);
			$this->ev_info->set_columnas_fila(array('documento', 'fecha_nacimiento', 'mail'), 2);
			$this->ev_info->set_columnas_fila(array('nacionalidad', 'nombre_localidad_procedencia'), 3);
			
			$this->ev_info->set_etiqueta('nombre_localidad_procedencia', 'Localidad de Nacimiento');
			$this->ev_info->set_etiqueta('documento', 'Identificación');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		$filtro->columna('institucion')->set_condicion_fija('es_igual_a');
		$filtro->columna('tipo_documento')->set_condicion_fija('es_igual_a');
		$filtro->columna('pais')->set_condicion_fija('es_igual_a');
		$filtro->columna('nro_documento')->set_condicion_fija('es_igual_a');
		parent::conf__filtro($filtro);
	}

	function evt__filtro__filtrar($datos)
	{
		if (isset($datos) && !empty($datos)) {
			$aux = array();
			foreach (array_keys($datos) as $key) {
				$aux[$key] = $datos[$key]['valor'];
			}
			$this->s__datos = $datos;			
			$this->s__filtro = $aux;
		} else {
			unset($this->s__datos);
			$this->s__filtro = '';
		}		
	}
	
	function get_instituciones ()
	{
		return $this->cn()->get_instituciones();
	}
		
	//-----------------------------------------------------------------------------------
	//---- cuadro_persona ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_persona(guarani_pers_ei_cuadro $cuadro)
	{	
		if (isset($this->s__filtro)) {
			$cuadro->set_datos($this->cn()->get_actividades($this->s__filtro));
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_rtf -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__cuadro_rtf(guarani_pers_ei_cuadro $cuadro)
	{
		if (isset($this->s__filtro)) {
			$cuadro->set_datos($this->cn()->get_trayectos($this->s__filtro));
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/*
	 * Este evento se usa para que limpie las variables del cn que se utilizan para
	 * hacer la consulta al ws legajo.
	 */
	function post_eventos()
	{
		$this->cn()->reset_variables();
	}

}
?>