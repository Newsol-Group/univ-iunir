<?php
/**
 *
 *
 * @package     Matrícula
 * @subpackage  Regularidad
 * 
 */
class cn_pro_verificar_regularidad_alumno extends cn_procesos_bk
{
	protected $s__filtro;

	function cargar_atributos_parametros($propuestas)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, $propuestas);
	}
	
	function ejecutar_proceso($datos)
	{
		$this->s__proceso = guarani::proceso_bk(pro_verificar_regularidad_alumno::nombre);
		$this->s__proceso->set_datos($datos);
		guarani::ejecutar_proceso_bk($this->s__proceso);
	}

	function set_filtro($filtro)
	{
		$this->s__filtro = $filtro;
	}

	function etiquetas($dep = null)
	{
		if (is_null($dep)) {
			return guarani_etiquetas::get_tabla('sga_propuestas');
		} else {
			parent::etiquetas($dep);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Consultas --------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_listado($filtro)
	{
		$filtro = empty($filtro) ? 'true' : $filtro;
		$filtro .= " AND vw_perfil_de_datos_propuestas.estado = '".propuesta::estado_activo."' AND	sga_propuestas_tipos.control_regularidad = '".comunes::si."'";
		return toba::consulta_php('co_propuestas')->get_propuestas_planes_activos($filtro);
	}

}
?>