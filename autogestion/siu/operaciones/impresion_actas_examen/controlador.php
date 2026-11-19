<?php
namespace siu\operaciones\impresion_actas_examen;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\errores\error_guarani;
use SIU\Chulupi\nucleo\FinalizadorRequest;

class controlador extends controlador_g3w2
{
	public $llamado_mesa_params;

	function get_clase_vista()
	{
		return 'vista_actas';
	}

	function modelo()
	{
		return guarani::impresion_actas_examen();
	}

	function get_info_zona()
	{
		if (isset($this->llamado_mesa_params['llamado_mesa'])) {
			// Si no hay selección, no se activa la zona
			return array('zona' => 'examen', 'seleccion' => $this->llamado_mesa_params);
		}
	}

	function accion__index()
	{
		$this->llamado_mesa_params = $this->decodificar_param(0, 'get', '*');
	}

	function accion__imprimir_acta()
	{
		try {
			$acta = $this->decodificar_param(0, 'get');
			$comprobante = $this->modelo()->generar_comprobante($acta);
			$this->descargar_archivo($comprobante['path'], $comprobante['nombre_descarga'], true);
		} catch (FinalizadorRequest $e) {
			// Si se logra descargar el PDF se lanza una excepción de tipo 'FinalizadorRequest', esto no se trata de un error.
			throw $e;
		} catch (\Exception $e) {
			kernel::log()->add_error($e);
            $mensaje = kernel::traductor()->trans('impresion_actas_fallo_impresion');
			kernel::sesion()->set('error_imprimir_acta', $mensaje);
			$this->nuevo_request_url($_SERVER["HTTP_REFERER"]);
		}
	}

	function accion__cerrar_acta()
	{
		try {
			$this->llamado_mesa_params = $this->decodificar_param(0, 'get', '*');
			$params_acta = $this->llamado_mesa_params;
			kernel::log()->add_debug("===== Parametros acta =====", $params_acta);

			$notificaciones = $this->modelo()->evt__cerrar_acta($params_acta);
			$mensajes_notificacion = $this->get_mensajes_notificacion($notificaciones);
			kernel::renderer()->add($this->vista()->pagelet('actas'));

			kernel::renderer()->add_to_ajax_response('mensaje', kernel::traductor()->trans('acta_examen_cerrada_ok'));
			kernel::renderer()->add_to_ajax_response('notificaciones', $mensajes_notificacion);
		} catch (error_guarani $e) {
			$this->finalizar_request_con_notificaciones($e->get_mensaje(), $e->get_mensajes_usuario());
		} catch (\guarani_error $e) {
			$this->finalizar_request_con_notificaciones($e->get_mensaje(), $e->get_mensajes_usuario());
		}
	}
	
	function get_mensajes_notificacion($notificaciones)
	{
		$mensajes_notificacion = array();
		if (!empty($notificaciones)) {
			foreach ($notificaciones as $notificacion) {
				$params = array();
				$i = 1;
				foreach ($notificacion['params'] as $param) {
					$params["%$i%"] = $param;
					$i++;
				}
				$mensajes_notificacion[] = kernel::traductor()->trans($notificacion['descripcion_corta'], $params);
			}
		}
		return $mensajes_notificacion;
	}

}