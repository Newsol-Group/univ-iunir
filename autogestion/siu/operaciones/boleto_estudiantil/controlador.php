<?php

namespace siu\operaciones\boleto_estudiantil;

use siu\guarani;
use siu\modelo\transacciones\boleto_estudiantil;
use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;

class controlador extends controlador_g3w2
{

    
    /**
     * Ventana que se ejecuta al inicializar el controlador
     */
    function ini()
    {
        // Si el parámetro 'usa_boleto_estudiantil' esta en 'No' lo redirijo a "Inicio Alumno"
        if (!kernel::proyecto()->usa_boleto_estudiantil()) {
            klog("El parámetro 'usa_boleto_estudiantil' esta en 'No se utiliza boleto estudiantil', se redirige a 'Inicio Alumno'");
            $this->nuevo_request('inicio_alumno');
        }
    }

    /**
     * @return boleto_estudiantil
     */
    function modelo()
    {
        return guarani::boleto_estudiantil();
    }

    function accion__index()
    {
    }

    function accion__nueva_solicitud()
    {

        $mostrar_mensaje_error_aviso = false;
        $tipo_mensaje = '';
        $mensaje = '';

        //Si la petición viene por POST
        if (kernel::request()->isPost()) {
            klog("Es post NUEVA_SOLICITUD");
            try{

                $this->check_csrf();

                $datos_basicos_persona = $this->modelo()->info__datos_basicos_persona();
                

                //Si tiene un email seteado
                if (isset($datos_basicos_persona['email']) && $datos_basicos_persona['email']) {
                    klog("Hay mail NUEVA_SOLICITUD");

                    $tiene_solicitud = $this->modelo()->info__tiene_solicitud();

                    // Si ya tiene una solicitud
                    if ($tiene_solicitud) {
                        klog("tiene solicitud NUEVA_SOLICITUD");
                        $mostrar_mensaje_error_aviso = true;
                        $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                        $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_ya_ha_solicitado_anteriormente');
                        
                    }
                    else {//sino
                        klog("no tiene solicitud NUEVA_SOLICITUD");
                        $cumple_condicion_be = $this->modelo()->info__cumple_condicion_be();
                        //Si cumpla la condicion para solicitar el BE
                        if ($cumple_condicion_be['cumple']) {
                            klog("cumple NUEVA_SOLICITUD");
                            $body_ws = array(
                                "user" => boleto_estudiantil::info__config_sube_ws_user(),
                                "password" => boleto_estudiantil::info__config_sube_ws_pass(),
                                "student_id" => $datos_basicos_persona['nro_documento'],
                                "student_id_type" => $datos_basicos_persona['tipo_documento'],
                                "student_name" => $datos_basicos_persona['nombres'],
                                "student_last_name" => $datos_basicos_persona['apellido'],
                                "student_department" => boleto_estudiantil::info__config_sube_ws_ua()
                            );
                            $datos = boleto_estudiantil::ws_solicitar_beneficio($body_ws);

                            switch ($datos['status']) {
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_OK: {
                                    $condicion = $cumple_condicion_be['condicion'];
                                    $observaciones = $cumple_condicion_be['mensaje'];
                                    $this->modelo()->evt__realizar_solicitud($datos['data']['tid'], $datos['data']['redirect-url'], $condicion, $observaciones);
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_EXITO;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_ok', array(
                                        '%url_formulario_sube%' => $datos['data']['redirect-url']
                                    ));
                                    break;
                                }
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_ERROR_VALIDAR_DATOS: {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_error_validar_datos');
                                    kernel::log()->add_error(new \Exception("POST benefit-request | status: {$datos['status']} | message: {$datos['message']}"));
                                    break;
                                }
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_ERROR_CON_DATOS: {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_error_datos');
                                    kernel::log()->add_error(new \Exception("POST benefit-request | status: {$datos['status']} | message: {$datos['message']}"));
                                    break;
                                }
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_ERROR_CREDENCIALES: {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_error_credenciales');
                                    kernel::log()->add_error(new \Exception("POST benefit-request | status: {$datos['status']} | message: {$datos['message']}"));
                                    break;
                                }
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_BENEFICIO_YA_ASIGNADO: {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_EXITO;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_ya_tenes_beneficio');
                                    kernel::log()->add_info("POST benefit-request", $datos);
                                    break;
                                }
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_BENEFICIO_YA_ASIGNADO_PERO_DESHABILITADO: {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_ya_tenes_beneficio_pero_deshabilitado');
                                    kernel::log()->add_info("POST benefit-request", $datos);
                                    break;
                                }
                                case \boleto_estudiantil::STATUS_CODE_SOLICITUD_ERROR: {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_error');
                                    kernel::log()->add_error(new \Exception("POST benefit-request | status: {$datos['status']} | message: {$datos['message']}"));
                                    break;
                                }
                                default : {
                                    $mostrar_mensaje_error_aviso = true;
                                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.solicitud_error_desconocido');
                                    kernel::log()->add_error(new \Exception("POST benefit-request | Error desconocido."));
                                }

                            }

                        }//sino
                        else {
                            klog("no cumple NUEVA_SOLICITUD");
                            $mostrar_mensaje_error_aviso = true;
                            $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                            $mensaje = kernel::traductor()->trans('boleto_estudiantil.no_cumplis_condiciones');

                        }
                    }


                }
                else {//sino
                    klog("No hay mail NUEVA_SOLICITUD");
                    $mostrar_mensaje_error_aviso = true;
                    $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                    $mensaje = kernel::traductor()->trans('boleto_estudiantil.email_sin_definir');

                }

            }
            catch (\Exception $e){

                $mostrar_mensaje_error_aviso = true;
                $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
                $mensaje = kernel::traductor()->trans('boleto_estudiantil.ocurrio_error');
                kernel::log()->add_error($e);

            }

        }//sino
        else {
            klog("No es post NUEVA_SOLICITUD");
            $mostrar_mensaje_error_aviso = true;
            $tipo_mensaje = pagelet_inicial::TIPO_MENSAJE_ERROR;
            $mensaje = kernel::traductor()->trans('boleto_estudiantil.ocurrio_error');

        }
        $pagelet = $this->vista()->pagelet('inicial');
        $pagelet->data['mostrar_mensaje_error_aviso'] = $mostrar_mensaje_error_aviso;
        $pagelet->data['tipo_mensaje'] = $tipo_mensaje;
        $pagelet->data['mensaje'] = $mensaje;

    }

}