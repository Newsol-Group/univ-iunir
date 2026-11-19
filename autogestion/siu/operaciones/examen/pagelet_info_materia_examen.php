<?php

namespace siu\operaciones\examen;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use \siu\modelo\entidades\parametro;
use \siu\guarani;

class pagelet_info_materia_examen extends pagelet
{
	const MATERIA_SELECCIONADA = 'mat_sel';
	const ERROR_MATERIA_SELECCIONADA = 'error_mat_sel';

	function ini()
	{
		$this->add_mensaje_js('error_subcomision', kernel::traductor()->trans('info_materia.errores.subcomision'));
	}

	function get_nombre()
	{
		return 'info_materia_examen';
	}

	protected function get_codigo_response()
	{
		if ($this->estado == self::MATERIA_SELECCIONADA) {
			return 2;
		}
		return parent::get_codigo_response();
	}

	function get_actividad()
	{
		return $this->controlador->get_var('materia');
	}

	function get_materia_nombre($materia)
	{
		$info = $this->modelo()->info__materia($materia);
		return $info['nombre'];
	}

	function get_nro_transaccion($mesa)
	{
		$info = $this->modelo()->info__inscripcion($mesa);
		return $info['URL_NRO_TRANSACCION'];
	}

	function get_mensajes()
	{
		return $this->controlador->get_var('mensajes');
	}

	protected function existe_insc_examen($materia)
	{
		// cambiar en existe_inscripcion_a_examen que devuelve un arreglo
		// puede estar inscripto en más de una mesa
		return $this->modelo()->existe_inscripcion_a_examen($materia);
	}

	/**
	 * Shortcut al modelo
	 * 
	 * @return \siu\modelo\transacciones\inscripcion_examen
	 */
	protected function modelo()
	{
		return $this->controlador->modelo();
	}

	function get_js_files()
	{
		$rs = array(
			kernel::vinculador()->vinculo_recurso("js/filtro.js")
		);
		return array_merge($rs, parent::get_js_files());
	}

	function prepare()
	{
		$this->data = array();
		$this->add_mensajes_js();
		$this->data['mensajes'] = $this->get_mensajes();
		$this->data['csrf'] = $this->generar_csrf();
		switch ($this->estado) {
			case 'inicial':
				$this->prepare_inicial();
				break;
			case self::MATERIA_SELECCIONADA:
				$this->prepare_materia_seleccionada();
				break;
		}
	}

	protected function add_mensajes_js()
	{
		$this->add_var_js('msg_baja', kernel::traductor()->trans('mensaje_baja_examen'));
		$this->add_var_js('msg_confirmar_baja', kernel::traductor()->trans('mensaje_confirmar_baja'));
		$this->add_var_js('msg_baja_exitosa', kernel::traductor()->trans('baja_exitosa'));
		$this->add_var_js('msg_titulo_enviar', kernel::traductor()->trans('titulo_enviar_comprobante'));
		$this->add_var_js('msg_enviar_boton', kernel::traductor()->trans('enviar_por_mail'));
		$this->add_var_js('msg_enviar', kernel::traductor()->trans('enviar_comprobante_examen', array('%1%' => kernel::persona()->get_mail())));
		$this->add_var_js('msg_falta_mail', kernel::traductor()->trans('mail_no_cargado', array('%1%' => kernel::vinculador()->crear('configuracion'))));
		$this->add_var_js('envio_mail_exitoso', kernel::traductor()->trans('envio_mail_exitoso'));

		$mail = kernel::persona()->get_mail();
		if (empty($mail)) {
			$this->add_var_js('falta_mail', true);
		} else {
			$this->add_var_js('falta_mail', false);
		}
		$this->add_var_js('url_baja_inicial', kernel::vinculador()->crear('examen', 'baja_inicial'));
	}

	protected function prepare_inicial()
	{
		$parametros_insc_examen = array();
		$parametros_insc_examen['persona'] = kernel::persona()->get_id();
		$parametros_insc_examen['estado'] = 'T';
		$parametros_insc_examen['vigentes'] = 'S';

		$propuesta = kernel::persona()->get_id_propuesta_activa();
		$inscripciones = guarani::persona()->examenes()->get_inscripciones();
		$inscs_por_propuesta = array();
		foreach ($inscripciones as $insc) {
			if ($insc['propuesta'] == $propuesta) {
				$inscs_por_propuesta[] = $insc;
			}
		}

		$this->data['inscripciones'] = $inscs_por_propuesta;
	}

	protected function get_vinculo_comp($actividad, $inscripcion)
	{
		return kernel::vinculador()->crear('examen', 'generar_comprobante', array('actividad' => $actividad, 'inscripcion' => $inscripcion));
	}

	protected function get_vinculo_imp_comp($actividad, $inscripcion)
	{
		return kernel::vinculador()->crear('examen', 'generar_comprobante', array('actividad' => $actividad, 'inscripcion' => $inscripcion, 'i' => 1));
	}

	protected function get_vinculo_mail_comp($actividad, $inscripcion)
	{
		return kernel::vinculador()->crear('examen', 'enviar_comprobante', array('actividad' => $actividad, 'inscripcion' => $inscripcion));
	}

	protected function prepare_materia_seleccionada()
	{
		$actividad = $this->get_actividad();

		$this->data['llamados'] = $this->modelo()->info__lista_mesas($actividad);

		// @TODO EN CURSADAS SE TRAE INFORMACION DE LA INSCRIPCION ACA!!! Y SE PEGA HACIENDO OTRO FOREACH, ACA EN EXAMENES
		// LA INFORMACION DE LA INSCRIPCION SE TRAE YA EN INFO_LISTA_MESAS( ESTA BIEN?)

		foreach ($this->data['llamados'] as $key => $llamado_mesa) {
			$this->data['llamados'][$key]['MATERIA'] = $actividad;
			if (isset($this->data['llamados'][$key]['INSCRIPTO']) && $this->data['llamados'][$key]['INSCRIPTO']) {
				$inscripcion = $this->data['llamados'][$key]['ID_INSCRIPCION'];
				$this->data['llamados'][$key]['URL_COMP'] = $this->get_vinculo_comp($actividad, $inscripcion);
				$this->data['llamados'][$key]['URL_IMP_COMP'] = $this->get_vinculo_imp_comp($actividad, $inscripcion);
				$this->data['llamados'][$key]['URL_MAIL_COMP'] = $this->get_vinculo_mail_comp($actividad, $inscripcion);
			}
			$fecha = date_parse_from_format('Y-m-d', $llamado_mesa['fecha_examen']);
			$this->data['llamados'][$key]['fecha_visual'] = $fecha['day'] . '/' . $fecha['month'] . '/' . $fecha['year'];
		}

		$this->data['materia_nombre'] = $this->get_materia_nombre($actividad);
		$this->data['materia'] = $actividad;
		$this->data['url_baja'] = kernel::vinculador()->crear('examen', 'baja');
		$this->data['url_alta'] = kernel::vinculador()->crear('examen', 'inscribir');
		$this->data['param_insc_examen_seleccion_auto_tipo_insc'] = parametro::get_valor('exa_instancia_automatica', guarani::persona()->get_id_propuesta_activa());
	}

	protected function prepare_error_materia_seleccionada()
	{
		$this->data['mensajes'] = $this->get_mensajes();
	}

	protected function prepare_reinscripcion()
	{
		
	}

}