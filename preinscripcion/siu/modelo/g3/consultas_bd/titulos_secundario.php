<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\g3\interface_titulos_secundario;

class titulos_secundario extends entidad_g3 implements interface_titulos_secundario 
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => 'titulo, nombre',
		'from' => 'mdp_titulos',
		'where' => "titulo_tipo = 'C'",
		'order_by' => 'nombre',
		'clave' => 'titulo',
		'campo_nombre' => 'nombre'
	);

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	/**
	 * Nombre o campo descriptivo de una entidad particular
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre($id = null)
	{
		if (empty($id)) {
			return '';
		}
		$datos = $this->get_entidad($id);
		return formatear($datos[$this->sql['campo_nombre']]);
	}
	
	/**
	 * Obtiene una lista de títulos secundarios a partir de una parte de su nombre.
	 * Para usar con el selector de títulos secundario.
	 * 
	 * @param string $titulo_nombre
	 * @return array
	 */
	function get_titulos($titulo_nombre)
	{
		$titulo_nombre = kernel::db()->quote("%$titulo_nombre%");
		$condiciones = " f_limpiar_acentos(mdp_titulos.nombre) ILIKE f_limpiar_acentos($titulo_nombre)::text";
		$sql = $this->armar_sql($condiciones);
		return $this->consultar($sql);
	}

	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $id
	 * @return array
	 */
	function test($id)
	{
		$salida = parent::test($id);
		$salida['titulos'] = $this->get_titulos('ciencias natu');
		// Se elimina de la respuesta el listado de opciones para evitar saturar 
		// el log por la gran cantidad de datos
		unset($salida['datos']['opciones']);
		return $salida;
	}
	
}