<?php

namespace SIU\Guarani\php\rest\v2\modelos;

use SIUToba\rest\lib\rest_filtro_sql;
use SIUToba\rest\lib\rest_hidratador;
use SIUToba\rest\rest;
use SIU\Guarani\php\rest\v2\oferta_cursos\recurso_oferta_cursos;

class rest_oferta_cursos_nucleo {

    /**
     * @return rest_filtro_sql
     */
    function get_filtro_base() {
        $filtro = new rest_filtro_sql();
        return $filtro;
    }

    protected function get_modelo($nombre) {
        $modelos = recurso_oferta_cursos::_get_modelos();
        return $modelos[$nombre];
    }

    function get_list() {
        $inscripcion_vigente = rest::request()->get('inscripcion_vigente', '1');
        $datos = \toba::consulta_php('co_comisiones')->get_info_oferta_cursos($inscripcion_vigente);

        foreach ($datos as $key => $dato) {

            switch ($dato['estado']) {
                case \comunes::estado_activo:
                    $datos[$key]['estado'] = \comunes::estado_activo_desc;
                    break;
                case \comunes::estado_baja:
                    $datos[$key]['estado'] = \comunes::estado_baja_desc;
                    break;
                case \comunes::estado_pendiente:
                    $datos[$key]['estado'] = \comunes::estado_pendiente_desc;
                    break;
            }

            $datos[$key]['periodo_inscripcion'] = \toba::consulta_php('co_periodos_de_inscripcion')->get_periodo_inscripcion_por_comision($dato['comision']);

            $datos[$key]['periodo_dictado'] = array('inicio' => $dato['fecha_inicio_dictado'], 'fin' => $dato['fecha_fin_dictado']);
            unset($datos[$key]['fecha_inicio_dictado']);
            unset($datos[$key]['fecha_fin_dictado']);

            $modalidades_cursadas = explode('/', $dato['modalidades_cursada']);
            $modalidadesArray = Array();
            if (!empty($modalidades_cursadas)) {
                foreach ($modalidades_cursadas as $modalidad) {
                    array_push($modalidadesArray, array('modalidad' => $modalidad));
                }
            }
            $datos[$key]['modalidades_cursada'] = $modalidadesArray;

            $horariosArray = Array();
            if (!empty($dato['horarios'])) {
                $horarios = explode(' / ', $dato['horarios']);
                if (!empty($horarios)) {
                    foreach ($horarios as $horario) {
                        $split_horario = explode(' - ', $horario);
                        if (!empty($split_horario)) {
                            $dia = $this->getDiaCompleto(substr($split_horario[0], 0, 3));
                            $inicio = substr($split_horario[0], 4, 5);
                            $fin = substr($split_horario[0], 12, 5);
                        } else {
                            $dia = '';
                            $inicio = '';
                            $fin = '';
                        }
                        $split_tipo_aula = explode(' (', $split_horario[1]);
                        if (!empty($split_tipo_aula)) {
                            $tipo = $split_tipo_aula[0];
                            $aula = (isset($split_tipo_aula[1])) ? trim($split_tipo_aula[1], ')') : '';
                        } else {
                            $tipo = '';
                            $aula = '';
                        }
                        array_push($horariosArray, array('dia' => $dia, 'inicio' => $inicio, 'fin' => $fin, 'tipo' => $tipo, 'aula' => $aula));
                    }
                }
            }
            $datos[$key]['horarios'] = $horariosArray;
            $docentesArray = Array();
            if (!empty($dato['docentes'])) {
                $docentes = explode(', ', $dato['docentes']);
                if (!empty($docentes)) {
                    foreach ($docentes as $docente) {
                        $split_docentes_cargo = explode(' (', $docente);
                        array_push($docentesArray, ['docente' => $split_docentes_cargo[0] ?? '', 'cargo' => trim($split_docentes_cargo[1] ?? '', ')'),]);
                    }
                }
            }
            $datos[$key]['docentes'] = $docentesArray;

            $datos[$key]['costos'] = Array();
            if ($dato['es_cobrable'] == 'Si') {
                $valorizar = $dato['propuesta'] . '|' . $dato['actividad'] . '|' . $dato['comision'];
                try {
                    $valores = \cobro::get_servicios_instancias_valorizaciones($valorizar);
                } catch (\Exception $ex) {
                    $valores = $ex;
                }
                $importe_y_moneda = Array();
                foreach ($valores as $valor) {
                    array_push($importe_y_moneda, array('importe' => number_format($valor['importe'], 2), 'moneda' => $valor['moneda']['descripcion']));
                }
                $datos[$key]['costos'] = $importe_y_moneda;
            }
			
        }
        if (!empty($dato)) {
            $d = rest_hidratador::hidratar($this->get_modelo('Oferta_cursos'), $datos);
        } else
            $d = array();
        return $d;
    }

    private function getDiaCompleto($dia) {
        $dias = array('Dom' => 'Domingo', 'Lun' => 'Lunes', 'Mar' => 'Martes', 'Mie' => 'Miércoles', 'Jue' => 'Jueves', 'Vie' => 'Viernes', 'Sab' => 'Sábado');
        return $dias[$dia];
    }

}
