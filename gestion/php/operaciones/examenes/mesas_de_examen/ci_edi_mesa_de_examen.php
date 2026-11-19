<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class ci_edi_mesa_de_examen extends ci_edicion_entidad
{
	//Datos temporales de los Formularios y Cuadros de selección
	protected $datos_generales_temp;
	protected $seleccion_llamados_temp;
	protected $seleccion_propuestas_temp;
	protected $seleccion_modalidades_temp;
	protected $datos_instancias_temp;
	protected $seleccion_instancias_temp;
	
	// Filas agregadas por defecto.
	protected $s__modalidades_defecto_agregadas = false;
	protected $s__llamados_defecto_agregados = false;
	protected $s__propuestas_defecto_agregadas = false;
	
	//Generales
	protected $s__actividad_seleccionada;	//Actividad seleccionada en la pantalla de navegación.
	protected $s__turno_seleccionado;		//Turno de Exámen seleccionado en los filtros de la pantalla inicial.
	protected $s__ubicacion_seleccionada;	//Ubicación seleccionada en los filtros de la pantalla inicial.
	protected $s__permite_instancia_libre = true;
	protected $indx_msg_eof;				//Se recupera el índice del mensaje eof para cuadros del ci padre.

	function ini()
	{
		$this->indx_msg_eof = $this->controlador()->get_indx_msg_eof();
		$this->dep('seleccion_modalidades')->set_columna_ordenamiento('modalidad_nombre');
		$this->dep('seleccion_llamados')->set_columna_ordenamiento('nombre');
		$this->dep('seleccion_propuestas')->set_columna_ordenamiento('nombre_abreviado');
	}

	/**
	 * Se resetean las variables de sesión utilizadas al mantener información, para poder limpiarlas desde el ci padre o 
	 * desde el ci de navegación, ante el cambio de nodo en el árbol, o el evento cancelar edición de un nodo o volver a 
	 * la pantalla de selección.
	 */
	function resetear_vars()
	{
		unset($this->s__actividad_seleccionada);
		unset($this->s__turno_seleccionado);
		unset($this->s__ubicacion_seleccionada);

		$this->s__modalidades_defecto_agregadas = false;
		$this->s__llamados_defecto_agregados = false;
		$this->s__propuestas_defecto_agregadas = false;
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		// Se obtienen los datos del Filtro y la selección del usuario en la pantalla inicial (ci de navegación)
		$this->s__turno_seleccionado = $this->controlador()->get_turno();
		$this->s__ubicacion_seleccionada = $this->controlador()->get_ubicacion();
		$this->s__actividad_seleccionada = $this->controlador()->get_actividad();

		// Recupera las propuestas asociadas para controlar si para alguna de las propuestas, la actividad permite rendirse libre
		// Si no permite rendirse libre, se elimina de la lista de instancias elegidas
		if (isset($this->seleccion_propuestas_temp)) {
			$datos_propuestas = $this->seleccion_propuestas_temp;
		} else {
			$datos_propuestas = $this->cn()->entidad()->get_datos_propuestas();
		}
		$filtro = guarani_sql::armar_condicion_in_dupla($datos_propuestas, 'vw_elementos_plan', 'propuesta', 'propuesta', $datos_propuestas, 'vw_elementos_plan', 'plan', 'plan');
		$nuevo_permite_instancia_libre = toba::consulta_php('co_mesas_de_examen')->get_hay_propuestas_vinculadas_instancia_libre($this->s__actividad_seleccionada['elemento'], $filtro);
		if ($this->s__permite_instancia_libre != $nuevo_permite_instancia_libre) {
			$this->s__permite_instancia_libre = $nuevo_permite_instancia_libre;
			if (!$this->s__permite_instancia_libre) {
				$this->cn()->entidad()->eliminar_instancia_libre_mesa();
			}
		}
	}

	/**
	 * Se extiende la función del padre, ci_edicion_entidad, donde se verifica la editabilidad de la entidad, y en caso
	 * de no ser editable se setean de solo lectura las dependencias y se eliminan los eventos de modificación sobre la 
	 * misma. Se definen los eventos adicionales que en este caso particular también permiten modificar la entidad.
	 */
	function post_configurar()
	{
		parent::post_configurar();
		if (!$this->cn()->entidad()->es_editable()) {
			//Para el cuadro de Instancias a seleccionar para la Mesa y el formulario ml de Instancias asociadas.
			$inhabilitar_eventos['formulario_ml'] = array('eliminar');
			$inhabilitar_eventos['cuadro'] = array('agregar');
			$this->inhabilitar_componentes_eventos($inhabilitar_eventos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA GENERAL 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- datos_generales --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_generales(toba_ei_formulario $form)
	{
		if (isset($this->datos_generales_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_generales_temp);
		} else {
			// Se setea el ef oculto elemento con la Actividad seleccionada en el Filtro inicial (ci de navegación), que se
			// usa como campo maestro para obtener difentes valores.
			$form->ef('elemento')->set_estado($this->s__actividad_seleccionada['elemento']);
			$form->ef('turno_examen')->set_estado($this->s__turno_seleccionado);
			if ($this->cn()->entidad_es_nueva()) {
				// Si el usuario seleccionó una Ubicación en la pantalla inicial se sugiere como valor de Ubicación para la Mesa.
				if (!empty($this->s__ubicacion_seleccionada)) {
					$form->ef('ubicacion')->set_estado($this->s__ubicacion_seleccionada);
				}
			}
			$form->set_datos($this->cn()->entidad()->get_datos_generales());
		}
		if (guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::no) {
			$form->desactivar_efs(array('acepta_planes_personalizados'));
		}
	}

	function evt__datos_generales__modificacion($datos)
	{
		if (guarani::parametros()->get_valor('pro_plan_pers_usa_planes_personalizados') == comunes::no) {
			$datos['acepta_planes_personalizados'] = comunes::no;
		}
		$this->datos_generales_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_modalidades --------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_modalidades(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_modalidades_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_modalidades_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_modalidades();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_modalidad_cursada', 'modalidad', 'modalidad', true);
		$datos_posibles = toba::consulta_php('co_alumnos')->get_modalidades_cursadas_alumnos($filtro);

		$form_ml->set_datos($datos);

		// Por defecto deberán aparecer seleccionadas todas las modalidades.
		if ($this->cn()->entidad_es_nueva() && !$this->s__modalidades_defecto_agregadas) {
			$this->s__modalidades_defecto_agregadas = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	function evt__seleccion_modalidades__modificacion($datos)
	{
		$this->seleccion_modalidades_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_llamados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_llamados(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_llamados_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_llamados_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_llamados();
		}

		$filtro = empty($datos) ? '' : guarani_sql::armar_condicion_in($datos, 'sga_llamados_turno', 'llamado', 'llamado', true);
		$datos_posibles = toba::consulta_php('co_turnos_de_examen')->get_llamados_turno($this->s__turno_seleccionado, $filtro);
		// Para todos los llamados aún no asociados a la Mesa, se setea si estado como no suspendido.
		foreach ($datos_posibles as $clave => $llamado) {
			$datos_posibles[$clave]['estado_descr'] = comunes::no_desc;
		}

		$formateo = new toba_formateo('html');
		foreach ($datos as $clave => $valor) {
			// Formateo de las horas para visualizarlas correctamente en el ef_editable con expresión regular.
			if (!empty($valor['hora_inicio'])) {
				$datos[$clave]['hora_inicio'] = substr($valor['hora_inicio'], 0, 5);
			}
			if (!empty($valor['hora_finalizacion'])) {
				$datos[$clave]['hora_finalizacion'] = substr($valor['hora_finalizacion'], 0, 5);
			}
			// Descripción del estado del llamado.
			if (!empty($valor['estado'])) {
				if ($valor['estado'] == mesa_de_examen::mesa_estado_activo) {
					$datos[$clave]['estado_descr'] = comunes::no_desc;
				} else if ($valor['estado'] == mesa_de_examen::mesa_estado_suspendido) {
					$datos[$clave]['estado_descr'] = comunes::si_desc;
				}
			}
		}

		$form_ml->set_datos($datos);

		// Por defecto deberán aparecer seleccionados todos los llamados.
		if ($this->cn()->entidad_es_nueva() && !$this->s__llamados_defecto_agregados) {
			$this->s__llamados_defecto_agregados = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	/**
	 * Se define la configuración de las filas del form ml de Llamados. Para cada llamado, si el mismo está asociado a la 
	 * Mesa entonces la Fecha de Exámen es obligatoria. Además si se está editando la Mesa, en caso de que el Llamado 
	 * correspondiente a la fila dada esté actualmente asociado a la Mesa y tenga actas o inscriptos para el
	 * mismo, entonces no puede desasociarse de la mesa, por lo cual deshabilitarse el checkbox requerido para dicha fila.
	 *
	 * @param integer $fila clave interna de la fila del form que se está configurando
	 */
	function conf_fila__seleccion_llamados($fila)
	{
		if (!$this->cn()->entidad_es_nueva() && $this->cn()->entidad()->es_editable()) {
			$datos_ml_llamados = $this->dep('seleccion_llamados')->get_datos();
			if (!empty($datos_ml_llamados[$fila]['requerido']) && ($datos_ml_llamados[$fila]['requerido'] == '1')) {

				$llamado = toba::db()->quote($datos_ml_llamados[$fila]['llamado']);
				$datos_mesa = $this->cn()->entidad()->get_datos_generales();
				$mesa_examen = $datos_mesa['mesa_examen'];
				$filtro = "sga_llamados_mesa.llamado = $llamado";

				$cant_actas = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_llamados_mesa($mesa_examen, $filtro);
				$cant_inscriptos = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_llamados_mesa($mesa_examen, $filtro);
				$cant_inscripciones_bajas = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_bajas_para_llamados_mesa($mesa_examen, $filtro);
				$evals_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_llamados_mesa($mesa_examen, $filtro);

				if ($cant_actas > 0) {
					$this->dep('seleccion_llamados')->ef('requerido')->set_solo_lectura(true);
					$this->dep('seleccion_llamados')->ef('fecha')->set_solo_lectura(true);
					$this->dep('seleccion_llamados')->ef('hora_inicio')->set_solo_lectura(true);
					$this->dep('seleccion_llamados')->ef('hora_finalizacion')->set_solo_lectura(true);
				} else {
					$this->dep('seleccion_llamados')->ef('requerido')->set_solo_lectura(false);
					$this->dep('seleccion_llamados')->ef('fecha')->set_solo_lectura(false);
					$this->dep('seleccion_llamados')->ef('hora_inicio')->set_solo_lectura(false);
					$this->dep('seleccion_llamados')->ef('hora_finalizacion')->set_solo_lectura(false);
				}

				if ($cant_inscriptos > 0 || $cant_inscripciones_bajas > 0 || $evals_llamados_mesa > 0) {
					$this->dep('seleccion_llamados')->ef('requerido')->set_solo_lectura(true);
				}
			} else {
				$this->dep('seleccion_llamados')->ef('requerido')->set_solo_lectura(false);
				$this->dep('seleccion_llamados')->ef('fecha')->set_solo_lectura(false);
				$this->dep('seleccion_llamados')->ef('hora_inicio')->set_solo_lectura(false);
				$this->dep('seleccion_llamados')->ef('hora_finalizacion')->set_solo_lectura(false);
			}
		}
	}

	function evt__seleccion_llamados__modificacion($datos)
	{
		$this->seleccion_llamados_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_propuestas ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_propuestas(form_ml_requerido $form_ml)
	{
		if (isset($this->seleccion_propuestas_temp)) {
			$this->controlador->set_hubo_cambios();
			$datos = $this->seleccion_propuestas_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_propuestas();
		}

		// Se filtra por planes estructurados para que en el ML no aparezcan las propuestas vinculadas a planes personalizados
		$filtro = "sga_planes.tipo_plan = '" . planes::tipo_estructurado . "'";
		$filtro .= empty($datos) ? '' : " AND " . guarani_sql::armar_condicion_not_in_dupla($datos, 'sga_propuestas', 'propuesta', 'propuesta', $datos, 'sga_planes', 'plan', 'plan');
		$datos_posibles = toba::consulta_php('co_propuestas')->get_propuestas_por_elemento_para_examen($this->s__actividad_seleccionada['elemento'], $filtro);

		$form_ml->set_datos($datos);

		// Por defecto deberán aparecer seleccionadas todas las propuestas.
		if ($this->cn()->entidad_es_nueva() && !$this->s__propuestas_defecto_agregadas) {
			$this->s__propuestas_defecto_agregadas = true;
			foreach ($datos_posibles as $dato) {
				$dato['requerido'] = 1;
				$form_ml->agregar_registro($dato);
			}
		} else {
			$form_ml->set_datos_posibles($datos_posibles);
		}
	}

	function evt__seleccion_propuestas__modificacion($datos)
	{
		$this->seleccion_propuestas_temp = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DATOS INSTANCIAS 
	//-----------------------------------------------------------------------------------

	function conf__pant_datos_instancias(toba_ei_pantalla $pantalla)
	{
		/**
		 * Si la Mesa de Examen tiene Inscriptos (aunque estén dados de baja), Actas creadas (aunque estén anuladas)
		 * en alguno de sus Llamados asociados ó Alumnos Inscriptos entonces no se permite editar sus instancias.
		 */
		if (!$this->cn()->entidad_es_nueva()) {
			$datos_mesa = $this->cn()->entidad()->get_datos_generales();
			$mesa_examen = $datos_mesa['mesa_examen'];
			$inscriptos_llamados_mesa = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_llamados_mesa($mesa_examen);
			$inscripciones_bajas_llamados_mesa = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_bajas_para_llamados_mesa($mesa_examen);
			$actas_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_llamados_mesa($mesa_examen);
			$evals_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_llamados_mesa($mesa_examen);
			// No se permite agregar ni eliminar instancias.
			if ($inscriptos_llamados_mesa > 0 || $inscripciones_bajas_llamados_mesa > 0 || $actas_llamados_mesa > 0 || $evals_llamados_mesa > 0) {
				$this->dep('datos_instancias')->evento('eliminar')->desactivar();
				$filtro = "sga_actas.estado = '".acta::estado_cerrada."'"; 
				$actas_cerradas_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_llamados_mesa($mesa_examen, $filtro);
				// No se permite editar la escala de notas de ninguna instancia.
				if ($actas_cerradas_llamados_mesa > 0) {
					$this->pantalla()->set_descripcion(toba::mensajes()->get('800_entidad_no_editable'));
					$this->dep('datos_instancias')->set_solo_lectura();
					$this->dep('datos_instancias')->eliminar_evento('modificacion');
				}
				$this->dep('seleccion_instancias')->evento('seleccion')->desactivar();
				$this->dep('seleccion_instancias')->evento('agregar')->desactivar();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- datos instancias -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_instancias(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_instancias_temp)) {
			$this->controlador()->set_hubo_cambios();
			$datos = $this->datos_instancias_temp;
		} else {
			$datos = $this->cn()->entidad()->get_datos_instancias();
		}
		
		$propuestas = $this->cn()->entidad()->get_datos_propuestas(false);
		$ids_propuestas = array_column($propuestas, "propuesta");
		foreach ($datos as $key => $dato) {
			$datos[$key]['propuestas'] = $ids_propuestas;
			$datos[$key]['escala_asignada'] = $dato['escala_nota'];
		}

		$icono_informacion = new icono_info_escala_nota();
		$form_ml->ef('escala_nota')->agregar_icono_utileria($icono_informacion);
		$form_ml->set_datos($datos);
	}

	function evt__datos_instancias__modificacion($datos)
	{
		try {
			$this->cn()->entidad()->set_datos_instancias($datos);
		} catch (guarani_error $e) {
			$this->seleccion_instancias_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__datos_instancias__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_instancia_mesa($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion instancias ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_instancias(toba_ei_cuadro $cuadro)
	{
		//Setea el mensaje eof del cuadro de Instancias de la Mesa.
		//Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		//Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de Instancias de la mesa.
		$etiqueta = guarani_etiquetas::get_tabla('sga_mesas_examen_instancias');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));

		// Recupera las instancias ya asociadas a la mesa para no mostrarlas dentro de los disponibles para seleccionar.
		$filtro = '';
		$instancias_asociadas = $this->cn()->entidad()->get_datos_instancias();

		// Si no permite la instancia libre, se agrega a las instancias asociadas para que la consulta que devuelve
		// las instancias aplicables a la mesa no devuelva la libre
		if (!$this->s__permite_instancia_libre) {
			$instancias_asociadas[] = array('instancia' => mesa_de_examen::inst_exam_libre);
		}

		if (!empty($instancias_asociadas)) {
			$filtro .= guarani_sql::armar_condicion_in($instancias_asociadas, 'sga_instancias', 'instancia', 'instancia', true);
		}

		$instancias_mesa = toba::consulta_php('co_mesas_de_examen')->get_instancias_aplicables_a_mesa($filtro);
		$cuadro->set_datos($instancias_mesa);
	}

	function evt__seleccion_instancias__seleccion($seleccion)
	{
		$this->seleccion_instancias_temp = $seleccion;
	}

	function evt__seleccion_instancias__agregar()
	{
		if (!empty($this->seleccion_instancias_temp)) {
			foreach ($this->seleccion_instancias_temp as $fila => $instancia) {
				$this->cn()->entidad()->agregar_instancia_mesa($instancia);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Cáscara del método descripción del ef popup info_escala, que solo se utiliza para mostrar una imagen.
	 */
	function metodo($in)
	{
		return '';
	}

	/**
	 * Ventana que se ejecuta una vez que todos los eventos se han disparado para este objeto
	 */
	function post_eventos()
	{
		try {
			if (isset($this->datos_generales_temp)) {
				$this->cn()->entidad()->set_datos_generales($this->datos_generales_temp);
				unset($this->datos_generales_temp);
			}
			if (isset($this->seleccion_modalidades_temp)) {
				$this->cn()->entidad()->set_datos_modalidades($this->seleccion_modalidades_temp);
				unset($this->seleccion_modalidades_temp);
			}
			if (isset($this->seleccion_llamados_temp)) {
				$this->cn()->entidad()->set_datos_llamados($this->seleccion_llamados_temp);
				unset($this->seleccion_llamados_temp);
			}
			if (isset($this->seleccion_propuestas_temp)) {
				$this->cn()->entidad()->set_datos_propuestas($this->seleccion_propuestas_temp);
				unset($this->seleccion_propuestas_temp);
			}
		} catch (guarani_error $e) {
			$this->resetear_cambio_pantalla();
			throw $e;
		}
	}

}