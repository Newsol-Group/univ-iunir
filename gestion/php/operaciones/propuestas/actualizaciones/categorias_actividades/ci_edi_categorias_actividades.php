<?php
/**
 *
 *
 * @package 	Propuestas
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_categorias_actividades extends guarani_pers_ci
{
	protected $datos_temp_categoria;
	protected $datos_temp_detalles;
	
	//-----------------------------------------------------------------------------------
	//---- categoria --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__categoria(toba_ei_formulario $form)
	{
		if (isset($this->datos_temp_categoria)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp_categoria);
		} else {
			$form->set_datos($this->cn()->entidad()->get_datos());
		}
		
		if (!$this->cn()->entidad()->es_nueva()) {
			$datos = $this->cn()->entidad()->get_datos();
			if (toba::consulta_php('co_categorias_actividades')->categoria_en_uso($datos['categoria'])) {
				$form->set_solo_lectura(array('nombre'));	
			}	
		}
	}

	function evt__categoria__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_categoria = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- detalles ---------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__detalles(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_temp_detalles)) {
			$this->controlador->set_hubo_cambios();
			$form_ml->set_datos($this->datos_temp_detalles);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_detalle());
		}
	}

	function evt__detalles__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_detalle($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_detalles = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

}
?>