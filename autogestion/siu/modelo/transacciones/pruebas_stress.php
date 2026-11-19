<?php

namespace siu\modelo\transacciones;


use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\modelo\datos\catalogo;

class pruebas_stress extends transaccion
{

    function evt__limpiar_datos_comisiones_1er_cuatrimestre_2015()
    {
        try{
            kernel::db()->abrir_transaccion();
            catalogo::consultar('pruebas_stress', 'limpiar_datos_comisiones_1er_cuatrimestre_2015');
            kernel::db()->cerrar_transaccion();
        }
        catch (ErrorKernel $e){
            kernel::db()->abortar_transaccion();
            kernel::log()->add_error($e);
            $e = new error_guarani(kernel::traductor()->trans('pruebas_stress.error_limpiar_datos_comisiones_1er_cuatrimestre_2015'));
            throw $e;
        }

    }

}