<?php
namespace siu\operaciones\inicio_alumno;

use SIU\Chulupi\kernel;
use SIU\Chulupi\interfaz\pagelet;

class pagelet_lista_carreras extends pagelet
{
	function get_nombre()
	{
		return 'lista_carreras';
	}

	function prepare()
	{
		$this->data['periodos_lectivos'] = $this->controlador->info_periodos_lectivos();
		$this->data['turnos_examen'] = $this->controlador->info_turnos_examen();
		$this->data['url_qr_periodo'] = kernel::vinculador()->crear('inicio_alumno', 'qr_periodo');
                kernel::log()->add_debug('carreras', $this->data);

                $this->add_var_js('error_controlar_periodo_inscripcion_vigente', false);
                $this->data['error_controlar_periodo_inscripcion_vigente'] = false;
                // Si hubo error al controlar período de inscripción vigente para Inscripción a Materias
                if (kernel::sesion()->esta_seteada('error_controlar_periodo_inscripcion_vigente')) {
                    $this->add_var_js('error_controlar_periodo_inscripcion_vigente', true);
                    $this->data['error_controlar_periodo_inscripcion_vigente'] = kernel::sesion()->get('error_controlar_periodo_inscripcion_vigente');
                    kernel::sesion()->borrar('error_controlar_periodo_inscripcion_vigente');
                }

	}

}