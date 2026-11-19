<?php
/**
 * 
 *
 * @package 	Egresados
 * @subpackage 	Actualizaciones
 * 
 */
class ci_edi_actualizar_tramites_certificacion extends ci_edicion_simple
{
	//-----------------------------------------------------------------------------------
	//---- formulario -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__formulario(toba_ei_formulario $form)
    {
        $datos = [];

        if (isset($this->datos_temp)) {
            $this->controlador->set_hubo_cambios();
            $datos = $this->datos_temp;
        } else {
            // Se desactivan los campos que no serán visibles, cualquiera sea la acción seleccionada.
            $form->desactivar_efs(aplanar_matriz($this->get_campos_ocultos(), 'campo'));
            $datos = $this->cn()->entidad()->get_datos_tramite();
        }

        $datos['error_expediente_sudocu'] = false;
        // @see: https://redmine.siu.edu.ar/redmine/issues/34780
        // Si usa EEI
        if ($this->usa_expediente_electronico()) {
            $datos['nro_expediente_sudocu'] = $datos['nro_expediente'];
            $datos['descr_expediente_sudocu'] = $datos['nro_expediente_descr'];

            // Si el Expediente es un UUID válido lo busco en SUDOCU
            if (sudocu::idTramiteValido($datos['nro_expediente'])) {
                // Busco la descripción del trámite en SUDOCU (la descripción del trámite en SUDOCU puede cambiar al editarse)
                $descrTramiteSUDOCU = sudocu::getDescripcionTramiteComboEditable($datos['nro_expediente']);
                // Si $descrTramiteSUDOCU es null (falla la obtención del trámite de SUDOCU) pongo 'error_expediente_sudocu' en true
                $datos['error_expediente_sudocu'] = is_null($descrTramiteSUDOCU);
            }
        }

        $form->set_datos($datos);
    }

    function evt__formulario__modificacion($datos)
	{
		try {
			
			// @see: https://redmine.siu.edu.ar/redmine/issues/34780
            // Si usa EEI
            if ($this->usa_expediente_electronico()) {
                
                // Si no falló la obtención del trámite de SUDOCU (si falla no debo modificar nada)
                if (!$datos['error_expediente_sudocu']) {
                    
                    // Si esta desasociando el Expediente del Trámite de Certificación
                    if (is_null($datos['nro_expediente_sudocu'])) {
                        
                        $datos['nro_expediente'] = null;
                        $datos['nro_expediente_descr'] = null;
                        
                    } else {// Si esta asociando el Expediente al Trámite de Certificación
                        
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
                }
            }

            $datos = $this->cn()->entidad()->recalcular_historia_academica($datos);

			$this->cn()->entidad()->set_datos_tramite($datos);
			$this->cn()->entidad()->set_formularios($datos);
			$this->cn()->entidad()->agregar_cambio_estado($datos);
		} catch (guarani_error $e) {
			$this->datos_temp = $datos;
			throw $e;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ajax__campos_formularios($accion, toba_ajax_respuesta $respuesta)
	{
		$datos_tramite = $this->cn()->entidad()->get_datos_tramite();
		$campos = toba::consulta_php('co_circuitos_tramite')->get_campos_por_circuito_estado_accion($datos_tramite['circuito'], $datos_tramite['estado'], $accion);
		$formularios = toba::consulta_php('co_circuitos_tramite')->get_formularios_por_circuito_estado_accion($datos_tramite['circuito'], $datos_tramite['estado'], $accion);
		
		// @see: https://redmine.siu.edu.ar/redmine/issues/34780
        // Recorro todos los campos del formulario
		foreach ($campos as $key => $campo) {
			
            // En el caso del campo 'nro_expediente' del formulario
			if ($campo['campo'] == 'nro_expediente') {
				
                // Si usa EEI
				if ($this->usa_expediente_electronico()) {
                    
                    $campos[$key]['campo'] = 'nro_expediente_sudocu';
                    
                    // Si el Expediente es un UUID válido lo busco en SUDOCU
                    if (sudocu::idTramiteValido($datos_tramite['nro_expediente'])) {
                        // Busco la descripción del trámite en SUDOCU (la descripción del trámite en SUDOCU puede cambiar al editarse)
                        $descrTramiteSUDOCU = sudocu::getDescripcionTramiteComboEditable($datos_tramite['nro_expediente']);
                        // Si $descrTramiteSUDOCU es null (falla la obtención del trámite de SUDOCU) muestro el campo 'descr_expediente_sudocu', sino muestro 'nro_expediente_sudocu'
                        $campos[$key]['campo'] = is_null($descrTramiteSUDOCU)? 'descr_expediente_sudocu' : 'nro_expediente_sudocu'; 
                    }
                }
				
			}
            
		}

		$respuesta->set(array('campos' => $campos, 'formularios' => $formularios));
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function get_acciones()
	{
		$datos_tramite = $this->cn()->entidad()->get_datos_tramite();
		return toba::consulta_php('co_circuitos_tramite')->get_acciones_por_circuito_estado($datos_tramite['circuito'], $datos_tramite['estado'] ?? null);
	}

	function get_estado_final($accion)
	{
		$datos_tramite = $this->cn()->entidad()->get_datos_tramite();
		return toba::consulta_php('co_circuitos_tramite')->get_estado_final_por_circuito_estado_accion($datos_tramite['circuito'], $datos_tramite['estado'], $accion);
	}

	function get_campos_visibles()
	{
		$datos_tramite = $this->cn()->entidad()->get_datos_tramite();
		$accion = isset($this->datos_temp['accion']) ? $this->datos_temp['accion'] : null;
		return toba::consulta_php('co_circuitos_tramite')->get_campos_por_circuito_estado_accion($datos_tramite['circuito'], $datos_tramite['estado'], $accion);
	}

	function get_campos_ocultos()
	{
		$campos_visibles = $this->get_campos_visibles();
		$filtro = '';
		if (! empty($campos_visibles)) {
			$filtro = guarani_sql::armar_condicion_in($campos_visibles, 'mce_campos', 'campo', 'campo', true);
		}
		return toba::consulta_php('co_circuitos_tramite')->get_listado_campos_egreso($filtro);
	}
	
	function get_colaciones_propuestas()
	{
		$datos_tramite = $this->controlador()->get_datos_tramite();		
		return guarani::co('co_colaciones')->get_colaciones_una_propuesta($datos_tramite['propuesta']);		
	}
	
	function usa_expediente_electronico()
	{
		$datos_tramite_ci = $this->controlador()->get_datos_tramite();
		$ras = toba::consulta_php('co_responsables_academicas')->get_propuestas_ras($datos_tramite_ci['propuesta']);
		$egr_usa_expediente_electronico = guarani::parametros()->get_valor(sudocu::EGR_USA_EXPEDIENTE_ELECTRONICO, $ras);
		return ($egr_usa_expediente_electronico == comunes::si);
	}
}