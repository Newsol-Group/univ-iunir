<?php
/**
 *
 * @package Exámenes
 * @subpackage Actas
 */
class ci_nav_generar_acta_examen extends ci_nav_mesas_zona
{
	protected $s__acta;
	protected $s__nro_acta;
	protected $hay_cambios_seleccion_alumnos;
	
	function conf__seleccion_mesa(toba_ei_pantalla $pantalla)
	{
		$this->cn()->resetear_atributos_parametros();
		$pantalla->set_descripcion('En caso que no se permita generar más de un acta para la mesa (parámetro <strong>exa_multiples_actas_examen</strong> establecido en <strong>No</strong>), sólo pueden seleccionarse mesas sin actas generadas.');
	}
	
	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		if (guarani::parametros()->get_valor('exa_multiples_actas_examen') == comunes::no) {
			$pantalla->set_descripcion('No se pueden eliminar los alumnos de la mesa ya que sólo existe una única acta');
			$pantalla->set_titulo('Selección de Alumnos');
		}
		
		// Se invoca al editor porque si el parametro de multiples actas está establecido en N 
		// se deben cargar a todos los alumnos en el ini del editor.
		$this->dep('editor');
		parent::conf__edicion($pantalla);
		$this->hay_cambios_seleccion_alumnos = $this->cn()->hay_cambios_seleccion_alumnos();
	}

	function get_acta_generada()
	{
		return $this->s__acta;
	}

	function evt__guardar()
	{
		// Se obtienen los datos del acta.
		$nro_acta = $this->dep('editor')->get_nro_acta();
		$observaciones = $this->dep('editor')->get_observaciones();

		// Se crea el acta.
		$this->s__nro_acta = $nro_acta;
		$this->s__acta = $this->cn()->generar_acta($nro_acta, $observaciones);

		// Se resetea el cn.
		$this->cn()->resetear();

		// Se muestra el resumen de los datos del nuevo acta.
		$this->set_pantalla('informacion');
	}

	/**
	 * Se redefine la función resetear de ci_nav_mesas para resetear la variables 
	 * propias de navegación.
	 */
	function resetear_variables()
	{
		unset($this->s__acta);
		unset($this->s__nro_acta);
		parent::resetear_variables();
	}

	function conf_evt__cuadro__seleccion(toba_evento_usuario $evento, $fila)
	{
		// Si ya hay un acta generada para la mesa y el parámetro exa_multiples_actas_examen = N, no se deberá mostrar este botón.
		$datos = $this->dep('cuadro')->get_datos();
		
		// Atributos para resolver atributos
		$mesa_examen = $datos[$fila]['mesa_examen'];
		$atributos = aplanar_matriz(toba::consulta_php('co_mesas_de_examen')->get_propuestas_mesa_examen($mesa_examen), 'propuesta');
		
		if (($datos[$fila]['cant_actas_sin_anular'] > 0) && (guarani::parametros()->get_valor('exa_multiples_actas_examen', $atributos) == comunes::no)) {
			$evento->ocultar();
		} else {
			$evento->mostrar();
		}
	}

	function get_datos_mesa_info()
	{
		$datos = $this->get_datos_ev_mesa();
		$datos['nro_acta'] = $this->s__nro_acta;
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- filtro -----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro(toba_ei_filtro $filtro)
	{
		parent::conf__filtro($filtro);
		$filtro->columna('actividad')->set_condicion_fija('contiene');
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function ev_info_resumen()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			$this->ev_info->set_style('border-style: solid; border-width: 1px; border-color: #DDD;');
			$this->ev_info->set_style_fila('height: 30px;');
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'anio_academico', 'nombre_turno_examen', 'nombre', 'nombre_llamado', 'fecha_formato_visual', 'nombre_ubicacion', 'codigo_actividad'));
			$this->ev_info->set_columnas_fila(array('anio_academico', 'nombre_turno_examen', 'nombre'), 1);
			$this->ev_info->set_max_columnas_por_fila(3);
			$this->ev_info->set_etiqueta('anio_academico', 'Año Académico');
			$this->ev_info->set_etiqueta('nombre_turno_examen', 'Turno');
			$this->ev_info->set_etiqueta('nombre', 'Mesa');
			$this->ev_info->set_etiqueta('nombre_llamado', 'Llamado');
			$this->ev_info->set_etiqueta('fecha_formato_visual', 'Fecha');
			$this->ev_info->set_etiqueta('cod_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
		}
		return $this->ev_info;
	}

	function conf__informacion(toba_ei_pantalla $pantalla)
	{
		//Datos elemento visual
		$datos = $this->get_datos_mesa_info();
		$this->ev_info_resumen()->set_datos($datos);

		//Se modifica la etiqueta e imagen del botón
		$this->evento('cancelar')->set_etiqueta('Ir al inicio');
		$this->evento('cancelar')->set_imagen('/home.png');
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