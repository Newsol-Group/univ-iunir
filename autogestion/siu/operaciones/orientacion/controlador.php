<?php
namespace siu\operaciones\orientacion;

use SIU\Chulupi\kernel;
use siu\guarani;
use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\util\validador;
use siu\modelo\_comun\comunes;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\parametro;

class controlador extends controlador_g3w2
{
	function modelo()
	{
		return guarani::orientacion();
	}

    function accion__index()
    {

        $propuesta = guarani::persona()->get_id_propuesta_activa();
        $plan = guarani::persona()->get_id_plan_activo();
        $alumno = guarani::persona()->get_id_alumno_activo();
        $plan_version = guarani::persona()->get_id_plan_version_activo();

        $orientaciones_plan = \toba::consulta_php('co_planes')->get_plan_version_orientaciones($plan_version);
        $datos_plan = \toba::consulta_php('co_planes')->get_datos_plan($plan);

        $parametro_elegir_orientacion = parametro::get_valor('mat_elegir_orientacion', $propuesta);
        $pagelet_formulario = $this->vista()->pagelet('formulario');

        if (kernel::request()->isPost())
        {
            $orientacion = $this->validate_param('id_orientacion', 'post', validador::TIPO_ALPHANUM);
            $parametros = array('alumno' => $alumno, 'plan' => $plan, 'orientacion' => $orientacion, 'observaciones' => null);

            /* Paso a un array la columna con los id de las orientaciones que tiene el plan*/
            $array_column = array_map(function ($element){ return $element['elemento'];}, $orientaciones_plan);

            /* Busco en el array con las orientaciones del plan la orientacion que se quiere seleaccionar */
            $orientacion_seleccionada = array_search($orientacion, $array_column);

            if ($orientacion_seleccionada !== false)
            {
                catalogo::consultar('orientacion', 'registrar_seleccion_orientacion', $parametros);
                $pagelet_formulario->data['orientacion_guardada'] = true;
            }
        }

        $orientaciones_elegidas = \toba::consulta_php('co_alumnos')->get_orientaciones_alumno($alumno);
		
		
		$pagelet_formulario->data['elegir_orientacion'] = false;
		
        if ($parametro_elegir_orientacion == comunes::si)
        {			
			$pagelet_formulario->data['elegir_orientacion'] = true;
            /* Paso a un array la columna con los ids de las orientaciones que tiene seleccionado el alumno*/
            $array_column = array_map(function ($element){ return $element['orientacion'];}, $orientaciones_elegidas);
            if (!empty($orientaciones_plan))
            {
                foreach ($orientaciones_plan as $key => $orientacion_plan)
                {
                    /* Busco si el registro de la orientacion actual esta dentro de las elegidas por el alumno */
                    $orientacion_seleccionada = array_search($orientacion_plan['elemento'], $array_column);

                    ($orientacion_seleccionada !== false) ? $orientaciones_plan[$key]['seleccionada'] = true : $orientaciones_plan[$key]['seleccionada'] = false;
                }

                (!empty($array_column)) ? $pagelet_formulario->data['tiene_orientacion_seleccionada'] = true : $pagelet_formulario->data['tiene_orientacion_seleccionada'] = false;

                $pagelet_formulario->data['orientaciones'] = $orientaciones_plan;
                $pagelet_formulario->data['datos_plan'] = ((! empty($datos_plan)) ? $datos_plan['0'] : $datos_plan);
            }
        }
        return $pagelet_formulario;
    }

}
?>