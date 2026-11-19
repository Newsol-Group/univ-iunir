<?php
class g3_error extends toba_error
{
	protected $errores;
	protected $hay_critico;

	function __construct($mensajes, $mensaje_solo_debug = '', $titulo_ventana = null)
	{
		if (is_array($mensajes)) {
			$this->set_errores($mensajes);
			$this->determinar_error_critico();
			$mensaje_visible = implode("\n", aplanar_matriz($mensajes, 'mensaje'));
		} else {
			$this->hay_critico = true;
			$mensaje_visible = $mensajes;
		}
		parent::__construct($mensaje_visible, $mensaje_solo_debug, $titulo_ventana);
	}

	function set_errores($errores)
	{
		$this->errores = $errores;
	}

	function procesar_con_metodo_anterior()
	{
		return (!isset($this->errores) || guarani_arreglos::count($this->errores) == 1);
	}

	function determinar_error_critico()
	{
		if (!isset($this->hay_critico)) {
			$this->hay_critico = false;
			foreach ($this->errores as $error) {
				if ($error['tipo'] == comunes::error_critico) {
					$this->hay_critico = true;
					break;
				}
			}
		}
	}

	function get_errores()
	{
		if (!isset($this->errores)) {
			return array('mensaje' => $this->get_mensaje(), 'tipo' => comunes::error_critico);
		}
		return $this->errores;
	}

	function hay_error_critico()
	{
		return $this->hay_critico;
	}

	function notificar()
	{
		if (!isset($this->errores)) {
			toba::logger()->error($this->get_mensaje());
			toba::notificacion()->agregar($this->get_mensaje(), comunes::error_critico);
		} else {
			foreach ($this->errores as $error) {
				toba::logger()->error($error['mensaje']);
				toba::notificacion()->agregar($error['mensaje'], $error['tipo']);
			}
		}
	}

}
?>
