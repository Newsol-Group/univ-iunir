<?php 
/**
 * Representa las restricciones aplicadas para realizar una Acción.
 *
 *
 * @package 	Requisitos
 * @subpackage 	Requisitos por Acción
 * 
 */
class cn_ent_requisito_x_accion extends cn_entidad
{
	protected $hay_que_generar_aplanado = true;

	function cargar($id)
	{
		$this->tabla('requisitos_grupos')->persistidor()->activar_perfil_de_datos();
		parent::cargar($id);
	}

	function hay_que_generar_aplanado()
	{
		return $this->hay_que_generar_aplanado;
	}

	function get_datos_accion()
	{
		return $this->tabla('accion')->get();
	}
	
	//-------------------------------------------------------------------
	
	//Permite obtener los ids internos de las filas correpondientes a los grupos de requisitos actuales.
	function get_id_filas_requisitos_grupos()
	{
		return $this->tabla('requisitos_grupos')->get_id_filas();
	}
	
	//Permite obtener los ids internos de las filas correpondientes a los requisitos grupos que verifiquen las condiciones dadas.
	function get_id_filas_requisitos_grupos_condicion($condicion)
	{
		return $this->tabla('requisitos_grupos')->get_id_fila_condicion($condicion, false, false);
	}
	
	function get_cursor_requisitos_grupos()
	{
		return $this->tabla('requisitos_grupos')->get_cursor();	
	}
	
	function set_cursor_requisitos_grupos($id_fila)
	{
		$this->tabla('requisitos_grupos')->set_cursor($id_fila);
	}
	
	function unset_cursor_requisitos_grupos()
	{
		$this->tabla('requisitos_grupos')->resetear_cursor();	
	}
	
	function restaurar_cursor_requisitos_grupos()
	{
		$this->tabla('requisitos_grupos')->restaurar_cursor();	
	}
	
	function agregar_requisito_grupo($datos)
	{
		return $this->tabla('requisitos_grupos')->nueva_fila($datos);
	}
	
	function eliminar_requisito_grupo($id_fila)
	{
		return $this->tabla('requisitos_grupos')->eliminar_fila($id_fila);
	}
	
	/**
	 * Recupera el id interno de la fila del grupo de requisitos correspondiente al nivel de aplicación para el subtipo o 
	 * la entidad dada, si es que existe (comparando con el campo de la tabla dado según corresponda). En caso contrario 
	 * retorna null.
	 */
	function get_id_requisito_grupo_entidad_subtipo($entidad_subtipo, $campo)
	{
		$condicion = array($campo=>$entidad_subtipo);
		$id_grupos = $this->tabla('requisitos_grupos')->get_id_fila_condicion($condicion);
		if (!empty($id_grupos)) {
			return current($id_grupos);
		}
	}

	/**
	 * Recupera los datos del requisito por acción asociado al id de fila interno dado, o en su defecto asociado al 
	 * cursor actual en la tabla, si es que existe uno (si no no se retorna nada).
	 */
	function get_datos_requisito_grupo($id_fila=null)
	{
		if (isset($id_fila)) {
			return $this->tabla('requisitos_grupos')->get_fila($id_fila);
		} else {
			$cursor = $this->get_cursor_requisitos_grupos();
			if (isset($cursor)) {
				return $this->tabla('requisitos_grupos')->get();
			}
		} 
	}
	
	function get_datos_requisitos_grupos()
	{
		return $this->tabla('requisitos_grupos')->get_filas();
	}

	//-------------------------------------------------------------------
	
	//Permite obtener el id interno de la fila correpondiente al requisito por acción con el id dado.
	function get_id_fila_requisito_x_accion($id_req_accion)
	{
		$ids = $this->tabla('requisitos_x_accion')->get_id_fila_condicion(array('requisito_accion'=>$id_req_accion), false);
		if (!empty($ids)) {
			return current($ids);
		}
	}
	
	//Permite obtener los ids internos de las filas correpondientes a los requisitos por acción actuales.
	function get_id_filas_requisitos_x_accion()
	{
		return $this->tabla('requisitos_x_accion')->get_id_filas(false);
	}
	
