<?php
class cn_ent_docente extends cn_entidad
{
	function set_datos_docente($datos)
	{
		$this->validar_datos_docente($datos);
		$this->tabla('docente')->set($datos);
	}

	function get_datos_docente()
	{
		return $this->tabla('docente')->get();
	}

	function persona_es_docente()
	{
		$docente = $this->tabla('docente')->get();
		return (!empty($docente));
	}

	//---------------------------------------------------------------------

	function get_datos_docente_resp()
	{
		return $this->tabla('docente_resp')->get_filas();
	}

	function set_datos_docente_resp($datos)
	{
		$docente_resp = $this->tabla('docente_resp')->get_filas();
		$docente_resp_aplanadas = aplanar_matriz($docente_resp, 'responsabilidad');
		$docente_resp_a_borrar = array_diff($docente_resp_aplanadas, $datos);
		$docente_resp_a_insertar = array_diff($datos, $docente_resp_aplanadas);

		$docente_resp_asociativas = rs_convertir_asociativo($docente_resp, array('responsabilidad'), apex_datos_clave_fila);
		foreach ($docente_resp_a_borrar as $docente_resp) {
			$this->tabla('docente_resp')->eliminar_fila($docente_resp_asociativas[$docente_resp]);
		}

		foreach ($docente_resp_a_insertar as $docente_resp) {
			$this->tabla('docente_resp')->nueva_fila(array('responsabilidad' => $docente_resp));
		}
	}

	//---------------------------------------------------------------------

	function get_datos_docente_ra()
	{
		return $this->tabla('docente_ra')->get_filas();
	}

	function set_datos_docente_ra($datos)
	{
		$this->tabla('docente_ra')->procesar_filas($datos);
	}

	//--------------------------------------------------------------------------

	function baja_en_catedras_tribunales($datos)
	{
		if (isset($datos['docente'])) {
			guarani::act('act_docentes')->actualizar_docente_catedras($datos['docente'], comunes::estado_baja);
			guarani::act('act_docentes')->actualizar_docente_tribulanes($datos['docente'], comunes::estado_baja);
		}
	}

	//--------------------------------------------------------------------------

	function validar_datos_docente($datos)
	{
		$existe_docente = toba::consulta_php('co_docentes')->existe_docente($datos['persona']);
		
		if( ($this->es_nueva()) && ($existe_docente) ) {
			$this->set_error('800MAT_existe_docente');
		} else {
			if (isset($datos['legajo'])) {
				$this->validar_legajo($datos['legajo']);
			}
		}

		if ($this->hay_errores()) {
			throw guarani::error($this->errores);
		}
	}

	function validar_legajo($legajo)
	{
		$docente = $this->tabla('docente')->get_columna('docente');
		if (!toba::consulta_php('co_docentes')->validar_legajo($legajo, $docente)) {
			$this->set_error('dato_existente', array('legajo', 'docente'));
		}
	}

	//--------------------------------------------------------------------------
	/**
	 *  Determina si el docente no esta asociado a alguna cátedra.
	 * 
	 * 	@param integer docente	ID del docente a validar
	 */
	function validar_asociacion_catedra()
	{
		$docente = $this->tabla('docente')->get_columna('docente');
		if (toba::consulta_php('co_docentes')->tiene_catedras_asociadas($docente)) {
			$this->set_error('800MAT_docente_catedra');
		}
	}

	/**
	 *  Determina si el docente no esta asociado a algún tribunal de examen.
	 * 
	 * 	@param integer docente	ID del docente a validar
	 */
	function validar_asociacion_tribunal_examen()
	{
		$docente = $this->tabla('docente')->get_columna('docente');
		if (toba::consulta_php('co_docentes')->tiene_tribunales_examen_asociados($docente)) {
			$this->set_error('800MAT_docente_tribunal');
		}
	}

	/**
	 *  Determina si el docente no esta asociado a alguna comisión.
	 * 
	 * 	@param integer docente	ID del docente a validar
	 */
	function validar_asociacion_comision()
	{
		$docente = $this->tabla('docente')->get_columna('docente');
		if (toba::consulta_php('co_docentes')->tiene_comisiones_asociadas($docente)) {
			$this->set_error('800MAT_docente_comision');
		}
	}

