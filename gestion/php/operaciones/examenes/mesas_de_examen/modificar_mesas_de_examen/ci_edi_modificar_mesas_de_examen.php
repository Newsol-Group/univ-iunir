<?php
/**
 *
 *
 * @package Examenes
 * @subpackage Mesas de Examen
 */
class ci_edi_modificar_mesas_de_examen extends ci_edi_llamado_mesa
{
	protected $s__datos_llamado_mesa;
	protected $s__permite_instancia_libre = true;
	
	protected $s__seleccion_instancias;
    protected $s__descripcion = '';
            
	function ini()
	{
		$this->indx_msg_eof = '800_eof_cuadro';
		
		$this->dep('form_ml_datos_seleccion_instancias')->set_columna_ordenamiento('instancia_nombre');
		
		$datos = $this->cn()->entidad()->get_datos_llamado();        
		$this->s__datos_llamado_mesa = toba::consulta_php('co_mesas_de_examen')->get_datos_llamado_mesa($datos['llamado_mesa']);        
        $actas = $this->cn()->get_actas($datos['llamado_mesa']);
        if (!empty($actas)) {
            $this->s__descripcion = guarani_mensajes::get('800EXA_modificar_llamado_mesa');
        }
		$mesa = $this->s__datos_llamado_mesa['mesa_examen'];
		
		//Recupera las propuestas asociadas para controlar si para alguna de las propuestas, la actividad permite rendirse libre
		//Si no permite rendirse libre, se elimina de la lista de instancias posibles.
		$filtro_propuestas = " ROW (vw_elementos_plan.propuesta, vw_elementos_plan.plan) IN 
										(SELECT sga_mesas_examen_propuestas.propuesta,
												sga_mesas_examen_propuestas.plan
										 FROM	sga_mesas_examen_propuestas
										 WHERE	sga_mesas_examen_propuestas.mesa_examen = $mesa)";
		$this->s__permite_instancia_libre = toba::consulta_php('co_mesas_de_examen')->get_hay_propuestas_vinculadas_instancia_libre($this->s__datos_llamado_mesa['actividad'], $filtro_propuestas);
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS PRINCIPALES ---------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_datos_principales(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_descripcion($this->s__descripcion, 'warning');
	}

	//---- datos_principales ------------------------------------------------------------

	function conf__datos_principales(guarani_pers_ei_formulario $form)
	{
		if (isset($this->datos_principales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_principales_temp);
		} else {
			$datos = $this->cn()->entidad()->get_datos_llamado();
			$datos['actividad_descripcion'] = $this->s__datos_llamado_mesa['actividad_descripcion'];
			$datos['anio_academico'] = $this->s__datos_llamado_mesa['anio_academico'];
			$datos['turno_examen_nombre'] = $this->s__datos_llamado_mesa['turno_examen_nombre'];
			$datos['mesa_examen_nombre'] = $this->s__datos_llamado_mesa['mesa_examen_nombre'];
			$datos['ubicacion_nombre'] = $this->s__datos_llamado_mesa['ubicacion_nombre'];
			$form->set_datos($datos);
		}
	}

	function evt__datos_principales__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_llamado($datos);
		} catch (guarani_error $e) {
			$this->datos_principales_temp = $datos;
			throw $e;
		}
	}

	//---- cuadro_actas -----------------------------------------------------------------

	function conf__cuadro_actas(guarani_pers_ei_cuadro $cuadro)
	{
		$llamado_mesa = toba::db()->quote($this->controlador()->get_llamado_mesa());
        $datos = $this->cn()->get_actas($llamado_mesa);		
		foreach ($datos as $clave => $valor) {
			if ($datos[$clave]['estado'] == acta::estado_abierta) {
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/verde.png', true, null, null, 'Acta Abierta', null);
			} elseif ($datos[$clave]['estado'] == acta::estado_cerrada){
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/rojo.png', true, null, null, 'Acta Cerrada', null);
			} elseif ($datos[$clave]['estado'] == acta::estado_anulada){
				$datos[$clave]['estado_semaforo'] = toba_recurso::imagen_proyecto('iconos_estados/azul.png', true, null, null, 'Acta Anulada', null);
			}
		}
		return $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCENTES ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_docentes(toba_ei_pantalla $pantalla)
	{        
		$pantalla->set_descripcion($this->s__descripcion, 'warning');
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EVALUACIONES --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__pant_evaluaciones(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_descripcion($this->s__descripcion, 'warning');
		if ($this->cn()->entidad()->hay_evaluacion_seleccionada()) {
			$this->controlador()->evento('guardar')->desactivar();
			$this->controlador()->evento('cancelar')->desactivar();
		}
	}

	function conf__datos_evaluacion(toba_ei_formulario $form)
	{
		parent::conf__datos_evaluacion($form);
		// Si hay alumnos que tienen notas cargadas no es posible eliminar la evaluación ni editar la escala de nota y las instancias.
		if ($this->cn()->entidad()->hay_evaluacion_seleccionada()) {
			$datos_evaluacion = $this->cn()->entidad()->get_datos_evaluacion_seleccionada();
			if (isset($datos_evaluacion['evaluacion']) && toba::consulta_php('co_evaluaciones')->tiene_notas_cargadas($datos_evaluacion['evaluacion'])) {
				$form->evento('eliminar')->desactivar();
				$form->set_solo_lectura(array('escala_nota', 'instancias'));
			}
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS INSTANCIAS ----------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_instancias(toba_ei_pantalla $pantalla)
	{
		$pantalla->set_descripcion($this->s__descripcion, 'warning');
	}
	
	//-----------------------------------------------------------------------------------
	//---- Instancias asociadas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_instancias_asociadas(guarani_ei_cuadro $cuadro)
	{
		$datos_llamado = $this->cn()->entidad()->get_datos_llamado();
		$datos = toba::consulta_php('co_mesas_de_examen')->get_instancias_mesa_examen($datos_llamado['mesa_examen']);
		foreach($datos as $clave => $instancia) {
			$datos[$clave]['info_escala_notas'] = $this->get_html_icono_info_escala_notas($instancia['escala_nota']);
		}
		$cuadro->set_datos($datos);
	}

	/**
	 * Retorna el HTML de un vínculo (con imagen) a la operación que muestra el detalle de una escala de notas.
	 */
	function get_html_icono_info_escala_notas($escala)
	{
		$vinculo = toba::vinculador()->get_url(toba_proyecto::get_id(), '37000054', array('escala'=>$escala), array('celda_memoria'=>'escala_notas'));
		$javascript = "abrir_popup('info','$vinculo',{'width':'450','height':'420'})";

		$salida = "<a class='icono-utileria' href='#' onclick=\"$javascript\">";
		$salida .= toba_recurso::imagen_toba('info_chico.gif', true, null, null, 'Ver detalle de escala');
		$salida .= '</a>';

		return $salida;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Selección instancias ---------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__form_ml_datos_seleccion_instancias(form_ml_requerido $form_ml)
	{
		$datos = array();
		// Datos en memoria de las instancias que se están agregando en esta operación a la Mesa.
		if (isset($this->s__seleccion_instancias)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->s__seleccion_instancias;
		}
		
		// Datos de las instancias ya asociadas a la mesa (del cuadro superior), guardadas en la BD, 
		// no modificables.
		$datos_asociadas = $this->dep('cuadro_instancias_asociadas')->get_datos();
		
		//Si no permite la instancia libre, se agrega a las instancias asociadas para que la consulta que devuelve
        //las instancias aplicables a la mesa no devuelva la libre
        if (!$this->s__permite_instancia_libre){
            $datos_asociadas[] = array('instancia' => mesa_de_examen::inst_exam_libre);
        }
		
		$inst_a_excluir = array_merge($datos, $datos_asociadas);
		
		// Filtro para sacar de los Datos Posibles las Instancias ya seleccionadas en el transcurso de la operación
		// o ya son parte de las intancias de la Mesa en la BD.
		$filtro = empty($inst_a_excluir) ? '' : guarani_sql::armar_condicion_not_in($inst_a_excluir, 'sga_instancias', 'instancia', 'instancia');
		
		$datos_posibles = toba::consulta_php('co_mesas_de_examen')->get_instancias_aplicables_a_mesa($filtro);
		$escala_notas_defecto = $this->get_escala_notas_defecto();
		foreach ($datos_posibles as $key=>$instancia) {
			if (empty($instancia['escala_nota'])) {
				$datos_posibles[$key]['escala_nota'] = $escala_notas_defecto; 
			}
		}
		
		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion); 

		$form_ml->set_datos($datos);
		$form_ml->set_datos_posibles($datos_posibles);
		$form_ml->set_descripcion(guarani_mensajes::get('800EXA_modificar_mesa_instancias'));
	}
	
	function get_escala_notas_defecto()
	{
		$columna = 'escala_nota_examen';
		
		$escalas = toba::consulta_php('co_elementos')->get_escalas_notas_elemento_planes_mesa_examen($this->s__datos_llamado_mesa['actividad'], $this->s__datos_llamado_mesa['mesa_examen'], $columna);
		if (guarani_arreglos::count($escalas) == 1) {
			return $escalas[0][$columna];
		} else {
			$datos_elemento = toba::consulta_php('co_elementos')->get_datos_basicos_elemento($this->s__datos_llamado_mesa['actividad']);
			return $datos_elemento[$columna];
		}
	}
	
	function evt__form_ml_datos_seleccion_instancias__modificacion($datos)
	{
		$this->s__seleccion_instancias = $datos;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	//Cáscara del método descripción del ef popup info_escala, que solo se utiliza para mostrar una imagen.
	function metodo ($in)
	{
		return '';
	}
	
	function resetear_variables()
	{
		unset($this->s__datos_llamado_mesa);
		unset($this->s__permite_instancia_libre);
		unset($this->s__seleccion_instancias);
        unset($this->s__descripcion);
	}
	
	function get_instancias_agregadas()
	{
		return $this->s__seleccion_instancias;
	}
}
?>