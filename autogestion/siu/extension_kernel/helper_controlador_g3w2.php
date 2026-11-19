<?php

namespace siu\extension_kernel;

use SIU\Chulupi\kernel;
use SIU\Chulupi\nucleo\NuevoRequest;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\persona;

class helper_controlador_g3w2 implements interface_helper_controlador_g3w2{

    public function pre_accion(){

        $this->controlar_periodo_inscripcion_vigente_inscripcion_materias();
        
    }

    protected function controlar_periodo_inscripcion_vigente_inscripcion_materias()
    {

        // Obtengo el perfil actual
        $perfil = persona::get_perfil_activo();
        kernel::log()->add_debug("=== pre_accion - perfil ===", $perfil);

        // Obtengo la operación a la que se quiere acceder
        $operacion = kernel::ruteador()->get_id_operacion();
        kernel::log()->add_debug("=== pre_accion - operacion ===", $operacion);

        // Si es perfil 'Alumno' y la operación es "Inscripción a materias"
        if ($perfil == \persona::perfil_alumno && $operacion == 'cursada') {

            $plan_version = guarani::persona()->get_id_plan_version_activo();
            kernel::log()->add_debug("=== pre_accion - plan_version ===", $plan_version);

            $periodos_inscripcion_vigentes = catalogo::consultar('inscripcion_cursada', 'get_periodos_inscripcion_vigentes_por_plan_version', [
                'plan_version' => $plan_version
            ]);
            
            // Si no hay períodos de inscripción vigentes para el plan versión
            if (empty($periodos_inscripcion_vigentes)) {

                $excepciones_periodo_inscripcion = catalogo::consultar('inscripcion_cursada', 'get_excepciones_periodo_inscripcion_por_plan_version', [
                    'plan_version' => $plan_version
                ]);

                // Si no hay excepciones del período de inscripción para el plan versión
                if (empty($excepciones_periodo_inscripcion)) {

                    $mensaje = kernel::traductor()->trans('inicio_alumno.no_existe_periodo_vigente');

                    // Trato de recuperar el próximo "Período de Inscripción"
                    $proximo_periodo_inscripcion_vigente = catalogo::consultar('inscripcion_cursada', 'get_proximo_periodo_inscripcion_vigente_por_plan_version', [
                        'plan_version' => $plan_version
                    ]);

                    // Si existe próximo "Período de Inscripción" lo agrego al mensaje
                    if (!empty($proximo_periodo_inscripcion_vigente)) {
                        $mensaje .= " ";
                        $mensaje .= kernel::traductor()->trans('inicio_alumno.periodo_comienza', ['%dia%' => $proximo_periodo_inscripcion_vigente['fecha_inicio_visual']]);
                    }

                    $nuevo_request = new NuevoRequest('inicio_alumno');
                    kernel::sesion()->set('error_controlar_periodo_inscripcion_vigente', $mensaje);
                    throw $nuevo_request;

                }
            }

        }
        
    }

}