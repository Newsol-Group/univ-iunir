<?php

namespace siu\operaciones\requisitos_becas;

use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;
use siu\modelo\transacciones\becas;

class pagelet_listado extends pagelet
{

    /**
     * Shortcut al modelo
     * @return \siu\modelo\transacciones\becas
     */
    function modelo()
    {
        return $this->controlador->modelo();
    }

    function get_nombre()
    {
        return 'listado';
    }

    function prepare()
    {

        $this->prepare_constante();
        $this->prepare_urls();
        $this->prepare_varibles_js();
        
        $habilitacion = kernel::sesion()->get(becas::CLAVE_HABILITACION_SESION);
        // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
        $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);

        $this->data['habilitacion'] = $habilitacion;
        $this->data['es_reapertura'] = $es_reapertura;
        $this->data['requisitos'] = $this->modelo()->info__requisitos_convocatoria_by_persona($habilitacion);

        $this->data['info_habilitacion'] = $this->modelo()->info__habilitacion($habilitacion);

        $this->data['btn_continuar_disabled'] = $this->is_btn_continuar_disabled($this->data['requisitos']);

        $this->data['tipo_mensaje'] = '';
        $this->data['mensaje'] = '';
        // Mensaje
        if (kernel::sesion()->esta_seteada('mensaje')) {
            $this->data['tipo_mensaje'] = kernel::sesion()->get('tipo_mensaje');
            $this->data['mensaje'] = kernel::sesion()->get('mensaje');
            kernel::sesion()->borrar('tipo_mensaje');
            kernel::sesion()->borrar('mensaje');
        }
        
    }

    function prepare_constante()
    {
        $this->data['si'] = \comunes::si;
        $this->data['no'] = \comunes::no;
        $this->data['si_desc'] = \comunes::si_desc;
        $this->data['no_desc'] = \comunes::no_desc;
        $this->data['rechazado'] = \beca::REQUISITO_RECHAZADO;
        $this->data['no_posee'] = \beca::REQUISITO_NO_POSEE;
        $this->data['presentado'] = \beca::REQUISITO_PRESENTADO;
        $this->data['no_presentado'] = \beca::REQUISITO_NO_PRESENTADO;
    }

    function prepare_urls()
    {
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->data['url_guardar_noposeo'] = kernel::vinculador()->crear($operacion, 'guardar_noposeo');
        $this->data['url_descargar'] = kernel::vinculador()->crear($operacion, 'descargar');
        $this->data['url_editar'] = kernel::vinculador()->crear($operacion, 'editar');
        $this->data['url_continuar'] = kernel::vinculador()->crear('encuestas_kolla', 'index', kernel::sesion()->get(becas::CLAVE_HASH_SESION));
    }

    function prepare_varibles_js()
    {
        // Mensajes para el prompt de Guardar
        $this->add_mensaje_js('msg_prompt_no_poseo_guardar', kernel::traductor()->trans('requisitos.msg_prompt_no_poseo_guardar'));
        $this->add_mensaje_js('aceptar', kernel::traductor()->trans('aceptar'));
        $this->add_mensaje_js('cancelar', kernel::traductor()->trans('cancelar'));
    }


    function is_btn_continuar_disabled($requisitos)
    {
        $btn_continuar_disabled = false;
        foreach ($requisitos as $requisito) {
            // Si el requisito es obligatorio, no marco "No poseo" y no carga documento entonces lo deshabilito
            if ($requisito['obligatorio'] == \comunes::si && $requisito['no_posee'] == 0 && $requisito['presentado'] == \comunes::no) {
                $btn_continuar_disabled = true;
                break;
            }
        }
        return $btn_continuar_disabled;
    }
}