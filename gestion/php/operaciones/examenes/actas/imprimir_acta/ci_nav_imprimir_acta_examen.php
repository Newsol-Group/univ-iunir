<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_imprimir_acta_examen extends ci_nav_actas_zona
{
	// Variables para manejar el llamado desde otra operación.
	protected $s__operacion_a_volver;
	protected $s__celda_a_restaurar;

	function ini()
	{
		toba::memoria()->desactivar_reciclado();
		parent::ini();

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
			$this->seleccionar_acta($seleccion);
		}

		// Si viene de la operación "Modificar Mesas de Examen", se setean los filtros.
		$anio_academico = toba::memoria()->get_parametro('anio_academico');
		if (isset($anio_academico)) {
			$this->dependencia('filtro')->columna('anio_academico')->set_estado(array('valor' => $anio_academico));
			$this->s__datos = array('anio_academico' => array('condicion' => 'es_igual_a', 'valor' => $anio_academico));
			$this->s__filtro = "vw_mesas_examen.anio_academico = $anio_academico";

			$turno_examen = toba::memoria()->get_parametro('turno_examen');
			if (!empty($turno_examen)) {  // Acta fuera de calendario
				$this->dependencia('filtro')->columna('turno_examen')->set_estado(array('valor' => $turno_examen));
				$this->s__datos['turno_examen'] = array('condicion' => 'es_igual_a', 'valor' => $turno_examen);
				$this->s__filtro .= " AND vw_mesas_examen.turno_examen = $turno_examen";
			}

			$actividad = toba::memoria()->get_parametro('actividad');
			$this->dependencia('filtro')->columna('codigo')->set_estado(array('valor' => $actividad));
			$this->s__datos['codigo'] = array('condicion' => 'contiene', 'valor' => $actividad, 'condicion_cs' => 0, 'condicion_as' => 0);
			$filtro_condicion = new toba_filtro_condicion('contiene', 'ILIKE', '%', '%', '', '');
			$this->s__filtro .= ' AND '.$filtro_condicion->get_sql('f_limpiar_acentos((sga_elementos.codigo || sga_elementos.nombre)::varchar)', guarani_texto::limpiar_acentos($actividad));

			$mesa_examen = toba::memoria()->get_parametro('mesa_examen');
			$this->dependencia('filtro')->columna('nombre')->set_estado(array('valor' => $mesa_examen));
			$this->s__datos['nombre'] = array('condicion' => 'es_igual_a', 'valor' => $mesa_examen, 'condicion_cs' => 0, 'condicion_as' => 0);
			$this->s__filtro .= ' AND '.$filtro_condicion->get_sql('f_limpiar_acentos(vw_mesas_examen.mesa_examen_nombre::varchar)', guarani_texto::limpiar_acentos($mesa_examen));

			$fecha = toba::memoria()->get_parametro('fecha');
			$this->dependencia('filtro')->columna('fecha')->set_estado(array('condicion' => 'es_igual_a', 'valor' => $fecha));
			$this->s__datos['fecha'] = array('condicion' => 'es_igual_a', 'valor' => $fecha);
			$this->s__filtro .= " AND vw_mesas_examen.mesa_examen_fecha = '$fecha'";
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
			$this->setear_datos_mesa(array('entidad' => $this->s__llamado_mesa_entidad));
			$this->seleccionar_acta(array('id_acta' => $acta));
		}
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
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro_actas__seleccion_acta($seleccion)
	{
		toba::zona()->cargar($seleccion['id_acta']);
		parent::evt__cuadro_actas__seleccion_acta($seleccion);
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$this->ev_info()->ocultar_columnas(array('libro_nombre'));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cancelar()
	{
		if (!empty($this->s__operacion_a_volver) && !empty($this->s__celda_a_restaurar) && ($this->s__operacion_a_volver != '5000121') && ($this->s__operacion_a_volver != '38000088')) {
			echo toba_js::abrir();
			echo "document.location.href='" .
			toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), $this->s__operacion_a_volver, array('volvio_imp' => true), array('celda_memoria' => $this->s__celda_a_restaurar)) . "'\n";
			echo toba_js::cerrar();
		} else {
			parent::evt__cancelar();
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_controlador($this);
			$this->ev_info->set_columnas_no_imprimible(array('rectifica_a'));
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'llamado_nombre', 'libro_nombre', 'fecha_formato_visual', 'cod_nombre_actividad', 'icono_info_escalas_notas', 'ubicacion_nombre', 'rectifica_a', 'tipo_acta_descr', 'estado_descr', 'version'));
			$this->ev_info->set_max_columnas_por_fila(4);
			$this->ev_info->set_columnas_fila(array('nro_acta', 'anio_academico', 'turno_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('mesa_nombre', 'llamado_nombre', 'fecha_formato_visual'), 2);
			$this->ev_info->set_columnas_fila(array('cod_nombre_actividad', 'tipo_acta_descr', 'estado_descr', 'icono_info_escalas_notas'), 3);
			$this->ev_info->set_columnas_fila(array('ubicacion_nombre', 'version', 'rectifica_a'), 4);
			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('tipo_acta_descr', 'Tipo');
			$this->ev_info->set_etiqueta('estado_descr', 'Estado');
			$this->ev_info->set_etiqueta('rectifica_a', 'Rectifica a');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('libro_nombre', 'Libro');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('icono_info_escalas_notas', 'Escalas de Notas');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('version', 'Código de Verificación');

			$this->ev_info->set_td_etiqueta_width('100px');
			$this->ev_info->set_td_valores_width('200px');
		}
		return $this->ev_info;
	}

}
?>
