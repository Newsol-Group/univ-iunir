<?php
/**
 *
 *
 * @package		Examenes
 * @subpackage	Mesas de Examen
 * 
 */
class ci_edi_llamado_mesa extends ci_edicion_entidad
{
	// Datos temporales de los Formularios, Filtros y Cuadros de selecciónf
	protected $datos_docentes_temp;
	protected $seleccion_docentes_temp;
	protected $aplico_tribunal = false;  //Mantiene si en este pedido de página el usuario aplicó un tribunal al Llamado.
	protected $datos_evaluacion_temp;
	protected $seleccion_instancias_temp;
	protected $s__datos_filtro_docentes;
	protected $s__filtro_docentes;
	protected $s__tribunal_seleccionado;
	
	// Generales
	protected $s__actividad_seleccionada;	//Actividad seleccionada en la pantalla de navegación.
	protected $s__turno_seleccionado;		//Turno de Exámen seleccionado en los filtros de la pantalla inicial.
	protected $indx_msg_eof;				//Se recupera el índice del mensaje eof para cuadros del ci padre.

	function ini()
	{
		$this->indx_msg_eof = $this->controlador()->get_indx_msg_eof();
	}

	/**
	 * Se resetean las variables de sesión utilizadas al mantener información, para poder limpiarlas desde el ci padre o 
	 * desde el ci de navegación, ante el cambio de nodo en el árbol, o el evento cancelar edición de un nodo o volver a 
	 * la pantalla de selección.
	 */
	function resetear_vars()
	{
		unset($this->s__actividad_seleccionada);
		unset($s__turno_seleccionado);
		unset($s__datos_filtro_docentes);
		unset($s__filtro_docentes);
		unset($s__tribunal_seleccionado);

		$this->dep('editor_educacion_virtual')->resetear_vars();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf()
	{
		// Se obtienen los datos del Filtro y la selección del usuario en la pantalla inicial (ci de navegación)
		$this->s__turno_seleccionado = $this->controlador()->get_turno();
		$this->s__actividad_seleccionada = $this->controlador()->get_actividad();
	}

	/**
	 * Se extiende la función del padre, ci_edicion_entidad, donde se verifica la editabilidad de la entidad, y en caso
	 * de no ser editable se setean de solo lectura las dependencias y se eliminan los eventos de modificación sobre la 
	 * misma. Se definen los eventos adicionales que en este caso particular también permiten modificar la entidad, y se
	 * habilita el evento del filtro.
	 */
	function post_configurar()
	{
		/**
		 * Para esta operación particular se desea que los filtros tengan activados sus eventos, ya que no modifican la
		 * entidad, sino que solo permiten visualizar información. 
		 */
		unset($this->componentes_eventos_a_inhabilitar['filtro']);
		parent::post_configurar();
		if (!$this->cn()->entidad()->es_editable()) {
			// Para el cuadro de Docentes a seleccionar para el Llamado y el formulario ml de Docentes asociados.
			$inhabilitar_eventos['formulario_ml'] = array('eliminar');
			$inhabilitar_eventos['cuadro'] = array('agregar');
			// Para el formulario de aplicar Tribunal a los Docentes asociados al Llamado.
			// Para la asociación de Períodos de Inscripción.
			// Para las Evaluaciones del Llamado.
			$inhabilitar_eventos['formulario'] = array('confirmar', 'eliminar', 'cancelar', 'aplicar');
			$this->inhabilitar_componentes_eventos($inhabilitar_eventos);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA DOCENTES 
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_tribunal ----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Formulario que permite seleccionar un tribunal para aplicar todos sus docentes al Llamado de la Mesa.
	 */
	function conf__form_tribunal(toba_ei_formulario $form)
	{
		// Se setea el ef oculto elemento, para poder traer los tribunales de la Actividad para la que se creó la Mesa.
		$form->ef('elemento')->set_estado($this->s__actividad_seleccionada['elemento']);
		if (isset($this->s__tribunal_seleccionado)) {
			$form->ef('tribunal')->set_estado($this->s__tribunal_seleccionado);
		}
	}

	function evt__form_tribunal__aplicar($datos)
	{
		if (!empty($datos['tribunal'])) {
			// Si hay un tribunal seleccionado entonces Se setea la variable temporal y se modifican 
			// los docentes asociados al Llamado para que sean sólo los del tribunal dado.
			$this->s__tribunal_seleccionado = $datos['tribunal'];
			$this->cn()->entidad()->asociar_docentes_tribunal_llamado($this->s__tribunal_seleccionado);
			$this->aplico_tribunal = true;
		}
	}

	//-----------------------------------------------------------------------------------
	//---- docentes_asociados -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se muestran los docentes actualmente asociados al Llamado de la Mesa de Exámen. El usuario puede haberlos asociado
	 * previamente mediante la selección en el cuadro inferior y el botón Agregar, o habiendo presionado el botón Aplicar
	 * Tribunal (con el cual quedan asociados todos los docentes de dicho tribunal de exámen al llamado, y se eliminan los
	 * anteriores).
	 */
	function conf__docentes_asociados(toba_ei_formulario_ml $form_ml)
	{
		if (isset($this->datos_docentes_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form_ml->set_datos($this->datos_docentes_temp);
		} else {
			$form_ml->set_datos($this->cn()->entidad()->get_datos_docentes_llamado());
		}
	}

	function evt__docentes_asociados__modificacion($datos)
	{
		try {
			// Se realiza la modificación sólo si no se está en el caso en que el usuario acaba de aplicar un tribunal al 
			// llamado. En este último caso todas los docentes previos fueron eliminados, por lo que no se quiere considerar 
			// ninguna modificación adicional que se hubiese realizado con anterioridad sobre los mismos.
			if (!$this->aplico_tribunal) {
				$this->cn()->entidad()->set_datos_docentes_llamado($datos);
			}
		} catch (guarani_error $e) {
			$this->datos_docentes_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__docentes_asociados__eliminar($seleccion)
	{
		$this->cn()->entidad()->eliminar_docente_llamado($seleccion);
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_docentes --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_docentes(toba_ei_filtro $filtro)
	{		
		if (isset($this->s__datos_filtro_docentes)) {
			$filtro->set_datos($this->s__datos_filtro_docentes);
		} else {
			// Si se seleccionó una Cátedra a asociar a la Mesa en la solapa de Datos Generales de la Mesa de Exámen, debe
			// recuperarse la misma de la entidad padre (ya que no es un dato de la entidad actual) y se setearse por defecto 
			// el filtro de catedra con ese valor.
			$datos_llamado = $this->cn()->entidad()->get_datos_llamado();
			$datos_mesa_padre = toba::consulta_php('co_mesas_de_examen')->get_datos_mesa($datos_llamado['mesa_examen']);
			if (!empty($datos_mesa_padre['catedra'])) {
				$filtro->columna('catedra')->set_estado(array('condicion' => 'igual_a', 'valor' => $catedra));
				$filtro->columna('catedra')->set_visible(true);
				// En caso de que se haya elegido una cátedra en la solapa de datos generales de la Mesa, el cuadro de docentes 
				// a asociar debe cargarse inicialmente según este filtro como si el usuario hubiese presionado filtrar.
				$this->s__datos_filtro_docentes = array('catedra' => $catedra);
				$this->s__filtro_docentes = '';
			}
		}
	}

	function evt__filtro_docentes__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_docentes = $datos;
			if (isset($datos['catedra'])) {
				$this->dep('filtro_docentes')->columna('catedra')->resetear_estado();
			}
			$filtro_ra = '';
			if (isset($datos['responsable_academica'])) {
				$filtro_ra .= " AND EXISTS (SELECT * FROM sga_docentes_ra WHERE sga_docentes_ra.docente = sga_docentes.docente AND sga_docentes_ra.responsable_academica = {$datos['responsable_academica']['valor']})";
				$this->dep('filtro_docentes')->columna('responsable_academica')->resetear_estado();
			}
			$this->s__filtro_docentes = "{$this->dep('filtro_docentes')->get_sql_where()} $filtro_ra";
		} else {
			unset($this->s__datos_filtro_docentes);
			$this->s__filtro_docentes = '';
		}
	}

	function evt__filtro_docentes__cancelar()
	{
		unset($this->s__datos_filtro_docentes);
		unset($this->s__filtro_docentes);
	}

	//-----------------------------------------------------------------------------------
	//---- seleccion_docentes -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__seleccion_docentes(toba_ei_cuadro $cuadro)
	{
		/**
		 * Si no están seteados los filtros (sobre los docentes a asociar), entonces es la carga inicial del cuadro 
		 * (el usuario no presionó filtrar o presionó limpiar). No se traen datos (no se ejecuta la consulta) y se pone 
		 * un mensaje en el cuadro que indica al usuario que debe filtrar para obtener datos.
		 */
		// Setea el mensaje eof del cuadro de docentes.
		// Recupera el índice del mensaje de eof del cuadro del ci de navegación.
		$indx_msj = $this->indx_msg_eof;
		if (!isset($this->s__filtro_docentes)) {
			$indx_msj .= '_sinfiltro';
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj));
		} else {
			// El usuario presionó filtrar (ya sea con datos o con el filtro vacío).
			$indx_msj .= '_filtrado';
			// Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de docentes.
			$etiqueta = guarani_etiquetas::get_tabla('sga_docentes');
			$etiqueta = $etiqueta->get_enf('plural');
			$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));
			// Recupera los docentes ya asociados al Llamado de la Mesa para no mostrarlos dentro de los disponibles a seleccionar.
			$docentes_asociados = $this->cn()->entidad()->get_datos_docentes_llamado();
			// Arma el filtro para obtener los docentes según la condición not in y el filtro ingresado por el usuario.
			$filtro = 'true';
			if (!empty($docentes_asociados)) {
				$condicion_not_in = guarani_sql::armar_condicion_in($docentes_asociados, 'sga_docentes', 'docente', 'docente', true);
				$filtro .= ' AND ' . $condicion_not_in;
			}
			if ($this->s__filtro_docentes) {
				$filtro .= ' AND ' . $this->s__filtro_docentes;
			}
			$catedra = null;
			if (isset($this->s__datos_filtro_docentes['catedra'])) {
				$catedra = $this->s__datos_filtro_docentes['catedra']['valor'];
			}
			$docentes = toba::consulta_php('co_docentes')->get_docentes_activos_catedras($catedra, $filtro);
			$cuadro->set_datos($docentes);
		}
	}

	function evt__seleccion_docentes__seleccion($seleccion)
	{
		$this->seleccion_docentes_temp = $seleccion;
	}

	function evt__seleccion_docentes__agregar()
	{
		if (!empty($this->seleccion_docentes_temp)) {
			foreach ($this->seleccion_docentes_temp as $docente) {
				$this->cn()->entidad()->agregar_docente_llamado($docente);
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- PANTALLA EVALUACIONES 
	//-----------------------------------------------------------------------------------

	function conf__pant_evaluaciones(toba_ei_pantalla $pantalla)
	{
		/**
		 * Si el Llamado tiene Alumnos Inscriptos (aunque estén dados de baja), Actas creadas (aunque
		 * estén anuladas) ó Alumnos asociados entonces no se permite editar las evaluaciones manuales
		 */
		if (!$this->cn()->entidad_es_nueva()) {

			// Parámetros
			$datos_llamado = $this->cn()->entidad()->get_datos_llamado();
			$llamado_mesa = $datos_llamado['llamado_mesa'];
			$mesa_examen = $datos_llamado['mesa_examen'];
			$filtro = "sga_llamados_mesa.llamado_mesa = $llamado_mesa";

			// Datos para los controles de la pantalla
			$inscriptos_llamados_mesa = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_para_llamados_mesa($mesa_examen, $filtro);
			$inscripciones_bajas_llamados_mesa = toba::consulta_php('co_inscripciones_examenes')->get_cant_inscripciones_bajas_para_llamados_mesa($mesa_examen, $filtro);
			$actas_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_actas_evaluaciones_para_llamados_mesa($mesa_examen, $filtro);
			$evals_llamados_mesa = toba::consulta_php('co_evaluaciones')->get_cant_evaluaciones_para_llamados_mesa($mesa_examen, $filtro);

			if ($inscriptos_llamados_mesa > 0 || $inscripciones_bajas_llamados_mesa > 0 || $actas_llamados_mesa > 0 || $evals_llamados_mesa > 0) {

				$this->pantalla()->set_descripcion(toba::mensajes()->get('800_entidad_no_editable'));

				$this->dep('datos_evaluacion')->set_solo_lectura();
				$this->dep('datos_evaluacion')->evento('eliminar')->desactivar();
				$this->dep('datos_evaluacion')->evento('cancelar')->desactivar();
				$this->dep('datos_evaluacion')->evento('confirmar')->desactivar();

				$this->dep('cuadro_evaluaciones')->evento('seleccion')->desactivar();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_evaluaciones ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__cuadro_evaluaciones(toba_ei_cuadro $cuadro)
	{
		// Recupera la etiqueta enfatizada y plural del concepto asociado a la tabla de evaluaciones.
		$indx_msj = $this->indx_msg_eof;
		$etiqueta = guarani_etiquetas::get_tabla('sga_evaluaciones');
		$etiqueta = $etiqueta->get_enf('plural');
		$cuadro->set_eof_mensaje(guarani_mensajes::get($indx_msj, array($etiqueta)));

		// Recupera las evaluaciones ya asociadas al Llamado de la Mesa.
		$evaluaciones = $this->cn()->entidad()->get_datos_evaluaciones();
		$datos = array();
		foreach ($evaluaciones as $clave => $valor) {
			if ($valor['tipo_eval_automatica'] == comunes::no) {
				$valor['hora_inicio'] = substr($valor['hora_inicio'], 0, 5);
				$valor['hora_fin'] = substr($valor['hora_fin'], 0, 5);
				$datos[$clave] = $valor;
			}
		}

		// Seteo de los datos formateados
		$cuadro->set_datos($datos);
	}

	function evt__cuadro_evaluaciones__seleccion($seleccion)
	{
		// Se setea la evaluación seleccionada en la tabla correspondiente para su edición.
		$id_eval = current($seleccion);
		$this->cn()->entidad()->seleccionar_evaluacion($id_eval);
		//Se setea la etiqueta del botón agregar del formulario de datos de la evaluación como Confirmar (ya que se está editando).
		$this->dependencia('datos_evaluacion')->evento('confirmar')->set_etiqueta('Confirmar');
	}

	//-----------------------------------------------------------------------------------
	//---- datos_evaluacion -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__datos_evaluacion(toba_ei_formulario $form)
	{
		// Se agrea ícono de utilería (pop-up de detalle de escala de notas)
		$icono_informacion = new icono_info_escala_nota();
		$form->ef('escala_nota')->agregar_icono_utileria($icono_informacion);
		
		// Se setea el ef oculto llamado_mesa, para poder cargar las Instancias asociadas a la Mesa a la que corresponde el
		// Llamado que está siendo editado. A partir de estas se podrán seleccionar las instancias a asociar a una evaluación
		// definida para el mismo.
		$datos_llamado = $this->cn()->entidad()->get_datos_llamado();
		$form->ef('llamado_mesa')->set_estado($datos_llamado['llamado_mesa']);
		if (isset($this->datos_evaluacion_temp)) {
			$this->controlador()->set_hubo_cambios();
			$form->set_datos($this->datos_evaluacion_temp);
		} else {
			if ($this->cn()->entidad()->hay_evaluacion_seleccionada()) {
				$datos_evaluacion = $this->cn()->entidad()->get_datos_evaluacion_seleccionada();
				$datos_evaluacion['instancias'] = $this->get_multi_sel_instancias();
				$form->set_datos($datos_evaluacion);
			} else {
				// En caso de estar agregando una nueva evaluación al llamado se elimina el evento de eliminar del form.
				$form->evento('eliminar')->desactivar();
				// Se setean los datos por defecto para la fecha y horas de la evaluación con los datos del Llamado.
				$form->ef('fecha')->set_estado($datos_llamado['fecha']);
				$hora_inicio = substr($datos_llamado['hora_inicio'], 0, 5);
				$form->ef('hora_inicio')->set_estado($hora_inicio);
				if (isset($datos_llamado['hora_finalizacion'])) {
					$hora_fin = substr($datos_llamado['hora_finalizacion'], 0, 5);
					$form->ef('hora_fin')->set_estado($hora_fin);
				}
			}
		}
	}

	/**
	 * Obtiene el arreglo para cargar el ef multi-seleccion-check a partir de las instancias asociadas a la evaluación 
	 * actualmente seleccionada para editar.
	 */
	function get_multi_sel_instancias()
	{
		$instancias = $this->cn()->entidad()->get_datos_instancias_evaluacion();
		$claves = array();
		foreach ($instancias as $clave => $valor) {
			$claves[] = $valor['instancia'];
		}
		return $claves;
	}

	function evt__datos_evaluacion__confirmar($datos)
	{
		try {
			if ($this->cn()->entidad()->hay_evaluacion_seleccionada()) {
				// Se está editando una evaluación.
				$this->cn()->entidad()->set_datos_evaluacion($datos);
			} else {
				// Se está agregando una evaluación, y se selecciona la misma para luego poder agregar todas las instancias.
				$this->cn()->entidad()->nueva_evaluacion($datos);
			}
			$this->cn()->entidad()->set_datos_instancias_evaluacion($datos['instancias']);
			$this->cn()->entidad()->deseleccionar_evaluacion();
		} catch (guarani_error $e) {
			$this->datos_evaluacion_temp = $datos;
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__datos_evaluacion__cancelar()
	{
		$this->cn()->entidad()->deseleccionar_evaluacion();
	}

	function evt__datos_evaluacion__eliminar()
	{
		$this->cn()->entidad()->eliminar_evaluacion_seleccionada();
	}
    
    function get_catedras_actividad_para_mesas() 
    {        
        // Se setean las opciones del filtro de tipo combo cátedra, que dependen de la actividad seleccionada en la primer 
		// pantalla. Como las opciones dependen de este último valor, no puede cargarse el combo desde el editor.
		return toba::consulta_php('co_catedras')->get_catedras_actividad_para_mesas($this->s__actividad_seleccionada['elemento'] ?? null);		
    }    
}