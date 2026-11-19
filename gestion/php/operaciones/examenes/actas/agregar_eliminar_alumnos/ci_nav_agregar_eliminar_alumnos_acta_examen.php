<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_agregar_eliminar_alumnos_acta_examen extends ci_nav_actas_zona
{
	protected $hay_cambios_seleccion_alumnos;

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

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		parent::conf();
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse actas abiertas.');
		}
	}

	/**
	 * Se redefine la configuración de la pantalla de edición, para eliminar una columna a mostrar del ev de las pant. de selección.
	 */
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if (guarani::parametros()->get_valor('exa_multiples_actas_examen') == comunes::no) {
			$pantalla->set_descripcion('No se pueden eliminar alumnos del acta ya que existe una única por mesa de examen.');
			$pantalla->set_titulo('Selección de Alumnos');
		}

		parent::conf__edicion($pantalla);
		$this->ev_info()->ocultar_columnas(array('libro_nombre'));
		$this->hay_cambios_seleccion_alumnos = $this->cn()->hay_cambios_seleccion_alumnos();
	}

	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		// Datos elemento visual
		$this->ev_info_resumen()->set_datos($this->get_datos_ev());

		// Se modifica la etiqueta e imagen del botón
		$this->evento('cancelar')->set_etiqueta('Ir al inicio');
		$this->evento('cancelar')->set_imagen('/home.png');
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf_evt__cuadro_actas__seleccion_acta(toba_evento_usuario $evento, $fila)
	{
		// Si el acta no está Abierta no se permite seleccionarla para agregar o eliminar alumnos de la misma.
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

	//-----------------------------------------------------------------------------------
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	//Función de configuración para el ev de la pantalla de Información.
	function ev_info_resumen()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'llamado_nombre', 'fecha_formato_visual', 'ubicacion_nombre', 'cod_nombre_actividad'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'turno_nombre', 'mesa_nombre'), 1);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('nro_acta', 'Acta Nro.');
			$this->ev_info->set_etiqueta('libro_nombre', 'Libro');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_llamado_mesa()
	{
		return $this->s__datos_mesa_llamados;
	}

	function get_datos_acta_seleccionada()
	{
		return $this->s__datos_acta;
	}

	protected function get_datos_evaluacion_mesa()
	{
		$where = "	sga_evaluaciones.evaluacion 	  = '" . $this->get_evaluacion() . "' AND
					sga_evaluaciones_tipos.automatica = '" . comunes::si . "' AND
					sga_evaluaciones_tipos.aplica_a   = '" . acta::evaluacion_tipo_aplica_mesa . "'";
		return current(toba::consulta_php('co_evaluaciones')->get_listado($where));
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		//Se modifican los datos del detalle de exámenes del acta.
		$this->cn()->guardar_acta_modificada($this->s__datos_acta);
		$this->cn()->resetear();
		//En la última pantalla se muestra el resumen de los datos del acta modificada.
		$this->set_pantalla('informacion');
	}

	//-----------------------------------------------------------------------------------
	//---- JavaScript -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios = guarani_mensajes::get('800_no_hubo_cambios');

		if ($this->hay_cambios_seleccion_alumnos) {
			$confirmar = 'var hay_cambios_alumnos = true;';
		} else {
			$confirmar = 'var hay_cambios_alumnos = false;';
		}

		echo "
			{$this->objeto_js}.evt__cancelar = function() {
				$confirmar
				if (hay_cambios_alumnos) {
					resp = confirm('$msj_confirmacion');
					if (! resp) {
						return false;
					}
				}
				return true;
			}

			{$this->objeto_js}.evt__guardar = function() {
				$confirmar
				if (! hay_cambios_alumnos) {
					alert('$msj_sin_cambios');
					return false;
				}
				return true;
			}

            toba.set_callback_menu({$this->objeto_js}.evt__cancelar);
		";
	}
}
?>