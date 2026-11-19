<?php
/**
 * 
 * Representa las restricciones aplicadas para realizar una Acción.
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class ci_edi_requisitos_x_accion extends guarani_pers_ci
{
	
	protected $tipo_req_a_agregar;
	protected $form_ml;
	protected $datos_temp;

	/**
	 * Deben guardarse los datos del requisito o entidad estado agregado en variables de sesión, ya que el registro de
	 * requisitos_x_accion no puede darse de alta hasta que no se llega a la última pantalla (configuración), donde el 
	 * usuario define los Grupos a los cuáles se aplicará ese requisito/entidad-estado, que es la tabla padre de la relación.
	 */
	protected $s__datos_filtro_req_ex;
	protected $s__filtro_req_ex;
	protected $s__datos_filtro_entidades;
	protected $s__filtro_entidades;
	protected $s__id_fila_req_x_accion_seleccionado;
	protected $s__req_agregado;
	protected $s__req_seleccionado;
	protected $s__params_req;
	protected $s__entidad_agregada;
	protected $s__entidad_seleccionada;
	protected $s__estado_entidad;
	protected $s__regla_entidad;
	protected $s__params_regla;
	protected $s__nivel;

	/**
	 * Se define esta función para poder resetear los valores de todas las variables de sesión ante el evento volver 
	 * (cancelar), definido en ci controlador de este (ci de navegación).
	 */
	function resetear_variables_sesion()
	{
		unset($this->s__id_fila_req_x_accion_seleccionado);
		unset($this->s__req_agregado);
		unset($this->s__req_seleccionado);
		unset($this->s__params_req);
		unset($this->s__entidad_agregada);
		unset($this->s__entidad_seleccionada);
		unset($this->s__estado_entidad);
		unset($this->s__regla_entidad);
		unset($this->s__params_regla);
		unset($this->s__nivel);
	}

	/**
	 * Busca dentro de todos los subtipos de entidad (o entidades) correspondientes a los grupos de requisitos asociados al 
	 * requisito-entidad que se está editando (recibidos en $grupos_subtipos), el subtipo (o entidad) que debe determinarse
	 * si debe se como parte de la configuración del Requisito, recibido en $subtipo_entidad_a_chequear. 
	 * Retorna el valor correspondiente a marcar el subtipo en el campo Requisito o en Not Requisito 
	 * (de acuerdo al valor del $operador_not) si lo encuentra, y false en caso contrario. Busca los subtipos entidades o 
	 * las entidades particulares de acuerdo a lo recibido en el parámetro $campo.
	 */

	function marcar_subtipo_entidad($grupos_subtipos, $subtipo_entidad_a_chequear, $campo)
	{
		foreach ($grupos_subtipos as $grupo) {
			if (isset($grupo[$campo]) && $grupo[$campo] == $subtipo_entidad_a_chequear) {
				return $grupo['operador_not'];
			}
		}
	}

	//Consultas sobre las variables de clase que definen el nivel de aplicación del Requisito.
	function hay_nivel_tipo_definido() 
	{
		return isset($this->s__nivel['tipo']);
	}
	
	function get_nivel_tipo()
	{
		return $this->s__nivel['tipo'];
	}

	function get_nivel_entidad_tipo()
	{
		return $this->s__nivel['entidad_tipo'];
	}
	
	function get_nivel_entidad_tipo_dato($dato)
	{
		return $this->s__nivel['entidad_tipo_datos'][$dato];
	}
	
	/**
	 * Permite eliminar todas las dependencias relacionadas con la configuración del requisito o entidad-estado, y habilitar 
	 * solo las correspondientes según el nivel de aplicación actual.
	 * Se factoriza en un método ya que debe realizarse desde la pantalla de configuración de un requisito así como desde
	 * la pantalla de configuración de una entidad estado.
	 */
	function habilitar_dependencias_nivel()
	{		
		//Se realiza el procesamiento de las variables y dependencias relacionadas con las Pantallas de Configuración 
		//del Requisito o Entidad-Estado seleccionado o agregado.
		if ($this->pantalla()->existe_dependencia('form_ml_subtipos')) {
			$this->pantalla()->eliminar_dep('form_ml_subtipos');
		}
		if ($this->pantalla()->existe_dependencia('filtro_entidades')) {
			$this->pantalla()->eliminar_dep('filtro_entidades');
		}
		if ($this->pantalla()->existe_dependencia('form_ml_entidades')) {
			$this->pantalla()->eliminar_dep('form_ml_entidades');
		}
		if ($this->pantalla()->existe_dependencia('form_aplic_instit')) {
			$this->pantalla()->eliminar_dep('form_aplic_instit');
		}
		if ($this->hay_nivel_tipo_definido()) {
			//Habilito dependencias necesarias
			switch ($this->get_nivel_tipo()) {
				case g3entidad::nivel_aplicacion_tipo_entidad:
					$entidad = $this->get_nivel_entidad_tipo();
					if ($entidad == g3entidad::tipo_institucion) {
						$this->pantalla()->agregar_dep('form_aplic_instit');
					} else {
						$this->pantalla()->agregar_dep('filtro_entidades');
						$this->pantalla()->agregar_dep('form_ml_entidades');
					}
					break;
				case g3entidad::nivel_aplicacion_tipo_subtipo:
					$this->pantalla()->agregar_dep('form_ml_subtipos');
					break;
			}
		} 
	}
	
	/**
	 * Permite determinar cuál es el Nivel de Aplicación correspondiente al requisito o entidad-estado que el usuario 
	 * seleccionó para editar a partir el Requisito Grupo asociado al mismo. Se invoca solo en caso de edición y no en los 
	 * casos en que se agregó, desde las pantallas de configuración del requisito o entidad (se factoriza en un método porque 
	 * se realiza el mismo procesamiento desde ambas pantallas: pant_configurar_requisito y pant_configurar_ent_estado). 
	 * A partir de este se setean los diferentes datos necesarios en la variable de clase nivel.
	 */
	function set_nivel_aplicacion_seleccion()
	{
		if (isset($this->s__req_seleccionado) || isset($this->s__entidad_seleccionada)) {
			$id_req_grupo = $this->cn()->entidad()->get_fila_grupo_padre_req_x_accion($this->s__id_fila_req_x_accion_seleccionado);
			$datos_req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
			$requisito_grupo = $this->cn()->entidad()->get_datos_requisito_grupo($id_req_grupo);
			$this->s__nivel['para_todo'] = false;
			$this->s__nivel['op_not_institucion'] = null;
			if (isset($requisito_grupo['entidad_subtipo'])) {
				/**
				 * El requisito está aplicado a nivel de todo un subtipo entidad. Se debe recuperar el tipo de ese subtipo 
				 * para la carga del nivel de aplicación.
				 */
				$datos_subtipo = toba::consulta_php('co_g3entidades')->get_datos_subtipo($requisito_grupo['entidad_subtipo']);
				$this->s__nivel['tipo'] = g3entidad::nivel_aplicacion_tipo_subtipo;
				$this->s__nivel['entidad_tipo'] = $datos_subtipo['entidad_tipo'];
			} elseif (isset($requisito_grupo['entidad'])) {
				/**
				 * El requisito está aplicado a nivel de entidades particulares (puede haber màs de un requisito grupo, pero
				 * se toma uno solo para obtener el tipo asociado a esas entidades y mostrarlo en el nivel de aplicación).
				 */
				$datos_entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad($requisito_grupo['entidad']);
				$this->s__nivel['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
				$this->s__nivel['entidad_tipo'] = $datos_entidad['entidad_tipo'];
				if ($this->s__nivel['entidad_tipo'] == g3entidad::tipo_institucion) {
					$this->s__nivel['para_todo'] = true;
					$this->s__nivel['op_not_institucion'] = $datos_req_accion['operador_not']; 
				}
			}
			$this->s__nivel['entidad_tipo_datos'] = g3entidad::get_datos_tipo($this->s__nivel['entidad_tipo']);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Se ejecuta al inicio de todos los request en donde participa el componente
	 */
	function ini()
	{
		/**
		 * Se modifica el comportamiento del ci de navegacion (seteando no volver al guardar), para que cuando el usuario 
		 * presione el botón Guardar, se permanezca en la pantalla de edición.
    	 */
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}

	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__pant_agregar_requisito_ent_est(toba_ei_pantalla $pantalla)
	{
		$this->dependencia('form_nombre_accion')->ef('nombre')->set_etiqueta('Agregar Requisitos para la Acción:');
	}

	function conf__pant_params_req(toba_ei_pantalla $pantalla)
	{
		$this->dependencia('form_nombre_accion')->ef('nombre')->set_etiqueta('Configurar Requisitos para la Acción:');
		$this->controlador()->evento('guardar')->desactivar();
	}

	function conf__pant_params_ent_estado(toba_ei_pantalla $pantalla)
	{
		$this->dependencia('form_nombre_accion')->ef('nombre')->set_etiqueta('Configurar Requisitos para la Acción:', 200);
		$this->dependencia('form_entidad_estado')->set_solo_lectura(array('entidad', 'estado', 'regla'));
		$this->dependencia('form_entidad_estado')->set_efs_obligatorios(array('entidad', 'estado', 'regla'), false);
		$this->dependencia('form_entidad_estado')->eliminar_evento('verificar_entidad_estado');
		$this->controlador()->evento('guardar')->desactivar();
	}

	function conf__pant_configurar_requisito(toba_ei_pantalla $pantalla)
	{
		$this->dependencia('form_nombre_accion')->ef('nombre')->set_etiqueta('Configurar Requisitos para la Acción:', 200);
		if (!isset($this->s__req_agregado)) {
			//Se seleccionó un requisito para su edición.
			//Deben recuperarse los datos necesarios para visualizar las pantallas correctamente.
			$this->s__id_fila_req_x_accion_seleccionado = $this->controlador()->get_visualizador_requisito_seleccionado();
			$datos_req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
			$this->s__req_seleccionado = $datos_req_accion['requisito'];	
			//Se setean en la variable de clase nivel los diferentes datos correspondientes a la aplicación del requisito 
			//seleccionado, si es que la misma todavía no tiene valor.
			if (!isset($this->s__nivel)) {
				$this->set_nivel_aplicacion_seleccion();
			}
		}
		//Se hace un control de si el requisito que se va a configurar (ya sea agregado o seleccionado) tiene parámetros,
		//ya que si no es así no tiene sentido mostrar el cuadro con la definición de parámetros de la regla asociada al mismo.
		$mostrar_cuadro_params = false;
		if (isset($this->s__params_req)) {
			$mostrar_cuadro_params = true;
		} else {
			//No se pasó por la pantalla de definición de parámetros del requisito, esto es porque se seleccionó o porque
			//se agregó pero no tiene parámetros formales, solo en el primer caso chequeo si tiene params para mostrar.
			if (!isset($this->s__req_agregado)) {
				$params = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($this->s__req_seleccionado);
				if (!empty($params)) {
					$mostrar_cuadro_params = true;
				}
			}
		}
		if (!$mostrar_cuadro_params) {
			$pantalla->eliminar_dep('form_ml_params_req');
		} else {
			//Como el form de parámetros del requisito se usa solo para mostrar el valor de los mismos en esta pantalla,
			//se saca la condición de obligatorio sobre el campo valor del parámetro y se de solo lectura.
			$this->dependencia('form_ml_params_req')->ef('parametro_valor')->set_obligatorio(false);
			$this->dependencia('form_ml_params_req')->ef('parametro_valor')->set_solo_lectura();
			$this->dependencia('form_ml_params_req')->eliminar_evento('verificar_requisito_params');
		}
		//Se agregan o eliminan las dependencias correspondientes al nivel de aplicación (si es que el mismo existe).
		$this->habilitar_dependencias_nivel();
		//Se desactivan los botones volver y guardar del ci de navegación, ya que no tienen sentido mientras el usuario
		//no haya presionado Confirmar o Cancelar configuración para el requisito.
		$this->controlador()->evento('guardar')->desactivar();
		$this->controlador()->evento('cancelar')->desactivar();
	}


	function conf__pant_configurar_ent_estado(toba_ei_pantalla $pantalla)
	{
		$this->dependencia('form_nombre_accion')->ef('nombre')->set_etiqueta('Configurar Requisitos para la Acción:', 200);
		if (!isset($this->s__entidad_agregada)) {
			//Se seleccionó una entidad-estado para su edición.
			//Deben recuperarse los datos necesarios para visualizar las pantallas correctamente.
			$this->s__id_fila_req_x_accion_seleccionado = $this->controlador()->get_visualizador_requisito_seleccionado();
			$datos_req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
			$this->s__entidad_seleccionada = $datos_req_accion['entidad'];	
			//Se setean en la variable de clase nivel los diferentes datos correspondientes a la aplicación de la entidad
			//seleccionada, si es que la misma todavía no tiene valor.
			if (!isset($this->s__nivel)) {
				$this->set_nivel_aplicacion_seleccion();
			}
		}
		//Se hace un control de si la entidad-estado que se va a configurar (ya sea agregado o seleccionado) tiene parámetros,
		//ya que si no es así no tiene sentido mostrar el cuadro con la definición de parámetros de la regla asociada a la misma.
		$mostrar_cuadro_params = false;
		if (isset($this->s__params_regla)) {
			$mostrar_cuadro_params = true;
		} else {
			//No se pasó por la pantalla de definición de parámetros de la entidad-estado, esto es porque se seleccionó o porque
			//se agregó pero no tiene parámetros formales, solo en el primer caso chequeo si tiene params para mostrar.
			if (!isset($this->s__entidad_agregada)) {
				if (isset($datos_req_accion['regla'])) {
					$params = toba::consulta_php('co_reglas')->tiene_parametros($datos_req_accion['regla']);
					if (!empty($params)) {
						$mostrar_cuadro_params = true;
					}
				}
			}
		}
		if (!$mostrar_cuadro_params) {
			$pantalla->eliminar_dep('form_ml_params_ent');
		} else {
			/**
			 * Como el form de parámetros de la entidad-estado se usa solo para mostrar el valor de los mismos en 
			 * esta pantalla, se saca la condición de obligatorio sobre el campo valor del parámetro y se pone de 
			 * solo lectura. Además se elimina el evento de confirmación.
			 */
			$this->dependencia('form_ml_params_ent')->ef('parametro_valor')->set_obligatorio(false);
			$this->dependencia('form_ml_params_ent')->ef('parametro_valor')->set_solo_lectura();
			$this->dependencia('form_ml_params_ent')->eliminar_evento('confirmar');
		}
		$this->dependencia('form_entidad_estado')->set_solo_lectura(array('entidad', 'estado', 'regla'));
		$this->dependencia('form_entidad_estado')->set_efs_obligatorios(array('entidad', 'estado', 'regla'), false);
		$this->dependencia('form_entidad_estado')->eliminar_evento('verificar_entidad_estado');
		//Se agregan o eliminan las dependencias correspondientes al nivel de aplicación (si es que el mismo existe).
		$this->habilitar_dependencias_nivel();
		//Se desactivan los botones volver y guardar del ci de navegación, ya que no tienen sentido mientras el usuario
		//no haya presionado Confirmar o Cancelar configuración para el requisito.
		$this->controlador()->evento('guardar')->desactivar();
		$this->controlador()->evento('cancelar')->desactivar();
	}

	//-----------------------------------------------------------------------------------
	//---- Dependencias -----------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//-----------------------------------------------------------------------------------
	//---- form_nombre_accion -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_nombre_accion(toba_ei_formulario $form)
	{
		$datos_accion = $this->cn()->entidad()->get_datos_accion();
		if (!empty($datos_accion)) {
			$form->set_datos($datos_accion);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_req_a_agregar -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_req_a_agregar(toba_ei_formulario $form)
	{
		//Si ya se había seleccionado un valor para el combo requisito a agregar en un pedido de pantalla previo, se setea.
		if (isset($this->tipo_req_a_agregar)) {
			$form->set_datos(array('req_a_agregar'=>$this->tipo_req_a_agregar));
		}
	}

	function evt__form_req_a_agregar__modificacion($datos)
	{
		//Se almacena en una variable de sesión el tipo de requisito agregado.
		if (isset($datos['req_a_agregar'])) {
			$this->tipo_req_a_agregar = $datos['req_a_agregar'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_reqexistentes ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__filtro_reqexistentes(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_req_ex)) {
			$filtro->set_datos($this->s__datos_filtro_req_ex);
		}
	}

	function evt__filtro_reqexistentes__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_req_ex = $datos;
			$this->s__filtro_req_ex = $this->dep('filtro_reqexistentes')->get_sql_where();
		} else {
			$this->evt__filtro_reqexistentes__cancelar();
		}
	}

	function evt__filtro_reqexistentes__cancelar()
	{
		unset($this->s__datos_filtro_req_ex);
		$this->s__filtro_req_ex = '';
	}


	//-----------------------------------------------------------------------------------
	//---- cuadro_reqexistentes ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	*	Permite recuperar el mensaje toba actualmente asociado al eof del cuadro de requisitos existentes.
	*/	
	function get_msg_eof_req_ex()
	{
		$indx_msg = $this->controlador()->get_msg_eof();
		if (isset($this->s__filtro_req_ex)) {
			$indx_msg = $indx_msg . '_filtrado';
		}
		$etiquetas = guarani_etiquetas::get_tabla('sga_requisitos_validos'); 
		return guarani_mensajes::get($indx_msg, array($etiquetas->get_enf('plural')));
	}
	
	function conf__cuadro_reqexistentes(toba_ei_cuadro $cuadro)
	{
		//Se recuperan los requisitos que es posible asociar a la acción y aquellos que ya se encuentran ligados a la misma.
		//En el cuadro de selección se muestra la diferencia entre ambos conjuntos.
		$datos_accion = $this->cn()->entidad()->get_datos_accion();
		$requisitos_asociados = $this->cn()->entidad()->get_datos_requisitos_x_accion();
		$condicion_not_req = '';
		if (!empty($requisitos_asociados)) {
			$condicion_not_req = guarani_sql::armar_condicion_not_in($requisitos_asociados, 'sga_requisitos', 'requisito', 'requisito');
		}
		//Se pasa a la consulta el filtro sobre los requisitos seteado por el usuario y la condicion con los req a excluir.
		$requisitos_asociables = toba::consulta_php('co_requisitos_x_accion')->get_req_asociables_a_accion($datos_accion['accion'], $condicion_not_req, $this->s__filtro_req_ex);
		$cuadro->set_datos($requisitos_asociables);
		$cuadro->set_eof_mensaje($this->get_msg_eof_req_ex());
	}

	function evt__cuadro_reqexistentes__seleccion($seleccion)
	{
		/**
	 	* Se almacena en la variable de sesión correspondiente el id del requisito existente que el usuario asoció 
	 	* a la acción. Luego se pasa a la pantalla para configurar sus parámetros.
	 	*/
		//Se almacena en la variable de sesión correspondiente el id del requisito existente que el usuario asoció a la acción.
		$this->s__req_agregado = current($seleccion);
		//Se chequea si el requisito seleccionado para agregar tiene o no parámetros, para pasar a la pantalla de definición
		//de parámetros del requisito, o a la de configuración del requisito directamente.
		$params = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($this->s__req_agregado);
		if (!empty($params)) {
			$this->set_pantalla('pant_params_req');
		} else {
			$this->set_pantalla('pant_configurar_requisito');
		}
	}


	//-----------------------------------------------------------------------------------
	//---- form_nombre_req --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_nombre_req(toba_ei_formulario $form)
	{
		//Recupera el nombre del Requisito seleccionado para agregar por el usuario, o seleccionado desde la pantalla de visualización.
		if (isset($this->s__req_agregado)) {
			//Estoy en la pantalla de definición de parámetros, o en la pantalla de configuración para un requisito agregado.
			$requisito = $this->s__req_agregado;
		} else {
			//Estoy en la pantalla de configuración con un requisito seleccionado por el usuario.
			$requisito = $this->s__req_seleccionado;
		}
		$datos_requisito = toba::consulta_php('co_requisitos')->get_requisito_datos($requisito);
		$form->set_datos($datos_requisito);
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_params_req -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_params_req(toba_ei_formulario_ml $form_ml)
	{
		/**
		 * Se diferencia cómo cargar el cuadro de parámetros del requisito, de acuerdo a si se viene de (o se está en) la 
		 * pantalla de Agregar Requisito Existente, o si se viene de algunas de las pantallas de Visualización habiendo 
		 * seleccionado un Requisito para editar.
		 */
		if (isset($datos_temp['form_ml_params_req'])) {
			$form_ml->set_datos($datos_temp['form_ml_params_req']);
		} else {
			if (isset($this->s__req_agregado)) {
				$requisito = $this->s__req_agregado;
			} else {
				//Desde la pantalla de visualización se seleccionó un requisito por acción de tipo 'requisito' y se pasó
				//a la pantalla de Configuración del Requisito.
				$requisito = $this->s__req_seleccionado;
				//Se recuperan los parámetros actuales definidos para el requisito por acción seleccionado.
				$datos_req_x_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
				$this->s__params_req = $datos_req_x_accion['parametros'];
			}
	
			//Se obtienen los parámetros formales del requisito seleccionado para agregar por el usuario.
			$datos_params_req = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($requisito);
			//Si la variable de sesión que mantiene los parámetros está seteada (quiere decir que el usuario estaba editando y
			//surgió un error (de duplicación), se vuelve a configurar el form con esos valores para su modificación.
			if (isset($this->s__params_req)) {
				$valor_params = explode('|', $this->s__params_req);
				if (guarani_arreglos::count($datos_params_req) == guarani_arreglos::count($valor_params)) {
					$index = 0;
					foreach ($datos_params_req as $clave=>$param) {
						$datos_params_req[$clave]['parametro_valor'] = $valor_params[$index];
						$index++;
					}
				}
			}
			$form_ml->set_datos($datos_params_req);
		}
	}

	function evt__form_ml_params_req__confirmar($datos)
	{
		/**
		 * El usuario confirma los parámetros actuales definidos para el requisito agregado. Se deben almacenar los mismos
		 * en la variable de clase: params_req en la forma en que serán luego almacenados en el requisito por acción, 
		 * es decir en forma de cadena con los valores de los parámetros separados por '|'. 
		 * Se validan los parámetros actuales de acuerdo a los tipos definidoa para los parámetros formales de
		 * la regla asociada al Requisito (y se informa el error si corresponde).
		 * A continuación se chequea que ya no exista un requisito asociado a la acción que se está editando, que sea 
		 * equivalente al requisito agregado con los mismos valores para sus parámetros formales. Si está duplicado se
		 * informa el error. 
		 * En caso de que los datos ingresados sean correctos, se continúa a la pantalla de Configuración del Requisito.
		 */
		try {
			$valor_params = guarani_arreglos::aplanar_matriz_sin_nulos($datos, 'parametro_valor');
			$lista_params = implode('|' , $valor_params);
			$this->s__params_req = $lista_params;
			$this->cn()->entidad()->validar_requisito_params($this->s__req_agregado, $this->s__params_req, $valor_params);
			$this->set_pantalla('pant_configurar_requisito');
		} catch (guarani_error $e) {
			$this->datos_temp['form_ml_params_req'] = $datos;	
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__form_ml_params_req__retornar_a_edicion()
	{
		/**
		 * Este evento se dispara cuando el usuario quiso ingresar un requisito-params repetido (ya
		 * asociado a la acción) y se le da la opción de salir a la pantalla de visualización para seleccionarlo 
		 * para su edición.
		 */
		$this->controlador()->set_pantalla('visualizacion');
	}

	//-----------------------------------------------------------------------------------
	//---- form_entidad_estado ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_entidad_estado(toba_ei_formulario $form)
	{
		//El usuario está en la pantalla de Agregar una entidad-estado.
		//Se setean los parámetros necesarios para ser recuperados en el Selector de Entidades invocado por el ef popup
		//de este form. Para esto se recupera primero el vínculo asociado a dicho ef popup.
		if (isset($this->datos_temp['form_entidad_estado'])) {
			$form->set_datos($this->datos_temp['form_entidad_estado']);
		} else {
			toba::memoria()->set_dato('para_requisitos', comunes::si);

			if (isset($this->s__entidad_agregada) || (isset($this->s__entidad_seleccionada))) {
				//Recupera los datos de la Entidad-Estado seleccionada para agregar por el usuario, o seleccionada desde la pantalla de visualización.
				if (isset($this->s__entidad_agregada)) {
					//Estoy en la pantalla de definición de parámetros, o en la pantalla de configuración para una entidad-estado agregada.
					$entidad = $this->s__entidad_agregada;
				} elseif (isset($this->s__entidad_seleccionada)) {
					//Estoy en la pantalla de configuración con una entidad-estado seleccionado por el usuario.
					$entidad = $this->s__entidad_seleccionada;
					$req_x_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
					$this->s__estado_entidad = $req_x_accion['estado'];
					$this->s__regla_entidad = $req_x_accion['regla'];
				}
				$datos_form = array('entidad'=>$entidad, 'estado'=>$this->s__estado_entidad);
				if (isset($this->s__regla_entidad)) {
					$form->ef('regla')->set_estado($this->s__regla_entidad);
				}
				$form->set_datos($datos_form);
			}
		}
	}

	function evt__form_entidad_estado__confirmar($datos)
	{
		try {
			//Se asigna a las variables de sesión los valores de la entidad-estado que está agregando el usuario
			$this->s__entidad_agregada = $datos['entidad'];
			$this->s__estado_entidad = $datos['estado'];
			if (isset($datos['regla'])) {
				$this->s__regla_entidad = $datos['regla'];
			}
			//Se controla que la combinación entidad-estado agregada no esté duplicada (si es así se dispara un error).
			$this->cn()->entidad()->validar_entidad_estado_no_duplicado($this->s__entidad_agregada, $this->s__estado_entidad);
			//En caso de que los datos agregados sean correctos se continúa hacia la pantalla que corresponde.
			$definir_params = false;
			if (isset($this->s__regla_entidad)) {
				$definir_params = toba::consulta_php('co_reglas')->tiene_parametros($this->s__regla_entidad);
			}
			if ($definir_params) {
				$this->set_pantalla('pant_params_ent_estado');
			} else {
				$this->set_pantalla('pant_configurar_ent_estado');
			}
		} catch (guarani_error $e) {
			$this->datos_temp['form_entidad_estado'] = $datos;	
			throw new guarani_error($e->getMessage());
		} 
	}

	function evt__form_entidad_estado__retornar_a_edicion()
	{
		/**
		 * Este evento se dispara cuando el usuario quiso ingresar una entidad-estado repetido (ya
		 * asociado a la acción) y se le da la opción de salir a la pantalla de visualización para 
		 * seleccionarlo para su edición.
		 */
		$this->controlador()->set_pantalla('visualizacion');
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_params_ent ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_params_ent(toba_ei_formulario_ml $form_ml)
	{
		/**
		 * Se diferencia cómo cargar el cuadro de parámetros de la entidad-estado, de acuerdo a si se viene de (o se está en) la 
		 * pantalla de Agregar Entidad-Estado, o si se viene de algunas de las pantallas de Visualización habiendo 
		 * seleccionado una entidad-estado para editar.
		 */
		if (isset($this->s__entidad_agregada)) {
			$entidad = $this->s__entidad_agregada;
		} else {
			//Desde la pantalla de visualización se seleccionó un requisito por acción de tipo 'entidad-estado' y se pasó
			//a la pantalla de Configuración de la Entidad Estado.
			$entidad = $this->s__entidad_seleccionada;
			//Se recuperan los parámetros actuales definidos para el requisito por acción seleccionado, asociados a la regla
			//definida para el mismo (si es que està seteada).
			$datos_req_x_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
			$this->s__regla_entidad = $datos_req_x_accion['regla'];
			$this->s__params_regla = $datos_req_x_accion['parametros'];
		}

		//Se obtienen los parámetros formales de la entidad-estado seleccionada para agregar por el usuario.
		if (isset($this->s__regla_entidad)) {
			$datos_params_regla = toba::consulta_php('co_reglas')->get_regla_params_def($this->s__regla_entidad);
			//Si la variable de sesión que mantiene los parámetros está seteada (quiere decir que el usuario estaba editando
			//y surgió un error en la definición de params, o que se está en la pantalla de configuración, se vuelve a
			//configurar el form con esos valores para su modificación.
			if (isset($this->s__params_regla)) {
				$valor_params = explode('|', $this->s__params_regla);
				if (guarani_arreglos::count($datos_params_regla) == guarani_arreglos::count($valor_params)) {
					$index = 0;
					foreach ($datos_params_regla as $clave=>$param) {
						$datos_params_regla[$clave]['parametro_valor'] = $valor_params[$index];
						$index++;
					}
				}
			}
			$form_ml->set_datos($datos_params_regla);
		}
	}

	function evt__form_ml_params_ent__confirmar($datos)
	{
		/**
		 * El usuario confirma los parámetros actuales definidos para la entidad-estado agregada. Se deben almacenar los 
		 * mismos en la variable de clase: params_regla en la forma en que serán luego almacenados en el requisito por acción, 
		 * es decir en forma de cadena con los valores de los parámetros separados por '|'. 
		 * A continuación se validan los parámetros actuales de acuerdo a los tipos definidoa para los parámetros formales de
		 * la regla asociada a la entidad-estado (y se informa el error si corresponde).
		 * En caso de que los datos ingresados sean correctos, se continúa a la pantalla de Configuración de la Entidad-Estado.
		 */
		try {
			$valor_params = guarani_arreglos::aplanar_matriz_sin_nulos($datos, 'parametro_valor');
			$lista_params = implode('|' , $valor_params);
			$this->s__params_regla = $lista_params;
			//La función validar_entidad_estado_params_tipos invoca a regla::validar_parametro, que se encarga de lanzar el
			//error correspondiente.
			$this->cn()->entidad()->validar_entidad_estado_params_tipos($this->s__regla_entidad, $valor_params);
			$this->set_pantalla('pant_configurar_ent_estado');
		} catch (guarani_error $e) {
			$this->datos_temp['form_ml_params_ent'] = $datos;	
			throw new guarani_error($e->getMessage());
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_aplicacion_req ----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_aplicacion_req(toba_ei_formulario $form)
	{
		/**
		 * Se configuran los datos a partir de la variable de clase que mantiene el nivel de aplicación.
		 * En caso de un requisito o entidad-estado seleccionado, la misma se seteó con el valor correspondiente al 
		 * configurar la pantalla actual. Si el requisito o entidad-estado fue agregado por el usuario, la variable nivel 
		 * no tendrá valor definido hasta que el usuario no lo seleccione.
		 */
		$datos = array();
		if ($this->hay_nivel_tipo_definido()) {
			if ($this->s__nivel['para_todo']) {
				$datos['nivel_aplicacion'] = g3entidad::nivel_aplicacion_tipo_total.','.$this->get_nivel_entidad_tipo();
			} else {
				$datos['nivel_aplicacion'] = $this->get_nivel_tipo().','.$this->get_nivel_entidad_tipo();
			}	
		}
		$form->set_datos($datos);
	}

	function evt__form_aplicacion_req__modificacion($datos)
	{
		$nivel = $datos['nivel_aplicacion'];
		if ($nivel) {
			$nivel_datos = explode(',', $nivel);
			$this->s__nivel['tipo'] = $nivel_datos[0];
			$this->s__nivel['entidad_tipo'] = $nivel_datos[1];
			$this->s__nivel['para_todo'] = false;
			$this->s__nivel['op_not_institucion'] = null;
			//Si es para toda la institución, entonces seteo a nivel entidad para la institucion de la instalacion
			if ($this->s__nivel['tipo'] == g3entidad::nivel_aplicacion_tipo_total) {
				$this->s__nivel['tipo'] = g3entidad::nivel_aplicacion_tipo_entidad;
				$this->s__nivel['entidad_tipo'] = g3entidad::tipo_institucion;
				$this->s__nivel['para_todo'] = true;
			}
			$this->s__nivel['entidad_tipo_datos'] = g3entidad::get_datos_tipo($this->s__nivel['entidad_tipo']);
		} 
	}

	//-----------------------------------------------------------------------------------
	//---- form_aplic_instit ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	//Operador Not Aplicado para el caso en que se configura el requisito para Toda la Institución.
	function conf__form_aplic_instit(toba_ei_formulario $form)
	{
		if (isset($this->s__nivel['op_not_institucion'])) {
			$form->set_datos($this->s__nivel);
		}
	}

	function evt__form_aplic_instit__modificacion($datos)
	{
		 if (isset($datos)) {
		 	$this->s__nivel['op_not_institucion'] = $datos['op_not_institucion'];
		 }
	}
	
	//-----------------------------------------------------------------------------------
	//---- form_ml_subtipos -------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_subtipos(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_temp['form_ml'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['form_ml']);	
		} else {
			if ($this->hay_nivel_tipo_definido()) {
				//Cambio el nombre de la columna
				$form->ef('entidad_subtipo_nombre')->set_etiqueta('Tipos de ' . $this->get_nivel_entidad_tipo_dato('tipo_nombre'));
				// El único tipo de entidad posible es "Propuestas". Los tipos de propuesta se obtienen de la tabla 'sga_propuestas_tipos' (filtrada con perfil de datos).
				$subtipos = guarani::co('co_propuestas')->get_propuestas_tipos_reporte();
				if (isset($this->s__req_seleccionado) || isset ($this->s__entidad_seleccionada)) {
					//El usuario seleccionó un req_x_accion para editar que està aplicado a nivel de subtipos de entidades.
					//Deben recuperarse los registros de requisitos grupos asociados a este requisito-params o entidad-estado.
					$datos_req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
					//Se recuperan todos los requisitos por acción que representen al mismo requisito-params o entidad-estado.
					$requisitos_entidades_coincidentes = $this->cn()->entidad()->get_requisitos_entidades($datos_req_accion);
					//Se recuperan todos los requisitos grupos padres asociados a los requisitos entidades obtenidos, y con
					//qué valor del operador not se asocia el requisito entidad a cada grupo (ese dato está en el req x acción).
					$requisitos_grupos_aplicacion = $this->cn()->entidad()->get_grupos_requisitos_entidades($requisitos_entidades_coincidentes);
					//Determino si los subtipos están cargados para aplicarles el requisito, el not requisito o ninguno.
					foreach ($subtipos as $clave => $subtipo_valor) {
						$marcar = $this->marcar_subtipo_entidad($requisitos_grupos_aplicacion, $subtipo_valor['entidad_subtipo'], 'entidad_subtipo');
						if (!is_null($marcar)) {
							$subtipos[$clave]['operador_not'] = $marcar;
						}
					}
				}
				$form->set_datos($subtipos);
			}
		}
	}

	function evt__form_ml_subtipos__modificacion($datos)
	{
		//Se setea la información del formulario en una variable que los mantiene para acceder ante el evento Confirmar.
		$this->form_ml = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- filtro_entidades -------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function conf__filtro_entidades(guarani_ei_filtro $filtro)
	{
		if (isset($this->s__datos_filtro_entidades)) {
			$filtro->set_datos($this->s__datos_filtro_entidades);
		}
	}

	function evt__filtro_entidades__filtrar($datos)
	{
		if (isset($datos)) {
			$this->s__datos_filtro_entidades = $datos;
			//Cambio la expresion de la columna del filtro según el nivel de aplicación seleccionado por el usuario.
			$this->dependencia('filtro_entidades')->columna('nombre')->set_expresion($this->get_nivel_entidad_tipo_dato('tabla') . '.nombre');
			$this->s__filtro_entidades = $this->dependencia('filtro_entidades')->get_sql_where();
		} else {
			$this->evt__filtro_entidades__cancelar();		
		}
	}

	function evt__filtro_entidades__cancelar()
	{
		unset($this->s__datos_filtro_entidades);
		$this->s__filtro_entidades = '';
	}

	//-----------------------------------------------------------------------------------
	//---- form_ml_entidades ------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function conf__form_ml_entidades(toba_ei_formulario_ml $form)
	{
		if (isset($this->datos_temp['form_ml'])) {
			$this->controlador->set_hubo_cambios();
			$form->set_datos($this->datos_temp['form_ml']);	
		} else {
			if ($this->hay_nivel_tipo_definido()) {
				//Se cambia el nombre de la columna según el tipo del nivel de aplicación.
				$form->ef('entidad_descripcion')->set_etiqueta($this->get_nivel_entidad_tipo_dato('tipo_nombre'));
				$datos_entidades = toba::consulta_php('co_g3entidades')->get_listado_tipo_detallado($this->get_nivel_entidad_tipo(), $this->s__filtro_entidades);
				if (isset($this->s__req_seleccionado) || isset($this->s__entidad_seleccionada)) {
					//El usuario seleccionó un req_x_accion para editar que està aplicado a nivel de entidades.
					//Deben recuperarse los registros de requisitos grupos asociados a este requisito-params o entidad-estado.
					$datos_req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($this->s__id_fila_req_x_accion_seleccionado);
					//Se recuperan todos los requisitos por acción que representen al mismo requisito-params o entidad-estado.
					$requisitos_entidades_coincidentes = $this->cn()->entidad()->get_requisitos_entidades($datos_req_accion);
					//Se recuperan todos los requisitos grupos padres asociados a los requisitos entidades obtenidos, y con
					//qué valor del operador not se asocia el requisito entidad a cada grupo (ese dato está en el req x acción).
					$requisitos_grupos_aplicacion = $this->cn()->entidad()->get_grupos_requisitos_entidades($requisitos_entidades_coincidentes);
					//Determino si las entidades están cargadas para aplicarles el requisito, el not requisito o ninguno.
					foreach ($datos_entidades as $clave => $entidad_valor) {
						$marcar = $this->marcar_subtipo_entidad($requisitos_grupos_aplicacion, $entidad_valor['entidad'], 'entidad');
						if (!is_null($marcar)) {
							$datos_entidades[$clave]['operador_not'] = $marcar;
						}
					}
				}
				$form->set_datos($datos_entidades);
			}
		}
	}

	function evt__form_ml_entidades__modificacion($datos)
	{
		//Se setea la información del formulario en una variable que los mantiene para acceder ante el evento Confirmar.
		$this->form_ml = $datos;
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	function evt__confirmar_configuracion()
	{
		try {
			if ($this->hay_nivel_tipo_definido()) {
				//Se determina el tipo del requisito que se está agregando o editando, y todos los datos del 
				//requisito por acción a agregar.
				if (isset($this->s__req_agregado) || isset($this->s__req_seleccionado)) {
					$tipo_req = requisito::comp_requisito;
					if (isset($this->s__req_agregado)) {
						$req_accion = $this->s__req_agregado;
					} elseif (isset($this->s__req_seleccionado)) {
						$req_accion = $this->s__req_seleccionado;
					}
					$datos_req_accion['req_parametros'] = $this->s__params_req;
				} elseif (isset($this->s__entidad_agregada) || isset($this->s__entidad_seleccionada)) {
					$tipo_req = requisito::comp_entidad_estado;
					if (isset($this->s__entidad_agregada)) {
						$req_accion = $this->s__entidad_agregada;
					} elseif (isset($this->s__entidad_seleccionada)) {
						$req_accion = $this->s__entidad_seleccionada;
					}
					$datos_req_accion['estado'] = $this->s__estado_entidad;
					$datos_req_accion['regla'] = $this->s__regla_entidad;
					$datos_req_accion['regla_parametros'] =	$this->s__params_regla;
				}
				/**
				 * Se invoca al método en el cn entidad que realiza todos los cambios correspondientes en las tablas de 
				 * requisitos por acción y requisitos grupos (bajas y altas de registros), según la nueva configuración
				 * seleccionada para el requisito-entidad que se está editando o agregando. También se realiza la validación
				 * de que la configuración cargada por el usuario seleccione al menos una entidad o subtipo a la que se aplique
				 * el Requisito - Not requisito.
				 */
				$this->cn()->entidad()->setear_datos_configuracion($this->form_ml, $this->s__nivel['tipo'], $this->s__nivel['para_todo'], $this->s__nivel['op_not_institucion'], $this->get_nivel_entidad_tipo_dato('nombre'), $tipo_req, $this->s__id_fila_req_x_accion_seleccionado, $req_accion, $datos_req_accion);
			}
			$this->resetear_variables_sesion();
			$this->controlador()->set_pantalla('visualizacion');
		} catch (guarani_error $e) {
			$this->datos_temp['form_ml'] = $this->form_ml;	
			throw new guarani_error($e->getMessage());
		}
	}

	function evt__cancelar_configuracion()
	{
		$this->resetear_variables_sesion();
		$this->controlador()->set_pantalla('visualizacion');
	}

	
	//-----------------------------------------------------------------------------------
	//---- Consultas AJAX ---------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * El usuario está dando agregando una entidad-estado para asociar a la acción, se valida si la misma entidad-estado
	 * no está ya asociada a la acción en algún requisito_x_accion.
	 */
	function ajax__es_entidad_tipo_modulo($params, toba_ajax_respuesta $respuesta)
	{
		$tipo = toba::consulta_php('co_g3entidades')->get_tipo_entidad($params['entidad']);
		if ($tipo == g3entidad::tipo_modulo) {
			$respuesta->set(true);
		} else {
			$respuesta->set(false);
		}
	}
	
	/**
	 * El usuario está dando agregando una entidad-estado para asociar a la acción, se valida si la misma entidad-estado
	 * no está ya asociada a la acción en algún requisito_x_accion.
	 */
	function ajax__validar_entidad_estado($params, toba_ajax_respuesta $respuesta)
	{
		$mensaje_retorno_edicion = guarani_mensajes::get('800_retornar_a_edicion', array('Entidad-Estado'));
		$this->cn()->entidad()->validar_entidad_estado_no_duplicado($params['entidad'], $params['estado']);
		if (!$this->cn()->entidad()->hay_errores()) {
			$respuesta->set(true);
		} else {
			$msj_error = $this->cn()->entidad()->get_mensaje_error();
			$msj =  $msj_error . ' ' . $mensaje_retorno_edicion;
			$salida = array('mensaje'=>$msj);
			$respuesta->set($salida);
		}
	}

	/**
	 * El usuario está dando agregando un requisito-params para asociar a la acción, se valida si el mismo requisito-params
	 * no está ya asociado a la acción en algún requisito_x_accion.
	 */
	function ajax__validar_requisito_params($params, toba_ajax_respuesta $respuesta)
	{
		$mensaje_retorno_edicion = guarani_mensajes::get('800_retornar_a_edicion', array('Requisito-Parámetros'));
		$this->cn()->entidad()->validar_requisito_params_no_duplicado($this->s__req_agregado, $params['req_params']);
		if (!$this->cn()->entidad()->hay_errores()) {
			$respuesta->set(true);
		} else {
			$msj_error = $this->cn()->entidad()->get_mensaje_error();
			$msj =  $msj_error . ' ' . $mensaje_retorno_edicion;
			$salida = array('mensaje'=>$msj);
			$respuesta->set($salida);
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- JAVASCRIPT -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	function extender_objeto_js()
	{
		parent::extender_objeto_js();
		//Se realiza todo el procesamiento relativo al evento filtrar sólo si la pantalla actual tiene al filtro de 
		//entidades como dependencia asociada.
		if ($this->pantalla()->existe_dependencia('filtro_entidades')) {
			$msj_confirm_filtrar = guarani_mensajes::get('800_confirmar_filtro_pierde_datos');
        	$id_js_filtro = $this->dependencia('filtro_entidades')->get_id_objeto_js();
        	$id_js_form_entidades = $this->dependencia('form_ml_entidades')->get_id_objeto_js();
       
			echo "
				//Chequea si seleccionó algo en el formulario ml de entidades o hizo alguna modificación
				//Si es así, se pide confirmación porque puede perder los datos de lo marcado al filtrar
				//(si el nuevo filtro no incluye a las filas que estaban seleccionadas con Requisito o Not Requisito).
				{$id_js_filtro}.evt__filtrar = function() {
					//Obtiene las filas del formulario multilinea.
					var filas = {$id_js_form_entidades}.filas();
					//Si el usuario realizó cambios desde la carga del formulario debe emitirse el mensaje de confirmación.
					var confirmar_filtrar = {$id_js_form_entidades}.hay_cambios();
					if (confirmar_filtrar) {
						return confirmar_filtrado();
					}
					//Si el usuario no realizó cambios pero el formulario tenía datos cargados (alguna fila chequeada), 
					//también debe emitirse el mensaje de alerta al filtrar.
					for (id_fila in filas) {
						var id_ef = {$id_js_form_entidades}.ef('operador_not').ir_a_fila(filas[id_fila]);
						if (id_ef.tiene_estado()) {
							confirmar_filtrar = true;
							break;
						}
					}
					if (confirmar_filtrar) {
						return confirmar_filtrado();
					}
				}
			
				function confirmar_filtrado(proyecto, operacion, url, es_popup) {
					if (! es_popup || typeof(es_popup) == 'undefined') {
						resp = confirm('$msj_confirm_filtrar');
						if (resp) {
							return true;
						} else {
							return false;
						}
					}
					return true;
				}
				
			";
		}
	}
	
}
?>