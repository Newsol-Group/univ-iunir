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
class ci_vis_requisitos_x_accion extends guarani_pers_ci
{
	
	protected $entidad_seleccionada;
	
	//-----------------------------------------------------------------------------------
	//---- Auxiliares -------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	// Función que permite obtener desde los ci's hijos la acción seleccionada.
	function get_accion_seleccionada()
	{
		return $this->controlador()->get_accion_seleccionada();
	}
	
	/**
	 * Función utilizada para crear el árbol por requisitos. Cuando se agrega un requisito-parámetros o entidad-estado
	 * a nivel de entidades particulares, debe crearse para el requisito raíz un nodo hijo por cada subtipo correspondiente 
	 * a esas entidades (por ejemplo: Grado-Algunas) y un nodo nieto correspondiente a la entidad. La función permite 
	 * verificar si este nodo hijo ya existe para el requisito raíz, y si es así se lo retorna.
	 *
	 * @param str 	$hijo_nombre			Nombre del nodo que representa el subtipo de la entidad.
	 * @param array $hijos_requisito_ent	Nodos hijos (subtipos) del requisito raíz para el cual se quiere crear el nuevo nodo.
	 */
	function existe_subtipo_hijo($hijo_nombre, $hijos_requisito_ent)
	{
		$nodo_subtipo = null;
		foreach ($hijos_requisito_ent as $hijo_subtipo) {
			$hijo_subtipo_nombre = $hijo_subtipo->get_nombre_corto();
			$comparacion = strcmp($hijo_subtipo_nombre, $hijo_nombre);
			if (strcmp($hijo_subtipo_nombre, $hijo_nombre) == 0) {
				$nodo_subtipo = $hijo_subtipo;	
				break;
			}	
		}
		return $nodo_subtipo;
	}
	
	//-----------------------------------------------------------------------------------
	//---- Configuraciones --------------------------------------------------------------
	//-----------------------------------------------------------------------------------
	
	/**
	 * Se ejecuta al inicio de todos los request en donde participa el componente
	 */
	function ini()
	{
		/**
		 * Se modifica el comportamiento del ci de navegacion (seteando no volver al guardar), para que cuando el usuario 
		 * presione el botón Guardar, se permanezca en la pantalla de visualización.
    	 */
		parent::ini();
		$this->controlador()->set_no_volver_al_guardar();
	}
	
