<?php
namespace siu\operaciones\constancias_cursos;

use SIU\Chulupi\ErrorKernelValidacion;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\validador;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo_g3\constancia;

class controlador extends controlador_g3w2
{

    /**
     * @return \siu\modelo\transacciones\constancias_cursos
     */
    function modelo()
    {
        return guarani::constancias_cursos();
    }

    function accion__index()
    {
    }

    /**
     * Retorna la solicitud a partir del hash
     */
    protected function decodificar_solicitud($hash)
    {
        $solicitudes = $this->modelo()->info__constancias();

        foreach($solicitudes as $solicitud) {

            if ($solicitud[catalogo::id] == $hash) {
                return $solicitud;
            }

        }

        //Si no existe la solicitud
        klog("No existe el certificado a descargar.");
        throw new \Exception(kernel::traductor()->trans('constancias_cursos.no_se_pudo_descargar_certificado'));

    }

    function accion__descargar_comprobante()
    {
        try{

            $this->vista()->pagelet('constancias')->add_var_js('hubo_error', false);

            try{
                $solicitud_hash = $this->validate_param(0, 'get', validador::TIPO_ALPHANUM);
            }
            catch(ErrorKernelValidacion $e){
                klog("Fallo la validacion del Hash del certificado.");
                throw new \Exception(kernel::traductor()->trans('constancias_cursos.no_se_pudo_descargar_certificado'));
            }

            klog2('hash solicitud', $solicitud_hash);

            $solicitud = $this->decodificar_solicitud($solicitud_hash);

            if($solicitud){//Existe la solicitud

                klog2('solicitud seleccionada', $solicitud);

                //Valido si el certificado se puede descargar
                $certificado_vencido = false;
                $fecha_actual = new \DateTime('today');

                if($solicitud['fecha_fin_vigencia']){
                    $fecha_fin_vigencia = \DateTime::createFromFormat('!d/m/Y', $solicitud['fecha_fin_vigencia']);

                    if($fecha_fin_vigencia < $fecha_actual){
                        $certificado_vencido = true;
                    }
                }

                //Si permite descarga y la interfaz es autogestion y el estado es online y no esta vencido muestro el boton para descargar certificado...
                $permite_descargar = $solicitud['permite_descargar'] && ($solicitud['interfaz'] == \comunes::interfaz_autogestion) && ($solicitud['estado'] == \constancias::estado_solicitud_online) && (!$certificado_vencido);

                if(!$permite_descargar){//Sino permite descargar
                    klog("No se puede descargar el certificado, fallo la validacion.");
                    throw new \Exception(kernel::traductor()->trans('constancias_cursos.no_se_pudo_descargar_certificado'));
                }
                $this->descargar_archivo(constancia::descargar_comprobante($solicitud['solicitud']), 'comprobante.pdf', true);
            }

        }
        catch(\Exception $e)
		{

			$message = '';
			// Si es un error de Jasper
			if (get_class($e) == 'java_InternalException') {
				$message = kernel::traductor()->trans('constancias_cursos.no_se_pudo_descargar_certificado');
				$excepcion = new \Exception(kernel::traductor()->trans('jasper.error'));
				kernel::log()->add_error($excepcion);
			} else {
				$message = $e->getMessage();
			}

			$this->vista()->pagelet('constancias')->add_var_js('hubo_error', true);
			$this->vista()->pagelet('constancias')->add_var_js('msj_error', $message);
		}
	}

}