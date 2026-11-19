<?php

namespace nsg01\modelo\transacciones;

use SIU\Chulupi\kernel;
use SIU\Chulupi\util\MailJob;
use siu\modelo\datos\catalogo;
use siu\modelo\_comun\abm;
use siu\operaciones\datos_censales\_comun\form_censo;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use siu\modelo\entidades\parametro;

class datos_censales extends \siu\modelo\transacciones\datos_censales
{
	const EMAIL_ADMINISTRACION = 'administracion@iunir.edu.ar';
	const SESION_ERROR_NOTIFICACION = 'nsg01.datos_censales.error_notificacion_honorarios';
	const MENSAJE_ERROR_NOTIFICACION = 'Los datos fueron guardados, pero no fue posible notificar a Administración. Por favor, comuníquese con el sector.';

	function evt__guardar($seccion, $datos)
	{
		$cambio = $this->detectar_cambio_datos_honorarios($seccion, $datos);

		parent::evt__guardar($seccion, $datos);

		if (!is_null($cambio)) {
			$this->encolar_notificacion_datos_honorarios($cambio);
		}
	}

	function opciones__trabajo_ocupacion()
	{

		if (kernel::persona()->es_perfil_docente()){
			$datos = \toba::consulta_php('co_personas', true)->get_cond_iva();
		}
		else {
			$datos = \toba::consulta_php('co_personas', true)->get_trabajos_ocupaciones();
		}

		return $this->armar_combo_opciones($datos, 'valor', 'descr', true, false);
	}

	protected function detectar_cambio_datos_honorarios($seccion, $datos)
	{
		if (!kernel::persona()->es_perfil_docente()) {
			return null;
		}

		if ($seccion === 'datos_personales') {
			$cuil_actual = kernel::persona()->get_cuil();
			$valor_anterior = isset($cuil_actual['nro_documento']) ? trim((string) $cuil_actual['nro_documento']) : '';
			$valor_nuevo = isset($datos['cuil']) ? trim((string) $datos['cuil']) : '';

			// El comportamiento base solo permite cargar el CUIL/CUIT si aun no existe.
			if ($valor_anterior === '' && $valor_nuevo !== '') {
				return $this->crear_cambio('CUIL/CUIT', $valor_anterior, $valor_nuevo);
			}
		}

		if ($seccion === 'datos_laborales') {
			$datos_actuales = $this->info__datos_censales();
			$valor_anterior = isset($datos_actuales['trabajo_tarea_descripcion'])
				? trim((string) $datos_actuales['trabajo_tarea_descripcion'])
				: '';
			$valor_nuevo = isset($datos['trabajo_tarea_descripcion'])
				? trim((string) $datos['trabajo_tarea_descripcion'])
				: '';

			if ($valor_anterior !== $valor_nuevo) {
				return $this->crear_cambio('CBU', $valor_anterior, $valor_nuevo);
			}
		}

		return null;
	}

	protected function crear_cambio($campo, $valor_anterior, $valor_nuevo)
	{
		return array(
			'campo' => $campo,
			'valor_anterior' => $this->enmascarar_valor($valor_anterior),
			'valor_nuevo' => $this->enmascarar_valor($valor_nuevo),
		);
	}

	protected function enmascarar_valor($valor)
	{
		$valor = trim((string) $valor);
		if ($valor === '') {
			return 'Sin informar';
		}

		$cantidad_visibles = min(4, strlen($valor));
		return str_repeat('*', strlen($valor) - $cantidad_visibles) . substr($valor, -$cantidad_visibles);
	}

	protected function encolar_notificacion_datos_honorarios($cambio)
	{
		$persona = kernel::persona();
		$nombre_docente = str_replace(array("\r", "\n"), ' ', $persona->get_nombre());
		$documento = $persona->get_tipo_documento_desc_abreviada() . ' ' . $persona->get_nro_documento();

		$asunto = 'Actualizacion de datos para honorarios - ' . $nombre_docente;
		$cuerpo = '<p>Un docente actualizó datos relacionados con el cobro de honorarios desde Autogestión.</p>'
			. '<ul>'
			. '<li><strong>Docente:</strong> ' . $this->escapar_html($nombre_docente) . '</li>'
			. '<li><strong>Legajo:</strong> ' . $this->escapar_html($persona->get_legajo_docente()) . '</li>'
			. '<li><strong>Documento:</strong> ' . $this->escapar_html($documento) . '</li>'
			. '<li><strong>Campo modificado:</strong> ' . $this->escapar_html($cambio['campo']) . '</li>'
			. '<li><strong>Valor anterior:</strong> ' . $this->escapar_html($cambio['valor_anterior']) . '</li>'
			. '<li><strong>Valor nuevo:</strong> ' . $this->escapar_html($cambio['valor_nuevo']) . '</li>'
			. '<li><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</li>'
			. '</ul>';

		try {
			$mail_job = new MailJob('', $asunto, $cuerpo, null, '', 'utf-8');
			$mail_job->set_hacia(array(self::EMAIL_ADMINISTRACION));
			$mail_job->set_html(true);
			$mail_job->enviar();
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
			kernel::sesion()->set(self::SESION_ERROR_NOTIFICACION, true);
		}
	}

	protected function escapar_html($valor)
	{
		return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
	}

}
