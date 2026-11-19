<?php
/**
 *
 * 
 * @package Exámenes
 * @subpackage Actas
 * 
 */
class ci_nav_rectificar_acta extends ci_nav_actas_zona
{
	protected $s__acta_generada;
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
		//Se redefine la etiqueta del ci ante el cambio de la pantalla de selección de mesas, actas y edición a información.
		if ($this->s__pantalla == 'informacion') {
			$this->set_titulo('Información');
		}
		//Se setea el aviso de la deshabilitación del evento de selección de Actas en la pantalla correspondiente.
		if ($this->s__pantalla == 'seleccion_acta') {
			$this->pantalla()->set_descripcion('Sólo pueden seleccionarse Actas Cerradas sin Rectificativas Abiertas');
		}
	}

	/**
	 * Se redefine la configuración de la pantalla de edición, para eliminar una 
	 * columna a mostrar del ev de las pant. de selección.
	 */
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$this->ev_info()->ocultar_columnas(array('icono_info_escalas_notas', 'tipo_acta_descr'));
		$this->hay_cambios_seleccion_alumnos = $this->cn()->hay_cambios_seleccion_alumnos();
		$pantalla->set_titulo('Selección de Alumnos');
	}

	/**
	 * Configuración de la pantalla de información para setear los datos del elemento visual.
	 */
	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		//Datos elemento visual
		$datos = $this->get_datos_acta_generada();
		$this->ev_info_resumen()->set_datos($datos);

		//Se modifica la etiqueta e imagen del botón
		$this->evento('cancelar')->set_etiqueta('Ir al inicio');
		$this->evento('cancelar')->set_imagen('/home.png');
	}
	
	//-----------------------------------------------------------------------------------
	//---- cuadro_actas -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se redefine como se muestra el semáforo de estados para el cuadro de actas, 
	 * según si el acta tiene o no rectificativas abiertas.
	 */
	function formatear_semaforo($datos)
	{
		foreach ($datos as $clave => $valor) {
			if ($datos[$clave]['tiene_rectificativas_abiertas']) {
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/amarillo.png', true, null, null, 'Acta con Rectificativa Abierta', null);
			} else {
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta OK', null);
			}
		}
		return $datos;
	}

	/**
	 * Se define la configuración del evento de selección para el cuadro de actas.
	 */
	function conf_evt__cuadro_actas__seleccion_acta(toba_evento_usuario $evento, $fila)
	{
		// Si el Acta tiene Rectificativas Abiertas no se permite seleccionarla para Rectificar.
		$datos = $this->dep('cuadro_actas')->get_datos();
		if (($datos[$fila]['tiene_rectificativas_abiertas']) || ($datos[$fila]['estado'] == acta::estado_abierta)) {
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
	//---- ev_info ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Función de configuración para el ev de la pantalla de Información.
	 */
	function ev_info_resumen()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 20px;');
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'anio_academico', 'turno_nombre', 'mesa_nombre', 'llamado_nombre', 'fecha_formato_visual', 'ubicacion_nombre', 'cod_nombre_actividad'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'turno_nombre', 'mesa_nombre'), 1);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('turno_nombre', 'Turno');
			$this->ev_info->set_etiqueta('mesa_nombre', 'Mesa');
			$this->ev_info->set_etiqueta('llamado_nombre', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('ubicacion_nombre', 'Ubicación');
			$this->ev_info->set_etiqueta('nro_acta', 'Acta Rectificativa');
		}
		return $this->ev_info;
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_datos_acta_generada()
	{
		$filtro = 'sga_actas.id_acta = ' . $this->get_acta_generada();
		return current(toba::consulta_php('co_actas')->get_listado_actas_examen_con_sin_mesas($filtro));
	}

	function get_acta_generada()
	{
		return $this->s__acta_generada;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__guardar()
	{
		// Se obtienen los datos del acta rectificativa a generar.
		$nro_acta = $this->dependencia('editor')->get_nro_acta();
		$observaciones = $this->dependencia('editor')->get_observaciones();
		$documento = $this->dependencia('editor')->get_documento();

		// Se crea el acta rectificativa.
		$this->s__acta_generada = $this->cn()->generar_rectificativa($nro_acta, $documento, $observaciones);

		// Se recarga la zona (algunas operaciones no son accesibles para actas con rectificativas abiertas).
		toba::zona()->recargar();

		// Se resetea el cn.
		$this->cn()->resetear();

		// Se muestra el resumen de los datos del nuevo acta.
		$this->set_pantalla('informacion');
	}

	function extender_objeto_js()
	{
		$msj_confirmacion = guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_seleccion_alumnos = guarani_mensajes::get('800_sin_seleccion_alumnos');
		if ($this->hay_cambios_seleccion_alumnos) {
			$confirmar = 'var hay_cambios_alumnos = true; var hay_cambios_formulario = toba.hay_cambios();';
		} else {
			$confirmar = 'var hay_cambios_alumnos = false; var hay_cambios_formulario = toba.hay_cambios();';
		}

		echo "
			{$this->objeto_js}.evt__cancelar = function() {
				$confirmar
				if (hay_cambios_alumnos || hay_cambios_formulario) {
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
					alert('$msj_sin_seleccion_alumnos');
					return false;
				}
				return true;
			}

            toba.set_callback_menu({$this->objeto_js}.evt__cancelar);
		";
	}

}
?>