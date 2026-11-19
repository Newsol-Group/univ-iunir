<?php

class ci_estado_proceso_pago extends guarani_pers_ci 
{
    protected $s__operacion_retorno;
    protected $ev_info_resumen;
            
    function conf() 
    {
        //En la url va a llegar un retorno una vez que se termine el proceso de pago
        //es donde se va a volver cuando el usuario vuelva de completar el pago en SQ        
        $parametros = toba::memoria()->get_parametros();
        if (isset($parametros['retorno'])) {
            $this->s__operacion_retorno = $parametros['retorno'];
        } else {
            throw new toba_error('No se puede acceder a la operación');
        }
        
        $datos = array();
        if (isset($parametros['estado_tramite_pago'])) {
            //El pago se hace en el momento en la caja.
            if ($parametros['estado_tramite_pago'] == cobro::ESTADO_TRAMITE_PAGO_OK_INSTANTANEO) {
                $datos['mensaje'] = 'Proceso de pago completado exitosamente.';
                $datos['nro_transaccion'] = $parametros['nro_transaccion'];
            }
            //El usuario desde SQ cancelo el pago.
            if ($parametros['estado_tramite_pago'] == cobro::ESTADO_TRAMITE_PAGO_CANCELO) {
                $datos['mensaje'] = 'Se cancelo el pago.';
            }
            $this->ev_info_resumen()->set_columnas_a_mostrar(array_keys($datos));
            $this->ev_info_resumen()->set_datos($datos);
        }
    }

    function ev_info_resumen() 
    {
        if (!isset($this->ev_info_resumen)) {
            $this->ev_info_resumen = guarani::ev_info_datos();
            $this->ev_info_resumen->set_td_etiqueta_width('110px');            
            $this->ev_info_resumen->set_max_columnas_por_fila(1);            
            $this->ev_info_resumen->set_etiqueta('mensaje', 'Mensaje: ');
            $this->ev_info_resumen->set_etiqueta('nro_transaccion', 'N° Transacción: ');
        }
        return $this->ev_info_resumen;
    }

    function evt__continuar() 
    {
        echo toba_js::abrir();
        echo "document.location.href='" .
        toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_retorno) . "'\n";
        echo toba_js::cerrar();
    }

}

?>