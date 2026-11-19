<?php

class toba_mensajes
{

    /**
     * Si el mensaje fue definido con comodines (%numero%)
     * Estos pueden ser reemplazados por valores provistos en la llamada
     */
    function parsear_parametros($mensaje, $parametros)
    {
        if(is_array($parametros)){
            //Si se enviaron parametros los pongo en el
            //lugar de los comodines
            for($a=0; $a < \guarani_arreglos::count($parametros); $a++){
                $mensaje = str_replace("%".($a+1)."%", $parametros[$a], $mensaje);
            }
            //Por si todavia quedan comodines
            $mensaje = preg_replace('/\%[^ 	]*\%/',"",$mensaje);
        }else{
            //No hay parametros: elimino los comodines.
            $mensaje = preg_replace('/\%[^ 	]*\%/',"",$mensaje);
        }
        return $mensaje;
    }
    
}