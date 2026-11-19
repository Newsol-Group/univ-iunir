<?php
namespace siu\operaciones\notificador;

use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;

class controlador extends controlador_g3w2
{
    
   /**
    * @return null
    */
    function modelo()
    {
        return null;
    }
    
    function accion__descargar_adjunto()
    {
        
        $hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
        $datos_archivo = \toba::consulta_php('co_mensajes')->get_archivo_adjunto_by_hash($hash);
        $this->descargar_archivo($datos_archivo['path_archivo']);
        
    }

    function accion__borrar_adjunto()
    {
        
        $hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
        $datos_archivo = \toba::consulta_php('co_mensajes')->get_archivo_adjunto_by_hash($hash);

		if (file_exists($datos_archivo['path_archivo'])) {
			$dir = dirname($datos_archivo['path_archivo']);
			unlink($datos_archivo['path_archivo']);
			rmdir($dir);
		}

		\guarani::act('act_mensajes')->eliminar_rep_archivo($datos_archivo['id_archivo']);
        
		$this->finalizar_request();
    }
}