<?php
/**
 *
 *
 * @package		Matriculas
 * @subpackage	Inscripciones
 * 
 */
class cn_man_insc_prop extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'insc_prop';
	}

	function get_datos_props($where = '')
	{
		return toba::consulta_php('co_propuestas')->get_propuestas_planes_activos($where);
	}

	function get_datos_pers($filtro = '')
	{
		return toba::consulta_php('co_personas')->get_listado($filtro);
	}

	function cargar_atributos_parametros_propuesta($propuesta)
	{
		$operacion = guarani::get_id_operacion();

		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras(array($propuesta));
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_ra, $ras);

		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_propuesta, array($propuesta));

		$this->cargar_dependencias_legajo();
	}

	function cargar_atributos_parametros_plan($plan)
	{
		$operacion = guarani::get_id_operacion();
		guarani::cache_parametros()->set_atributos_operacion($operacion, parametro::nivel_plan, array($plan));
	}

	protected function cargar_dependencias_legajo()
	{
		try {
			$operacion = guarani::get_id_operacion();
			if (guarani::parametros()->get_valor('mat_utiliza_legajo') != comunes::si) {
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_genera_legajo_inscripcion');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_legajo_alumno_generacion');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_legajo_alumno_unico');
				guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_inscripciones_a_generar_legajo');
			} else {
				if (guarani::parametros()->get_valor('mat_genera_legajo_inscripcion') != comunes::si) {
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_legajo_alumno_generacion');
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_legajo_alumno_unico');
					guarani::cache_parametros()->set_parametro_no_usado($operacion, 'mat_inscripciones_a_generar_legajo');
				}
			}
		} catch (guarani_error_param_no_usado $e) {
			
		}
	}

}
?>