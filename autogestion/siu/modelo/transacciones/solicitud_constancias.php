<?php
namespace siu\modelo\transacciones;

use SIU\Chulupi\ErrorKernelDb;
use \SIU\Chulupi\kernel;
use siu\errores\error_guarani;
use siu\errores\error_guarani_usuario;
use \siu\modelo\datos\catalogo;

class solicitud_constancias extends transaccion_alumno
{
	protected $operacion = '800SIUCON002';
	protected $constancia;
	
	public function setConstancia($constancia)
	{
		$this->constancia = $constancia;
	}
	
	public function getConstancia()
	{
		return $this->constancia;
	}
	
	//---------------------------------------------
	//	EVENTOS
	//---------------------------------------------

	function evt__controla_nueva_solicitud()
	{	
		$resultado = array('ok' => false, 'mensajes' => array());
		$mensajes_controles = $this->controles_solicitud_constancia();
		$resultado['mensajes'] = $mensajes_controles;	
		return $resultado;
	}
	
	function evt__controla_cant_max_solicitudes_aa()
	{	
		$resultado = ['ok' => false, 'mensajes' => []];
		$mensajes_controles = $this->controles_cant_max_solicitudes_aa();
		$resultado['mensajes'] = $mensajes_controles;	
		return $resultado;
	}
	
	function evt__nueva_solicitud($constancia, $copias, $presentar, $observaciones, $formulario, $formulario_campo, $formulario_valor)
	{
        $resultado = array('ok' => false, 'mensajes' => array());

		try
		{
			kernel::db()->abrir_transaccion();
			$cantidades = catalogo::consultar('solicitudes', 'get_cantidades', array(
				'constancia' => $constancia,
				'alumno' => $this->alumno
			));

			if ($copias > $cantidades['max_cant_copias']){
                throw new error_guarani(kernel::traductor()->trans('800CON_limite_copias_sobrepasado_alumno', array(
                    '%1' => $cantidades['max_cant_copias']
                )));
            }
			
			$this->validar_constancia($constancia);
			
			// Puntos de control para verificar que el Alumno esté en condiciones de Solicitar Certificados.			

            $permite_descargar = \toba::consulta_php('co_constancias')->get_se_puede_descargar_constancia($constancia);
            $estado = \constancias_nucleo::estado_solicitud_pendiente;
            $codigo_verificacion = null;
            if($permite_descargar){
                $estado = \constancias_nucleo::estado_solicitud_online;
                $codigo_verificacion = \constancias_nucleo::generar_codigo_verificacion();
            }

			$ok = catalogo::consultar('solicitudes', 'nueva_solicitud', array(
				'alumno' => $this->alumno,
				'constancia' => $constancia,
				'copias' => $copias,
				'presentar' => $presentar,
				'observaciones' => $observaciones,
				'interfaz' => 2,
				'estado' => $estado,
                'codigo_verificacion' => $codigo_verificacion,
                'formulario' => $formulario,
                'formulario_campo' => $formulario_campo,
                'formulario_valor' => $formulario_valor
			));

			kernel::db()->cerrar_transaccion();
			if($ok) {
				$resultado['ok'] = true;
			} else {
				$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('nueva_solicitud_error'));
			}
		}
		catch (error_guarani_usuario $e) {//Si hay error en los controles.
			kernel::log()->add_debug('Error controles solicitud constancia', $e);
			kernel::db()->abortar_transaccion();
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('800CON_error_control_solicitud_alumno'));
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => $e->get_mensaje());
		}
		catch (error_guarani $e) {//Si hay error con la cantidad de copias/solicitudes.
			kernel::log()->add_debug('Error solicitud constancia', $e);
			kernel::db()->abortar_transaccion();
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => $e->get_mensaje());
		}
		catch (ErrorKernelDb $e) {//Si hay error en la DB.
			kernel::log()->add_debug('Error DB solicitud constancia', $e);
			kernel::db()->abortar_transaccion();
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('nueva_solicitud_error'));
		}
		catch (\Exception $e) {
			kernel::log()->add_debug('Error al insertar la solicitud', $e);
			kernel::db()->abortar_transaccion();
			$resultado['mensajes'][] = array('mtipo' => 'E', 'mcuerpo' => kernel::traductor()->trans('nueva_solicitud_error'));
		}

		return $resultado;
	}
	
	function validar_constancia($constancia)
	{
		if ($constancia == \constancias::constancia_de_alumno_regular) {
			$alumno = $this->alumno;
			
			if (! (\toba::consulta_php('co_alumnos')->es_alumno_activo($alumno) && \toba::consulta_php('co_alumnos')->es_alumno_regular($alumno))) {
				throw new error_guarani(kernel::traductor()->trans('constancias_certificados.no_sos_activo_regular'));
			}
		}
	}
		
	function controles_solicitud_constancia()
	{
		// Disparar controles
		try {
			list($resultado_controles, $mensajes) = $this->disparar_puntos_de_control_alumno_constancia();
		} catch (error_guarani $e) {
			$mensajes = $e->get_mensajes_usuario();
		}
		return $mensajes;
	}
	
	protected function disparar_puntos_de_control_alumno_constancia()
	{
		$puntos_de_control = array(
			\guarani_punto_de_control::alumno_entidad,
			\guarani_punto_de_control::persona_operacion,
			\guarani_punto_de_control::alumno_operacion,
			\guarani_punto_de_control::alumno_anio_academico
		);
		
		// Traigo el último período de reinscripción a la propuesta.
		$anio_academico = \toba::consulta_php('co_reinscripciones')->get_anio_academico_de_ultimo_periodo_reinscripcion($this->alumno, $this->plan_version, \comunes::interfaz_autogestion);
		
		$parametros = array(
			'persona'			=> $this->persona,
			'alumno'			=> $this->alumno,
			'plan_version'		=> $this->plan_version,
			'fecha'				=> \guarani_fecha::get_hoy(),
			'anio_academico'	=> $anio_academico
		);
		
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();
		return array($resultado_controles, $mensajes_controles);
	}
	
	function controles_cant_max_solicitudes_aa()
	{
		// Disparar controles
		try {
			list($resultado_controles, $mensajes) = $this->disparar_puntos_de_control_cant_max_solicitudes_aa();
		} catch (error_guarani $e) {
			$mensajes = $e->get_mensajes_usuario();
		}
		return $mensajes;
	}
	
	protected function disparar_puntos_de_control_cant_max_solicitudes_aa()
	{
		$puntos_de_control = [
			\guarani_punto_de_control::alumno_constancia_certificado
		];
		
		$parametros = [
			'alumno'		=> $this->alumno,
			'plan_version'	=> $this->plan_version,
			'constancia'	=> $this->getConstancia(),
			'fecha'			=> \guarani_fecha::get_hoy()
		];
		
		$proceso = $this->nuevo_proceso_control($puntos_de_control, $parametros);
		$resultado_controles = $proceso->ejecutar();
		$mensajes_controles = $proceso->get_mensajes();
		return [$resultado_controles, $mensajes_controles];
	}
	
}