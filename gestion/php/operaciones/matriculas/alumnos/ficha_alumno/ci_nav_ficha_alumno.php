<?php
/**
 * 
 *
 * @package 	Matriculas
 * @subpackage 	Reportes
 *
 */
class ci_nav_ficha_alumno extends ci_rep_sin_listado_inicial 
{
	// Componente Visual de Información.
	protected $ev_info;
	
	// Persona seleccionada
	protected $s__persona;
	
	function get_tabla_principal()
	{
		return 'mdp_personas';
	}
	
	function get_datos($filtro)
	{
		return toba::consulta_php('co_personas')->get_listado_personas_con_insc_propuesta($filtro);
	}
	
	function set_persona_seleccionada($persona)
	{
		$this->s__persona = $persona;
	}
	
	function get_persona_seleccionada()
	{
		return $this->s__persona;
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->set_persona_seleccionada($seleccion['persona']);
		$this->dep('ci_reportes_ficha_alumno')->unset_vars();
		$this->set_pantalla('reportes');
	}
	
	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__reportes()
	{
		$datos = toba::consulta_php('co_personas')->get_datos_persona($this->get_persona_seleccionada());
		$this->ev_info()->set_datos($datos);
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_columnas_a_mostrar(array('nombre_completo', 'documento'));
			
			$this->ev_info->set_columnas_fila(array('nombre_completo'), 1);			
			$this->ev_info->set_columnas_fila(array('documento'), 2);
			
			$this->ev_info->set_max_columnas_por_fila(1);
			
			$this->ev_info->set_etiqueta('nombre_completo', 'Apellido y Nombre:');
			$this->ev_info->set_etiqueta('documento', 'Tipo y Nro. de Identificación:');	
			
			$this->ev_info->set_table_width('');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__volver()
	{
		unset($this->s__persona);
		$this->set_pantalla('seleccion');
	}

}
?>