	/**
	 *  Determina si el docente no esta asociado a algún llamado de una mesa de examen.
	 * 
	 * 	@param integer docente	ID del docente a validar
	 */
	function validar_asociacion_llamado_mesa_examen()
	{
		$docente = $this->tabla('docente')->get_columna('docente');
		if (toba::consulta_php('co_docentes')->tiene_llamados_mesas_examen_asociados($docente)) {
			$this->set_error('800MAT_docente_mesa');
		}
	}
	/**
	 *  Determina si el docente no esta asociado a algún item de encuestas.
	 * 
	 * 	@param integer docente	ID del docente a validar
	 */
	function validar_asociacion_item()
	{
		$docente = $this->tabla('docente')->get_columna('docente');
		if (toba::consulta_php('co_docentes')->tiene_items_asociados($docente)) {
			$this->set_error('800MAT_docente_item');
		}
	}

        //--------------------------------------------------------------------------

        /**
	 * Verifica si el docente es nuevo (no existe en la DB).
         *
	 *  @return boolean TRUE Si el docente es nuevo, FALSE en caso contrario.
	 */
	function es_docente_nuevo()
	{
            $docente = $this->tabla('docente')->get_columna('docente');
            return is_null($docente);
	}

        /**
         * Crea la cuenta en SIU-Araí para SIU-Guaraní Autogestión
         *
         * @param int $persona ID de persona.
         *
         * @throws toba_error En caso de error
         * @return void.
         */
	function crear_cuenta_arai($persona)
	{
            // Si no vincula usuarios con SIU-Araí salgo
            if (!toba::instalacion()->vincula_arai_usuarios()) {
                return false;
            }
 
            // Solo para docentes que se crean nuevos, no ediciones de ya existentes
            if (!$this->es_docente_nuevo()) {
                return false;
            }
            
            $datos_arai = toba::consulta_php('co_personas')->get_datos_arai_x_persona($persona);

            $usuario = $datos_arai['usuario'];
            // Si no tiene usuario en SIU-Guaraní salgo
            if (empty($usuario)) {
                return false;
            }

            $arai_identificador_sso = $datos_arai['arai_identificador_sso'];
            $arai_uuid = $datos_arai['arai_uuid'];
            $identificador_usuario = arai_usuarios::getDriver()->getIdentificadorUsuarioByVersionApi($arai_identificador_sso, $arai_uuid);
            // Si no tiene asociado usuario en SIU-Araí salgo
            if (empty($identificador_usuario)) {
                return false;
            }

            try {
                arai_usuarios::getDriver()->eliminarCuentas($identificador_usuario, comunes::interfaz_autogestion);
                arai_usuarios::getDriver()->crearCuentas($identificador_usuario, $usuario, comunes::interfaz_autogestion);
            } catch (\Exception $e) {
                throw new toba_error($e->getMessage());
            }
	}

        /**
         * Elimina la cuenta en SIU-Araí para SIU-Guaraní Autogestión
         *
         * @param int $persona ID de persona.
         *
         * @throws toba_error En caso de error
         * @return void.
         */
	function eliminar_cuenta_arai($persona)
	{
            // Si no vincula usuarios con SIU-Araí salgo
            if (!toba::instalacion()->vincula_arai_usuarios()) {
                return false;
            }

            $datos_arai = toba::consulta_php('co_personas')->get_datos_arai_x_persona($persona);

            $arai_identificador_sso = $datos_arai['arai_identificador_sso'];
            $arai_uuid = $datos_arai['arai_uuid'];
            $identificador_usuario = arai_usuarios::getDriver()->getIdentificadorUsuarioByVersionApi($arai_identificador_sso, $arai_uuid);
            // Si no tiene asociado usuario en SIU-Araí salgo
            if (empty($identificador_usuario)) {
                return false;
            }

            try {
                arai_usuarios::getDriver()->eliminarCuentas($identificador_usuario, comunes::interfaz_autogestion);
            } catch (\Exception $e) {
                $this->set_error($e->getMessage());
            }
        }
}