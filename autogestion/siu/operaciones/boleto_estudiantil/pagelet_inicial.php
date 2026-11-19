<?php
namespace siu\operaciones\boleto_estudiantil;

use siu\guarani;
use siu\modelo\transacciones\boleto_estudiantil;
use SIU\Chulupi\interfaz\pagelet;
use SIU\Chulupi\kernel;

class pagelet_inicial extends pagelet
{

    const TIPO_MENSAJE_ERROR = 'alert-error';
    const TIPO_MENSAJE_EXITO = 'alert-success';
    const TIPO_MENSAJE_INFO = 'alert-info';
    const TIPO_MENSAJE_ADVERTENCIA = 'alert';

    function ini()
    {
    }

    function get_nombre()
    {
        return 'inicial';
    }

    /**
     * Shortcut al modelo
     * @return boleto_estudiantil
     */
    protected function modelo()
    {
        return guarani::boleto_estudiantil();
    }

    function prepare()
    {

        try {

            $this->data['csrf'] = $this->generar_csrf();
            $solicitud_deshabilitada = false;

            $this->data['url_editar_email'] = kernel::vinculador()->crear('configuracion', 'index');
            $operacion = kernel::ruteador()->get_id_operacion();
            $this->data['url_nueva_solicitud'] = kernel::vinculador()->crear($operacion, 'nueva_solicitud');
            $this->data['datos_basicos_persona'] = $this->modelo()->info__datos_basicos_persona();
            $this->data['datos_basicos_persona_correctos'] = $this->datos_basicos_persona_correctos($this->data['datos_basicos_persona']);
           
            $tiene_solicitud = $this->modelo()->info__tiene_solicitud();

            // Si ya tiene una solicitud o tiene algún dato incorrecto
            if($tiene_solicitud || (!$this->data['datos_basicos_persona_correctos'])) $solicitud_deshabilitada = true;
            $this->data['solicitud_deshabilitada'] = $solicitud_deshabilitada;
            
            // Listado de solicitudes para el historial
            $this->data['listado_solicitudes'] = $this->modelo()->info__listado_solicitudes();

            //Si desde el controlador se notifica de mostrar mensaje de error/aviso lo muestro
            if (isset($this->data['mostrar_mensaje_error_aviso']) && $this->data['mostrar_mensaje_error_aviso']) {
                $this->mostrar_mensaje_error_aviso($this->data['tipo_mensaje'], $this->data['mensaje']);
                return false;
            }

            //Si ya realizo la solicitud
            if($tiene_solicitud) {

                $mensaje = kernel::traductor()->trans('boleto_estudiantil.tramite_solicitado');
                $ultima_solicitud_pendiente = $this->modelo()->info__ultima_solicitud_pendiente();

                //Si hay una solicitud pendiente
                if(!empty($ultima_solicitud_pendiente)) {
                    $mensaje .= ". ";
                    $mensaje .= kernel::traductor()->trans('boleto_estudiantil.link_formulario_sube', array(
                       '%url_formulario_sube%' => $ultima_solicitud_pendiente['url_formulario_sube']
                    ));
                }

                $this->mostrar_mensaje_error_aviso(static::TIPO_MENSAJE_ADVERTENCIA, $mensaje);
            }

        }
        catch (\Exception $e) {
            //le muestro mensaje de error
            $mensaje = kernel::traductor()->trans('boleto_estudiantil.ocurrio_error');
            $this->mostrar_mensaje_error_aviso(static::TIPO_MENSAJE_ERROR, $mensaje);
            kernel::log()->add_error($e);
        }
    }

    function mostrar_mensaje_error_aviso($tipo_mensaje, $mensaje)
    {
        // Seteo mensaje a mostrar vía JS
        $this->add_var_js('tipo_mensaje', $tipo_mensaje);
        $this->add_var_js('mensaje', $mensaje);
    }

    function datos_basicos_persona_correctos($datos_basicos_persona)
    {
        $correctos = true;
        //Si no tiene email seteado los marco como incorrectos
        if ((!isset($datos_basicos_persona['email'])) || (!$datos_basicos_persona['email'])) {
            $correctos = false;
        }

        return $correctos;
    }
}