	//Permite obtener los ids internos de las filas correspondientes a los requisitos por acción hijos del requisito grupo dado.
	function get_id_filas_requisitos_x_accion_grupo($id_fila_grupo)
	{
		$this->set_cursor_requisitos_grupos($id_fila_grupo);
		$ids = $this->tabla('requisitos_x_accion')->get_id_filas();
		$this->restaurar_cursor_requisitos_grupos();
		return $ids;
	}
	
	//Permite obtener los ids internos de las filas correpondientes a los requisitos por acción que verifiquen las condiciones dadas.
	function get_id_filas_requisitos_x_accion_condicion($condicion)
	{
		return $this->tabla('requisitos_x_accion')->get_id_fila_condicion($condicion, false, false);
	}
	
	//Permite obtener los ids internos de las filas correpondientes a los requisitos por acción que verifiquen las 
	//condiciones dadas, incluyendo al campo parámetros que tiene valor nulo.
	function get_id_filas_requisitos_x_accion_condicion_parametros_nulos($condicion)
	{
		return $this->tabla('requisitos_x_accion')->get_id_fila_condicion_campo_nulo($condicion, false, 'parametros');
	}
	
	//Permite obtener el id interno de la fila de la tabla padre requisitos_grupos, correpondiente a la fila de requisitos por acción dada.
	function get_fila_grupo_padre_req_x_accion($id_fila_req)
	{
		return $this->tabla('requisitos_x_accion')->get_id_fila_padre('requisitos_grupos', $id_fila_req);	
	}

	//Permite obtener el id interno de las filas de la tabla padre requisitos_grupos correpondientes a las filas de 
	//requisitos por acción dadas.
	function get_filas_grupos_padres_requisitos_x_accion($ids_filas_req)
	{
		return $this->tabla('requisitos_x_accion')->get_id_padres($ids_filas_req, 'requisitos_grupos');	
	}
	
	function agregar_requisito_x_accion($datos)
	{
		return $this->tabla('requisitos_x_accion')->nueva_fila($datos);
	}
	
	function eliminar_requisito_x_accion($id_fila)
	{
		return $this->tabla('requisitos_x_accion')->eliminar_fila($id_fila);
	}
		
	/** 
	* Retorna los datos de un requisito por acción determinado según el id interno correspondiente a la fila dada.
	*/
	function get_datos_fila_requisito_x_accion($id_fila)
	{
		return $this->tabla('requisitos_x_accion')->get_fila($id_fila);
	}
	
	function get_datos_requisitos_x_accion()
	{
		return $this->tabla('requisitos_x_accion')->get_filas(null, false, false);
	}

	function get_altas_modificaciones_requisitos_x_accion()
	{
		return $this->tabla('requisitos_x_accion')->get_id_filas_a_sincronizar(array('i', 'u'));
	}

	//---------------------------------------------------------------------

	/**
	 * Permite obtener del conjunto de requisitos por acción actual, todos los requisitos que representan el mismo 
	 * requisito-entidad que el requisito por acción dado como parámetro.
	 * Es decir si el requisito por acción es de tipo requisito se comparan el id de requisito y sus parámetros, y si
	 * el requisito por acción es de tipo entidad se comparan el id de entidad y su estado.
	 * @param array $req_x_accion son los datos del requisito por acción que representa el requisito-entidad a recuperar.
	 * @return array conjunto de ids internos de filas correspondientes a los requisitos por acción que representan el mismo
	 * requisito-entidad que el requisito por acción dado.
	 */
	function get_requisitos_entidades($req_x_accion)
	{
		$tipo = $req_x_accion['tipo'];
		if ($tipo == requisito::comp_requisito) {
			$condicion = array('requisito'=>$req_x_accion['requisito'], 'parametros'=>$req_x_accion['parametros']);
			if (!is_null($req_x_accion['parametros'])) {
				$requisitos_entidades = $this->get_id_filas_requisitos_x_accion_condicion($condicion);
			} else {
				$requisitos_entidades = $this->get_id_filas_requisitos_x_accion_condicion_parametros_nulos($condicion);	
			}
		} elseif ($tipo == requisito::comp_entidad_estado) {
			$condicion = array('entidad'=>$req_x_accion['entidad'], 'estado'=>$req_x_accion['estado']);
			$requisitos_entidades = $this->get_id_filas_requisitos_x_accion_condicion($condicion);
		}
		return $requisitos_entidades;
	}

