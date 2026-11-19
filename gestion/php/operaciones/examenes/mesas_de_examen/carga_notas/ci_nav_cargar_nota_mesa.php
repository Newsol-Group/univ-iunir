<?php
class ci_nav_cargar_nota_mesa extends ci_nav_mesas_zona
{
	protected $hay_cambios_seleccion_alumnos;
	protected $hay_cambios_carga_notas;

	function ini()
	{
		if ( toba::zona()->cargada() && ! $this->cn()->entidad_cargada() ) {
			$mesa_entidad = toba::zona()->get_editable();
			$this->setear_datos_mesa(array('entidad' => $mesa_entidad));
			$this->seleccionar_mesa('seleccion_alumnos');
		} else {
			parent::ini();
		}
	}

	function conf__edicion(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);

		$datos = $this->get_datos_ev_mesa();
		$datos['icono_info_escalas_notas'] = $this->get_html_icono_info_escalas_notas();
		$this->ev_info()->set_datos($datos);
		$this->ev_info()->set_columnas_no_imprimible('icono_info_escalas_notas');
		$this->hay_cambios_carga_notas = $this->cn()->hay_cambios_carga_notas();
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__cuadro__seleccion($seleccion)
	{
		$llamado_mesa_entidad = $seleccion['entidad'];
		$cantidad_evaluaciones = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_llamado_mesa_entidad($llamado_mesa_entidad);
		
		if ($cantidad_evaluaciones == 0) {
			
			//Mensaje de error por no tener evaluaciones
			$mje = guarani_mensajes::get('800_sin_evaluacion');
			throw guarani::error($mje);
		}
		
		//Setea mesa y datos de la mesa
		toba::zona()->cargar($llamado_mesa_entidad);
		$this->setear_datos_mesa($seleccion);
		$this->seleccionar_mesa('seleccion_alumnos');
	}

	function evt__cargar_nota()
	{
		$this->set_pantalla('edicion');
	}

	function conf__seleccion_alumnos(toba_ei_pantalla $pantalla)
	{
		parent::conf__edicion($pantalla);
		$this->hay_cambios_seleccion_alumnos = $this->cn()->hay_cambios_seleccion_alumnos();
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
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	// Se redefinen los eventos de volver porque manejan diferentes pantallas.
	
	// Cancela desde la pantalla de selección de alumnos, para volver a la selección de mesas.
	function evt__cancelar_preseleccion()
	{
		$this->dependencia('selector_alumnos')->resetear_variables();
		$this->resetear_variables();
		$this->cn()->resetear();
		toba::zona()->resetear();
		$this->set_pantalla('seleccion_mesa');
	}
	
	/**
	 * Cancela desde la pantalla de edición de notas, volviendo a la selección de alumnos.
	 */
	function evt__cancelar()
	{
		$this->dependencia('editor')->resetear_variables();
		$this->set_pantalla('seleccion_alumnos');
	}
	
	//No se redefine el evt guardar porque utiliza el definido en el ci de navegación genérico.

	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function extender_objeto_js()
	{
		$msj_confirmacion			= guarani_mensajes::get('800_abandono_edicion');
		$msj_sin_cambios			= guarani_mensajes::get('800_no_hubo_cambios');
		$msj_sin_seleccion_alumnos	= guarani_mensajes::get('800_sin_seleccion_alumnos');

		$pantalla =$this->get_id_pantalla();
		switch ($pantalla){
			case 'seleccion_alumnos':
				if ($this->hay_cambios_seleccion_alumnos) {
					$confirmar = 'var confirmar = true;';
				} else {
					$confirmar = 'var confirmar = false;';
				}
				echo "	{$this->objeto_js}.evt__cargar_nota = function() {
							$confirmar
							if (! confirmar) {
								alert('$msj_sin_seleccion_alumnos');
								return false;
							}
							return true;
						}
					";

			break;
			case 'edicion' :
				if ($this->hay_cambios_carga_notas) {
					$confirmar = 'var confirmar = true;';
				} else {
					$confirmar = 'var confirmar = toba.hay_cambios();';
				}
				echo "	{$this->objeto_js}.evt__guardar = function() {
							$confirmar
							if (! confirmar) {
								alert('$msj_sin_cambios');
								return false;
							}
							return true;
						}
						{$this->objeto_js}.evt__cancelar = function() {
							$confirmar
							if (confirmar) {
								resp = confirm('$msj_confirmacion');
								if (! resp) {
									return false;
								}
							}
							return true;
						}
						toba.set_callback_menu({$this->objeto_js}.evt__cancelar);
					";
			break;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de escalas de notas por instancia para una mesa de examen.
	 */
	function get_html_icono_info_escalas_notas()
	{
		$vinculo = toba::vinculador()->crear_vinculo(toba_proyecto::get_id(), '38000089', array('mesa_examen'=>$this->get_mesa_examen()), array('celda_memoria'=>'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'630','scrollbars':'yes'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalles de escalas');
		$salida .= '</a>';

		return $salida;
	}
}
?>