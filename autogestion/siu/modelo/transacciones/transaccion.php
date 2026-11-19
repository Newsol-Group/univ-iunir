<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\controles\proceso_control;
use siu\guarani;
use siu\errores\error_guarani;
use siu\modelo\datos\catalogo;

/**
 * Los controles suponen una acumulacion ordenada.
 *
 *	@todo: El dato tiene que manejarse en sesion con una clave de transaccion
 * 
 *	Esta clave es la que se usa en el esquema de controles enfocado en esta
 * transaccion. tambien el control de secuencias (para controlar errores de hilo por navegacion con tabs
 * el espacio necesitaria un finalizar() tanto como iniciar() para manejar el 
 * ciclo de vida de esta info
 */
abstract class transaccion
{
	static protected $controles_activos = true;
	protected $operacion;
	protected $datos;
	protected $id;
	protected $persona;
	protected $opciones_request = array();

        public const OP_INSCRIPCION_CURSADA = 1;
        public const OP_INSCRIPCION_CURSO = 2;
        public const OP_INSCRIPCION_EXAMEN = 3;
        public const OP_INSCRIPCION_EXAMEN_CURSO = 4;

        function __construct($persona)
	{
		$this->persona = $persona;	
		$this->iniciar();
	}
	
	function iniciar(){}
	
	
	function nuevo_proceso_control($puntos_de_control, $parametros, $operacion=null)
	{
		if(!isset($operacion)) {
			if(!isset($this->operacion)) {
				throw new error_guarani('CONTROL TRANSACCION: No esta definida la operacion');
			}
			$operacion = $this->operacion;
		}
		return new proceso_control($puntos_de_control, $parametros, $operacion);
	}
	
	function control__inicial(){}

	//------------------------------------------------------
	// Helper manejo operaciones
	//------------------------------------------------------
	
	protected function get_opcion_enviada($seleccion, $metodo)
	{
		if (!isset($this->opciones_request[$seleccion])) {
			$opciones = $this->$metodo();
			if (!isset($opciones[$seleccion])) {
				throw new error_guarani('Opcion invalida');
			}	
			$this->opciones_request[$seleccion] = $opciones[$seleccion];
		}
		//kernel::log()->add_debug('OPCION SELECCIONADA', $this->opciones_request[$seleccion]);
		return $this->opciones_request[$seleccion];
	}

        /**
         * Bloquea acceso simultáneo.
         *
         * @param int $operacion Código de operación.
         *
         * @throws \SIU\Chulupi\ErrorKernelNotificaciones Si falla el control acceso simultáneo
         *
         * @return void.
         */
        function bloquear_simultaneidad_operaciones($operacion)
        {
            // Si no se controla el acceso simultáneo a operaciones entonces salgo
            if (!kernel::proyecto()->es_control_simultaneidad_operaciones_activo()) {
                return true;
            }

            $parametros = [
                'persona' => $this->persona,
                'operacion' => $operacion,
                'tiempo' => kernel::proyecto()->get_control_simultaneidad_operaciones_ttl_segundos()
            ];
            $resultado  = catalogo::consultar('sistema', 'bloquear_simultaneidad_operaciones', $parametros);

            $hubo_error = false;
            $msj_error = '';
            switch ($resultado) {
                case -1:
                    $hubo_error = true;
                    $msj_error = kernel::traductor()->trans('simultaneidad_operaciones.error_acceso_simultaneo');
                    break;
                case -2:
                    $hubo_error = true;
                    $msj_error = kernel::traductor()->trans('simultaneidad_operaciones.error_db');
                    break;
            }

            // Si hubo error
            if ($hubo_error) {
                $e = new error_guarani('');
                $e->add_mensaje_usuario(guarani::crear_mensaje(guarani::control_error, $msj_error));
                throw $e;
            }

        }

        /**
         * Desbloquea acceso simultáneo.
         *
         * @param int $operacion Código de operación.
         *
         * @return void.
         */
        function desbloquear_simultaneidad_operaciones($operacion)
        {

            // Si no se controla el acceso simultáneo a operaciones entonces salgo
            if (!kernel::proyecto()->es_control_simultaneidad_operaciones_activo()) {
                return true;
            }

            $parametros = [
                'persona' => $this->persona,
                'operacion' => $operacion,
                'tiempo' => kernel::proyecto()->get_control_simultaneidad_operaciones_ttl_segundos()
            ];
            catalogo::consultar('sistema', 'desbloquear_simultaneidad_operaciones', $parametros);

        }
	
}