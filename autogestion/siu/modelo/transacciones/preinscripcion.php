<?php

namespace siu\modelo\transacciones;

use SIU\Chulupi\kernel;
use siu\modelo\_comun\abm;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\formularios\guarani_form_elemento as util_form;
use siu\operaciones\datos_censales\_comun\util_form_censo;
use siu\modelo\entidades\parametro;
use siu\modelo\transacciones\solicitar_certificacion;

class preinscripcion extends transaccion
{
    protected $datos_preinsc;

	function hay_periodo_preinsc_vigente()
	{
        return catalogo::consultar('preinscripcion', 'hay_periodo_preinsc_vigente');
    }

	function proximo_inicio_preinscripcion()
	{
        return catalogo::consultar('preinscripcion', 'proximo_inicio_preinscripcion');
    }

	function get_datos_preinscripciones()
	{
        if (!isset($this->datos_preinsc)) {
            $parametros = array('persona' => $this->persona);
            $this->datos_preinsc = catalogo::consultar('preinscripcion', 'get_propuestas_preinscripcion', $parametros);
        }
        return $this->datos_preinsc;
    }

	function get_requisitos_persona_propuesta($propuesta)
	{
        $parametros = [
            'persona' => $this->persona,
            'propuesta' => $propuesta,
            'estado_req_digital' => null
        ];
        return catalogo::consultar('preinscripcion', 'get_requisitos_persona_propuesta', $parametros);
    }

	function get_cant_preinscripciones_vigentes()
	{
        $parametros = array(
            'persona' => $this->persona
        );
        $cant_preinscripciones = catalogo::consultar('preinscripcion', 'get_cantidad_preinscripciones_vigentes', $parametros);
        return $cant_preinscripciones;
    }

	function agregar_propuesta_preinsc($datos)
	{
        try {
            kernel::db()->abrir_transaccion();

            $periodo_insc = catalogo::consultar('preinscripcion', 'get_periodo_preinscripcion', array('propuesta' => $datos['propuesta'],
                        'ubicacion' => $datos['ubicacion'],
                        'modalidad' => $datos['modalidad']));
            $pre_meses_vigencia_preinsc = parametro::get_valor('pre_meses_vigencia_preinsc');
            $hoy = \guarani_fecha::get_hoy();
            $fecha_vencimiento = \guarani_fecha::get_fecha_desplazada($hoy, $pre_meses_vigencia_preinsc, 'mes');

            $datos_alta = array(
                'persona' => $this->persona,
                'responsable_academica' => $datos['responsable_academica'],
                'propuesta' => $datos['propuesta'],
                'ubicacion' => $datos['ubicacion'],
                'modalidad' => $datos['modalidad'],
                'periodo_insc' => $periodo_insc,
                'fecha_fin_vigencia' => $fecha_vencimiento,
                'estado' => 'N', //preinscripcion::nueva hasta que se cierre y pase a pendiente
            );

            abm::alta('sga_preinscripcion_propuesta', $datos_alta);

            kernel::db()->cerrar_transaccion();
        } catch (Exception $e) {
            kernel::db()->abortar_transaccion();
            throw $e;
        }
    }

	function eliminar_propuesta_preinsc($id)
	{
        $datos_preinsc = $this->get_datos_preinscripciones();
        if (empty($datos_preinsc[$id])) {
            return;
        }
        $datos = $datos_preinsc[$id];

        $clave = [];
        $clave['preinscripcion'] = $datos['preinscripcion'];
        abm::baja('sga_preinscripcion_propuesta', $clave);

        $ra_inscripcion_eliminada = $datos['responsable_academica'];
        $inscripciones_restantes = $datos_preinsc;
        // Quito la inscripción eliminada
        unset($inscripciones_restantes[$id]);
        $ra_inscripciones_restantes = array_column($inscripciones_restantes, 'responsable_academica');
        // Si no hay otras inscripciones para la RA de la inscripción eliminada
        // entonces devuelvo el turno
        if (!in_array($ra_inscripcion_eliminada, $ra_inscripciones_restantes)) {
            $parametros = [
                        'persona'   => $this->persona,
                        'ra'        => $ra_inscripcion_eliminada
            ];
            catalogo::consultar('preinscripcion', 'devolver_turno', $parametros);
        }

        unset($this->datos_preinsc);
    }

