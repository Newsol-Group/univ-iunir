<?php
/**
 *
 *
 * @package Propuestas
 * @subpackage PlanElementos
 */
 class ci_nav_actividad_materia_generica extends ci_navegacion 
{
	protected $s__estoy_duplicando = false;
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//---- cuadro -----------------------------------------------------------------------
	
	function evt__cuadro__duplicar($seleccion)
	{
		parent::evt__cuadro__seleccion($seleccion);
		$this->cn()->entidad()->duplicar();
		$this->cn()->entidad()->limpiar_campos_elemento();
		$this->s__estoy_duplicando = true;
	}

	function estoy_duplicando()
	{
		return $this->s__estoy_duplicando;
	}
	
	function limpiar_duplicando()
	{
		$this->s__estoy_duplicando = false;
	}
	
	function evt__eliminar()
	{	
		if ($this->cn()->entidad()->pertenece_a_plan()) {
			$this->mensaje_error();
		} else {
			parent::evt__eliminar();
		}
		$this->limpiar_duplicando();
	}

	function evt__guardar()
	{
		if ( ($this->cn()->entidad()->pertenece_a_plan()) && (!($this->estoy_duplicando())) ) {
			$this->mensaje_error();
		} else {
			try {
				$this->cn()->guardar();
				$this->evt__cancelar();
				toba::notificacion()->agregar($this->get_mensaje('guardar_ok'), 'info');
			} catch (guarani_error $e)	{
				throw $e;
			}
		}
		$this->limpiar_duplicando();
	}

	function evt__cancelar()
	{
		$this->dep('editor')->disparar_limpieza_memoria();
		parent::evt__cancelar();
		$this->limpiar_duplicando();
	}

	function mensaje_error()
	{
		return toba::notificacion()->agregar(guarani_mensajes::get('800_dato_no_modif_elim',array('materia genérica', 'pertenece a un plan de estudios')), 'error');		
	}
	
	function get_msg_confirmar_eliminar($dep=null)
	{
		$etiqueta = 'la Actividad Genérica';
		return guarani_mensajes::get($this->indx_msg_confirmar_eliminar, array($etiqueta));
	}
}
?>
