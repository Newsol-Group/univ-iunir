<?php
namespace siu\operaciones\reinscripcion;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_errores_control;
use siu\guarani;
use SIU\Chulupi\util\validador;

class controlador extends controlador_errores_control
{
	function modelo()
	{
		return guarani::reinscripcion_carerra();
	}

	function accion__index()
	{
		try {
            $this->modelo()->puede_reinscribirse();
		} catch (\siu\errores\error_guarani_usuario $e) {
            $this->add_var('mensajes',array(0 => array('mcuerpo'=> $e->get_mensaje())));
		}
	}

	function accion__reinscribir()
	{
        $operacion_retorno = false;
        $mensajes = array();
        if (kernel::request()->isPost()) {
            try{
                $this->modelo()->puede_reinscribirse();
                $respuesta = $this->modelo()->reinscribir();
                static::desactivar_modo_reinscripcion();
                $this->add_var('url_comprobante',kernel::vinculador()->crear('reinscripcion', 'generar_comprobante' ,array($respuesta['estado'])));
                
                if (!empty($respuesta['advertencia'])){
                    $mensajes = $respuesta['advertencia'];
                }
                if ($respuesta['estado'] != false){
                    $this->add_var('reinscripcion_aceptada',true);
                    $mensajes[] = array('mtipo'=>'','mcuerpo'=> kernel::traductor()->trans('reinscripcion_completa'));
                }
                
                $operacion_retorno = static::get_operacion_retorno();
            } catch (\siu\errores\error_guarani_usuario $e) {
                $mensajes[] = array('mtipo'=>'E','mcuerpo'=> $e->get_mensaje());
            }
        }
        $this->add_var('mensajes',$mensajes);

        if (!empty($operacion_retorno)) {
            $this->nuevo_request($operacion_retorno);
        }
	}

	function accion__generar_comprobante()
	{
		$nro_trans = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
		$datos = $this->modelo()->datos_comprobante($nro_trans);
		$path_archivo = kernel::proyecto()->get_dir_temp().'/reins_'.  uniqid().'.png';
		$generador_comp = kernel::localizador()->instanciar('operaciones\reinscripcion\generador_comprobantes_reinscripcion');
		$generador_comp->set_path_destino($path_archivo);
		$generador_comp->set_tipo_generacion(generador_comprobantes_reinscripcion::TIPO_GENERACION_STREAM);
		$generador_comp->generar_comprobante($datos);
		$this->finalizar_request();
	}

	static function activar_modo_reinscripcion($op_retorno)
	{
		kernel::sesion()->set('datos_censales_reinscripcion', 1);
		kernel::sesion()->set('datos_censales_retorno', $op_retorno);
	}

	static function desactivar_modo_reinscripcion()
	{
		kernel::sesion()->borrar('datos_censales_reinscripcion');
	}

	protected static function get_operacion_retorno()
	{
		if (kernel::sesion()->esta_seteada('datos_censales_retorno')){
			return kernel::sesion()->get('datos_censales_retorno');
		} else {
			return false;
		}
	}

	protected function modelo__puede_reinscribirse()
	{
		$this->add_var('mensajes', $this->modelo()->puede_reinscribirse());
	}

	protected function modelo__reinscribir()
	{
		$this->add_var('mensajes', $this->modelo()->reinscribir());
	}
}
?>