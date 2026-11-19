<?php
class cn_ent_generar_acta_examen extends cn_ent_acta_examen
{
	/**
	 * Genera una nueva acta de examen para la mesa, inserta una nueva cabecera 
	 * y asocia a los detalles de evaluaciones correspondientes el id del acta
	 *
	 * @param $nro_acta
	 * @param $observaciones
	 * @return int id_acta
	 */
	function generar_acta($nro_acta, $observaciones)
	{
		$this->validar_nro_acta($nro_acta);
		try {
			$this->desactivar_transacciones_anidadas();
			
			toba::db()->abrir_transaccion();
			$id_acta = $this->generar_cabecera_acta($nro_acta, $observaciones);
			$this->generar_instancias_acta($id_acta);
			$this->asociar_detalles_acta($id_acta);
			$this->sincronizar();
			toba::db()->cerrar_transaccion();
			
			$this->activar_transacciones_anidadas();
			return $id_acta;
		} catch (toba_error $e) {
			toba::db()->abortar_transaccion();
			$this->activar_transacciones_anidadas();
			throw $e;
		}
	}

	/**
	 * Inserta una nueva cabecera de un acta de exámenes
	 *
	 * @param $nro_acta
	 * @param $observaciones
	 * @return int id_acta
	 */
	function generar_cabecera_acta($nro_acta, $observaciones)
	{
		// Datos por defecto para la creación de un acta.
		$evaluacion = $this->get_evaluacion();
		$llamado_mesa = $this->get_llamado_mesa();
		$lineas_folio = guarani::parametros()->get_valor('exa_lineas_folio_acta_examen');
		$digital = guarani::parametros()->get_valor('exa_utiliza_acta_digital');
		$origen = acta::origen_examen;
		$tipo_acta = acta::tipo_normal;
		// Se inserta el nuevo acta.
		return guarani::act('act_actas')->insertar_acta_examen($nro_acta, $origen, $evaluacion, $llamado_mesa, $lineas_folio, $tipo_acta, $observaciones, null, null, $digital);
	}

	/**
	 *  Se asocia al acta las instancias correspondeintes a la mesa de examen
	 */
	function generar_instancias_acta($id_acta)
	{
		$mesa_examen = $this->get_mesa_examen();
		guarani::act('act_actas')->insertar_acta_instancias_de_mesa($id_acta, $mesa_examen);
	}

	/**
	 * Asocia a los alumnos asociados el id del acta recientemente generada
	 * 
	 * @param $id_acta 
	 */
	function asociar_detalles_acta($id_acta)
	{
		$condiciones = array('agregado' => true);
		$ids = $this->get_ids_detalle($condiciones);
		$this->modificar_filas_columna_detalle($ids, 'id_acta', $id_acta);
	}

	function hay_cambios_seleccion_alumnos()
	{
		$datos = $this->get_ids_detalle(array('agregado' => true));
		return !empty($datos);
	}

	/**
	 * Controla que el número de acta no esté repetido dentro de las actas de examen.
	 * 
	 * @param $nro_acta
	 */
	protected function validar_nro_acta($nro_acta)
	{
		if (toba::consulta_php('co_actas')->nro_acta_en_uso($nro_acta, acta::origen_examen)) {
			throw guarani::error('Ya existe un Acta de Examen con el mismo Nro. de Acta.');
		}
	}

}
?>