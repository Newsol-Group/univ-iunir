<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_solicitar_certificaciones extends guarani_pers_ci
{
	protected $datos_form_temp;
	const chars_maximo_combo = 200;
	//-----------------------------------------------------------------------------------
	//---- Pantalla Inicial -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos_alumno = $this->controlador()->get_datos_alumno();
		$this->controlador()->ev_info()->set_datos($datos_alumno);
	}

	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__formulario(toba_ei_formulario $form)
	{
		$datos = [];
		
		if (isset($this->datos_form_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->datos_form_temp;
		} else {
			$datos_mencion = aplanar_matriz($this->cn()->entidad()->get_datos_menciones(), 'mencion');
			$datos = $this->cn()->entidad()->get_datos();
			$datos['menciones'] = $datos_mencion;
			$form->set_datos($datos);
		}
		
		// @see: https://redmine.siu.edu.ar/redmine/issues/34780
        // Si usa EEI
		if ($this->usa_expediente_electronico()) {
			$datos['nro_expediente_sudocu'] = ($datos['nro_expediente'] ?? null);
		}
		$form->set_datos($datos);
		
		if (!$this->cn()->entidad_es_nueva()) {
			$form->ef('certificado')->set_solo_lectura();
		}
	}

	function evt__formulario__modificacion($datos)
	{
		try {
			
			// @see: https://redmine.siu.edu.ar/redmine/issues/34780
			if ($this->usa_expediente_electronico()) {
				$datos['nro_expediente'] = $datos['nro_expediente_sudocu'];
                
                /*
                 * @see: https://redmine.siu.edu.ar/redmine/issues/44569
                 * Cargo la descripción del trámite de SUDOCU en sga_certificados_otorg.nro_expediente_descr
                 */
                // Si el Expediente es un UUID válido lo busco en SUDOCU
                if (sudocu::idTramiteValido($datos['nro_expediente'])) {
                    $descrTramiteSUDOCU = sudocu::getDescripcionTramiteComboEditable($datos['nro_expediente']);
                    $datos['nro_expediente_descr'] = $descrTramiteSUDOCU;
                }
            }
			
			if ($this->cn()->entidad_es_nueva()) {
				$datos_alumno = $this->controlador()->get_datos_alumno();
				$datos_certif = toba::consulta_php('co_certificados')->get_certificado($datos['certificado']);

				$datos['alumno'] = $datos_alumno['alumno'];
				$datos['persona'] = $datos_alumno['persona'];
				$datos['plan_version'] = $datos_alumno['plan_version'];
				$datos['circuito'] = $datos_certif['circuito'];
				$datos['estado'] = null;
			}

			$this->cn()->entidad()->set_datos($datos);
			$this->cn()->entidad()->set_datos_menciones($datos['menciones']);
		} catch (guarani_error $e) {
			$this->datos_form_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Recupera los certificados disponibles de acuerdo al plan versión del alumno y el modo (alta, modificación).
	 */
	function get_certificados_disponibles_plan_version()
	{
		toba_ef_combo::set_maximo_descripcion(self::chars_maximo_combo);
		if ($this->cn()->entidad_es_nueva()) {			
			// Se obtienen los certificados para los cuales el alumno no ha iniciado trámite de solicitud.
			$datos_alumno = $this->controlador()->get_datos_alumno();
			$certificados = toba::consulta_php('co_certificados')->get_certificados_no_tramitados_por_alumno($datos_alumno['plan_version'], $datos_alumno['alumno']);
		} else {
			// Se obtienen los datos del certificado que solicitó el alumno.
			$datos_tramite = $this->cn()->entidad()->get_datos();
			$certificados = array(toba::consulta_php('co_certificados')->get_certificado($datos_tramite['certificado']));
		}
		return $certificados;
	}

	/**
	 * Retorna el filtro que excluye certificados para los cuales el alumno ya ha iniciado un trámite de solicitud.
	 */
	function get_condicion_tramites()
	{
		$tramites = $this->controlador()->get_tramites();
		return empty($tramites) ? 'true' : guarani_sql::armar_condicion_in($tramites, 'sga_certificados', 'certificado', 'certificado', true);
	}
	
	function usa_expediente_electronico()
	{
		$datos_alumno = $this->controlador()->get_datos_alumno();
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($datos_alumno['propuesta']);
		$egr_usa_expediente_electronico = guarani::parametros()->get_valor(sudocu::EGR_USA_EXPEDIENTE_ELECTRONICO, $ras);
		return ($egr_usa_expediente_electronico == comunes::si);
	}
}