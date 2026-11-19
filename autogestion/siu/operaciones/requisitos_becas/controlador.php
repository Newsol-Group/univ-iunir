<?php

namespace siu\operaciones\requisitos_becas;

use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use SIU\Chulupi\kernel;
use siu\modelo\transacciones\becas;
use SIU\Chulupi\util\validador;
use siu\modelo\_comun\clienteRDI;

class controlador extends controlador_g3w2
{

    /**
     * @return \siu\modelo\transacciones\becas
     */
    function modelo()
    {
        return guarani::becas();
    }

    /**
     * Ventana que se ejecuta antes de ejecutar una acción
     */
    protected function pre_accion()
    {
        parent::pre_accion();
        
        try {
            $accion = $operacion = kernel::ruteador()->get_accion();
            kernel::log()->add_debug("=== pre_accion - accion ===", $accion);

            // No controlo cuando la acción es "error"
            if ($accion !== 'error') {
                // Controlo si viene de la operación "Trámites > Convocatorias a Becas"
                $this->controlar_viene_convocatoria_beca();
                // Controlo si usa RDI
                $this->controlar_usa_rdi();
                // Controlo si la convocatoria fue cerrada
                $this->controlar_convocatoria_cerrada();
            }

            $acciones_post = ['guardar_noposeo', 'eliminar', 'subir'];
            if (in_array($accion, $acciones_post)) {
                // Controlo si viene por POST
                $this->controlar_viene_post();
            }

        } catch (\Exception $e) {
            // Redirijo para mostrar error
            kernel::sesion()->set('msg_requisitos_becas_error', $e->getMessage());
            $operacion = kernel::ruteador()->get_id_operacion();
            $this->nuevo_request($operacion, 'error');
        }

    }

    function accion__error()
    {
        // Si viene mensaje de error
        if (kernel::sesion()->esta_seteada('msg_requisitos_becas_error')) {
            $mensaje = kernel::sesion()->get('msg_requisitos_becas_error');
            kernel::sesion()->borrar('msg_requisitos_becas_error');
        } else {// Sino muestro error genérico
            $mensaje = kernel::traductor()->trans('requisitos.msj_error_generico');
        }

        $pagelet = $this->vista()->set_pagelet('error');
        $pagelet->data['mensaje'] = $mensaje;
        return false;
    }

    function accion__index()
    {
        $pagelet = $this->vista()->set_pagelet('listado');
    }

    function accion__editar()
    {
        $hubo_error = false;
        $msg_error = '';

        try {
            $habilitacion = $this->validate_param(0, 'get', validador::TIPO_INT);
            $requisito_id = $this->validate_param(1, 'get', validador::TIPO_INT);

            $requisitos = $this->modelo()->info__requisitos_convocatoria_by_persona($habilitacion);

            $requisito = $requisitos[$requisito_id] ?? null;

            // Si el requisito no existe
            if (is_null($requisito)) {
                throw new \Exception(kernel::traductor()->trans('requisitos_becas.error_existe_requisito'));
            }

        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
            // Redirijo al listado
            $operacion = kernel::ruteador()->get_id_operacion();
            $this->nuevo_request($operacion, 'index');
        } else {
            $pagelet = $this->vista()->set_pagelet('edicion');
            $pagelet->data['requisito'] = $requisito;
        }
    }

    function accion__guardar_noposeo()
    {
        $hubo_error = false;
        $msg_error = '';
        
        try {
            $habilitacion = $this->validate_param('habilitacion', 'post', validador::TIPO_INT);
            $requisito = $this->validate_param('requisito', 'post', validador::TIPO_INT);
            $no_posee = $this->validate_param('no_posee_actual', 'post', validador::TIPO_INT);

            list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__actualizar_requisito_aspirante($habilitacion, $requisito, null, $no_posee);

            // Si hubo error en el modelo
            if ($hubo_error_modelo) {
                throw new \Exception($msg_error_modelo);
            }

        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
        // Si se guarda con éxito
        } else {
            // Redirijo al listado
            kernel::sesion()->set('tipo_mensaje', 'alert-success');
            kernel::sesion()->set('mensaje', kernel::traductor()->trans('requisitos_becas.msg_guardar_exito'));
        }

        // Redirijo al listado
        $operacion = kernel::ruteador()->get_id_operacion();
        $this->nuevo_request($operacion, 'index');
    }