    function finalizar_propuesta_preinsc($id) {
        kernel::sesion()->borrar('mensaje_error');
        kernel::sesion()->borrar('mensaje_ok');
        $mensaje = '';
        $datos_preinsc = $this->get_datos_preinscripciones();
        if (empty($datos_preinsc[$id])) {
            return;
        }
        $datos = $datos_preinsc[$id];
       
        //DATOS CENSALES cambio de estado si no hay faltantes si hay faltantes pido completar los datos 
        
        $hay_faltantes = $this->get_datos_faltantes();
        
        //REQUISITOS
        $parametros = ['persona' => $datos['persona'],
            'propuesta' => $datos['propuesta'],
            'estado_req_digital' => null
        ];
        $requisitos = catalogo::consultar('preinscripcion', 'get_requisitos_persona_propuesta', $parametros);

        foreach ($requisitos as $requisito) {
            if (
				// Si es "Documento Digital obligatorio en Pre-Inscripción"
				$requisito['doc_digital_obligatorio_preinscripcion'] == \comunes::si
				// y Estado presentación digital es "-- No cargado --" o "-- Vencido --"
				&& in_array($requisito['estado_req_digital'], [solicitar_certificacion::estado_no_presentado, solicitar_certificacion::estado_vencido])
			)
			{
               $faltan_requisitos[] = $requisito['requisito_nombre'];
            }
        }
        $parametros_turno['persona'] = $datos['persona'];
        $parametros_turno['ra'] = $datos['responsable_academica'];

       // Turno asignado
        $turno_asignado = catalogo::consultar('preinscripcion', 'get_turno_presentacion', $parametros_turno);

       // Turnos
	$turnos = $this->get_listado_turnos();

        if (empty($hay_faltantes) && empty($faltan_requisitos) && (empty($turnos) || (!empty($turnos) && !empty($turno_asignado)))) {
            //cambio de estado para poder procesar estado copiada a gestion P
            $estado = catalogo::consultar('preinscripcion', 'cambiar_estado_preinscripcion_3w', $datos);
            kernel::sesion()->borrar('mensaje_error');
            unset($this->datos_preinsc);
            $mensaje = ($estado == 'N') ?  kernel::traductor()->trans('preinscripcion.reabierta_ok'): kernel::traductor()->trans('preinscripcion.cerrada_ok') ;
            kernel::sesion()->set('mensaje_ok', $mensaje);
        } else {

            if (!empty($hay_faltantes)) {
                $link_datos_censales = 'datos_censales';
                $mensaje = kernel::traductor()->trans('preinscripcion.falta_datos_censales', ['%link%' => $link_datos_censales]);
            }
            if (!empty($faltan_requisitos)) {
                $br=(!empty($mensaje))?'</br>':'' ;
                $mensaje .= $br. kernel::traductor()->trans('preinscripcion.faltan_requisitos') . implode(', ', $faltan_requisitos);
            }

			// Si hay turnos de presentación de documentación, pero no se seleccionó ninguno
            if (!empty($turnos) && empty($turno_asignado)) {
               $br=(!empty($mensaje))?'</br>':'' ;
                $mensaje .= $br.  kernel::traductor()->trans('preinscripcion.falta_turno');
            }

            kernel::sesion()->set('mensaje_error', $mensaje);
        }


        return $mensaje;
    }

    //--------------------------------------------------------------------------
    //---- Datos para selector de Propuestas -----------------------------------
    //--------------------------------------------------------------------------

	function get_responsables_academicas()
	{
        $datos = catalogo::consultar('preinscripcion', 'get_responsables_academicas');
        return util_form::armar_combo_opciones($datos, 'responsable_academica', 'nombre', true, false);
    }

	function get_propuestas_disponibles_preinscripcion($ra)
	{
        $parametros = array(
            'ra' => $ra,
            'persona' => $this->persona,
        );
        $datos = catalogo::consultar('preinscripcion', 'get_propuestas_disponibles_preinscripcion', $parametros);
        if (empty($datos)) {
            $salida = array();
            $salida[null] = kernel::traductor()->trans('preinscripcion.sin_propuestas');
            return $salida;
        }
        return util_form::armar_combo_opciones($datos, 'propuesta', 'nombre', true, false);
    }

	function get_ubicaciones_propuesta($propuesta)
	{
        $parametros = array('propuesta' => $propuesta);
        $datos = catalogo::consultar('preinscripcion', 'get_ubicaciones_propuesta', $parametros);
        return util_form::armar_combo_opciones($datos, 'ubicacion', 'nombre', true, false);
    }