	/**
	 * Retorna los datos correspondientes a los grupos padres de cada requisito por acción dado. Se emplea para 
	 * obtener todos los grupos a los que se aplica un mismo requisito-entidad, con el valor correspondiente al 
	 * operador Not con el que se aplica (esto se obtiene del registro correspondiente en requisitos por acción).
	 * 
	 * @param array $requisitos_entidades conjunto de requisitos por acción para el cual se desea obtener los grupos padres.
	 */
	function get_grupos_requisitos_entidades($requisitos_entidades)
	{
		/**
		 * Se obtienen los id's de los requisitos grupos padres de los requisitos entidades dados.
		 * Se puede utilizar la función get_id_padres sobre el datos tabla (que elimina valores repetidos del resultado)
		 * porque se sabe que los grupos padres de un mismo requisito-entidad (o not requisito-entidad) serán únicos, 
		 * sin existir valores duplicados.
		 */
		$ids_req_grupos = $this->get_filas_grupos_padres_requisitos_x_accion($requisitos_entidades);
		//Se obtienen los datos correspondientes a cada id de fila de requisitos grupos.
		$grupos_requisitos = array();
		$index = 0;
		//A cada grupo debe asociarse el operador not de su requisito entidad hijo (requisito por acción por el cual se
		//llegó al grupo).
		foreach ($ids_req_grupos as $req_grupo) {
			$grupo = $this->get_datos_requisito_grupo($req_grupo);
			$datos_req_entidad = $this->get_datos_fila_requisito_x_accion($requisitos_entidades[$index]);
			$grupo['operador_not'] = $datos_req_entidad['operador_not'];
			$grupos_requisitos[] = $grupo;
			$index++;
		}
		return $grupos_requisitos;
	}

	//---------------------------------------------------------------------
	
	/**
 	  * Realiza todo el procesamiento necesario sobre las tablas de requisitos por acción y requisitos grupos, para 
 	  * guardar la nueva configuración para el requisito-entidad que el usuario está agregando o editando.
      *	@param 	array	datos_requisitos contiene los datos de la nueva configuración (entidades/subtipos a las que se aplica el requisito).
      * Si se aplicó a nivel de Toda la Institución entonces es null.
      * @param	char	aplicacion		 determina si el requisito se configuró a nivel de subtipos o de entidades.
      * @param	char	para_todo		 determina si el requisito se configuró para toda la Institución.
      * @param	integer	op_not_inst	 	 en caso de que se aplique a toda la institución determina si se aplicó Req o No Req, si no es null.
      * @param	char	nombre_entidad_nivel nombre del nivel al que se aplicó la configuración del requisito.
      *	@param 	char	tipo_req		 determina si lo que se está agregando o editando es un requisito o una entidad.	
      * @param	integer id_req_accion_seleccionado	identificador del requisito por acción que se seleccionó en caso de 
      * edición (en caso de no estar en edición su valor es null).
      * @param	integer	req_entidad		identificador del requisito o de la entidad particular que se está agregando o editando.
      * @param	array	$datos_req_entidad	datos del requisito o de la entidad particular que se está agregando o editando.
      * (parámetros, estado, regla, y parámetros de la regla, dependiendo de si el tipo es entidad o requisito).
    */

