<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_cargar_notas_acta_examen extends ci_nav_actas_zona
{
	// Variables para manejar el llamado desde otra operación.
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;

	function ini()
	{
		//---- Se carga la zona si corresponde ---------------------------------
		if (toba::zona()->cargada() && !$this->cn()->entidad_cargada()) {
			$id_acta = toba::zona()->get_editable();
			$where = "sga_actas.id_acta = " . toba::db()->quote($id_acta);
			$datos = current($this->cn()->get_listado($where));
			$seleccion = array(
				'id_acta' => $datos['id_acta'],
				'mesa_examen' => $datos['mesa_examen'],
				'llamado_mesa_entidad' => $datos['llamado_mesa_entidad'],
				'evaluacion' => $datos['evaluacion'],
				'llamado_mesa' => $datos['llamado_mesa']
			);
			$this->s__acta_seleccionada = $seleccion;
			$this->seleccionar_mesa('edicion');
		}
	}

	function ini__operacion()
	{
		parent::ini__operacion();
		$acta = toba::memoria()->get_parametro('id_acta');
		if (isset($acta)) {
			// Si viene de generación de actas, se carga la zona de ACTAS en lugar de la de exámenes
			toba::zona()->resetear();
			toba::zona()->cargar($acta);

			$this->s__operacion_a_volver = toba::memoria()->get_parametro('operacion_a_volver');
			$this->s__celda_a_restaurar = toba::memoria()->get_parametro('celda_a_restaurar');
			$this->s__llamado_mesa_entidad = toba::memoria()->get_parametro('llamado_mesa_entidad');
			$datos = toba::consulta_php('co_actas')->get_datos_basicos_acta_examen($acta);
			$this->setear_datos_mesa(array('entidad' => $this->s__llamado_mesa_entidad));
			$this->seleccionar_acta(array('id_acta' => $acta, 'evaluacion' => $datos['evaluacion']));
			$this->seleccionar_mesa('edicion', true);
		}
	}

	function conf()
	{
		parent::conf();
		//Se redefine la descripción del ci ante el cambio de la pantalla de selección de actas.
		//Se setea el aviso de la deshabilitación del evento de selección de Actas en la pantalla correspondiente.
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse Actas Abiertas');
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EDICION -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$this->ev_info()->ocultar_columnas(array('tipo_acta_descr'));
		$this->hay_cambios = $this->cn()->hay_cambios();
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA SELECCION ACTA ------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se define la configuración del evento de selección para el cuadro de actas.
	 */
	function conf_evt__cuadro_actas__seleccion_acta(toba_evento_usuario $evento, $fila)
	{
		// Si el acta no está Abierta no se permite seleccionarla para cargar notas en la misma.
		$datos = $this->dep('cuadro_actas')->get_datos();
		$estado = $datos[$fila]['estado'];
		if ($estado != acta::estado_abierta) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	function evt__cuadro_actas__seleccion_acta($seleccion)
	{
		toba::zona()->cargar($seleccion['id_acta']);
		$this->s__acta_seleccionada = $seleccion;
		$this->seleccionar_mesa('edicion');
	}

	protected function get_datos_evaluacion_mesa()
	{
		$where = "	sga_evaluaciones.evaluacion 	  = '" . $this->get_evaluacion() . "' AND
					sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a   = '" . acta::evaluacion_tipo_aplica_mesa . "'";

		return current(toba::consulta_php('co_evaluaciones')->get_listado($where));
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('codigo')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		//Se modifican los datos del detalle de exámenes del acta.
		$this->cn()->guardar($this->s__datos_acta);
	}

}
?>