	//-----------------------------------------------------------------------------------
	//---- arbol_x_niveles --------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del arbol previo a la generación de la salida
	 * El formato de carga a través del método set_datos es un arreglo de objetos que implementen la interface toba_nodo_arbol
	 */
	function conf__arbol_x_niveles(arbol_requisitos $arbol)
	{
		$raices = array();
		$ids_req_grupos = $this->cn()->entidad()->get_id_filas_requisitos_grupos();
		foreach ($ids_req_grupos as $id_grupo) {
			/**
	 	 	* Por cada grupo de requisitos se crea  una raíz del àrbol con el Requisito o Entidad-Estado y operador Not 
	 	 	* correspondientes.
	 	 	* Las claves que se utilizan para identificar los nodos en el árbol son:
	 	 	* Si el nodo es raíz: 'gr_id del grupo de requisito'.
			* Si el nodo es una hoja del àrbol: 'rn_id del requisito por accion'.
	 		*/
			$grupo = $this->cn()->entidad()->get_datos_requisito_grupo($id_grupo);
			$clave_raiz = 'gr_' . $id_grupo;
			$ocultar_utilerias_hijos = false;
			if (isset($grupo['entidad'])) {
				//Para el caso en que la entidad es la institución gestionante en lugar de mostrar su nombre se muestra Toda la Inst.
				if ($grupo['entidad'] == institucion::get_entidad_gestionante()) {
					$nombre_raiz = 'Para Toda la Institución';	
				} else {
					$nombre_raiz = $grupo['entidad_nombre'];
				}
				$datos_subtipo = toba::consulta_php('co_g3entidades')->get_datos_entidad($grupo['entidad']);
				$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
				if (!in_array($datos_subtipo['entidad_tipo'], $tipos_perfil_datos)) {
					$ocultar_utilerias_hijos = true;
				}
			} elseif (isset($grupo['entidad_subtipo'])) {
				$nombre_raiz = $grupo['subtipo_nombre'];
				$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
				if (!in_array($grupo['entidad_subtipo'], $subtipos_perfil_datos)) {
					$ocultar_utilerias_hijos = true;
				}
			}
			$raiz = new nodo_arbol_requisito($nombre_raiz, null, $clave_raiz);
			$raiz->ocultar_utilerias();
			/**
	 	 	* Por cada requisito acción definido asociado a el grupo actual, se crea un hijo de la raíz generada. 
	 		*/
			$hijos_grupo = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_grupo($id_grupo);
			foreach ($hijos_grupo as $id_req_accion) {
				$req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_req_accion);
				$tipo_req = $req_accion['tipo'];
				$operador_not = $req_accion['operador_not'];
				if ($tipo_req == requisito::comp_requisito) {
					//El requisito por accion es de tipo requisito
					$hijo_clave = 'rn_' . $id_req_accion; 
					//Se arma la lista correspondiente a los paràmetros actuales del requisito, para mostrarlos en el nodo.
					$lista_params = guarani_texto::string_armar_lista($req_accion['parametros']);
					$hijo_nombre = $req_accion['nombre'] . ' ' . $lista_params;	//Nombre del Requisito y paràmetros.
				} elseif ($tipo_req == requisito::comp_entidad_estado) {
					// Si la entidad no es visible por el perfil de datos no se agrega la raíz ni sus hijos.
					if (empty($req_accion['entidad_nombre'])) {
						break;
					}
					//El requisito por accion es de tipo entidad-estado
					$hijo_clave = 'rn_' . $id_req_accion; 
					$hijo_nombre = $req_accion['entidad_nombre'] . ' - ' . $req_accion['estado_nombre'];	//Nombre de la entidad y del estado.
				}
				if ($operador_not) {
					$hijo_nombre = 'NO ' . $hijo_nombre;
				}
				$hijo = new nodo_arbol_requisito($hijo_nombre, $raiz, $hijo_clave);
				$raiz->agregar_hijo($hijo);
				$hijo->set_arbol($arbol);
				if ($ocultar_utilerias_hijos) {
					$hijo->ocultar_utilerias();
				}
			}
			if ($raiz->tiene_hijos_cargados()) {
				$raiz->set_arbol($arbol);
				array_push($raices, $raiz);
			}
		}
		
