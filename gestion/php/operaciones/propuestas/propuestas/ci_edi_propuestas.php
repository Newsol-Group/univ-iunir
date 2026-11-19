<?php
/**
 *
 *
 * @package 	Propuestas Formativas
 * @subpackage 	-
 * 
 */
class ci_edi_propuestas extends ci_edicion_simple
{
	const chars_maximo_combo = 200;

	protected $datos_temp_ubicaciones;

	function ini()
	{
		$this->dep('form_ml_ubicaciones')->set_columna_ordenamiento('ubicacion_nombre');
	}

	function conf()
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		if ($this->cn()->entidad_es_nueva()) {
			if ($form->existe_ef('estado') && $form->existe_ef('fecha_baja') && $form->existe_ef('documento_baja')) {
				$form->desactivar_efs(array('estado', 'fecha_baja', 'documento_baja'));
			}			
		} else {
			// Código no editable en modificación
			$form->ef('codigo')->set_solo_lectura();
			
			//Si hay al menos un plan activo, no se puede modificar el tipo de propuesta
			if ($this->cn()->entidad()->posee_planes_activos()) {
				$form->set_solo_lectura(array('propuesta_tipo', 'estado', 'fecha_alta', 'documento_alta'));
			}
		}
		
		if (isset($this->datos_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp);		
		} else {
			$datos = $this->cn()->entidad()->get_datos();
			$datos['areas_tematicas'] = aplanar_matriz($this->cn()->entidad()->get_datos_areas_tem(), 'area_tematica');
			$form->set_datos($datos);
		}
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			if ($this->cn()->entidad_es_nueva()) {
				$datos['estado'] = propuesta::estado_activo;
			}
			$this->cn()->entidad()->set_datos($datos);
		} catch ( guarani_error $e ) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_ubicaciones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_ubicaciones(form_ml_requerido $form_ml)
	{
		if (isset($this->datos_temp_ubicaciones)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_temp_ubicaciones;
		} else {
			$datos = $this->cn()->entidad()->get_datos_ofertas();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_ubicaciones', 'ubicacion', 'ubicacion', true);
		$datos_posibles = toba::consulta_php('co_ubicaciones')->get_ubicaciones_perfil_de_datos($filtro);

		if ($this->cn()->entidad()->es_nueva() && toba::consulta_php('co_ubicaciones')->get_cantidad_ubicaciones() == 1 && isset($datos_posibles[0])) {
			$datos_posibles[0]['requerido'] = 1;
			$form_ml->agregar_registro($datos_posibles[0]);
		}

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
	}

	function evt__form_ml_ubicaciones__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_ofertas($datos);
		} catch (guarani_error $e) {
			$this->datos_temp_ubicaciones = $datos;
			throw $e;
		}
	}
}
?>