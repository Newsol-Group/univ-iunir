<?php
class ci_edi_plan_activacion extends guarani_pers_ci
{
	protected $datos_version_temp;

	function conf()
	{
		if (!$this->cn()->entidad()->es_nueva_version()) {
			toba::notificacion()->agregar('Sólo pueden activarse versiones nuevas.', 'info');
		} else {
			if (!$this->cn()->entidad()->tiene_certificados() &&
				!$this->cn()->entidad()->es_plan_para_administrar_cursos() &&
				!$this->cn()->entidad()->es_propuesta_vocacional()) {
				toba::notificacion()->agregar('La versión no puede activarse porque no tiene certificados asignados.', 'info');
			}
		}
	}

	function conf__plan_info(toba_ei_formulario $form)
	{
		$datos = $this->cn()->entidad()->get_datos_plan();
		$form->set_datos($datos);
	}

	function conf__plan_activacion(toba_ei_formulario $form)
	{
		if (isset($this->datos_version_temp)) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_version_temp);
		} else {
			$datos_plan = $this->cn()->entidad()->get_datos_plan();
			$datos_version = $this->cn()->entidad()->get_datos_version();
			if ($datos_plan['tipo_plan'] == planes::tipo_personalizado ||
				$datos_plan['tipo_plan'] == planes::tipo_convenio ||
				$this->cn()->entidad()->es_propuesta_vocacional() ||
				$datos_plan['tipo_plan'] == planes::tipo_estructurado && $datos_plan['administra_cursos'] == comunes::si) {
				if (!isset($datos_version['fecha_entrada_vigencia']) && isset($datos_plan['fecha_entrada_vigencia'])) {
					$datos_version['fecha_entrada_vigencia'] = $datos_plan['fecha_entrada_vigencia'];
					$this->controlador->set_hubo_cambios();
				}
				if (!isset($datos_version['documento_alta']) && isset($datos_plan['documento_alta'])) {
					$datos_version['documento_alta'] = $datos_plan['documento_alta'];
					$this->controlador->set_hubo_cambios();
				}
			}
			$form->set_datos($datos_version);
		}
		$form->ef('fecha_entrada_vigencia_fijo')->set_estado($form->ef('fecha_entrada_vigencia')->get_estado());

		if (!$this->cn()->entidad()->es_nueva_version() ||
			!$this->cn()->entidad()->tiene_certificados() && !$this->cn()->entidad()->es_plan_para_administrar_cursos() && !$this->cn()->entidad()->es_propuesta_vocacional()) {
			$form->eliminar_evento('modificacion');
			$this->controlador()->evento('guardar')->desactivar();
			$form->set_solo_lectura();
			$form->desactivar_efs(array('documento_alta', 'fecha_entrada_vigencia'));
		} else {
			if ($form->existe_ef('documento_alta_numero')) {
				$form->desactivar_efs(array('documento_alta_numero'));
			}
			if ($form->existe_ef('fecha_entrada_vigencia_fijo')) {
				$form->desactivar_efs(array('fecha_entrada_vigencia_fijo'));
			}
		}
	}

	function evt__plan_activacion__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_version($datos);
		} catch (guarani_error $e) {
			$this->datos_version_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function conf__plan_certificados(toba_ei_cuadro $cuadro)
	{
		$cuadro->eliminar_evento('seleccion');
		$datos = $this->cn()->entidad()->get_certificados();
		$cuadro->set_datos($datos);
		$etiquetas = guarani_etiquetas::get_tabla('sga_planes_certificados');
		$indx_msg = '800_eof_cuadro';
		$msg = guarani_mensajes::get($indx_msg, array($etiquetas->get_enf('plural')));
		$cuadro->set_eof_mensaje($msg);
	}

}
?>