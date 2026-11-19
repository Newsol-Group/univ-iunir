<?php

namespace siu\modelo_g3;

use SIU\Chulupi\kernel;

/**
 * Clase personalizada para consumir los WS de Araí Documentos
 * @see https://api.siu.edu.ar/?spec=arai-docs-backend_v1
 * @package Araí
 */
class arai_documentos extends \arai_documentos {
    
	/**
	 * Obtiene el usuario origen de Araí
	 * @param array $params Parámetros para determinar el usuario originante en personalización, se envían por defecto id, documento_tipo, ras y metodo. Consta del siguiente formato:
         * <pre>
         *   [
         *       'documento_tipo' => 'ACTA',
         *       'id'             => 123,
         *       'ras'            => [1, 2, 3, 4],
         *       'metodo'         => 'acta_nucleo::enviar_acta_arai_documentos'
         *   ]
         * </pre>
	 * @param string $usuario_proceso En caso de que la operación sea un proceso (background), el usuario que la ejecutó
	 * @throws \Exception Si el origen no tiene usuario vinculado en Araí.
	 * @return string Usuario origen de Araí
	 */
	public static function get_cuenta_usuario_origen_arai_documentos($params=[], $usuario_proceso=null)
	{
		$usuario_arai = kernel::persona()->get_usuario_arai();

		if (is_null($usuario_arai)) {
			throw \guarani::error(\guarani_mensajes::get('800_origen_no_vinculado_arai'));
		}

		return $usuario_arai;
	}

	protected static function getPathConfig()
    {
        $instalacionDir = kernel::proyecto()->get_dir_instalacion();
        return $instalacionDir."/arai_documentos.ini";
    }
    
    protected static function getDirLogs()
    {
        $instalacionDir = kernel::proyecto()->get_dir_instalacion();
        return $instalacionDir."/log";
    }
    
    protected static function getDirTemp()
    {
        return kernel::proyecto()->get_dir_temp();
    }

    protected static function getParamsDB()
    {
        $params = kernel::proyecto()->get_parametros_db();
		$params_db = [];
		$params_db['db_queue'] = $params['dbname'];
		$params_db['dbq_host'] = $params['host'];
		$params_db['dbq_port'] = $params['port'];
		$params_db['dbq_user'] = $params['pdo_user'];
		$params_db['dbq_password'] = $params['pdo_passwd'];
		$params_db['dbq_table_name'] = $params['schema'].".".static::TABLA_COLA;
		return $params_db;
    }
}