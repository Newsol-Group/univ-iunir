<?php
namespace siu\operaciones\mensajes;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use \siu\modelo\datos\catalogo;

class pagelet_lista_mensajes extends pagelet
{   
    const E_ENVIADOS  = 'enviados';
    const E_RECIBIDOS = 'recibidos';
    
    function ini()
    {
    }
	
    function get_nombre()
    {
        return 'lista_mensajes';
    }
    
    function modelo()
    {
        return $this->controlador->modelo();
    }
   
    protected function agregar_url_leer($mensajes, $accion)
    {
            foreach($mensajes as $key => $mensaje)
            {
                    $mensajes[$key]['URL_LEER'] = kernel::vinculador()->crear('mensajes', $accion, $mensaje[catalogo::id]);
                    if (isset($mensaje['ADJUNTOS']))
                    {
                            foreach($mensaje['ADJUNTOS'] as $key_adj => $archivo)
                            {
                                
                                $url_descarga = $archivo['url_archivo'];
                                // Si la columna 'url_archivo' viene vacía genero la URL
                                if(!$url_descarga){
                                    $url_descarga = kernel::vinculador()->crear('mensajes', 'descargar', array( 
                                                        'm' => $mensaje[catalogo::id], 
                                                        'a' => $archivo[catalogo::id], 
                                                        'e' => $this->get_estado(), 
	 	                                    )); 
                                }
                                
                                $mensajes[$key]['ADJUNTOS'][$key_adj]['URL_DESCARGA'] = $url_descarga;
                                $mensajes[$key]['ADJUNTOS'][$key_adj]['NOMBRE_ORIGINAL'] = $archivo['nombre_archivo'].'.'.$archivo['extension'];
                            }
                    }
            }
            return $mensajes;
    }

	/**
	 * Se genera este metodo para no cargar el notificador, al tener el titulo personalizado
	 * el mensaje que se muestra es genérico
	 */
	protected function asunto_mensaje_cursar_rendir($mensajes)
    {
            foreach($mensajes as $key => $mensaje)
            {
                if($mensaje['titulo'] === 'Aviso para %estado%'){
					$mensajes[$key]['titulo']= 'Aviso para rendir o cursar la materia';
				}    
					
            }
            return $mensajes;
    }

    function prepare()
	{
		$operacion = kernel::ruteador()->get_id_operacion();
		
		switch ($this->get_estado()) {

			case self::E_RECIBIDOS:

				$mensajes = $this->modelo()->get_mensajes_recibidos();
				$mensajes = $this->agregar_url_leer($mensajes, 'leer_mensaje');
				$mensajes = $this->asunto_mensaje_cursar_rendir($mensajes);
				$this->data['url_enviados'] = kernel::vinculador()->crear($operacion, 'enviados');
				break;

			case self::E_ENVIADOS:

				$parametros['ultimos'] = null;
				$parametros['fecha_hasta'] = null;
				$parametros['fecha_desde'] = null;

				if (isset($_POST['fecha_desde']) && !empty($_POST['fecha_desde'])) {
					$fecha = str_replace('/', '-', $_POST['fecha_desde']);
					$parametros['fecha_desde'] = date("Y-m-d", strtotime($fecha));
				}
				if (isset($_POST['fecha_hasta']) && !empty($_POST['fecha_hasta'])) {
					$fecha = str_replace('/', '-', $_POST['fecha_hasta']);
					$parametros['fecha_hasta'] = date("Y-m-d", strtotime($fecha));
				}
				if ($parametros['fecha_desde'] > $parametros['fecha_hasta'] && !empty($_POST['fecha_desde']) && !empty($_POST['fecha_hasta'])) {
					$this->add_var_js('mensaje_fecha_error', true);
				}
				if (isset($_POST['ultimos']) && $_POST['ultimos'] > 0 && $_POST['ultimos'] <= 100) {

					$parametros['ultimos'] = $_POST['ultimos'];
				}
				$mensajes = $this->modelo()->get_mensajes_enviados($parametros);
				$mensajes = $this->agregar_url_leer($mensajes, 'leer_mensaje_enviado');

				foreach ($mensajes as $key => $mensaje) {

					//Obtenga los nombres y apellidos de las personas, concatenado con el id de persona entre parentesis
					$nombres = $this->modelo()->get_personas_destinatarios_alumnos($mensaje['mensaje']);

					foreach ($nombres as $key2 => $nombre) {
						if (isset($mensajes[$key]['nombre_alumno'])) {
							$mensajes[$key]['nombre_alumno'] = $mensajes[$key]['nombre_alumno'] . ' - ' . $nombres[$key2]['apellido_nombres'];
						} else {
							$mensajes[$key]['nombre_alumno'] = $nombres[$key2]['apellido_nombres'];
						}
					}

					$mensajes[$key]['comisiones'] = $this->modelo()->get_comisiones($mensaje['mensaje']);
					$mensajes[$key]['mesa'] = $this->modelo()->get_mesa($mensaje['mensaje']);
				}

				$this->data['url_recibidos'] = kernel::vinculador()->crear($operacion);
				break;
		}


		$this->data['error'] = $this->controlador->get_var('error');
		$this->data['mensajes'] = $mensajes;
		
		if (kernel::persona()->es_docente()) {
			$operacion = kernel::ruteador()->get_id_operacion();
			$this->data['url_enviar'] = kernel::vinculador()->crear($operacion, 'enviar_mensaje');
			$this->data['puede_enviar'] = true;
		} else {
			$this->data['puede_enviar'] = true;
		}

		$this->data['es_docente'] = kernel::persona()->es_docente();
		$this->data['es_perfil_activo_docente'] = kernel::persona()->es_perfil_docente();

		if (kernel::sesion()->esta_seteada('mensaje_enviado_ok')) {
			kernel::sesion()->borrar('mensaje_enviado_ok');
			$this->add_var_js('mensaje_enviado_ok', true);
			$this->add_mensaje_js('mensaje_enviado_ok_msj', ucfirst(kernel::traductor()->trans('mensajes_enviar_exito')));
		}
		$this->data['alcance_grupo_alumnos'] = \mensajes::grupo_alumnos;
		$this->data['alcance_comisiones'] = \mensajes_nucleo::alumnos_comision;
		$this->data['alcance_mesa'] = \mensajes_nucleo::alumnos_mesa_examen;
	}

}
?>
