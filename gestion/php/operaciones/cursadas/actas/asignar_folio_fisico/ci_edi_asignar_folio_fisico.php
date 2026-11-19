<?php
/**
 *
 *
 * @package 	Cursadas
 * @subpackage 	Actas
 * 
 */
class ci_edi_asignar_folio_fisico extends guarani_pers_ci
{
	protected $s__libro_tomo;
	protected $s__primer_ff;
	protected $s__folios;
	
	protected $ev_info;
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_inicial(toba_ei_pantalla $pantalla)
	{
		$datos = $this->controlador()->get_datos_acta();
		$datos['icono_info_escala_notas'] = $this->controlador()->get_html_icono_info_escala_notas();
		$this->ev_info()->set_datos($datos);

		$es_acta_regulares = $this->controlador()->es_acta_regulares();
		$utiliza_acta_digital = ($datos['digital'] == comunes::si);
		if (!$utiliza_acta_digital) {
			$forma_asignacion_folio_fisico = $es_acta_regulares ? guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_regul') : guarani::parametros()->get_valor('cur_forma_asignacion_folio_fisico_promo');
		}

		if ($utiliza_acta_digital || $forma_asignacion_folio_fisico != acta::forma_asignacion_folio_fisico_manual) {
			$mje = $utiliza_acta_digital ? guarani_mensajes::get('800_error_folio_fisico_firma_digital') : guarani_mensajes::get('800_error_folio_fisico_automatico');
			$pantalla->agregar_notificacion($mje, 'error');
			$this->dep('form_libro')->set_solo_lectura();
                        $this->dep('form_libro')->desactivar_efs(['msj_posee_primer_ff']);
			$this->dep('form_ml_folios')->set_solo_lectura();
			$this->controlador()->pantalla('edicion')->evento('guardar')->desactivar();
                } else {

                    // En caso de que se seleccione un acta que ya tiene los folios asignados
                    if (!is_null($datos['folio_fisico'])) {

                        $this->dep('form_libro')->ef('libro')->set_estado($datos['libro_tomo']);
                        $this->dep('form_libro')->set_solo_lectura();

                        $this->dep('form_libro')->desactivar_efs(['primer_ff']);

			$this->dep('form_ml_folios')->set_datos([
                            [
                                'id_acta' => $datos['id_acta'],
                                'libro' => $datos['libro_tomo'],
                                'folio' => $datos['folio'],
                                'folio_fisico' => $datos['folio_fisico']
                            ]
                        ]);
			$this->dep('form_ml_folios')->set_solo_lectura();
			$this->controlador()->pantalla('edicion')->evento('guardar')->desactivar();

                    } else { // En caso de que se seleccione un acta que no tiene los folios asignados

                        $this->dep('form_libro')->desactivar_efs(['msj_posee_primer_ff']);

                    }
                }
	}
	
	function ev_info()
	{
		if (!isset($this->ev_info)) {
			$this->ev_info = guarani::ev_info_datos();
			
			$this->ev_info->set_columnas_a_mostrar(array('nro_acta', 'libro_nombre', 'anio_academico_periodo', 'periodo_nombre', 'comision_nombre', 'modalidad_nombre', 'codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas', 'nombre_turno'));
			
			$this->ev_info->set_columnas_fila(array('nro_acta', 'comision_nombre', 'libro_nombre'), 1);
			$this->ev_info->set_columnas_fila(array('anio_academico_periodo', 'periodo_nombre', 'modalidad_nombre'), 2);
			$this->ev_info->set_columnas_fila(array('codigo_y_nombre_actividad', 'nombre_ubicacion', 'icono_info_escala_notas'), 3);
			$this->ev_info->set_columnas_fila(array('nombre_turno'), 4);
			
			$this->ev_info->set_max_columnas_por_fila(3);

			$this->ev_info->set_etiqueta('nro_acta', 'Acta');
			$this->ev_info->set_etiqueta('comision_nombre', 'Comisión');			
			$this->ev_info->set_etiqueta('libro_nombre', 'Libro');
			
			$this->ev_info->set_etiqueta('anio_academico_periodo', 'Año Académico');
			$this->ev_info->set_etiqueta('periodo_nombre', 'P. Lectivo');
			$this->ev_info->set_etiqueta('modalidad_nombre', 'Modalidad');
			
			$this->ev_info->set_etiqueta('codigo_y_nombre_actividad', 'Actividad');
			$this->ev_info->set_etiqueta('nombre_ubicacion', 'Ubicación');
			$this->ev_info->set_etiqueta('icono_info_escala_notas', 'Escala de Notas');
			
			$this->ev_info->set_etiqueta('nombre_turno', 'Turno');
		}
		return $this->ev_info;
	}
	
	//-----------------------------------------------------------------------------------
	//---- operaciones auxiliares -------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function get_libros_actas()
	{
		$datos_acta = $this->controlador()->get_datos_acta();
		return toba::consulta_php('co_actas')->get_libros_tomos_actas($datos_acta);
	}
	
	function resetear_variables()
	{
		unset($this->s__libro_tomo);
		unset($this->s__primer_ff);
		unset($this->s__folios);
	}	
	
	function get_libro_tomo()
	{
		return $this->s__libro_tomo;
	}
	
	function get_folios()
	{
		return $this->s__folios;
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_libro -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_libro($form)
	{
		$form->ef('primer_ff')->set_expreg(comunes::er_numero_entero);

		$datos = array();
		if (isset($this->s__libro_tomo)) {
			$datos['libro'] = $this->s__libro_tomo;
		}
		if (isset($this->s__primer_ff)) {
			$datos['primer_ff'] = $this->s__primer_ff;
		}
		$form->set_datos($datos);

		$form->ef('primer_ff')->set_estilo_etiqueta('ei-form-etiq-oblig');  
		$etiqueta = $form->ef('primer_ff')->get_etiqueta() . ' (*)';
		$form->ef('primer_ff')->set_etiqueta($etiqueta);
	}
	
	function evt__form_libro__modificacion($datos)
	{
		$this->s__libro_tomo = $datos['libro'];
		$this->s__primer_ff = $datos['primer_ff'];
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_folios ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_folios(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->s__folios)) {
			$form_ml->set_datos($this->s__folios);
		} else {
			$id_acta = $this->controlador()->get_acta_seleccionada();
			$datos = guarani::co('co_actas')->get_folios_acta($id_acta);
			$form_ml->set_datos($datos);
		}
	}

	function evt__form_ml_folios__modificacion($datos)
	{
		$this->s__folios = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- AJAX -------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * El usuario selecciona un BH ya existente para asociar a la clase, por lo cual se recuperan los datos de 
	 * la misma para setearlos por defecto.
	 */
	function ajax__siguiente_ff($params, toba_ajax_respuesta $respuesta) 
	{
		$libro = $params['libro'];
		
		// Se obtiene el último folio físico asignado del libro (o cero si es un libro vacío)
		$folio_fisico = guarani::co('co_actas')->get_ultimo_folio_fisico($libro);
		
		$respuesta->set(array('siguiente_ff' => $folio_fisico + 1));
	}
}