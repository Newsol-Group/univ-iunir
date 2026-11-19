<?php
/**
 *
 *
 * @package 	Unidades de Gestión
 * @subpackage 	Unidades de Gestión
 * 
 */
class unidades_gestion_nucleo
{
	const dominio_institucion = 1;
	const dominio_tipo_prop = 2;
	const dominio_ra = 3;
	const dominio_prop = 4;

	/**
	 * Obtiene un listado con los posibles dominios de Unidades de Gestión.
	 */
	static function get_dominios()
	{
		return array(
			array('valor' => self::dominio_institucion, 'descr' => 'Toda la Institución'),
			array('valor' => self::dominio_tipo_prop, 'descr' => ' Tipos de Propuesta'),
			array('valor' => self::dominio_ra, 'descr' => 'Responsables Académicas'),
			array('valor' => self::dominio_prop, 'descr' => 'Propuestas'),
		);
	}

}
?>