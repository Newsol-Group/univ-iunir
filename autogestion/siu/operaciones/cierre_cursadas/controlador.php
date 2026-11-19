<?php
namespace siu\operaciones\cierre_cursadas;

use \SIU\Chulupi\kernel;
use \SIU\Chulupi\util\validador;
use siu\errores\error_guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo\transacciones\cierre_actas_cursada;
use SIU\Chulupi\nucleo\FinalizadorRequest;

class controlador extends controlador_g3w2
{
	protected $param_acta;

	function get_clase_vista()
	{
		return 'vista_actas';
	}

	function get_info_zona()
	{
		return isset($this->param_acta) ? array('zona' => 'comision', 'seleccion' => $this->param_acta) : null;
	}

	/**
	 * @return cierre_actas_cursada
	 */
	function modelo()
	{
		return guarani::cierre_actas_cursada();
	}

	function accion__index()
	{
		if (kernel::url()->hay_param(0)) {
			$this->param_acta = $this->decodificar_param(0, 'get');
			$this->vista()->pagelet('actas')->filtrar_comision($this->param_acta);
		}
	}

	function accion__cerrar_acta()
	{
		// Si la petición NO es POST salgo
		if(!kernel::request()->isPost()) return false;
		try {
			$this->param_acta = $this->decodificar_param(0, 'get');
			kernel::log()->add_debug("===== Parametros acta =====", $this->param_acta);
			$notificaciones = $this->modelo()->evt__cerrar_acta($this->param_acta);
			$mensajes_notificacion = $this->get_mensajes_notificacion($notificaciones);
			kernel::renderer()->add($this->vista()->pagelet('actas'));
			$this->vista()->pagelet('actas')->filtrar_comision($this->param_acta);
			kernel::renderer()->add_to_ajax_response('mensaje', kernel::traductor()->trans('acta_cursada_cerrada_correctamente'));
			kernel::renderer()->add_to_ajax_response('notificaciones', $mensajes_notificacion);
		} catch (error_guarani $e) {
			$this->finalizar_request_con_notificaciones($e->get_mensaje(), $e->get_mensajes_usuario());
		}
	}

	function accion__mostrar_comprobante()
	{
		$acta_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$this->param_acta = $this->decodificar_hash($acta_hash);
		$paglet_comprobante = $this->vista()->pagelet('comprobante');
		$paglet_comprobante->set_acta_hash($acta_hash);
		$paglet_comprobante->set_acta_params($this->param_acta);
	}

	function accion__imprimir_comprobante()
	{
		try {
			$this->param_acta = $this->decodificar_param(0, 'get');
			$comprobante = $this->modelo()->generar_comprobante($this->param_acta);
			$this->descargar_archivo($comprobante['path'], $comprobante['nombre_descarga']);
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

	function get_mensajes_notificacion($notificaciones)
	{
		$mensajes_notificacion = array();
		foreach ($notificaciones as $notificacion) {
			$params = array();
			$i = 1;
			foreach ($notificacion['params'] as $param) {
				$params["%$i%"] = $param;
				$i++;
			}
			$mensajes_notificacion[] = kernel::traductor()->trans($notificacion['descripcion_corta'], $params);
		}
		return $mensajes_notificacion;
	}

}