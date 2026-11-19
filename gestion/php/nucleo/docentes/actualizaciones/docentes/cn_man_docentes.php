<?php
class cn_man_docentes extends cn_manejador_entidad
{
	function get_indice_instancia()
	{
		return 'docente';
	}

	function get_listado($filtro)
	{
		$filtrar_perfil_de_datos = (guarani::parametros()->get_valor('doc_docentes_aplica_perfil') == comunes::si) ? true : false;
		return toba::consulta_php('co_docentes')->get_listado($filtro, $filtrar_perfil_de_datos);
	}

	function guardar()
	{
		$datos = $this->entidad()->get_datos_docente();

		if ($datos['estado'] == comunes::estado_baja) {
			$this->desactivar_transacciones_anidadas();
			try {
				toba::db()->abrir_transaccion();

				// Eliminación de la asignacion del docente en las cátedras y tribunales de examen.
				$this->entidad()->baja_en_catedras_tribunales($datos);

				// Sincronización de las modificaciones.
				$docente = $this->entidad()->sincronizar();

				toba::db()->cerrar_transaccion();
			} catch (toba_error $e) {
				toba::db()->abortar_transaccion();
				$this->activar_transacciones_anidadas();
				throw $e;
			}
			return array($docente);
		}

                // Creación de la cuenta en SIU-Araí para SIU-Guaraní Autogestión
                $this->entidad()->crear_cuenta_arai($datos['persona']);

		return parent::guardar();
	}

	function eliminar($id = null)
	{
		// Validaciones antes de eliminar
		$this->entidad()->validar_asociacion_catedra();
		$this->entidad()->validar_asociacion_tribunal_examen();
		$this->entidad()->validar_asociacion_comision();
		$this->entidad()->validar_asociacion_llamado_mesa_examen();
		$this->entidad()->validar_asociacion_item();

                $datos = $this->entidad()->get_datos_docente();
                // Eliminación de la cuenta en SIU-Araí para SIU-Guaraní Autogestión
                $this->entidad()->eliminar_cuenta_arai($datos['persona']);

		if ($this->entidad()->hay_errores()) {
			throw guarani::error($this->entidad()->get_errores());
		}

		parent::eliminar($id);
	}

}