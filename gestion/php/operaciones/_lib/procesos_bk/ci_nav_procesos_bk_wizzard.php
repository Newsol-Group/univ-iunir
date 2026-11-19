<?php
class ci_nav_procesos_bk_wizzard extends ci_navegacion_wizard
{
	function evt__result_anteriores()
	{
		$this->controlador()->set_pantalla('resultados_seleccion');
	}
	
	function evt__abortar()
	{
		$this->controlador()->evt__abortar();
	}
	
	function conf__procesar(toba_ei_pantalla $pantalla)
	{
		$confirm = 'Esta acción abortará la ejecución del procesamiento restante. Lo procesado hasta el momento no será revertido. ¿Desea continuar?';
		$pantalla->evento('abortar')->set_msg_confirmacion($confirm);
	}
	
	function modo_visualizacion()
	{
		return $this->controlador()->modo_visualizacion();
	}
}
?>
