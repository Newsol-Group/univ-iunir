<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;

class tipos_documentos extends entidad_g3
{

        const DNI = 0;

	protected static $instancia;
	
	protected $sql = array(
		'select' => 'tipo_documento, descripcion, desc_abreviada, orden_principal, habilita_inscripcion,
			tipo_de_dato, puede_eliminarse, exp_regular_validacion, exp_regular_mensaje',
		'from' => 'mdp_tipo_documento',
		'where' => '',
		'order_by' => 'tipo_documento',
		'clave' => 'tipo_documento',
		'campo_nombre' => 'descripcion'
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
	 * Nombre o campo descriptivo abreviado de una entidad particular
	 * Si la entidad no lleva registro de nombre abreviado, toma el nombre general
	 * 
	 * @param $id
	 * @return string
	 */
	function get_nombre_abreviado($id = null)
	{
		if (is_null($id)) {
			return null;
		}
		$datos = $this->get_entidad($id);
		return $datos['desc_abreviada'];
	}

}