<?php
namespace siu\operaciones\cursada;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use \siu\modelo\entidades\parametro;
use \siu\modelo\datos\catalogo;
use \siu\guarani;
use siu\modelo\_comun\comunes;

class pagelet_info_materia extends pagelet
{
	const INICIAL = 'inicial';
	const MATERIA_SELECCIONADA = 'mat_sel';
	const ERROR_MATERIA_SELECCIONADA = 'error_mat_sel';
	const REINSCRIPCION = 'reinsc';

	function ini()
	{
		$this->add_mensaje_js('error_subcomision', kernel::traductor()->trans('info_materia.errores.subcomision'));
	}

	function get_nombre()
	{
		return 'info_materia';
	}

	function get_actividad()
	{
		return $this->controlador->get_var('materia');
	}

	function get_materia_nombre()
	{
		$info = $this->modelo()->info__materia($this->get_actividad());
		return $info['nombre'];
	}

	function get_materia_codigo()
	{
		$info = $this->modelo()->info__materia($this->get_actividad());
		return $info['codigo'];
	}

	function get_materia_id()
	{
		$info = $this->modelo()->info__materia($this->get_actividad());
		return $info['id'];
	}

	function get_actividad_ra()
	{
		$info = $this->modelo()->info__materia($this->get_actividad());
		return $info['responsable_academica'];
	}

	function get_comision()
	{
		return $this->controlador->get_var('comision');
	}

	function get_mensajes()
	{
		return $this->controlador->get_var('mensajes');
	}