    function accion__eliminar()
    {
        $hubo_error = false;
        $msg_error = '';
        $operacion = kernel::ruteador()->get_id_operacion();
        
        try {

            // Redirijo al listado
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');

            $habilitacion = $this->validate_param('habilitacion', 'post', validador::TIPO_INT);
            $requisito = $this->validate_param('requisito', 'post', validador::TIPO_INT);

            // Redirijo a la edición
            $url_redirect = kernel::vinculador()->crear($operacion, 'editar', [$habilitacion, $requisito]);
            
            list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__eliminar_requisito_aspirante($habilitacion, $requisito);

            // Si hubo error en el modelo
            if ($hubo_error_modelo) {
                throw new \Exception($msg_error_modelo);
            }

        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
        // Si se guarda con éxito
        } else {
            // Redirijo al listado
            kernel::sesion()->set('tipo_mensaje', 'alert-success');
            kernel::sesion()->set('mensaje', kernel::traductor()->trans('requisitos_becas.msg_guardar_exito'));
        }

        $this->nuevo_request_url($url_redirect);
    }

    function accion__descargar()
    {

        $hubo_error = false;
        $msg_error = '';
        $operacion = kernel::ruteador()->get_id_operacion();
        
        try {

            // Redirijo al listado
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');
            
            $habilitacion = $this->validate_param(0, 'get', validador::TIPO_INT);
            $requisito = $this->validate_param(1, 'get', validador::TIPO_INT);

            // Redirijo a la edición
            $url_redirect = kernel::vinculador()->crear($operacion, 'editar', [$habilitacion, $requisito]);
            
            list($hubo_error_modelo, $msg_error_modelo, $path, $nombre_descarga) = $this->modelo()->evt__descargar_requisito_aspirante($habilitacion, $requisito);

            // Si hubo error en el modelo
            if ($hubo_error_modelo) {
                throw new \Exception($msg_error_modelo);
            }

            // Descargo el documento al browser
            $this->descargar_archivo($path, $nombre_descarga, true);
            
        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
            $this->nuevo_request_url($url_redirect);
        }
    }

    function accion__subir()
    {
        $hubo_error = false;
        $msg_error = '';
        $operacion = kernel::ruteador()->get_id_operacion();
        
        try {

            // Redirijo al listado
            $url_redirect = kernel::vinculador()->crear($operacion, 'index');

            $habilitacion = $this->validate_param('habilitacion', 'post', validador::TIPO_INT);
            $requisito = $this->validate_param('requisito', 'post', validador::TIPO_INT);


            // Redirijo a la edición
            $url_redirect = kernel::vinculador()->crear($operacion, 'editar', [$habilitacion, $requisito]);

            $indice_file = 'archivo';

            // Valido el documento subido
            list($hubo_error_documento_subido, $msg_error_documento_subido) = clienteRDI::validar_documento_subido($indice_file);

            // Si hubo error en la validación del documento subido
            if ($hubo_error_documento_subido) {
                throw new \Exception($msg_error_documento_subido);
            }

            // Guardo el documento subido al filesystem
			$archivo_subido = clienteRDI::guardar_archivos_subidos($indice_file);
            list($hubo_error_modelo, $msg_error_modelo) = $this->modelo()->evt__subir_requisito_aspirante($habilitacion, $requisito, $archivo_subido);

            // Si hubo error en el modelo
            if ($hubo_error_modelo) {
                throw new \Exception($msg_error_modelo);
            }

        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
        // Si se guarda con éxito
        } else {
            kernel::sesion()->set('tipo_mensaje', 'alert-success');
            kernel::sesion()->set('mensaje', kernel::traductor()->trans('requisitos_becas.msg_guardar_exito'));
        }

        // Redirijo
        $this->nuevo_request_url($url_redirect);
    }