	function setear_datos_configuracion	($datos_requisitos=null, $aplicacion, $para_todo, $op_not_inst=null, $nombre_entidad_nivel, $tipo_req, $id_req_accion_seleccionado=null, $req_entidad, $datos_req_entidad)
	{
		$this->validar_datos_configuracion($datos_requisitos, $nombre_entidad_nivel);
		if (isset($id_req_accion_seleccionado)) {
			/**
			 * En caso de que se esté editando se recorren los requisitos-entidades que coinciden con el requisito en edición, 
			 * para eliminar todos estos registros. Luego se realiza algo análogo para los requisitos-grupos correspondientes 
			 * a dichos requisitos-entidades, eliminándolos solo si no tienen otros requisitos hijos.
			 */
			$datos_req_accion = $this->get_datos_fila_requisito_x_accion($id_req_accion_seleccionado);
			$requisitos_entidades = $this->get_requisitos_entidades($datos_req_accion);
			$id_requisitos_grupos_aplicacion = $this->get_filas_grupos_padres_requisitos_x_accion($requisitos_entidades);
			foreach ($requisitos_entidades as $id_req_ent) {
				$this->eliminar_requisito_x_accion($id_req_ent);
			}
			foreach ($id_requisitos_grupos_aplicacion as $id_grupo) {
				$hijos_req_grupo = $this->get_id_filas_requisitos_x_accion_grupo($id_grupo);
				if (empty($hijos_req_grupo)) {
					$this->eliminar_requisito_grupo($id_grupo);
				}
			}
		}
		/**
		 * En caso de que se esté agregando un nuevo requisito o entidad-estado, o en caso de edición luego de haber 
		 * eliminado los registros de la configuración anterior, se recorren las filas correspondientes a los datos del
		 * los formularios (de subtipos o de entidades), para dar de alta el registro correspondiente en requisitos
		 * por acción, y en requisitos grupos de ser necesario.
		 */	
		if ($para_todo) {
			/**
			 * Si se está en el caso en que el requisito por acción se aplicó para toda la institución, datos_requisitos es
			 * null. Solo debe darse de alta un requisito grupo para la entidad de la institución gestionante (si es que no
			 * existe), y dar de alta el requisito por acción asociado al mismo con los datos correspondientes.
			 */
			$entidad_nivel = institucion::get_entidad_gestionante();
			$id_fila_grupo = $this->get_id_requisito_grupo_entidad_subtipo($entidad_nivel, 'entidad');
			if (is_null($id_fila_grupo)) {
				//Si no existe el grupo, se crea uno nuevo.
				$id_fila_grupo = $this->agregar_requisito_grupo(array('entidad'=>$entidad_nivel));
			}
			//Se setea el cursor en el grupo que será padre del registro a agregar en requisitos por acción.
			$this->set_cursor_requisitos_grupos($id_fila_grupo);
			//Se recuperan todos los datos para dar de alta el nuevo requisito por acción.
			$nuevo_req['tipo'] = $tipo_req;
			$nuevo_req['operador_not'] = $op_not_inst;
			/**
			 * Es necesario por uniformidad agregar el campo entidad o requisito en nulo, según corresponda porque 
			 * en el ci de edición se utilizan los datos de la tabla requisitos por acción para armar condiciones 
			 * not in, con lo cual es necesario aplanar los arreglos por clave entidad o requisito, y es necesario 
			 * que este campo esté aunque sea nulo.
			*/
			if ($tipo_req == requisito::comp_requisito ) {
				$nuevo_req['requisito'] = $req_entidad;
				$nuevo_req['parametros'] = $datos_req_entidad['req_parametros'];
				$nuevo_req['entidad'] = null;
			} elseif ($tipo_req == requisito::comp_entidad_estado) {
				$nuevo_req['entidad'] = $req_entidad;
				$nuevo_req['estado'] = $datos_req_entidad['estado'];
				$nuevo_req['regla'] = $datos_req_entidad['regla'];
				$nuevo_req['parametros'] = $datos_req_entidad['regla_parametros'];
				$nuevo_req['requisito'] = null;
			}
			$nueva_fila = $this->agregar_requisito_x_accion($nuevo_req);
		} else {
			if ($aplicacion == g3entidad::nivel_aplicacion_tipo_subtipo) {
				//Se agregaron requisitos a nivel de subtipos.
				$campo = 'entidad_subtipo';
			} elseif ($aplicacion == g3entidad::nivel_aplicacion_tipo_entidad) {
				//Se agregaron requisitos a nivel de entidades particulares.
				$campo = 'entidad';
			}
			foreach ($datos_requisitos as $nuevo_req) {
			/**
			 * Si la fila (subtipo o entidad) se seleccionó como parte de la configuración del requisito (ya sea incluyendo 
			 * el requisito o el not requisito), entonces el operador not tiene algún valor para la misma, y por lo tanto, 
			 * no debe crearse un registro en requisitos por acción para dicha fila.
			 */
				if (isset($nuevo_req['operador_not'])) {
				//Se verifica si ya existe el registro correspondiente al grupo al que se está aplicando este requisito.
					$id_fila_grupo = $this->get_id_requisito_grupo_entidad_subtipo($nuevo_req[$campo], $campo);
					if (is_null($id_fila_grupo)) {
						//Si no existe el grupo, se crea uno nuevo.
						$id_fila_grupo = $this->agregar_requisito_grupo(array($campo=>$nuevo_req[$campo]));
					}
					//Se setea el cursor en el grupo que será padre del registro a agregar en requisitos por acción.
					$this->set_cursor_requisitos_grupos($id_fila_grupo);
					//Se recuperan todos los datos para dar de alta el nuevo requisito por acción.
					$nuevo_req['tipo'] = $tipo_req;
					if ($tipo_req == requisito::comp_requisito ) {
						$nuevo_req['requisito'] = $req_entidad;
						$nuevo_req['parametros'] = $datos_req_entidad['req_parametros'];
						$nuevo_req['entidad'] = null;
					} elseif ($tipo_req == requisito::comp_entidad_estado) {
						$nuevo_req['entidad'] = $req_entidad;
						$nuevo_req['estado'] = $datos_req_entidad['estado'];
						$nuevo_req['regla'] = $datos_req_entidad['regla'];
						$nuevo_req['parametros'] = $datos_req_entidad['regla_parametros'];
						$nuevo_req['requisito'] = null;
					}
					$nueva_fila = $this->agregar_requisito_x_accion($nuevo_req);
				}
			}
		}
	}
	
