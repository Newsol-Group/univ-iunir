<?php
/**
 * 
 * @package Propuestas Formativas
 */
class act_propuestas_relaciones_nucleo
{
	/**
	 * Registra una "foto" de la relación entre propuestas.
	 *
	 * @param integer $relacion
	 */
	function cambios_relacion_propuestas($relacion)
	{
		$ejecutar = "	SELECT	*
						FROM	f_cambios_relacion_propuestas($relacion);
		";
		guarani_db::ejecutar($ejecutar, false, true);
		toba::logger()->info('************ Se registró el cambio en la relación entre propuestas con éxito. ************');
	}
}
?>