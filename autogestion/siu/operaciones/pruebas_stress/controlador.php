<?php
namespace siu\operaciones\pruebas_stress;

use SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\extension_kernel\controlador_g3w2;
use siu\guarani;

class controlador extends controlador_g3w2
{

    /**
     * @return \siu\modelo\transacciones\pruebas_stress
     */
    function modelo()
    {
        return guarani::pruebas_stress();
    }

    /**
     * Esta acción limpia los datos de las inscripciones que se realizaron en el test de stress.
     * Toma las inscripciones en base a las comisiones de la tabla _ts_com creada luego de levantar el backup de la base.
     * Borra inscripciones y actas de esas comisiones si hubiere alguna.
     */
    function accion__limpiar_datos_comisiones_2015()
    {

        $mensaje_error = null;

        if(kernel::request()->isPost()){

            try{
                $this->modelo()->evt__limpiar_datos_comisiones_1er_cuatrimestre_2015();
                $url = kernel::vinculador()->crear('acceso', 'index');
                $this->nuevo_request_url($url);
            }
            catch (error_guarani $e){
                $mensaje_error = $e->get_mensaje();
            }
            
        }
        else{
            $mensaje_error = kernel::traductor()->trans('pruebas_stress.error_peticion_no_post');
        }

        $this->vista()->pagelet('contenido')->data['mensaje_error'] = $mensaje_error;
    }

}