<?php
/**
 *
 *
 * @package 	Constancias y Certificados
 * @subpackage 	Administrar Constancias
 * 
 */
class ci_edi_administrar_constancias extends ci_edicion_simple
{
	// Datos temporales de los formularios de selección de autoridades y responsables académicas.
	protected $seleccion_autoridades_temp;
	protected $datos_temp_ras;

	function ini()
	{
		$this->dep('seleccion_autoridades')->set_columna_ordenamiento('autoridad_nombre_completo');
		$this->dep('form_ml_responsables_academicas')->set_columna_ordenamiento('nombre');
		parent::ini();
	}
	
	function conf__formulario(toba_ei_formulario $formulario)
	{
		$formulario->ef('max_cant_solicitudes')->set_expreg(comunes::er_numero_entero);
		$formulario->ef('max_cant_copias')->set_expreg(comunes::er_numero_entero);

		$formulario->ef('vigencia')->set_descripcion(guarani_mensajes::get('800CON_vigencia_dias'));
		$formulario->ef('permite_descargar')->set_descripcion(guarani_mensajes::get('800CON_permite_descargar'));
		$formulario->ef('aplica_a_cursos')->set_descripcion(guarani_mensajes::get('800CON_aplica_a_cursos'));
		$formulario->ef('exige_aprobacion')->set_descripcion(guarani_mensajes::get('800CON_exige_aprobacion'));
		
		parent::conf__formulario($formulario);
	}
	
	function evt__formulario__modificacion($datos)
	{
		$this->datos_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_autoridades --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_autoridades(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_autoridades_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_autoridades_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_autoridades();
		}
		$form_ml->set_datos($datos);

		// Se obtienen los datos posibles: Autoridades activas existentes que no estén ya asociadas a la Constancia.
		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_autoridades', 'autoridad', 'autoridad', true);
		$datos_posibles = toba::consulta_php('co_autoridades_cargos')->get_listado_autoridades_activas($filtro);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__seleccion_autoridades__modificacion($datos)
	{
		$this->seleccion_autoridades_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- responsables_academicas ------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_responsables_academicas(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_temp_ras)) {
			$this->controlador()->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_temp_ras);
		} else {
			$datos = $this->cn()->entidad()->get_datos_responsables_academicas();
		}

		$filtro = guarani_sql::armar_condicion_in($datos, 'sga_responsables_academicas', 'responsable_academica', 'responsable_academica', true);
		$institucion_instalacion = toba::db()->quote(guarani_configuracion::instancia()->get_valor_config('institucion_instalacion'));
		$filtro .= " AND sga_responsables_academicas.institucion = $institucion_instalacion";
		$datos_posibles = toba::consulta_php('co_responsables_academicas')->get_listado($filtro);

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function conf_fila__form_ml_responsables_academicas($fila)
	{
		$datos_ml = $this->dep('form_ml_responsables_academicas')->get_datos();
		if (!empty($datos_ml[$fila]['requerido']) && $datos_ml[$fila]['requerido'] == 1) {
			$datos_responsable_academica = toba::consulta_php('co_responsables_academicas')->get_datos_responsable_academica($datos_ml[$fila]['responsable_academica']);
			if (empty($datos_responsable_academica)) {
				$this->dep('form_ml_responsables_academicas')->ef('requerido')->set_solo_lectura(true);
			} else {
				$this->dep('form_ml_responsables_academicas')->ef('requerido')->set_solo_lectura(false);
			}
		} else {
			$this->dep('form_ml_responsables_academicas')->ef('requerido')->set_solo_lectura(false);
		}
	}

	function evt__form_ml_responsables_academicas__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_responsables_academicas($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_ras = $datos;
			throw $e;
		}
	}

	/**
	 * Ventana que se ejecuta una vez que todos los eventos se han disparado para este objeto. Utilizada para manejar
	 * el seteo de datos temporales para solapas con más de un componente de edición de datos.
	 */
	function post_eventos()
	{
		try {
			if (isset($this->datos_temp)) {
				$this->cn()->entidad()->set_datos($this->datos_temp);
				unset($this->datos_temp);
			}
			if (isset($this->seleccion_autoridades_temp)) {
				$this->cn()->entidad()->set_datos_autoridades($this->seleccion_autoridades_temp);
				unset($this->seleccion_autoridades_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}
}
?>