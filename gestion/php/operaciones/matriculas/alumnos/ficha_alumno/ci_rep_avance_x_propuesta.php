<?php
class ci_rep_avance_x_propuesta extends ci_reporte_ficha_alumno
{
    function conf__cuadro(toba_ei_cuadro $cuadro)
    {		
        $datos = $this->get_datos('');
        $cuadro->set_datos($datos);
        if (empty($datos)) {
            $msg = guarani_mensajes::get('800_eof_cuadro', array('Propuestas'));
            $cuadro->set_eof_mensaje($msg);
            $this->pantalla('pant_inicial')->eliminar_evento('pdf');
        }
    }
    
    public function get_datos($filtro) 
    {
        //Traigo todos los alumnos de la persona seleccionada.
        $persona = $this->controlador()->controlador()->get_persona_seleccionada();
        $alumnos = toba::consulta_php('co_alumnos')->get_alumnos_por_persona($persona);
        $datos = [];
        foreach($alumnos as $alumno) {
            //Busco el porcentaje de avance para cada alumno.
            $porcentajeAvance = toba::consulta_php('co_alumnos')->get_porcentaje_avance_propuesta($alumno['alumno']);    
            foreach($porcentajeAvance as $key => $reg) {
                //Agrego un indice donde venga la propuesta y la propuesta_desc para usarlo en el CC.                
                if ($porcentajeAvance[$key]['orientacion_elegida'] == comunes::si) {
                    $porcentajeAvance[$key]['orientacion_elegida'] = comunes::si_desc;
                } else {
                    $porcentajeAvance[$key]['orientacion_elegida'] = comunes::no_desc;
                }                
                $porcentajeAvance[$key]['propuesta'] = $alumno['propuesta'];
                $porcentajeAvance[$key]['propuesta_nombre'] = $alumno['propuesta_nombre'];
                $porcentajeAvance[$key]['propuesta_desc'] = $alumno['propuesta_desc'];                
            }
            //Mergeo los datos de los n alumnos.
            $datos = array_merge($datos, $porcentajeAvance);
        }
        //Ordeno el array por nombre de propuesta y luego por orientacion
        return rs_ordenar_por_columnas($datos, ['propuesta_nombre', 'orientacion_nombre']);
    }
    
    

}
?>