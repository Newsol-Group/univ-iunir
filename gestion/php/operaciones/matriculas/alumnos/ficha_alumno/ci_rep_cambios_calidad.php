<?php
class ci_rep_cambios_calidad extends ci_reporte_ficha_alumno
{
    function conf__cuadro(toba_ei_cuadro $cuadro)
    {		
        $datos = $this->get_datos('');
        $cuadro->set_datos($datos);
        if (empty($datos)) {
                $msg = guarani_mensajes::get('800_eof_cuadro', array('Cambios de calidad del alumno'));
                $cuadro->set_eof_mensaje($msg);
                $this->pantalla('pant_inicial')->eliminar_evento('pdf');
        }
    }

    function get_datos($filtro)
    {
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
        return guarani::co('co_alumnos')->get_cambios_calidad($persona);
    }

}

?>