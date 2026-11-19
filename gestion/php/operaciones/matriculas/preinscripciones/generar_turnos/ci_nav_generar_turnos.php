<?php
class ci_nav_generar_turnos extends ci_navegacion
{
	function es_edicion()
	{
		return !empty($this->s__seleccion);
	}
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		if ($this->pantalla('edicion')->existe_evento('eliminar') && ($this->cn()->entidad()->hay_turnos_asignados())) {
			$pantalla->evento('eliminar')->desactivar();
			$pantalla->evento('eliminar')->set_msg_ayuda('No se puede eliminar porque ya hay turnos asignados');
		}
		if ($this->es_edicion()) {
			$pantalla->evento('guardar')->desactivar();
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__filtro__filtrar($datos)
	{
		if (!empty($datos['responsables_academicas'])) {
			$ra = $datos['responsables_academicas']['valor'];
			$this->dep('filtro')->columna('responsables_academicas')->resetear_estado();
		}
		if (!empty($datos['ubicaciones'])) {
			$ubicaciones = $datos['ubicaciones']['valor'];
			$this->dep('filtro')->columna('ubicaciones')->resetear_estado();
		}
		if (!empty($datos['modalidad'])) {
			$modalidad = $datos['modalidad']['valor'];
			$this->dep('filtro')->columna('modalidad')->resetear_estado();
		}
		parent::evt__filtro__filtrar($datos);
		if (!empty($ra)) {
			$ra = toba::db()->quote($ra);
			$this->s__filtro .= " AND EXISTS (
								SELECT 	1
								FROM    pre_turnos_config_ra
								WHERE  	pre_turnos_config_ra.responsable_academica = $ra
										AND pre_turnos_config_ra.turno_config = pre_turnos_config.turno_config
							)";
		}
		if (!empty($ubicaciones)) {
			$ubicaciones = toba::db()->quote($ubicaciones);
			$this->s__filtro .= " AND EXISTS (
								SELECT 	1
								FROM    pre_turnos_config_ubicacion
								WHERE  	pre_turnos_config_ubicacion.ubicacion = $ubicaciones
										AND pre_turnos_config_ubicacion.turno_config = pre_turnos_config.turno_config
							)";
		}
		if (!empty($modalidad)) {
			$modalidad = toba::db()->quote($modalidad);
			$this->s__filtro .= " AND EXISTS (
								SELECT 	1
								FROM    pre_turnos_config_modalidad
								WHERE  	pre_turnos_config_modalidad.modalidad = $modalidad
										AND pre_turnos_config_modalidad.turno_config = pre_turnos_config.turno_config
							)";
		}
	}
	
	function conf__filtro(toba_ei_filtro $filtro)
	{
		// Si no hay un filtrado explícito, por defecto se muestran los períodos
		// de turnos no finalizados.
		if (empty($this->s__datos)) {
			$datos = array(
				'fecha_fin' => array(
					'condicion' => 'desde',
					'valor' => guarani_fecha::get_hoy()
				)
			);
			$this->evt__filtro__filtrar($datos);
		} 
		parent::conf__filtro($filtro);
		$this->dep('filtro')->columna('responsables_academicas')->set_condicion_fija('es_igual_a');
	}

}
?>