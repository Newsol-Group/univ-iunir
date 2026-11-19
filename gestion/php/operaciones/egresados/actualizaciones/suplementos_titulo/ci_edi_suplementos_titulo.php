<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_suplementos_titulo extends ci_edicion_simple
{
	protected $indx_msg_eof = '800_eof_cuadro';
	protected $indx_msg_confirmar_eliminar = '800_confirmar_elimin';
	
	protected $s__suplementos;
	protected $s__tipo_seleccionado;

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__edicion_suplementos(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->evento('cancelar')->desactivar();
		$this->controlador()->evento('procesar')->desactivar();
		$pantalla->evento('modificacion')->ocultar();

		$pantalla->eliminar_dep('form_ml_suplementos');
		if (isset($this->s__tipo_seleccionado['tipo_suplemento'])) {
			$pantalla->agregar_dep('form_ml_suplementos');
			$pantalla->evento('modificacion')->mostrar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		unset($this->s__tipo_seleccionado);
		unset($this->s__suplementos);
		
		$this->set_pantalla('listado_suplementos');
	}

	function evt__modificacion()
	{
		try {
			$this->cn()->entidad()->procesar_suplementos($this->s__suplementos);
			$this->controlador()->set_hubo_cambios();
		} catch (guarani_error $e) {
			throw guarani::error($e->getMessage());
		}
		
		$this->evt__cancelar();
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_suplementos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__cuadro_suplementos(guarani_pers_ei_cuadro $cuadro)
	{
		$cuadro->set_eof_mensaje(guarani_mensajes::get($this->indx_msg_eof, array('Suplementos del Título')));
		
		$datos = $this->cn()->entidad()->get_datos_suplementos();
		$cuadro->set_datos($datos);
		
		if(guarani_arreglos::count($datos) == 0) {
			$cuadro->evento('editar')->set_etiqueta('&Agregar Suplementos');
			$cuadro->evento('editar')->set_imagen('agregar.gif', 'proyecto');
		}
	}
	
	function evt__cuadro_suplementos__editar($datos)
	{
		$this->set_pantalla('edicion_suplementos');
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_suplemento --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__form_suplemento(form_suplemento $form)
	{
		$form->set_datos($this->s__tipo_seleccionado);
	}

	function evt__form_suplemento__modificacion($datos)
	{
		$this->s__tipo_seleccionado = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_suplementos ----------------------------------------------------------
	//-----------------------------------------------------------------------------------
	function conf__form_ml_suplementos(guarani_pers_ei_formulario_ml $form_ml)
	{
		if (isset($this->s__tipo_seleccionado['tipo_suplemento'])) {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_suplementos($this->s__tipo_seleccionado['tipo_suplemento']));
		}
	}
	
	function evt__form_ml_suplementos__modificacion($datos)
	{
		foreach ($datos as &$dato) {
			$dato['tipo_suplemento'] = $this->s__tipo_seleccionado['tipo_suplemento'];
		}
		
		$this->s__suplementos = $datos; 
	}
}
?>