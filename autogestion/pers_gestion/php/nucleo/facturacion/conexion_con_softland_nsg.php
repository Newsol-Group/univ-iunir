<?php 
/**
 *
 *
 * @package Facturacion
 * @subpackage Alumnos de Grado
 */
class conexion_con_softland_nsg
{
	/**
	 * Inserta una persona.
	 *
	 * @return conexion con la base de datos de Softland
	 */

	function conectar()
	{
		$sql = "SELECT valor_default FROM par_parametros_sistema WHERE parametro = 'softland_server';";
		$serverName = guarani_db::consultar_fila($sql)["valor_default"]; 

		$sql = "SELECT valor_default FROM par_parametros_sistema WHERE parametro = 'softland_db';";
		$database = guarani_db::consultar_fila($sql)["valor_default"];

		$sql = "SELECT valor_default FROM par_parametros_sistema WHERE parametro = 'softland_uid';";
		$uid = guarani_db::consultar_fila($sql)["valor_default"];

		$sql = "SELECT valor_default FROM par_parametros_sistema WHERE parametro = 'softland_pass';";
		$pass = guarani_db::consultar_fila($sql)["valor_default"];

		$connectionOptions = array(
      					'Database' => $database,
      					'Uid' => $uid,
      					'PWD' => $pass
    					);

		return sqlsrv_connect( $serverName, $connectionOptions);
	}
}
?>