<?php
/**
 *
 * 
 * @package		Reconocimiento de Actividades
 * @subpackage	Equivalencias
 * 
 */
class ci_edi_rectificar_equivalencia extends ci_edicion_simple
{
	protected $s__imprimir = false;

	function get_datos_basicos()
	{
		return $this->controlador()->get_datos_tramite_original();
	}

	function get_tramite_equiv_seleccionado()
	{
		return $this->controlador->get_tramite();
	}

	function es_rectificativa_nueva($activ)
	{
		$lista_actividades_nuevas = $this->dep('datos_princ')->get_activ_nuevas_arectificar();
		return in_array($activ, $lista_actividades_nuevas);
	}

	function habilitar_imprimir()
	{
		$this->s__imprimir = true;
	}

	function conf()
	{
		if (!$this->s__imprimir) {
			$this->pantalla()->tab('resumen')->desactivar();
		} else {
			$this->pantalla()->tab('datos_principales')->desactivar();
			$this->pantalla()->tab('cargar_equivalencia')->desactivar();
		}
	}

	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_datos_principales()
	{
		// Debe haber seleccionada alguna actividad para rectificar
		$datos = $this->cn()->entidad()->get_datos_equiv_otorgada();
		if (empty($datos)) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('una equivalencia'));
			throw guarani::error($mje);
		}
	}

	function evt__datos_principales__salida()
	{
		$this->validar_datos_principales();
	}

	//--------------------------------------------------------------------------
	//---- PANTALLA cargar_equivalencia 
	//--------------------------------------------------------------------------
	
	//--------------------------------------------------------------------------
	//---- Validación de salida ------------------------------------------------
	//--------------------------------------------------------------------------

	function validar_cargar_equivalencia()
	{
		// Se valida que se haya seleccionado alguna cargar_equivalencia
		if ($this->falta_seleccionar('id')) {
			$mje = guarani_mensajes::get('800_no_seleccion', array('alguna cargar_equivalencia'));
			throw guarani::error($mje);
		}

		$this->cn()->set_cargar_equivalencia($this->get_seleccion_valor('id'));
	}

	function limpiar_seleccion()
	{
		$this->s__imprimir = false;
	}

}
?>