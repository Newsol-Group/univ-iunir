<?php
class cuadro_alumnos_sin_actas extends guarani_pers_ei_cuadro
{
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		echo "		
		//---- Eventos ---------------------------------------------
		
		{$this->objeto_js}.evt__seleccion = function(fila)
		{
			var params = [];
			params['fila'] = fila;
			this.controlador.ajax('get_parametros', params, this, this.respuesta_reporte_actividades);
			return false;
		}
		
		{$this->objeto_js}.respuesta_reporte_actividades = function(respuesta)
		{
			abrir_popup('reporte', respuesta);
		}
		";
	}


}
?>