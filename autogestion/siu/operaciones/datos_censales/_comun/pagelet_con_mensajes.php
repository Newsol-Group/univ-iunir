<?php
namespace siu\operaciones\datos_censales\_comun;

use SIU\Chulupi\interfaz\pagelet;
use siu\modelo\transacciones\becas;

abstract class pagelet_con_mensajes extends pagelet
{	
	function datos_confirmados()
	{
		return $this->controlador->vista()->datos_confirmados();
	}
	
	function datos_error_control()
	{
		return $this->controlador->vista()->datos_error_control();
	}
	
	function datos_vigentes()
	{
		return $this->controlador->datos_vigentes();
	}
	
	function hay_campos_faltantes()
	{
		return $this->controlador->hay_datos_faltantes();
	}

	function hay_datos_faltantes_convocatoria()
	{
		return $this->controlador->hay_datos_faltantes_convocatoria();
	}

	function viene_de_convocatorias_becas()
	{
            return becas::viene_de_convocatorias_becas();
	}
	
	function get_url_siguiente_paso()
	{
            return becas::get_url_siguiente_paso();
	}
	
}