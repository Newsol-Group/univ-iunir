<?php

namespace siu\modelo\g3\consultas_bd;

use SIU\Chulupi\kernel;
use siu\modelo\util;
use siu\modelo\comunes;

class tipos_documentos_pais extends entidad_g3
{
	protected static $instancia;
	
	protected $sql = array(
		'select' => "mdp_tipo_documento.tipo_documento, 
					COALESCE(mdp_tipo_documento_pais.descripcion, mdp_tipo_documento.descripcion) as descripcion ,
					COALESCE(mdp_tipo_documento_pais.desc_abreviada, mdp_tipo_documento.desc_abreviada) as desc_abreviada ,
					mdp_tipo_documento.orden_principal, 
					mdp_tipo_documento.habilita_inscripcion,
					mdp_tipo_documento.tipo_de_dato, 
					mdp_tipo_documento.puede_eliminarse, 
					COALESCE(mdp_tipo_documento_pais.exp_regular_validacion, mdp_tipo_documento.exp_regular_validacion) as exp_regular_validacion ,
					COALESCE(mdp_tipo_documento_pais.exp_regular_mensaje, mdp_tipo_documento.exp_regular_mensaje) as exp_regular_mensaje,
							mdp_tipo_documento_pais.pais,

	( mdp_tipo_documento_pais.pais|| '_' || mdp_tipo_documento.tipo_documento) as pais_tipo",
		'from' => 'mdp_tipo_documento, mdp_tipo_documento_pais ',
		'where' => "mdp_tipo_documento.tipo_documento = mdp_tipo_documento_pais.tipo_documento
                            AND mdp_tipo_documento_pais.activo = '".comunes::si."'
                            AND mdp_tipo_documento.habilita_inscripcion = '".comunes::si."'",
		'order_by' => 'tipo_documento',
		'clave' => " mdp_tipo_documento_pais.pais|| '_' || mdp_tipo_documento.tipo_documento",
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
	function get_opciones_pais($condiciones = null, $con_no_seleccion = false)
	{
		$sql = $this->armar_sql($condiciones);
		$datos = $this->consultar($sql);
		return util::opciones()->armar_combo_opciones($datos, 'tipo_documento', 'descripcion', $con_no_seleccion);
	}
}