	function get_modalidades_propuesta($propuesta, $ubicacion)
	{
        $parametros = array('propuesta' => $propuesta, 'ubicacion' => $ubicacion);
        $datos = catalogo::consultar('preinscripcion', 'get_modalidades_propuesta', $parametros);
        if (empty($datos)) {
            $salida = array();
            $salida[null] = kernel::traductor()->trans('preinscripcion.sin_modalidades');
            return $salida;
        }
        return util_form::armar_combo_opciones($datos, 'modalidad', 'nombre');
    }

    //--------------------------------------------------------------------------
    //---- Turnos de presentación de documentación -----------------------------
    //--------------------------------------------------------------------------

    function get_listado_turnos() {
        $parametros = array('persona' => $this->persona);
        return catalogo::consultar('preinscripcion', 'get_listado_turnos', $parametros);
    }

    /**
     * Determina si existen turnos disponibles (no ocupados, vigentes) para una
     * Responsable Académica (RA) dada
     */
    function hay_turnos_disponibles($ra) {
        $parametros = array('ra' => $ra);
        return catalogo::consultar('preinscripcion', 'hay_turnos_disponibles', $parametros);
    }

    /**
     * Obtiene las fechas en las que exiten turnos disponibles
     * 
     */
    function get_fechas_turnos_disponibles($ra) {
        $parametros = array('ra' => $ra);
        return catalogo::consultar('preinscripcion', 'get_fechas_turnos_disponibles', $parametros);
    }

    /**
     * Obtiene la primera fecha en la que hay un turno disponible, o el string 
     * vacío en caso de no existir turnos disponibles
     */
	function get_fecha_minima($ra)
	{
        $parametros = array('ra' => $ra);
        return catalogo::consultar('preinscripcion', 'get_fecha_minima', $parametros);
    }

    /**
     * Obtiene la última fecha en la que hay un turno disponible, o el string 
     * vacío en caso de no existir turnos disponibles
     */
	function get_fecha_maxima($ra)
	{
        $parametros = array('ra' => $ra);
        return catalogo::consultar('preinscripcion', 'get_fecha_maxima', $parametros);
    }

    /**
     * Obtiene las franjas horarias en las que hay turnos disponibles para una 
     * RA dada en una determinada fecha
     */
	function get_franjas_horarias_disponibles($fecha, $ra)
	{
        $parametros = array(
            'fecha' => $fecha,
            'ra' => $ra,
        );
        return catalogo::consultar('preinscripcion', 'get_franjas_horarias_disponibles', $parametros);
    }

    /**
     * Asigna un turno según la solicitud
     */
	function reservar_turno($ra, $fecha, $franja)
	{
        $parametros = array(
            'persona' => $this->persona,
            'ra' => $ra,
            'fecha' => $fecha,
            'franja' => $franja,
        );
        return catalogo::consultar('preinscripcion', 'reservar_turno', $parametros);
    }

    /**
     * Deja nuevamente disponible un turno solicitado por un aspirante, para una
     * RA dada.
     */
	function devolver_turno($ra)
	{
        $parametros = array(
            'persona' => $this->persona,
            'ra' => $ra,
        );
        catalogo::consultar('preinscripcion', 'devolver_turno', $parametros);
    }

    /**
     * Determina qué turno de presentación(en caso que haya sido solicitado) de
     * un aspirante en una RA dada.
     */
	function get_turno_presentacion($ra)
	{
        $parametros = array(
            'persona' => $this->persona,
            'ra' => $ra,
        );
        return catalogo::consultar('preinscripcion', 'devolver_turno', $parametros);
    }

    function get_datos_faltantes() {
        $datos = $this->info__datos_censales();
        $forms = util_form_censo::$secciones_configurables;
        $campos_pagelets = util_form_censo::get_campos_secciones();

        $datos_faltantes = array();
        foreach ($forms as $form) {
            $campos_pagelet = $campos_pagelets[$form];
            $hay_faltantes = false;
            foreach ($campos_pagelet as $campos_secciones) {
                foreach ($campos_secciones as $campo) {
                    if (array_key_exists($campo, $datos) && (is_null($datos[$campo])) && (util_form_censo::visible($campo)) && (util_form_censo::obligatorio($campo))) {
                        if (!$hay_faltantes) {
                            $datos_faltantes[$form] = array();
                        }
                        $datos_faltantes[$form][] = $campo;
                        $hay_faltantes = true;
                    }
                }
            }
        }
        return $datos_faltantes;
    }

    function info__datos_censales() {
        $parametros = array('persona' => $this->persona);
        return catalogo::consultar('datos_censales', 'get_datos_censales', $parametros);
    }
}
