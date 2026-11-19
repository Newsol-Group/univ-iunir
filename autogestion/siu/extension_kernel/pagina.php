<?php

namespace siu\extension_kernel;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagina as pagina_base;
use siu\guarani;
use siu\modelo\entidades\persona;
use siu\modelo\entidades\parametro;
use siu\modelo\_comun\comunes;

class pagina extends pagina_base
{
    function get_titulo_completo()
    {
        $titulo = kernel::traductor()->trans('nombre_sistema');
        return "$titulo - " . $this->get_etiqueta('titulo');
    }

    protected function get_contexto_template()
    {
        $contexto = parent::get_contexto_template();

        $test_stress = defined('TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION') && (TEST_STRESS__NUNCA_ACTIVAR_EN_PRODUCCION == 1);
        if (!$test_stress) {
            $contexto['url_img_logo'] = kernel::vinculador()->vinculo_img('logo-transparente.png');
        } else {
            $contexto['url_img_logo'] = kernel::vinculador()->vinculo_img('logo-transparente_stress.png');
        }

        $contexto['url_favicon'] = kernel::vinculador()->vinculo_img('favicon.ico');
        $contexto['urls_guarani_css'] = kernel::vinculador()->vinculo_css('guarani');
        $contexto['personalizacion'] = kernel::proyecto()->get_personalizacion_activa();
        $contexto['personalizaciones'] = kernel::proyecto()->get_personalizaciones();
        $contexto['mash_personalizado'] = \guarani_arreglos::count($contexto['urls_guarani_css']) > 1;
        $contexto['url_img_logo_celular'] = kernel::vinculador()->vinculo_img('logo-celular.png');
        $contexto['url_inicio'] = kernel::vinculador()->crear_home();
        $contexto['barra_dev'] = kernel::proyecto()->get('barra_dev');
        $contexto['log_id'] = kernel::log()->get_id();
        $contexto['url_log'] = kernel::vinculador()->crear_sin_chequeo('logger');
        $contexto['url_log_info'] = kernel::vinculador()->crear_sin_chequeo('logger', 'ajax_info_log');
        $contexto['es_produccion'] = kernel::proyecto()->get_produccion();
        $contexto['log_activo'] = kernel::log()->esta_activo();
        $contexto['autocompletar_input_habilitado'] = kernel::proyecto()->autocompletar_input_habilitado();

        /*
         * Lo tuve que agregar acá, ya que con el perfil Anónimo la sesión está cerrada
         * https://api.jqueryui.com/autocomplete/#option-minLength
         */
        $contexto['ui_autocomplete_min_length'] = kernel::proyecto()->get_ui_autocomplete_min_length();

        // Para el perfil Anónimo termina aquí
        if (!kernel::sesion()->abierta()) {
            return $contexto;
        }

        if (kernel::persona()->perfil()->es(persona::perfil_alumno)) {
            $carrera_activa = kernel::persona()->get_info_propuesta_activa();

            $contexto['persona']['carrera_activa'] = $carrera_activa;
            $lista_carreras = kernel::persona()->get_lista_propuestas();
            $contexto['persona']['carreras'] = array(
                'lista' => $lista_carreras,
                'cantidad' => \guarani_arreglos::count($lista_carreras),
                'url_operacion' => kernel::vinculador()->crear('cursada')
            );
            $contexto['es_ingresante'] = kernel::persona()->es_ingresante();
			$contexto['alumno_calidad_pasivo'] = \alumno::calidad_pasivo;
        }

        $contexto['perfiles'] = kernel::persona()->get_perfiles();
        $contexto['perfil_activo'] = kernel::persona()->get_id_perfil_activo();
        $contexto['perfil'] = kernel::persona()->perfil();
        $contexto['url_cambio_perfil'] = kernel::vinculador()->crear('acceso', 'perfil');
        $contexto['url_cambio_carrera'] = kernel::vinculador()->crear('acceso', 'cambiar_carrera');
        $contexto['persona']['url_logout'] = kernel::vinculador()->crear('acceso', 'logout');
        $contexto['persona']['url_configuracion'] = kernel::vinculador()->crear('configuracion');
        $contexto['ga_params'] = kernel::proyecto()->get_ga_params();
        $contexto['url_mensajes'] = kernel::vinculador()->crear('mensajes');
        $datos = guarani::persona()->get_cantidad_mensajes();
        
        if ($datos) {
            $cantidad_mensajes = $datos['total'] - $datos['leidos'];
            $contexto['cantidad_mensajes'] = $cantidad_mensajes;
        } else {
            $contexto['cantidad_mensajes'] = 0;
        }

        if (isset($contexto['perfil_activo']) && ($contexto['perfil_activo'] == persona::perfil_alumno)) {
            $contexto['url_requisitos'] = kernel::vinculador()->crear('requisitos_ingreso');
            $contexto['cant_requisitos'] = guarani::persona()->get_cant_requisitos_pendientes();
        }

        $contexto['perfil_cursos'] = persona::perfil_cursos;
		$contexto['tiene_perfil_cursos'] = in_array(persona::perfil_cursos, $contexto['perfiles']);

        if (isset($contexto['perfil_activo'])) {
            //Si el perfil activo es Cursos le muestro un boton para salir del mismo
            if ($contexto['perfil_activo'] == persona::perfil_cursos) {
                $existe_perfil_activo_previo = kernel::sesion()->esta_seteada('perfil_activo_previo');
                $contexto['existe_perfil_activo_previo'] = $existe_perfil_activo_previo;
				$get_id_persona= array ('persona' => kernel::sesion()->get_id_persona());
				$cant_perfiles = persona::cantidad_perfiles($get_id_persona);
						
					// Si vino a cursos de un perfil previo lo redirigo al mismo, sino me fijo que tenga algun perfil mas que no sea cursos para dirigirlo alli.
					if ($existe_perfil_activo_previo) {
						$perfil_activo_previo = kernel::sesion()->get('perfil_activo_previo');
						$contexto['url_salir_cursos'] = kernel::vinculador()->crear('acceso', 'perfil', array('id' => $perfil_activo_previo));
					}else{

						$perfil_elegido = null;

						foreach($perfiles as $perfil){	
								if ($perfil!='Cursos'){
									$perfil_elegido = $perfil;
									break;
								}
						}
						$contexto['url_salir_cursos'] = kernel::vinculador()->crear('acceso', 'perfil', array('id' =>  $perfil_elegido));
					}
				$contexto['cant_perfiles'] = $cant_perfiles;
            } else {//sino le muestro un boton para ir a cursos
                $contexto['url_oferta_cursos'] = kernel::vinculador()->crear('acceso', 'perfil', array('id' => persona::perfil_cursos));
            }
        }

        if (parametro::get_valor(parametro::cur_alta_usuario_cursos_autogestion) == comunes::si) {
            $contexto['utiliza_cursos'] = comunes::si;
        } else {
            $contexto['utiliza_cursos'] = comunes::no;
            unset($contexto['perfiles'][array_search(persona::perfil_cursos, $contexto['perfiles'])]);
        }

        $contexto['comunes_si'] = comunes::si;
        $contexto['comunes_no'] = comunes::no;
		
        $ras = \toba::consulta_php('co_responsables_academicas')->get_ras_institucion_instalacion();
        $ras_valores = array_column($ras, 'valor');

        // Operación "Portal de pagos"
        $sq_usa_sanaviron = parametro::get_valor('sq_usa_sanaviron', $ras_valores);
        $contexto['usa_sanaviron'] = $sq_usa_sanaviron;

        // Operación "Boleto estudiantil"
        $contexto['usa_boleto_estudiantil'] = kernel::proyecto()->usa_boleto_estudiantil();

        return $contexto;
    }

}