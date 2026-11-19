<?php
/**
 * Representa un elemento del plan de estudios de tipo MODULO RAIZ
 *
 *
 * @package Propuestas
 * @subpackage Plan
 */
class plan_elemento_modulo_raiz_nucleo extends plan_elemento_modulo 
{
	function eliminar($padre=null)
	{
		$this->control_eliminar();
		$this->debug('Eliminar.');
		$this->set_eliminado();
		$this->eliminar_hijos();
	}

	//----------------------------------------------------------------
	//--  Controles  -------------------------------------------------
	//----------------------------------------------------------------

	protected function control_agregar_hijo($hijo)
	{
		parent::control_agregar_hijo($hijo);
		/*
		// Se saca este control por el ticket #2499. Ahora se permite mas de un modulo agrupador de orientaciones en la raiz del plan.
		// Es por el tema de poder contar diferentes orientaciones para titulos finales e intermedios.
		if (($hijo->get_tipo() == elemento::tipo_modulo &&	
		     $hijo->get_subtipo() == elemento::subtipo_agrupador_orientaciones) && 
			 $this->posee_agrupador_orientaciones() ) {
			throw new guarani_error("No es posible crear un modulo de tipo 'Agrupador de orientaciones' porque ya existe uno");	
		}
		*/
	}
	
	//----------------------------------------------------------------
	//--  INFO sobre EDICION  ----------------------------------------
	//----------------------------------------------------------------
		
	function posee_agrupador_orientaciones()
	{
		foreach($this->hijos as $hijo) {
			if (!$hijo['eliminado']) {
				$e = $hijo['instancia'];
				if( $e->get_subtipo() == elemento::subtipo_agrupador_orientaciones  ){
					return true;
				}
			}
		}
		return false;
	}

	function puede_eliminar()
	{
		return true;
	}	
}
?>