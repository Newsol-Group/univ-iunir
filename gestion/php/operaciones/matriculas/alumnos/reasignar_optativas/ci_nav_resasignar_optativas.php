<?php
class ci_nav_resasignar_optativas extends ci_nav_sin_listado_inicial
{
	
	function set_sin_listado_inicial()
	{
		$this->con_listado_inicial=false;
		$this->dep('filtro')->set_sin_filtrado_inicial();
	}
	
	function get_alumno_seleccionado()
	{
		return $this->s__seleccion['alumno'];
	}
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		
	}
	
	function evt__cuadro__seleccion($seleccion)
	{
		$this->s__seleccion = $seleccion;		
		$this->set_pantalla('edicion');
	}
	
	function evt__cancelar()
	{
		//Ademas de ir a la otra pantalla también deselecciono los nodos.
		$this->dep('ci_edi_reasignar_optativas')->dep('ci_cont_arbol_reasignacion')->deseleccionar_nodos();
		$this->cn()->limpiar_variables();
		$this->set_pantalla('seleccion');
	}
	
	function get_msg_eof($dep=null)
	{
		$indx_msg = $this->indx_msg_eof;
		if (isset($this->s__filtro) && ! empty($this->s__filtro)) {
			$indx_msg = $this->indx_msg_eof . '_filtrado';
		}
		$etiqueta = "Alumnos";
		return guarani_mensajes::get($indx_msg, array($etiqueta));
	}
	
	
}

?>