<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_nav_solicitar_certificaciones_nsg extends ci_nav_solicitar_certificaciones
{

	function conf__seleccion(toba_ei_pantalla $pantalla)
	{
		$this->s__datos_alumno = toba::consulta_php('co_alumnos')->get_datos_basicos_alumno($this->get_preseleccion());
		$this->ev_info()->set_datos($this->s__datos_alumno);
		$this->cn()->validar_solicitud_alumno($this->s__datos_alumno['persona'], $this->s__datos_alumno['alumno'], $this->s__datos_alumno['plan_version']);

	//	try {
	//		$this->cn()->validar_solicitud_certificacion($this->s__datos_alumno['alumno'], $this->s__datos_alumno['plan_version']);
	//	} catch (guarani_error $e) {
	//		toba::notificacion()->agregar($e->getMessage(), 'error');
	//		toba::logger()->error($e->getMessage());
	//	}

	}

	function evt__guardar()
	{
		$this->cn()->validar_solicitud_certificacion($this->s__datos_alumno['alumno'], $this->s__datos_alumno['plan_version']);
		// Si hay un trámite seleccionado => se trata de una edición.
		if (isset($this->s__seleccion)) {
			parent::evt__guardar();
		} else {
			$this->cn()->desactivar_transacciones_anidadas();
			
			//Si la solicitud no se pudo generar, devuelve false, sino un id de tramite.
			list($nro_solicitud, $estado_requisitos) = $this->cn()->generar_solicitud();
			if ($nro_solicitud) {
				if ($estado_requisitos == tramite_certificacion::estado_requisitos_aceptado) {
					// Si el alumno está en condiciones de obtener el certificado se navega a "Actualizar Trámite de Certificado".
					echo toba_js::abrir();
					echo "document.location.href='" .
					toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '32000019', array('nro_solicitud' => $nro_solicitud)) . "'\n";
					echo toba_js::cerrar();
				} else { // El estado de requisitos es "Pendiente".
					$datos_alumno = $this->get_datos_alumno();
					echo toba_js::abrir();
					echo "document.location.href='" .
					toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '5000111', array('persona' => $datos_alumno['persona'])) . "'\n";
					echo toba_js::cerrar();
				}
			} else {
				// Si el alumno NO está en condiciones de obtener el certificado se muestra un resumen de los requisitos no cumplidos.
				$this->set_datos_resumen($this->cn()->get_observaciones());
				$this->set_pantalla('resumen');
			}			
		}
	}

}