<?php
namespace siu\operaciones\validador_certificados;

use SIU\Chulupi\kernel;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use siu\modelo\datos\catalogo;
use siu\errores\error_guarani_usuario;
use siu\modelo_g3\constancia;
use siu\operaciones\validador_certificados\pagelet_validador;


class controlador extends controlador_g3w2 
{
	/**
     * @return null
     */
    function modelo()
    {
        return null;
    }

    function accion__index()
    {
        try{
            $this->vista()->pagelet('validador')->data['documento'] = null;
            $this->vista()->pagelet('validador')->data['documento_disabled'] = false;            

            $documento = $this->validate_param(0, 'get', validador::TIPO_INT, array(
                'allowempty' => true,
                'default' => null
            ));

            if($documento){
                $this->vista()->pagelet('validador')->data['documento'] = $documento;
                $this->vista()->pagelet('validador')->data['documento_disabled'] = true;                
            }
            
            $this->vista()->pagelet('validador')->data['codigo_validador'] = null;
            $this->vista()->pagelet('validador')->data['codigo_validador_disabled'] = false;            

            $codigo_verificacion = $this->validate_param(1, 'get', validador::TIPO_INT, array(
                'allowempty' => true,
                'default' => null
            ));

            if($codigo_verificacion){
                $this->vista()->pagelet('validador')->data['codigo_validador'] = $codigo_verificacion;
                $this->vista()->pagelet('validador')->data['codigo_validador_disabled'] = true;                
            }
        }
        catch(\Exception $e){

        }

    }

    function accion__validar()
    {
    	
    	if (kernel::request()->isPost()) {
            
                $documento = $this->validate_param('documento', 'post', validador::TIPO_TEXTO, array('allowempty' => false,'default' => false,'action' => false));
    		$parametros['documento'] = $documento;     
    		$codigo_verificacion = $this->validate_param('codigo_valid', 'post', validador::TIPO_INT, array('allowempty' => false,'default' => false,'action' => false));
    		$parametros['codigo_validador'] = $codigo_verificacion;
                $hubo_error=false;
                $datos_error=[];

    		try {
                //validacion documento
                if(!$documento){//Si documento vacio...
                    $datos_error['error_documento'] = true;
                    $datos_error['error_documento_msj'] = kernel::traductor()->trans('validador_certificados.error_documento_invalido');
                    $datos_error['documento'] = $documento;
                    $datos_error['documento_disabled'] = false;
                    $hubo_error=true;
                }
                elseif(!preg_match('/'.\persona::exp_reg_alfanum.'/', $documento)){//Si documento invalido...
                    $datos_error['error_documento'] = true;
                    $datos_error['error_documento_msj'] = kernel::traductor()->trans('validador_certificados.error_documento_invalido');
                    $datos_error['documento'] = $documento;
                    $datos_error['documento_disabled'] = false;
                    $hubo_error=true;
                }
                //validacion codigo
                if(!$codigo_verificacion){//Si codigo validador vacio...
                    $datos_error['error_codigo_validador'] = true;
                    $datos_error['error_codigo_validador_msj'] = kernel::traductor()->trans('validador_certificados.error_codigo_validacion_invalido');
                    $datos_error['codigo_validador'] = $codigo_verificacion;
                    $datos_error['codigo_validador_disabled'] = false;
                    $hubo_error=true;
                }
                elseif(!preg_match('/'.pagelet_validador::REGEXP_COD_VALIDACION.'/', $codigo_verificacion)){//Si codigo validador invalido...
                    $datos_error['error_codigo_validador'] = true;
                    $datos_error['error_codigo_validador_msj'] = kernel::traductor()->trans('validador_certificados.error_codigo_validacion_invalido');
                    $datos_error['codigo_validador'] = $codigo_verificacion;
                    $datos_error['codigo_validador_disabled'] = false;
                    $hubo_error=true;
                }
                //validacion captcha
                $captcha_ok = static::check_recaptcha2();
                if(!$captcha_ok) {//Si falla captcha...
                    $datos_error['error_recaptcha'] = true;
                    $datos_error['error_recaptcha_msj'] = kernel::traductor()->trans('error_recaptcha');
                    $hubo_error=true;
                }
                if ($hubo_error) {
                    $this->vista()->set_pagelet('validador');
                    $pagelet = $this->vista()->pagelet('validador');
                    $datos_error['documento'] = $documento;
                    $datos_error['codigo_validador'] = $codigo_verificacion;
                    $pagelet->data['hubo_error'] = true;
                    $pagelet->data['error'] = $datos_error;
                }else{
                    $certificado = catalogo::consultar('certificados', 'recuperar_certificado_web', $parametros);
                    $this->vista()->set_pagelet('confirmacion');
                    $pagelet = $this->vista()->pagelet('validador');

                    if($certificado){
                        $parametros = $certificado;
                        $pagelet->data['certificado_valido'] = true;
                        $pagelet->data['titulo'] = kernel::traductor()->trans('validador_certificados.certificado_valido');
                        $pagelet->data['descripcion'] = kernel::traductor()->trans('validador_certificados.certificado_valido_desc');
                        $pagelet->data['url_imprimir'] = kernel::vinculador()->crear('validador_certificados', 'imprimir', $parametros['solicitud']);

                    }
                    else{
                        $pagelet->data['certificado_valido'] = false;
                    } 
                }      
    		} catch (error_guarani_usuario $e) {
    			$this->vista()->set_pagelet('validador');
                        $pagelet = $this->vista()->pagelet('validador');
                        $pagelet->data['codigo_validador'] = $codigo_verificacion;
                        $pagelet->data['codigo_validador_disabled'] = true;
                        $pagelet->data['documento'] = $documento;
                        $pagelet->data['documento_disabled'] = true; 
    			$this->add_var('mensajes', $e);
    			kernel::log()->add_error($e);
    		}
    	}
    }

    //--------------------------------------------------------------------------
    //---- JASPER --------------------------------------------------------------
    //--------------------------------------------------------------------------

    function accion__imprimir() 
    {
        $solicitud = $this->validate_param(0, 'get', validador::TIPO_INT);
        $this->descargar_archivo(constancia::descargar_comprobante($solicitud), 'comprobante.pdf', true);
    }

    //--------------------------------------------------------------------------
    //---- RECAPTCHA -----------------------------------------------------------
    //--------------------------------------------------------------------------

    function usa_captcha()
    {
        return kernel::proyecto()->get('usa_captcha');
    }


}