	/**
	 * Shortcut al modelo
	 * @return \siu\modelo\transacciones\inscripcion_cursada
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
		$this->data['prueba_stress_habilitada'] = kernel::proyecto()->prueba_stress_habilitada();
		$this->add_mensajes_js();
		switch ($this->estado) {
			case self::INICIAL:
				$this->prepare_inicial();
				break;
			// seleccioné una materia que no estoy inscripto
			case self::MATERIA_SELECCIONADA:
				$this->prepare_materia_seleccionada();
				break;

			// seleccioné una materia que no estoy inscripto Y HUBO ERRor de controles
			case self::ERROR_MATERIA_SELECCIONADA:
				$this->prepare_error_materia_seleccionada();
				break;

			// estoy inscripto a una materia pero quiero reinscribirme en otra comisión
			case self::REINSCRIPCION:
				$this->prepare_reinscripcion();
				break;
		}
		
		$this->data['csrf'] = $this->generar_csrf();
	}

	protected function add_mensajes_js()
	{
		$this->add_var_js('msg_inscripcion_exitosa', $this->get_mensajes());
		$this->add_var_js('msg_baja', kernel::traductor()->trans('mensaje_baja_cursada'));
		$this->add_var_js('msg_confirmar_baja', kernel::traductor()->trans('mensaje_confirmar_baja'));
		$this->add_var_js('msg_baja_exitosa', kernel::traductor()->trans('baja_exitosa'));
		$this->add_var_js('msg_titulo_enviar', kernel::traductor()->trans('titulo_enviar_comprobante'));
		$this->add_var_js('msg_enviar', kernel::traductor()->trans('enviar_comprobante_cursada', array('%1%' => kernel::persona()->get_mail())));
		$this->add_var_js('msg_enviar_boton', kernel::traductor()->trans('enviar_por_mail'));
		$this->add_var_js('msg_falta_mail', kernel::traductor()->trans('mail_no_cargado', array('%1%' => kernel::vinculador()->crear('configuracion'))));
		$this->add_var_js('envio_mail_exitoso', kernel::traductor()->trans('envio_mail_exitoso'));

		$mail = kernel::persona()->get_mail();
		if (empty($mail)) {
			$this->add_var_js('falta_mail', true);
		} else {
			$this->add_var_js('falta_mail', false);
		}

		$this->add_var_js('url_baja_inicial', kernel::vinculador()->crear('cursada', 'baja_inicial'));
		$this->add_var_js('url_inscripcion_prioridad', kernel::vinculador()->crear('cursada', 'inscribir_prioridad'));
		$this->add_var_js('mensaje_error_sin_seleccion_de_comision', trans('mensaje_error_sin_seleccion_de_comision'));
		$this->add_var_js('aceptar', ucfirst(kernel::traductor()->trans('aceptar')));
		$this->add_var_js('cancelar', ucfirst(kernel::traductor()->trans('cancelar')));
	}

	protected function prepare_inicial()
	{
		$parametros_cursada = array();
		$parametros_cursada['persona'] = kernel::persona()->get_id();
		$parametros_cursada['estado'] = (\inscripcion_cursada::estado_aceptada.\inscripcion_cursada::estado_pendiente);  // Aceptadas y Pendientes
		$parametros_cursada['vigentes'] = \comunes::si;
		
		$inscripciones = guarani::persona()->cursadas()->get_inscripciones(kernel::persona()->get_id_propuesta_activa(), null);

		foreach ($inscripciones as $key => $inscripcion) {
			
			$inscripciones[$key]['URL_COMP'] = $this->get_vinculo_comp($inscripcion[catalogo::id]);
			$inscripciones[$key]['URL_IMP_COMP'] = $this->get_vinculo_imp_comp($inscripcion[catalogo::id]);
			$inscripciones[$key]['URL_MAIL_COMP'] = $this->get_vinculo_mail_comp($inscripcion[catalogo::id]);
			$inscripciones[$key]['URL_BAJA'] = kernel::vinculador()->crear('cursada', 'baja_inicial');
			
		}

		$this->data['inscripciones'] = $inscripciones;
	}

	protected function get_comision_ignorar()
	{
		if ($this->controlador->hay_var('ignorar_orden_comision')) {
			return $this->controlador->get_var('ignorar_orden_comision');
		}

		return false;
	}

	protected function get_vinculo_comp($inscripcion, $actividad = null)
	{
		$parametros = array(
			'inscripcion' => $inscripcion,
		);

		if (isset($actividad)) {
			$parametros['materia'] = $actividad;
		}

		return kernel::vinculador()->crear(
						'cursada', 'generar_comprobante', $parametros
		);
	}

	protected function get_vinculo_imp_comp($inscripcion, $actividad = null)
	{
		$parametros = array(
			'inscripcion' => $inscripcion,
			'i' => 1
		);

		if (isset($actividad)) {
			$parametros['materia'] = $actividad;
		}

		return kernel::vinculador()->crear(
						'cursada', 'generar_comprobante', $parametros
		);
	}

	protected function get_vinculo_mail_comp($inscripcion, $actividad = null)
	{
		$parametros = array('inscripcion' => $inscripcion);

		if (isset($actividad)) {
			$parametros['materia'] = $actividad;
		}
		return kernel::vinculador()->crear(
						'cursada', 'enviar_comprobante', $parametros
		);
	}

	protected function esta_inscripto_a_comision($comisiones_inscriptas, $id_comision)
	{
		foreach ($comisiones_inscriptas as $com_insc) {
			if ($id_comision == $com_insc['comision']) {
				return true;
			}
		}

		return false;
	}

	protected function get_comision_inscripta_info($comisiones_inscriptas, $id_comision)
	{
		foreach ($comisiones_inscriptas as $com_insc) {
			if ($id_comision == $com_insc['comision']) {
				return $com_insc;
			}
		}

		throw new \siu\errores\error_guarani("Se está buscando la comision $id_comision en la lista de comisiones inscriptas y no existe");
	}

	protected function agregar_comision_inscripta($orden, $inscripcion_a_comision, $actividad, $comision, $agregar_al_principio)
	{
		$inscripcion_a_comision['INSCRIPTO'] = true;
		$inscripcion_a_comision['ID'] = $comision[catalogo::id];
		$inscripcion_a_comision['actividad'] = $actividad;
		$inscripcion_a_comision['URL_COMP'] = $this->get_vinculo_comp($inscripcion_a_comision[catalogo::id], $actividad);
		$inscripcion_a_comision['URL_IMP_COMP'] = $this->get_vinculo_imp_comp($inscripcion_a_comision[catalogo::id], $actividad);
		$inscripcion_a_comision['URL_MAIL_COMP'] = $this->get_vinculo_mail_comp($inscripcion_a_comision[catalogo::id], $actividad);
		$inscripcion_a_comision['URL_BAJA'] = kernel::vinculador()->crear('cursada', 'baja');

		if (!$agregar_al_principio) {
			$this->data['comisiones'][$orden] = $inscripcion_a_comision;
		} else {
			unset($this->data['comisiones'][$orden]);
			$this->data['comisiones'] = array($orden => $inscripcion_a_comision) + $this->data['comisiones'];
		}
	}

	protected function agregar_comision_no_inscripta($orden, $actividad, $comision)
	{
		$this->data['comisiones'][$orden]['INSCRIPTO'] = false;
		$this->data['comisiones'][$orden]['ID'] = $comision[catalogo::id];
		$this->data['comisiones'][$orden]['actividad'] = $actividad;
		$this->data['comisiones'][$orden]['URL_INSCRIBIRSE'] = kernel::vinculador()->crear('cursada', 'inscribir');
		$this->data['comisiones'][$orden]['tiene_cupo'] = ($comision['tiene_cupo'] == 'S') ? true : false;
		if (isset($comision['cupo']) && $comision['cupo'] > 0) {
			$this->data['comisiones'][$orden]['cupo_definido'] = true;
			$porcentaje = round(($comision['cant_inscriptos'] <= 0 ? 0 : $comision['cant_inscriptos']) / $comision['cupo'] * 100, 0);
			$this->data['comisiones'][$orden]['porcentaje_cupo'] = $porcentaje > 100 ? 100 : $porcentaje;
		} else {
			$this->data['comisiones'][$orden]['mostrar_barra_cupo'] = false;
		}
		if (isset($comision['subcomisiones'])) {
			foreach ($comision['subcomisiones'] as $key_tc => $tipo_clase) {
				foreach ($tipo_clase as $key_sc => $subcomision) {
					$this->data['comisiones'][$orden]['subcomisiones'][$key_tc][$key_sc]['tiene_cupo'] = ($subcomision['tiene_cupo'] == 'S') ? true : false;
					if (isset($subcomision['cupo']) && $subcomision['cupo'] > 0) {
						$this->data['comisiones'][$orden]['subcomisiones'][$key_tc][$key_sc]['cupo_definido'] = true;
						$porcentaje_cupo_sub = round(($subcomision['cant_inscriptos'] <= 0 ? 0 : $subcomision['cant_inscriptos']) / $subcomision['cupo'] * 100, 0);
						$this->data['comisiones'][$orden]['subcomisiones'][$key_tc][$key_sc]['porcentaje_cupo'] = $porcentaje_cupo_sub > 100 ? 100 : $porcentaje_cupo_sub;
					} else {
						$this->data['comisiones'][$orden]['subcomisiones'][$key_tc][$key_sc]['mostrar_barra_cupo'] = false;
					}
				}
			}
		}
	}

	protected function get_max_inscripcion_por_prioridad()
	{
		$info = $this->modelo()->info__materia($this->get_actividad());
		$max = ($info['insc_cursada_por_prioridad'] == comunes::si) ? $info['prioridad_cantidad_maxima'] : 1;
		return $max;
	}

	protected function prepare_materia_seleccionada()
	{
		$actividad = $this->get_actividad();

		$comisiones = $this->modelo()->info__lista_comisiones($actividad);
		$comisiones_inscriptas = $this->modelo()->info__inscripciones($actividad);

		$id_comision_no_ordenar = $this->get_comision_ignorar();

		$this->data['dias_semana'] = $this->modelo()->info__dias_semana();
		$this->data['turnos_cursadas'] = $this->modelo()->info__turnos_cursadas();
		$this->data['ubicaciones'] = $this->modelo()->info__ubicaciones();

		$this->data['comisiones'] = $comisiones;
		$this->data['existe_inscripcion'] = false;
		foreach ($comisiones as $key => $comision) {
			if ($this->esta_inscripto_a_comision($comisiones_inscriptas, $comision['comision'])) {
				$this->data['existe_inscripcion'] = true;
				$inscripcion_a_comision = $this->get_comision_inscripta_info($comisiones_inscriptas, $comision['comision']);
				$agregar_al_principio = $comision[catalogo::id] != $id_comision_no_ordenar;
				$this->agregar_comision_inscripta($key, $inscripcion_a_comision, $actividad, $comision, $agregar_al_principio);
			} else {
				$this->agregar_comision_no_inscripta($key, $actividad, $comision);
			}
		}

		$maximo = $this->get_max_inscripcion_por_prioridad();
		$this->data['mensajes'] = $this->get_mensajes();
		$this->data['materia_nombre'] = $this->get_materia_nombre();
		$this->data['materia_codigo'] = $this->get_materia_codigo();
		$this->data['materia_id'] = $this->get_materia_id();
		$this->data['materia_insc_prioridad'] = $maximo > 1;
		$this->data['prioridad_cantidad_maxima'] = $maximo;
		$this->data['responsable_academica'] = $this->get_actividad_ra();

		// parámetros
		$this->data['params']['param_cur_controlar_cupo'] = parametro::get_valor('cur_controlar_cupo', array(guarani::persona()->get_id_propuesta_activa()));
		// sedes
		$this->data['sedes'] = guarani::lista_ubicaciones();

		$this->add_var_js('es_inscripcion_prioridad', $this->data['materia_insc_prioridad']);
		$this->add_var_js('existe_inscripcion', $this->data['existe_inscripcion']);
		$this->add_var_js('prioridad_cantidad_maxima', $this->data['prioridad_cantidad_maxima']);
		if ($maximo > 1) {
			$this->add_var_js('msg_max_inscripcion_prioridad_excedida', kernel::traductor()->trans('800CUR_max_inscripcion_prioridad_excedida', array('%1%' => $maximo)));
			$this->add_var_js('msg_comisiones_igual_periodo_lectivo', kernel::traductor()->trans('800CUR_comisiones_igual_periodo_lectivo'));
		}
		$this->add_var_js('msg_confirma_desea_inscribirse_a_cursar', 
					kernel::traductor()->trans('800CUR_confirmar_alta_insc', array(
					'%1%' => $this->get_materia_codigo(),
					'%2%' => $this->get_materia_nombre()
		)));
	}

	protected function prepare_error_materia_seleccionada()
	{
		$this->data['materia_nombre'] = $this->get_materia_nombre();
		$this->data['materia_codigo'] = $this->get_materia_codigo();
		$this->data['mensajes'] = $this->get_mensajes();
	}

	protected function prepare_reinscripcion()
	{
		
	}

}