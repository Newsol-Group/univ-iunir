<?php 
class ci_resultados_validacion extends guarani_pers_ci
{
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		$this->controlador()->pantalla()->eliminar_evento('volver');
	}

	function evt__volver()
	{
		$this->set_pantalla('seleccion');
	}

	function conf__cuadro(toba_ei_cuadro $cuadro)
	{
		$cuadro->set_datos($this->cn()->get_resultados());
	}

	function evt__cuadro__seleccion($seleccion)
	{
		$this->cn()->seleccionar($seleccion);
		$this->set_pantalla('edicion');
	}
}
?>