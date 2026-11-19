<?php
namespace siu\operaciones\alta_usuario_cursos;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\u;
use siu\modelo\datos\catalogo;

class pagelet_documentacion extends pagelet
{
	protected $mensaje_error;
	protected $form;
	
	function get_nombre()
    {
        return 'documentacion';
    }
	
	function get_form($id_html = null)
	{
		
	}
	
	function get_js_files()
	{
		$files = parent::get_js_files();
		$files[] = kernel::vinculador()->vinculo_recurso("js/jqueryui.extra.js");
		return $files;
	}
	
	function set_mensaje_error($mensaje)
	{
		$this->mensaje_error = $mensaje;
	}
	
	function prepare()
	{	
		$this->add_var_js('msj_confirmar_eliminar', kernel::traductor()->trans('alta_uc.req.msj_confirmar_eliminar'));
		
		if (true) {
			$this->data['mensaje_error'] = $this->controlador->get_var('mensajes');
		}

        if(kernel::sesion()->esta_seteada('error_archivo_subido')){
            $this->data['error_archivo_subido'] = kernel::sesion()->get('error_archivo_subido');
            kernel::sesion()->borrar('error_archivo_subido');
        }

		$this->data['url_volver'] = kernel::vinculador()->crear('alta_usuario_cursos');
		$this->data['url_crear_usuario'] = kernel::vinculador()->crear('alta_usuario_cursos', 'finalizar');
		
		$propuesta = $this->controlador->get_propuesta_seleccionada();
		$requisitos_a_presentar = $this->controlador->modelo()->info__requisitos_cursos($propuesta);
		
		$this->data['requisitos'] = array();
		foreach($requisitos_a_presentar as $requisito) {
			$clave = $requisito['requisito'];
			$clave_encriptada = catalogo::generar_id($clave);
			$conf = array();
			
			if ($this->controlador->requisito_cargado($clave_encriptada)) {
				$path_rel_url = 'temp/' . $this->controlador->get_nombre_archivo_cargado($clave_encriptada);
				$conf['imagen'] = kernel::proyecto()->get_alias().'/'.$path_rel_url;
				$conf['url_eliminar'] = kernel::vinculador()->crear('alta_usuario_cursos', 'eliminar_documentacion', array($clave_encriptada));
			} else {
				$conf['url_subir'] = kernel::vinculador()->crear('alta_usuario_cursos', 'upload_documentacion', array($clave_encriptada));
			}
			
			$conf['header'] = $requisito['nombre_descr']; 
			$conf['id_form'] = 'form_' . $clave_encriptada; 
			
			$this->data['requisitos'][$clave] = $conf;
		}

        /**
         * Obtiene el tamaño máximo de un fichero subido.
         * @see http://php.net/manual/es/ini.core.php#ini.upload-max-filesize
         */
        $tamanio_maximo_archivo_subido = ini_get('upload_max_filesize');

        // Si 'upload_max_filesize' es distinto a 0 (0 representa ilimitado)
        if ($tamanio_maximo_archivo_subido != 0){
            $tamanio_maximo_archivo_subido_bytes = u::return_bytes($tamanio_maximo_archivo_subido);
            $tamanio_maximo_archivo_subido_formateado = u::formatBytes($tamanio_maximo_archivo_subido_bytes);
            $this->data['advertencia_tam_max_archivo'] = kernel::traductor()->trans('alta_uc.req.advertencia', array(
                    '%tam_max_archivo%' => $tamanio_maximo_archivo_subido_formateado
            ));
        }

	}
	
}
?>