    function accion__modificar()
    {
        $hubo_error = false;
        $msg_error = '';

        try {
            $habilitacion = $this->validate_param(0, 'get', validador::TIPO_INT);
            $requisito_id = $this->validate_param(1, 'get', validador::TIPO_INT);

            $requisitos = $this->modelo()->info__requisitos_convocatoria_by_persona($habilitacion);

            $requisito = $requisitos[$requisito_id] ?? null;

            // Si el requisito no existe
            if (is_null($requisito)) {
                throw new \Exception(kernel::traductor()->trans('requisitos_becas.error_existe_requisito'));
            }

            // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
            $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);
            // Si es reapertura y no esta rechazado entonces no le permito modificar
            if ($es_reapertura && ($requisito['rechazado'] === \comunes::no)) {
                throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
            }

        } catch (\Exception $e) {
            $hubo_error = true;
            $msg_error = $e->getMessage();
        }

        // Si hubo error
        if ($hubo_error) {
            kernel::sesion()->set('tipo_mensaje', 'alert-error');
            kernel::sesion()->set('mensaje', $msg_error);
            // Redirijo al listado
            $operacion = kernel::ruteador()->get_id_operacion();
            $this->nuevo_request($operacion, 'index');
        } else {
            $pagelet = $this->vista()->set_pagelet('edicion');
            $pagelet->data['requisito'] = $requisito;
            $pagelet->data['es_mofificacion'] = true;
        }
    }

    function controlar_viene_convocatoria_beca()
    {
        // Si no viene de la operación "Trámites > Convocatorias a Becas"
        if (!becas::viene_de_convocatorias_becas()) {
            throw new \Exception(kernel::traductor()->trans('requisitos_becas.error_no_viene_convocatorias_becas'));
        }
    }

    function controlar_usa_rdi()
    {
        // Si no usa RDI (parámetro 'usa_repositorio_digital')
        if (!kernel::proyecto()->usa_rdi()) {
            throw new \Exception(kernel::traductor()->trans('requisitos.error_usa_repositorio_digital'));
        }
    }

    function controlar_viene_post()
    {
        // Si no es por POST
        if (!kernel::request()->isPost()) {
            throw new \Exception(kernel::traductor()->trans('requisitos.msj_error_generico'));
        }
    }

    function controlar_convocatoria_cerrada()
    {
        // true cuando viene del botón "Modificar Datos", false cuando viene del botón "Aplicar"
        $es_reapertura = kernel::sesion()->esta_seteada(becas::CLAVE_REAPERTURA_SESION);
        if ($es_reapertura) {
            $reapertura = kernel::sesion()->get(becas::CLAVE_REAPERTURA_SESION);
            $datos_reapertura = $this->modelo()->info__datos_reapertura($reapertura);
            $fecha_cierre = $datos_reapertura['fecha_hasta'];
        } else {
            $habilitacion = kernel::sesion()->get(becas::CLAVE_HABILITACION_SESION);
            $info_habilitacion = $this->modelo()->info__habilitacion($habilitacion);
            $fecha_cierre = $info_habilitacion['fecha_hasta'];
        }

        if (fecha_es_menor_a_fecha_actual_php($fecha_cierre, 'Y-m-d')) {
            $fecha_cierre_formateada = \DateTime::createFromFormat('Y-m-d', $fecha_cierre)->format('d/m/Y');
            throw new \Exception(kernel::traductor()->trans('requisitos_becas.error_convocatoria_cerrada', ['%fecha_cierre%' => $fecha_cierre_formateada]));
        }
    }
}