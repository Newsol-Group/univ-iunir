<?php
namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\modelo\opciones;
use siu\modelo\consultas;
use siu\modelo\lib\catalogo;
use siu\operaciones\_comun\util_formulario_preinscripcion;

class util
{
	/**
	 * 
	 * @return opciones
	 */
	static function opciones()
	{
		return kernel::localizador()->instanciar_singleton("modelo\\opciones");
	}
	
	/**
	 * 
	 * @return consultas
	 */
	static function consultas()
	{
		return kernel::localizador()->instanciar("modelo\\consultas");
	}
	
	/**
	 * 
	 * @return catalogo
	 */
	static function catalogo()
	{
		return kernel::localizador()->instanciar("modelo\\lib\\catalogo");
	}
	
	/**
	 * 
	 * @return util_formulario_preinscripcion
	 */
	static function form()
	{
		return kernel::localizador()->instanciar("operaciones\\_comun\\util_formulario_preinscripcion");
	}
}
?>