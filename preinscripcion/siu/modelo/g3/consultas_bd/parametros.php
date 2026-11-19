<?php
namespace siu\modelo\g3\consultas_bd;

use siu\modelo\g3\consultas_bd\_consultas_bd;
use siu\modelo\g3\interface_parametros;
use SIU\Chulupi\kernel;

class parametros extends _consultas_bd implements interface_parametros
{
	protected static $instancia;
	
	protected $tiempo_cache = 30;

	static function instancia()
	{
		$clase = substr(strrchr(get_class(), "\\"), 1);
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\g3\\consultas_bd\\$clase");
		}
		return self::$instancia;
	}
	
	/**
	 * Obtiene el valor de un parámetro de Guaraní.
	 * LIMITACIÓN: únicamente parámetros definidos en nivel 1 (Para toda la Institución)
	 * 
	 * Uso: para obtener los valores de
	 *  + pre_meses_vigencia_preinsc: Cantidad de meses de vigencia de la preinscripción a propuesta
	 *  + pre_cant_max_propuestas_preinsc: Cantidad máxima de propuestas en las que se puede preinscribir el aspirante
	 * 
	 * @param string $parametro
	 */
	function get_valor($parametro)
	{
		$parametro = kernel::db()->quote(strtoupper($parametro));
		
		$sql = "SELECT 	par_parametros_sistema.valor_default as valor
				FROM 	par_parametros_sistema
				WHERE	upper(par_parametros_sistema.parametro) = $parametro";
		
		$res = $this->consultar_fila($sql);
		return $res['valor'];
	}
	
	/**
	 * Prueba las funciones de la interfaz
	 * 
	 * @param $parametro
	 * @return array
	 */
	function test($parametro)
	{
		try {
			$datos = array();
			$datos['valor'] = $this->get_valor($parametro);
			$salida = array(
				'resultado' => true,
				'datos' => $datos,
				'msj' => 'OK',
			);
		} catch (\Exception $ex) {
			$salida = array(
				'resultado' => false,
				'msj' => $ex->getMessage(),
			);
		}
		return $salida;
	}
	
}
?>