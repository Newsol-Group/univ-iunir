<?php
/**
 *
 *
 * @package 	Administración
 * @subpackage 	Circuitos
 * 
 */
class cn_ent_formulario_tramite extends cn_entidad
{
	function set_datos($datos, $tabla = null, $validar=true)
	{
		$this->validar_datos($datos);
		$this->tabla('formulario')->set($datos);
	}
	
	function get_datos($tabla=null)
	{
		return $this->tabla('formulario')->get();
	}
	
	function validar_datos($datos)
	{
		// Valida que el nombre sea único
		$formulario = $this->tabla('formulario')->get_columna('formulario');
		$nombre = $datos['nombre'];
		
		if (!toba::consulta_php('co_circuitos_tramite')->validar_nombre_formulario($nombre, $formulario)) {
			$this->set_error('dato_duplicado', array('nombre', 'Formulario'));
			throw guarani::error($this->get_mensaje_error());
		}
		
		/*
		 * @see https://redmine.siu.edu.ar/redmine/issues/34763
		 * @see https://redmine.siu.edu.ar/redmine/projects/guarani3/wiki/Casos_de_UsoAdministracion_Formularios
		 * El campo "Incluir en expediente" solo puede ser 'S' cuando se trata de un circuito de egreso.
		 */
		if (($datos['tipo'] != circuito_tramite::tipo_egreso) && ($datos['incluir_en_expediente'] == comunes::si)) {
			throw guarani::error('El campo "Incluir en expediente" solo se puede editar cuando se trata de un circuito de egreso.');
		}
	}
}