		$arbol->set_datos($raices);
		$arbol->set_ancho_nombres(85);
	}

	//-----------------------------------------------------------------------------------
	//---- arbol_x_requisitos -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del arbol previo a la generación de la salida
	 * El formato de carga a través del método set_datos es un arreglo de objetos que implementen la interface toba_nodo_arbol
	*/
	function conf__arbol_x_requisitos(arbol_requisitos $arbol)
	{
		/**
	 	 * Se recorre la tabla de Requisitos por Acción.
	 	 * Cada requisito con sus parámetros y su correspondiente operador Not (en true o false), o cada entidad_estado 
	 	 * que sean diferentes, constituyen una raíz del árbol.
	 	 * Luego cada registro en requisitos por acción constituye un hijo de la raíz creada que corresponde a ese 
	 	 * requisito o entidad_estado y operador Not. Si el grupo padre de ese requisito por acción es de tipo 
	 	 * subtipo_entidad se muestra sólo un nodo con el nombre del mismo. Si es de tipo entidad, entonces se crea un 
	 	 * hijo con el nombre del subtipo de entidad que le corresponde, y luego se pone como hijo de este último la 
	 	 * entidad particular a la que se aplica el requisito para esa acción.
	 	*/
		$ids_requisitos_x_accion = $this->cn()->entidad()->get_id_filas_requisitos_x_accion();
		$raices = array();
		/**
	 	 * Las claves que se utilizan para identificar los nodos en el árbol son:
	 	 * Si el nodo es raíz: 'ra_tiporequisito_id del requisito y parámetros o de id de la entidad y del estado_valor del operador 
	 	 * not (true or false)'
	 	 * Si el nodo es un hijo de la raíz pero no es hoja, es decir que representa solo un subtipo de entidad al cual 
	 	 * se aplica el requisito, o del cual cuelgan las entidades a las que se aplica el requisito, el formato de clave es: 
	 	 * 'gr_id del requisito accion_id del grupo de requisito'.
	 	 * Si el nodo es una hoja del àrbol: Se utiliza el mismo formato de clave que el caso anterior.
	 	 * De esta forma se asegura que las claves no vayan a repetirse a lo largo de los nodos del àrbol, y ademàs de contar
	 	 * con toda la información necesaria para construir el àrbol.
	 	*/
		foreach ($ids_requisitos_x_accion as $id_req_accion) {
			$req_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_req_accion);
			$tipo_req = $req_accion['tipo'];
			$operador_not = $req_accion['operador_not'];
			//Obtengo los datos del requisito o entidad estado, según corresponda, asociados a ese requisito por acción.
			if ($tipo_req == requisito::comp_requisito) {
				//El requisito por accion es de tipo requisito
				$requisito = $req_accion['requisito'];
				if (isset($req_accion['parametros'])) {
					$parametros = $req_accion['parametros'];
				} else {
					$parametros = ' ';
				}
				$clave_raiz = 'ra_' . $tipo_req . '_' . $requisito . '_' . $parametros . '_' . $operador_not; 
				//Se arma la lista correspondiente a los paràmetros actuales del requisito, para mostrarlos en la raíz.
				$lista_params = guarani_texto::string_armar_lista($parametros);
				$nombre_raiz = $req_accion['nombre'] . ' ' . $lista_params;	//Nombre del Requisito y paràmetros.
			} elseif ($tipo_req == requisito::comp_entidad_estado) {
				// Si la entidad no es visible por el perfil de datos no se agrega la raíz ni sus hijos.
				if (empty($req_accion['entidad_nombre'])) {
					break;
				}
				//El requisito por accion es de tipo entidad-estado
				$entidad = $req_accion['entidad'];
				$estado = $req_accion['estado'];
				$clave_raiz = 'ra_' . $tipo_req . '_' . $entidad . '_' . $estado . '_' . $operador_not; 
				$nombre_raiz = $req_accion['entidad_nombre'] . ' - ' . $req_accion['estado_nombre'];	//Nombre de la entidad y del estado.
			}
			/**
			 * Controlo si ya existe en el àrbol una raíz con ese requisito-parametros o entidad-estado y ese valor para
			 * el operador not. Si no existe la creo e inserto el nodo como hijo, si ya existe directamente 
			 * le asocio como hijo al nodo.
			 */
			$raiz_existente = guarani_nodos::existe_nodo_raiz($clave_raiz, $raices);
			if (is_null($raiz_existente)) {
				//Creación de la raíz
				if ($operador_not) {
					$nombre_raiz = 'NO ' . $nombre_raiz;
				} 
				$raiz = new nodo_arbol_requisito($nombre_raiz, null, $clave_raiz);
				$raiz->set_arbol($arbol);
				array_push($raices, $raiz);
			} else {
				//Si la raíz ya existe se recupera.
				$raiz = $raiz_existente;
			}
			/**
	 		 * Se crea un hijo para la raíz creada o recuperada. Para esto es necesario chequear si el requisito se aplica 
	 		 * a nivel de subtipo entidad y queda un nodo hoja, o a nivel de entidad con lo cual se crea un hijo para el 
	 		 * subtipo y un nieto por cada entidad del mismo.
	 		*/
			//Obtengo los datos del requisito grupo correspondiente a ese requisito por acción.
			$fila_padre_req_grupo = $this->cn()->entidad()->get_fila_grupo_padre_req_x_accion($id_req_accion);
			$this->cn()->entidad()->set_cursor_requisitos_grupos($fila_padre_req_grupo);
			$grupo_req = $this->cn()->entidad()->get_datos_requisito_grupo();
			if (isset($grupo_req['entidad_subtipo'])) {
				$hijo_clave = 'gr_' . $id_req_accion . '_' . $fila_padre_req_grupo;
				$hijo_nombre = $grupo_req['subtipo_nombre'] . ' - ' . 'Todas';
				$hijo = new nodo_arbol_requisito($hijo_nombre, $raiz, $hijo_clave);
				$hijo->ocultar_utilerias();
				$raiz->agregar_hijo($hijo);
				$hijo->set_arbol($arbol);
				$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
				if (!in_array($grupo_req['entidad_subtipo'], $subtipos_perfil_datos)) {
					$raiz->ocultar_utilerias();
				}
			} elseif (isset($grupo_req['entidad'])) {
				/**
	 	 		 * Si el requisito se aplica a nivel de entidad hay que recuperar o crear el nodo con el subtipo de esa 
	 	 		 * entidad, y crear como hijo del mismo el nodo de la entidad.
	 	 		*/	
				$es_institucion = false;
				$datos_subtipo = toba::consulta_php('co_g3entidades')->get_datos_entidad($grupo_req['entidad']);
				if ($datos_subtipo['entidad_tipo'] == g3entidad::tipo_institucion) {
					//Si el requisito se aplica a Nivel de Toda la Institución no se etiqueta con el nombre de la misma.
					$hijo_nombre = 'Para Toda la Institución';	
					$es_institucion = true;
				} else {
					$hijo_nombre = $datos_subtipo['subtipo_nombre'] . ' - ' . 'Algunas';
				}
				$hijo_clave = 'gr_' . $id_req_accion . '_' . $fila_padre_req_grupo;
				/**
				 * Se chequea si el requisito-parametros o entidad-estado raíz del árbol correspondiente al requisito que 
				 * se está agregando, ya tiene asociado el nodo hijo que representa al subtipo de la entidad a agregar.
				 */
				$hijos_requisito_ent = $raiz->get_hijos();
				$subtipo_hijo = $this->existe_subtipo_hijo($hijo_nombre, $hijos_requisito_ent);
				if (!is_null($subtipo_hijo)) {
					//Si ya se había creado el nodo para ese subtipo solo lo recupero (debe ser el único hijo correspondiente a esa raíz).
					$hijo = $subtipo_hijo;
				} else {
					$hijo = new nodo_arbol_requisito($hijo_nombre, $raiz, $hijo_clave);
					$hijo->ocultar_utilerias();
					$raiz->agregar_hijo($hijo);
					$hijo->set_arbol($arbol);		
				}
				if (!$es_institucion) {
					//Agrego la entidad como nodo hijo del subtipo o nieto de la raíz (requisito), solo en caso de que no sea la Institución.
					if (!empty($grupo_req['entidad_nombre'])) {
						$nieto_clave = 'rn_' . $id_req_accion . '_' . $fila_padre_req_grupo;
						$nieto_nombre = $grupo_req['entidad_nombre'];
						$nieto = new nodo_arbol_requisito($nieto_nombre, $hijo, $nieto_clave);
						$nieto->ocultar_utilerias();
						$hijo->agregar_hijo($nieto);
						$nieto->set_arbol($arbol);
					}
				}
				$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
				if (!in_array($datos_subtipo['entidad_tipo'], $tipos_perfil_datos)) {
					$raiz->ocultar_utilerias();
				}
			}	
		}
		$arbol->set_datos($raices);
		$arbol->set_ancho_nombres(85);
	}

	//-----------------------------------------------------------------------------------
	//---- cuadro_req_x_entidad ---------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del cuadro previo a la generación de la salida
	 * El formato de carga es de tipo recordset: array( array('columna' => valor, ...), ...)
	 */
	function conf__cuadro_req_x_entidad(toba_ei_cuadro $cuadro)
	{
		$etiqueta = guarani_etiquetas::get_tabla('sga_requisitos_aplanado');
		$cuadro->set_eof_mensaje(guarani_mensajes::get('800_eof_cuadro_filtrado', array($etiqueta->get_enf('plural'))));
		$cuadro_requisitos = array();
		if (isset($this->entidad_seleccionada)) {
			//Se recupera el plan version asociado a la entidad recuperada por el selector.
			$datos_entidad = toba::consulta_php('co_planes')->get_planes_versiones(array($this->entidad_seleccionada));
			if (! empty($datos_entidad)) {
				$plan_version = $datos_entidad[0]['plan_version'];
				$accion = $this->cn()->entidad()->get_datos_accion();
				$requisitos_x_accion = toba::consulta_php('co_requisitos_x_accion')->get_requisitos_aplanados_accion_entidad($accion['accion'], $plan_version);
				if (!empty($requisitos_x_accion)) {
					/**
		 		 	* Se obtienen los datos del nombre correpondiente al requisito o a la entidad dependiendo del tipo de requisito
		 		 	* por acción. Ademàs se obtiene la descripciòn del estado de la entidad o se recuperan los paràmetros del 
		 		 	* requisito según corresponda. Se realiza un parsing de la cadena de parámetros actuales para mostrarlos con 
		 		 	* la forma: (p1,p2...pn).
		 			*/
					foreach ($requisitos_x_accion as $requisito_entidad) {
						$tipo = $requisito_entidad['tipo'];
						switch ($tipo) {
							case 'R':
	   							$nombre = $requisito_entidad['requisito_nombre'];
	   							$op_not = $requisito_entidad['operador_not'] ? 'NO' : '';
	   							$lista_params_actuales = guarani_texto::string_armar_lista($requisito_entidad['parametros']);
								$requisito_entidad['reqparams_entestado'] = $op_not . ' ' . $nombre . ' ' . $lista_params_actuales;
	   							break;
							case 'E':
								$datos_entidad = toba::consulta_php('co_g3entidades')->get_datos_entidad($requisito_entidad['entidad']);
								if (empty($datos_entidad)) {
									break 2;
								}
								$nombre = $datos_entidad['entidad_nombre'];
								$op_not = $requisito_entidad['operador_not'] ? 'NO' : '';
								$estado = $requisito_entidad['estado_nombre'];
								$requisito_entidad['reqparams_entestado'] = $op_not . ' ' . $nombre . ' - ' . $estado;
						}
						array_push($cuadro_requisitos, $requisito_entidad);
					}
				}
			}
		}
		$cuadro->set_datos($cuadro_requisitos);
	}

	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * @param array $seleccion Id. de la fila seleccionada
	 */
	function evt__cuadro_req_x_entidad__seleccion($seleccion)
	{
		$req_seleccionado = current($seleccion);
		//Se setea la variable que mantiene el requisito por acción seleccionado en el ci controlador.
		$id_req_seleccionado = $this->cn()->entidad()->get_id_fila_requisito_x_accion($req_seleccionado);
		$this->controlador()->set_visualizador_requisito_seleccionado($id_req_seleccionado);
		//Según el tipo del requisito agregado se va a la pantalla de configuración de requisitos o de entidades-estados.
		$datos_req = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_req_seleccionado);
		if (isset($datos_req['tipo'])) {
			$tipo = $datos_req['tipo'];
			if ($tipo == requisito::comp_requisito) {
				$this->controlador()->set_pantalla_configuracion_req();		
			} elseif ($tipo == requisito::comp_entidad_estado) {
				$this->controlador()->set_pantalla_configuracion_ent_estado();
			}
		}
	}

	function conf_evt__cuadro_req_x_entidad__seleccion(toba_evento_usuario $evento, $fila)
	{
		$datos = $this->dep('cuadro_req_x_entidad')->get_datos();
		if (!empty($datos[$fila]['entidad_grupo'])) {
			$datos_subtipo = toba::consulta_php('co_g3entidades')->get_datos_entidad($datos[$fila]['entidad_grupo']);
			$tipos_perfil_datos = g3entidad::get_tipos_niveles_perfil_datos();
			if (!in_array($datos_subtipo['entidad_tipo'], $tipos_perfil_datos)) {
				$evento->anular();
			}
		} elseif (!empty($datos[$fila]['entidad_subtipo_grupo'])) {
			$subtipos_perfil_datos = g3entidad::get_subtipos_niveles_perfil_datos();
			if (!in_array($datos[$fila]['entidad_subtipo_grupo'], $subtipos_perfil_datos)) {
				$evento->anular();
			}
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_nombre_accion -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del formulario previo a la generación de la salida
	 * El formato del carga debe ser array(<campo> => <valor>, ...)
	 */
	function conf__form_nombre_accion(toba_ei_formulario $form)
	{
		$datos_accion = $this->cn()->entidad()->get_datos_accion();
		if (!empty($datos_accion)) {
			$form->set_datos($datos_accion);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- form_seleccion_ent -----------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Permite cambiar la configuración del formulario previo a la generación de la salida
	 * El formato del carga debe ser array(<campo> => <valor>, ...)
	 */
	function conf__form_seleccion_ent(toba_ei_formulario $form)
	{
		if (isset($this->entidad_seleccionada)) {
			$form->ef('seleccion_entidad')->set_estado($this->entidad_seleccionada);
		}
	}
	
	/**
	 * Atrapa la interacción del usuario con el botón asociado
	 * @param array $datos Estado del componente al momento de ejecutar el evento. El formato es el mismo que en la carga de la configuración
	 */
	function evt__form_seleccion_ent__modificacion($datos)
	{
		//Recupera del popup del form la entidad que el usuario seleccionó para luego cargar el cuadro de entidades.
		if (isset($datos['seleccion_entidad'])) {
			$this->entidad_seleccionada = $datos['seleccion_entidad'];
		}
	}

	//-----------------------------------------------------------------------------------
	//---- Eventos ----------------------------------------------------------------------
	//-----------------------------------------------------------------------------------

	/**
	 * Atrapa la interacción del usuario a través del botón asociado. El método no recibe parámetros
	 */
	function evt__agregar()
	{
		//Se va a la pantalla para agregar Requisitos/Entidades-Estados existentes o crear uno nuevo.
		//Pantalla de Edición del ci padre, y dentro del ci de esa pantalla se setea la pantalla de Agregar como pantalla actual.
		$this->controlador()->set_pantalla('edicion');
	}

	/**
	 * Atrapa la interacción del usuario a través del botón asociado. El método no recibe parámetros
	 */
	function evt__seleccion($id_nodo)
	{
		/**
	 	 * El formato del nodo es: 'ra_tiporeq_idrequisito_parametros o identidad_idestado_operadornot' si el nodo 
	 	 * seleccionado es del àrbol por requisitos, y 'rn_idrequisitoaccion' si el nodo seleccionado es del àrbol 
	 	 * por niveles.
		 */
		$subclaves = explode('_', $id_nodo);
		$id_arbol = $subclaves[0];
		if ($id_arbol == 'ra') {
			//Nodo del Árbol por Requisitos.
			$tipo_req = $subclaves[1];
			if ($tipo_req == requisito::comp_requisito) {
				//El nodo es de tipo Requisito-Parámetros.
				$condicion = array('requisito' => $subclaves[2], 'parametros' => $subclaves[3], 'operador_not' => $subclaves[4]);
				if ($subclaves[3] == ' ') {
					//Si el requisito tiene parámetros vacíos la comparación en la tabla debe hacerse con null.
					$condicion['parametros'] = null;
					$ids = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_condicion_parametros_nulos($condicion);
					$id_req_accion = current($ids);
				} else {
					$ids = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_condicion($condicion);
					$id_req_accion = current($ids);
				}
			} elseif ($tipo_req == requisito::comp_entidad_estado) {
				//El nodo es de tipo Entidad-Estado.
				$condicion = array('entidad' => $subclaves[2], 'estado' => $subclaves[3], 'operador_not' => $subclaves[4]);
				$ids = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_condicion($condicion);
				$id_req_accion = current($ids);
			}
		} elseif ($id_arbol == 'rn') {
			//Nodo del Àrbol por Niveles.
			$id_req_accion = $subclaves[1];
			$datos_req = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_req_accion);
			$tipo_req = $datos_req['tipo'];
		}
		//Se setea la variable que mantiene el requisito por acción seleccionado en el ci controlador.
		$this->controlador()->set_visualizador_requisito_seleccionado($id_req_accion);
		//Según el tipo del requisito agregado se va a la pantalla de configuración de requisitos o de entidades-estados.
		if ($tipo_req == requisito::comp_requisito) {
			$this->controlador()->set_pantalla_configuracion_req();		
		} elseif ($tipo_req == requisito::comp_entidad_estado) {
			$this->controlador()->set_pantalla_configuracion_ent_estado();
		}
	}

	/**
	 * Atrapa la interacción del usuario a través del botón asociado. El método no recibe parámetros
	 */
	function evt__eliminar($id_nodo)
	{
		/**
	 	 * El formato del nodo es: 'ra_tiporeq_idrequisito_parametros o identidad_idestado_operadornot' si el nodo 
	 	 * seleccionado es del àrbol por requisitos, y 'rn_idrequisitoaccion' si el nodo seleccionado es del àrbol 
	 	 * por niveles.
		 */
		$subclaves = explode('_', $id_nodo);
		$id_arbol = $subclaves[0];
		if ($id_arbol == 'ra') {
			//Nodo del Árbol por Requisitos.
			$tipo_req = $subclaves[1];
			if ($tipo_req == requisito::comp_requisito) {
				//El nodo es de tipo Requisito-Parámetros.
				$condicion = array('requisito' => $subclaves[2], 'parametros' => $subclaves[3], 'operador_not' => $subclaves[4]);
				if ($subclaves[3] == ' ') {
					//Si el requisito tiene parámetros vacíos la comparación en la tabla debe hacerse con null.
					$subclaves[3] = null;
					$condicion['parametros'] = null;
					$ids = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_condicion_parametros_nulos($condicion);
					$id_req_accion = current($ids);
				} else {
					$ids = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_condicion($condicion);
					$id_req_accion = current($ids);
				}
			} elseif ($tipo_req == requisito::comp_entidad_estado) {
				//El nodo es de tipo Entidad-Estado.
				$condicion = array('entidad' => $subclaves[2], 'estado' => $subclaves[3], 'operador_not' => $subclaves[4]);
				$ids = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_condicion($condicion);
				$id_req_accion = current($ids);
			}
		} elseif ($id_arbol == 'rn') {
			//Nodo del Àrbol por Niveles.
			$id_req_accion = $subclaves[1];
		}
		/**
		 * Se recuperan los datos del requisito por acción seleccionado para eliminar.
		 */
		$req_x_accion = $this->cn()->entidad()->get_datos_fila_requisito_x_accion($id_req_accion);
		/**
		 * Se deben eliminar todos los requisitos por acción que tengan esa combinación de operador not-requisito-params
		 * u operador not-entidad-estado, según el tipo del requisito por acción. Se obtienen todos los ids de filas de
		 * los requisitos-entidades coincidentes con el requisito por acción a eliminar, y los de sus grupos padres 
		 * correspondientes.
		 */			
		$ids_requisitos_entidades_coincidentes = $this->cn()->entidad()->get_requisitos_entidades($req_x_accion);
		$ids_requisitos_grupos_padres = $this->cn()->entidad()->get_filas_grupos_padres_requisitos_x_accion($ids_requisitos_entidades_coincidentes);
		foreach ($ids_requisitos_entidades_coincidentes as $fila_req_entidad) {
			$this->cn()->entidad()->eliminar_requisito_x_accion($fila_req_entidad);
		}
		/** 
		 * Los requisitos grupos correspondientes a los requisitos por acción eliminados, se eliminan solo si no existen 
		 * otros requisitos por acción que los referencien, es decir si el requisito_grupo ya no tiene hijos.
		 */
		foreach ($ids_requisitos_grupos_padres as $fila_grupo) {
			$hijos_req_grupo = $this->cn()->entidad()->get_id_filas_requisitos_x_accion_grupo($fila_grupo);
			if (empty($hijos_req_grupo)) {
				$this->cn()->entidad()->eliminar_requisito_grupo($fila_grupo);
			}
		}
	}
	
}