	//---------------------------------------------------------------------

	/**
 	  * Determina si la nueva configuración seteada por el usuario para el requisito-entidad que se está editando
 	  * o agregando a la acción, es una configuración válida.
 	  * @param 	datos_requisitos	array	son los datos de las entidades o subtipos y el operador Not asociado (puede no 
      * tener valor si la entidad o subtipo correspondiente no se seleccionó para aplicarle el requisito).
 	 */
	function validar_datos_configuracion($datos, $nombre_entidad_nivel)
	{
		if (isset($datos)) {
			$this->validar_configuracion_requisito_no_vacia($datos, $nombre_entidad_nivel);
		}
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}	
	}
	
	/**
 	  * Determina si la nueva configuración seteada por el usuario para el requisito-entidad que se está editando
 	  * o agregando a la acción, es una configuración no vacía. Esto significa que el usuario debe haber 
	  * seleccionado alguno de los subtipos o entidades dadas a los que se aplique el requisito o Not Requisito. 
	  * Si no es así, no deben realizarse cambios. Se muestra el error y los datos siguen con los valores previos.
      *	@param 	datos_requisitos	array	son los datos de las entidades o subtipos y el operador Not asociado (puede no 
      * tener valor si la entidad o subtipo correspondiente no se seleccionó para aplicarle el requisito).
    */
	function validar_configuracion_requisito_no_vacia($datos_requisitos, $nombre_entidad_nivel) 
	{
		foreach ($datos_requisitos as $requisito) {
			if (isset($requisito['operador_not'])) {
				//Existe una entidad o subtipo a la cual se aplicó el requisito/not-requisito.
				return;
			}
		}
		//Se arma la etiqueta correspondiente al nivel en el que se está configurando.
		$etiqueta_nivel = guarani_etiquetas::get_concepto($nombre_entidad_nivel);
		$descripcion_entidad_nivel = $etiqueta_nivel->get('un', true); 	
		$this->set_error('800ADM_configuracion_requisito_vacia', array($descripcion_entidad_nivel));
	}
	
	/**
 	  * Determina si la combinación entidad-estado que se quiere asociar a la acción es válida.
      *	@param 	integer	entidad		es el id de la entidad seleccionada por el usuario para agregar como requisito de la acción.
      *	@param 	char	estado		es el id del estado seleccionado por el usuario para agregar como requisito de la acción.
    */
	function validar_entidad_estado($entidad, $estado) 
	{
		$this->validar_entidad_estado_no_duplicado($entidad, $estado);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	/**
 	  * Determina si la combinación entidad-estado no está definida ya como un requisito asociado a la acción
 	  * que se está editando (es decir si no es un registro de la tabla requisitos_x_accion).
      *	@param 	integer	entidad		es el id de la entidad seleccionada por el usuario para agregar como requisito de la acción.
      *	@param 	char	estado		es el id del estado seleccionado por el usuario para agregar como requisito de la acción.
    */
	function validar_entidad_estado_no_duplicado($entidad, $estado) 
	{
		$requisitos = $this->get_datos_requisitos_x_accion();
		foreach ($requisitos as $req) {
			if ($req['tipo'] == requisito::comp_entidad_estado) {
				if ($req['entidad'] == $entidad && $req['estado'] == $estado) {
					$this->set_error('dato_duplicado', array('Entidad-Estado', 'Acción'));
					break;
				}
			}
		}
	}
	
	/**
 	  * Determina si la combinación requisito-parametros que se quiere asociar a la acción es válida.
      *	@param 	integer	requisito		es el id del requisito seleccionado por el usuario para agregar como requisito de la acción.
	  *	@param 	str		lista_params	cadena de parámetros actuales correspondientes al requisito a asociar, separados por coma.
	  * @param 	array	array_params	arreglo con los parámetros actuales correspondientes al requisito a asociar.
    */
	function validar_requisito_params($requisito, $lista_params, $array_params) 
	{
		$this->validar_requisito_params_no_duplicado($requisito, $lista_params);
		$this->validar_requisito_params_tipos($requisito, $array_params);
		if ($this->hay_errores()) {
			throw new guarani_error($this->get_mensaje_error());
		}
	}
	
	/**
 	  * Determina si la combinación requisito-parametros no está definida ya como un requisito asociado a la acción
 	  * que se está editando (es decir si no es un registro de la tabla requisitos_x_accion).
 	  * Solo se realizará este chequeo para los requisitos que tengan parámetros, ya que aquellos que no los tienen,
 	  * si ya están asociados a la acción, directamente no se muestran al usuario, y de esta forma el mismo no tiene la 
 	  * posibilidad de seleccionarlos para agregarlos, con lo cual no pueden duplicarse.
      *	@param 	integer	requisito	es el id del requisito seleccionado por el usuario para agregar a la acción.
      *	@param 	char	params		es la cadena que contiene los parámetro actuales que componen el requisito a asociar a la acción.
      * @return boolean retorna true si el requisito-parametros no está ya asociado a la acción y false en caso contrario.
    */
	function validar_requisito_params_no_duplicado($requisito, $params) 
	{
		$requisitos = $this->get_datos_requisitos_x_accion();
		foreach ($requisitos as $req) {
			if ($req['tipo'] == requisito::comp_requisito) {
				if ($req['requisito'] == $requisito && $req['parametros'] == $params) {
					$this->set_error('dato_duplicado', array('Requisito-Parámetros', 'Acción'));
					break;
				}
			}
		}
	}
	
	/**
 	  * Determina si los parámetros actuales que el usuario ingresó para el requisito agregado, son válidos de acuerdo
 	  * a los tipos indicados para sus parámetros formales.
      *	@param 	integer	requisito	es el id del requisito seleccionado por el usuario para agregar a la acción.
      *	@param 	array	params		arreglo que contiene los parámetro actuales que componen el requisito a asociar a la acción.
      * @return boolean retorna true si los parámetros son de tipo válido de acuerdo a su definición.
    */
	function validar_requisito_params_tipos($requisito, $params)
	{
		$params_def = toba::consulta_php('co_requisitos')->get_requisito_regla_params_def($requisito);
		$index = 0;
		foreach ($params_def as $param => $def) {
			$valor = $params[$index];
			regla::validar_parametro($def, $valor);
			$index++;
		}
	}
	
	/**
 	  * Determina si los parámetros actuales que el usuario ingresó para la entidad-estado agregada, son válidos de acuerdo
 	  * a los tipos indicados para los parámetros formales de su regla asociada.
      *	@param 	integer	regla	es el id de la regla seleccionada por el usuario para asociar a la entidad-estado que desea agregar.
      *	@param 	array	params	arreglo que contiene los parámetro actuales que componen el requisito a asociar a la acción.
      * @return boolean retorna true si los parámetros son de tipo válido de acuerdo a su definición.
    */
	function validar_entidad_estado_params_tipos($regla, $params)
	{
		$params_def = toba::consulta_php('co_reglas')->get_regla_params_def($regla);
		$index = 0;
		foreach ($params_def as $param => $def) {
			$valor = $params[$index];
			regla::validar_parametro($def, $valor);
		}
	}

}
?>