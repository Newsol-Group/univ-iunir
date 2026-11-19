<?php
namespace siu\operaciones\encuestas_kolla;

use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use siu\modelo\entidades\encuestas_kolla;

class controlador extends controlador_g3w2
{
	function modelo()
	{
		return null;
	}
	
	function accion__index()
	{
		$pendiente_id = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM, array(
			'allowempty' => true,
			'default' => null
		)); 

		$pendientes = kernel::persona()->get_encuestas_pendientes();

		if(empty($pendientes)) return; //va a una vista estatica.

		$this->add_var('encuestas_pendientes', $pendientes); //para la lista
		$this->add_var('url_formulario', null); //inicializo para el iframe
		$this->add_var('hash_encuesta_seleccionada', $pendiente_id); //Hash de la encuesta seleccionada
		$this->add_var('encuesta_seleccionada_tipo_relevamiento', null); //Tipo de relevamiento de la encuesta seleccionada
		
		if (!empty($pendiente_id)) {
			$pedida = $this->validar_esta_pendiente($pendiente_id, $pendientes);			
			if ($pedida != null) {
				$codigo_externo = $pedida['cui'];
				$password = $pedida['password'];
				$habilitacion = $pedida['habilitacion'];
				$formulario = $pedida['formulario'];
				$url = encuestas_kolla::generar_url($habilitacion, $formulario, $codigo_externo, $password);
                $this->add_var('url_formulario', $url); //para el iframe
                $this->add_var('encuesta_seleccionada_id', $pedida['id']); //id de la encuesta seleccionada
				$this->add_var('encuesta_seleccionada_tipo_relevamiento', $pedida['tipo_relevamiento']); //Tipo de relevamiento de la encuesta seleccionada
			}

		}
		$pagelet = $this->vista()->pagelet('encuesta');
		kernel::renderer()->add($pagelet);
	}
	
	/**
	 * Viene por ajax cuando se captura el callback de js.
	 */
	function accion__encuesta_respondida(){

        if(kernel::request()->isPost()){

            $token = $this->get_param('t', 'post'); //viene un hash.. se va a decodificar y despues se deberia validar
            $habilitacion = $this->validate_param('h', 'post', validador::TIPO_INT);
            $fecha_respuesta = $this->validate_param('fecha_respuesta', 'post', validador::TIPO_DATE, array('format' => 'Y-m-d', 'default' => null, 'allowempty' => true));

            $ajax_response = [];
            $ajax_response['error_habilitacion'] = false;
            try{
                $a = kernel::persona()->set_encuestas_respondidas($habilitacion, $token, $fecha_respuesta);
                kernel::log()->add_debug('Param- token decodificado', $a);
                $ajax_response['materia'] = $a['formulario'];
                $ajax_response['codigo'] = $a['codigo_externo'];
            }
            catch(ErrorKernel $e){
                $ajax_response['error_habilitacion'] = true;
                $ajax_response['mensaje'] = $e->get_mensaje();
                $ajax_response['materia'] = "";
                $ajax_response['codigo'] = "";
            }

            $this->render_ajax('mensaje', $ajax_response);  // "hola mundo";

        }

	}
	
	/**
	 * Busco en la lista de pendientes si hay una con el id seleccionado
	 * @return mixed la fila si existe, sino null.
	 */
	private function validar_esta_pendiente($pendiente_id, $pendientes){
		foreach ($pendientes as $key => $encuesta){	
			if($encuesta['id'] == $pendiente_id	) 
			{
				return $pendientes[$key];
			}
		}
		return null;
	}
}