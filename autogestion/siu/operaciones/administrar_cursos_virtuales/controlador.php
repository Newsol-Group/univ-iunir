<?php
namespace siu\operaciones\administrar_cursos_virtuales;

use siu\extension_kernel\controlador_g3w2;
use SIU\Chulupi\kernel;
use siu\guarani;
use SIU\Chulupi\util\validador;

class controlador extends controlador_g3w2
{
	
	protected $hash_comision;
	protected $params_comision;
	protected $es_subcomision;
	
	protected $mensajes;
	protected $valores_campos;
	protected $errores_campos;

	/**
	 * @return \siu\modelo\transacciones\cursos_virtuales
	 */
	function modelo()
	{
		return guarani::cursos_virtuales();
	}

	function get_info_zona()
	{
		if (isset($this->params_comision['comision'])) { //si no esta seleccionado nada, no activo la zona
			return array('zona' => 'comision', 'seleccion' => $this->params_comision);
		}
	}
	
	function get_hash_comision()
	{
		return $this->hash_comision;
	}
	
	function get_params_comision()
	{
		return $this->params_comision;
	}

	function es_subcomision()
	{
		return $this->es_subcomision;
	}
	
	function accion__index()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->get_hash_comision();
		$this->modelo()->set_comision($comision);
		
		// Aquí se borra la plataforma seleccionada de la sesión
		$this->modelo()->unset_plataforma_seleccionada();
		
		$datos_curso_virtual = $this->modelo()->get_datos_curso_virtual();

		// Si la Comisión no posee un curso asociado
		if (empty($datos_curso_virtual)) {
			
			$plataformas = $this->modelo()->get_plataformas();
			// Si hay mas de una plataforma
			if (\guarani_arreglos::count($plataformas) > 1) {
				
				// Lo llevo a la acción 'seleccionar_plataforma' para que elija.
				$url_seleccionar_plataforma = kernel::vinculador()->crear($operacion, 'seleccionar_plataforma', $hash_comision);
				header("Location: $url_seleccionar_plataforma");
				exit();
			
			} else {// Si hay una sola plataforma tomo la primera
				$this->modelo()->set_datos_plataforma(current($plataformas));
			}
		} else {// Si la Comisión tiene un curso asociado tomo la plataforma de dicho curso
			$datos_plataforma = [];
			$datos_plataforma['plataforma'] = $datos_curso_virtual['plataforma'];
			$datos_plataforma['nombre'] = $datos_curso_virtual['plataforma_nombre'];
			$datos_plataforma['url'] = $datos_curso_virtual['plataforma_url'];
			$datos_plataforma['host'] = $datos_curso_virtual['plataforma_host'];
			$datos_plataforma['token'] = $datos_curso_virtual['plataforma_token'];
			$datos_plataforma['rol_alumno'] = $datos_curso_virtual['plataforma_rol_alumno'];
			$datos_plataforma['rol_alumno_curso'] = $datos_curso_virtual['plataforma_rol_alumno_curso'];
			$datos_plataforma['categoria_curso_default'] = $datos_curso_virtual['plataforma_categoria_curso_default'];
			$this->modelo()->set_datos_plataforma($datos_plataforma);
		}
		
		// Lo llevo a la acción 'home' para crear o vincular con un curso de Moodle.
		$url_home = kernel::vinculador()->crear($operacion, 'home', $hash_comision);
		header("Location: $url_home");
		exit();
		
	}
	
	function accion__home()
	{
		$this->load_params();

		$comision = $this->params_comision['comision'];
		$this->modelo()->cargar_contexto_comision($comision);
		
		$datos_plataforma = $this->modelo()->get_datos_plataforma();
		
		$plataforma_ok = false;
		$faltan_funciones = false;
		$tiene_actas_cerradas = false;
		
		// Si no existe una plataforma asociada a la Comisiï¿½n
		if (empty($datos_plataforma)) {

			$plataforma_ok = false;
			
		} else {

			// Si se logra establecer conexiï¿½n
			if ($this->modelo()->get_moodle_obj()->verificarConexion()) {
				
				$funciones_faltantes_moodle = $this->modelo()->get_funciones_faltantes_moodle();
				
				// Si todas las funciones requeridas fueron habilitadas
				if (empty($funciones_faltantes_moodle)) {
					$plataforma_ok = true;
					$faltan_funciones = false;
                                        // Si se trata de una comisiÃ³n con actas cerradas salgo

                                        if ($this->modelo()->validar_tiene_actas_cerradas($comision))
                                        { 
                                            $plataforma_ok = false;
                                            $tiene_actas_cerradas = true;
                                        }  
				} else { // si faltan habilitar funciones
					$plataforma_ok = false;
					$faltan_funciones = true;
				}
				
			} else {
				$plataforma_ok = false;
			}
			
		}

		// Si existe una plataforma asociada a la Comisiï¿½n y se logra establecer conexiï¿½n con ella
		if ($plataforma_ok) {

			$datos_curso_virtual = $this->modelo()->get_datos_curso_virtual();
			
			// Si la Comisiï¿½n no posee un curso asociado
			if (empty($datos_curso_virtual)) {

				$this->vista()->set_pagelet('sin_curso');
			
			} else {// Si la Comisiï¿½n posee un curso asociado
				
				$info_curso = $this->modelo()->get_info_curso_virtual();
				
				// Verifico que el curso verdaderamente exista en Moodle
				if (is_null($info_curso)) {
					
					// Si el curso no existe en Moodle lo elimino en Guaranï¿½
					$this->modelo()->eliminar_curso_guarani($datos_curso_virtual['curso']);
					$this->vista()->set_pagelet('sin_curso');
					
				} else {// Si el curso existe en Moodle, muestro informaciï¿½n del mismo
					
					$this->vista()->set_pagelet('con_curso');
					
				}
				
			}
			
		} elseif ($faltan_funciones) { // Si faltan habilitar funciones en Moodle
			
			$this->vista()->set_pagelet('faltan_funciones');
			
		} elseif ($tiene_actas_cerradas) { // Si tiene actas cerradas
			
			$this->vista()->set_pagelet('actas_cerradas');
			
		}  else { // Si no existe una plataforma asociada a la Comisiï¿½n o no se logra establecer conexiï¿½n
			
			$this->vista()->set_pagelet('sin_plataforma');
			
		}
	
	}
	
	function accion__seleccionar_plataforma()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$this->modelo()->set_comision($comision);
		
		// Limpio los mensajes, errores y valores
		$this->mensajes = [];
		$this->valores_campos = [];
		$this->errores_campos = [];
		
		// Si esta guardando
		if (kernel::request()->isPost()) {
			
			$plataforma	= $this->validate_param('plataforma', 'post', validador::TIPO_TEXTO, array('default' => null));
			
			kernel::log()->add_debug("=== plataforma ===", $plataforma);
			
			$datos_ok = $this->validar_datos_seleccionar_plataforma($plataforma);
			
			// Si falló la validación de datos
			if (!$datos_ok) {
				
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.datos_incorrectos'),
					'tipo' => 'alert-error'
				];
				kernel::sesion()->set('mensajes_seleccionar_plataforma', $this->mensajes);
				kernel::sesion()->set('valores_campos', $this->valores_campos);
				kernel::sesion()->set('errores_campos', $this->errores_campos);
				$this->vista()->set_pagelet('seleccion_plataforma');
				return false;
			}
			
			$this->modelo()->set_plataforma_seleccionada($plataforma);
			
			$operacion = kernel::ruteador()->get_id_operacion();
			$hash_comision = $this->get_hash_comision();
			$url_home = kernel::vinculador()->crear($operacion, 'home', $hash_comision);
			header("Location: $url_home");
			exit();
			
				
		} else {// Si esta cargando el formulario
			
			$this->vista()->set_pagelet('seleccion_plataforma');
			return false;
			
		}
	}
	
	function validar_datos_seleccionar_plataforma($plataforma)
	{
		$datos_ok = true;
		
		$this->valores_campos['plataforma'] = $plataforma;
		
		$plataformas = $this->modelo()->get_plataformas();
		$plataformas_id = array_column($plataformas, 'plataforma');

		if (is_null($plataforma)) {
			$this->errores_campos['curso'] = kernel::traductor()->trans('moodle.campo_obligatorio');
			$datos_ok = false;
		} elseif (!in_array($plataforma, $plataformas_id)) {
			$this->errores_campos['plataforma'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}
		
		return $datos_ok;
	}

	function accion__crear()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$this->modelo()->cargar_contexto_comision($comision);
		
		// Limpio los mensajes, errores y valores
		$this->mensajes = [];
		$this->valores_campos = [];
		$this->errores_campos = [];
		
		// Si esta guardando
		if (kernel::request()->isPost()) {
			
			$nombre				= $this->validate_param('nombre', 'post', validador::TIPO_TEXTO, array('default' => ""));
			$nombre_abrev		= $this->validate_param('nombre_abrev', 'post', validador::TIPO_TEXTO, array('default' => ""));
			$categoria			= $this->validate_param('categoria', 'post', validador::TIPO_TEXTO, array('default' => null));
			$descripcion		= $this->validate_param('descripcion', 'post', validador::TIPO_TEXTO, array('default' => ""));
			$visible			= $this->validate_param('visible', 'post', validador::TIPO_TEXTO, array('default' => 0));
			$fecha_inicio		= $this->validate_param('fecha_inicio', 'post', validador::TIPO_TEXTO, array('default' => null));
			$fecha_fin			= $this->validate_param('fecha_fin', 'post', validador::TIPO_TEXTO, array('default' => null));
			$importar			= $this->validate_param('importar', 'post', validador::TIPO_TEXTO, array('default' => 0));
			$copiar_de			= $this->validate_param('copiar_de', 'post', validador::TIPO_TEXTO, array('default' => null));
			$matricular_alumnos	= $this->validate_param('matricular_alumnos', 'post', validador::TIPO_TEXTO, array('default' => 0));
			
			$this->limpiar_datos_crear($nombre, $nombre_abrev, $descripcion, $fecha_inicio, $fecha_fin);
			
			kernel::log()->add_debug("=== nombre ===", $nombre);
			kernel::log()->add_debug("=== nombre_abrev ===", $nombre_abrev);
			kernel::log()->add_debug("=== categoria ===", $categoria);
			kernel::log()->add_debug("=== descripcion ===", $descripcion);
			kernel::log()->add_debug("=== visible ===", $visible);
			kernel::log()->add_debug("=== fecha_inicio ===", $fecha_inicio);
			kernel::log()->add_debug("=== fecha_fin ===", $fecha_fin);
			kernel::log()->add_debug("=== importar ===", $importar);
			kernel::log()->add_debug("=== copiar_de ===", $copiar_de);
			kernel::log()->add_debug("=== matricular_alumnos ===", $matricular_alumnos);
			
			$datos_ok = $this->validar_datos_crear($nombre, $nombre_abrev, $categoria, $descripcion, $visible, $importar, $copiar_de, $matricular_alumnos, $fecha_inicio, $fecha_fin);
			
			// Si fallÃ³ la validaciÃ³n de datos
			if (!$datos_ok) {
				
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.datos_incorrectos'),
					'tipo' => 'alert-error'
				];
				kernel::sesion()->set('mensajes_crear', $this->mensajes);
				kernel::sesion()->set('valores_campos', $this->valores_campos);
				kernel::sesion()->set('errores_campos', $this->errores_campos);
				$this->vista()->set_pagelet('crear');
				return false;
			}
			
			list($crear_curso_ok, $messageMoodle) = $this->crear_curso_virtual($nombre, $nombre_abrev, $categoria, $descripcion, $visible, $importar, $copiar_de, $matricular_alumnos, $fecha_inicio, $fecha_fin);
			
			// Si el curso se creo bien
			if ($crear_curso_ok) {
				
				kernel::sesion()->set('mensajes_con_curso', $this->mensajes);
				$operacion = kernel::ruteador()->get_id_operacion();
				$hash_comision = $this->get_hash_comision();
				$url_home = kernel::vinculador()->crear($operacion, 'home', $hash_comision);
				header("Location: $url_home");
				exit();
			
			} else {//sino
				
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.crear.fallo_creacion_curso_moodle', ['%moodle_msj%' => $messageMoodle]),
					'tipo' => 'alert-error'
				];
				kernel::sesion()->set('mensajes_crear', $this->mensajes);
				kernel::sesion()->set('valores_campos', $this->valores_campos);
				kernel::sesion()->set('errores_campos', $this->errores_campos);
				$this->vista()->set_pagelet('crear');
				return false;
				
			}
				
		} else {// Si esta cargando el formulario
			
			$this->vista()->set_pagelet('crear');
			return false;
			
		}
	}

	function limpiar_datos_crear(&$nombre, &$nombre_abrev, &$descripcion, &$fecha_inicio = null, &$fecha_fin = null)
	{
		$nombre = trim($nombre);
		$nombre_abrev = trim($nombre_abrev);
		$descripcion = trim($descripcion);
		if (!is_null($fecha_inicio)) $fecha_inicio = trim($fecha_inicio);
		if (!is_null($fecha_fin)) $fecha_fin = trim($fecha_fin);
	}
	
	function validar_datos_crear($nombre, $nombre_abrev, $categoria, $descripcion, $visible, $importar, $copiar_de, $matricular_alumnos, $fecha_inicio = null, $fecha_fin = null)
	{
		$datos_ok = true;
		$formato_fecha = 'd/m/Y';
		
		$this->valores_campos['nombre'] = $nombre;
		$this->valores_campos['nombre_abrev'] = $nombre_abrev;
		$this->valores_campos['categoria'] = $categoria;
		$this->valores_campos['descripcion'] = $descripcion;
		$this->valores_campos['visible'] = $visible;
		$this->valores_campos['importar'] = $importar;
		$this->valores_campos['copiar_de'] = $copiar_de;
		$this->valores_campos['matricular_alumnos'] = $matricular_alumnos;
		$this->valores_campos['fecha_inicio'] = $fecha_inicio;
		$this->valores_campos['fecha_fin'] = $fecha_fin;

                // Si "Nombre del curso" es vacío
		if (empty($nombre)) {
			$this->errores_campos['nombre'] = kernel::traductor()->trans('moodle.campo_obligatorio');
			$datos_ok = false;
		} // Si "Nombre del curso" excede su maxlength
                elseif (strlen($nombre) > \moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH) {
			$this->errores_campos['nombre'] = kernel::traductor()->trans('moodle.campo_excede_maxlength', ['%cant%' => \moodle::NOMBRE_COMPLETO_CURSO_MAXLENGTH]);
			$datos_ok = false;
		}

                // Si "Nombre abreviado" es vacío
		if (empty($nombre_abrev)) {
			$this->errores_campos['nombre_abrev'] = kernel::traductor()->trans('moodle.campo_obligatorio');
			$datos_ok = false;
		} // Si "Nombre abreviado" excede su maxlength
                elseif (strlen($nombre_abrev) > \moodle::NOMBRE_CORTO_CURSO_MAXLENGTH) {
			$this->errores_campos['nombre_abrev'] = kernel::traductor()->trans('moodle.campo_excede_maxlength', ['%cant%' => \moodle::NOMBRE_CORTO_CURSO_MAXLENGTH]);
			$datos_ok = false;
		}
		
		$categorias = $this->modelo()->get_categorias_cursos_moodle();
		$categorias_id = array_column($categorias, 'id');
		if (is_null($categoria) || !in_array($categoria, $categorias_id)) {
			$this->errores_campos['categoria'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}
		
		if (empty($descripcion)) {
			$this->errores_campos['descripcion'] = kernel::traductor()->trans('moodle.campo_obligatorio');
			$datos_ok = false;
		}
		
		if (!in_array($visible, ["0", "1"])) {
			$this->valores_campos['visible'] = 1;
			$this->errores_campos['visible'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}
		
		// Si ingresÃ³ "Fecha Fin" sin ingresar "Fecha Inicio"
		if (is_null($fecha_inicio) && !is_null($fecha_fin)) {
			$this->errores_campos['fecha_fin'] = kernel::traductor()->trans('moodle.fecha_fin_sin_fecha_inicio');
			$datos_ok = false;
		}
		
		if (!is_null($fecha_inicio) && !validar_fecha($fecha_inicio, $formato_fecha)) {
			$this->valores_campos['fecha_inicio'] = '';
			$this->errores_campos['fecha_inicio'] = kernel::traductor()->trans('moodle.fecha_invalida');
			$datos_ok = false;
		}
		
		if (!is_null($fecha_fin) && !validar_fecha($fecha_fin, $formato_fecha)) {
			$this->valores_campos['fecha_fin'] = '';
			$this->errores_campos['fecha_fin'] = kernel::traductor()->trans('moodle.fecha_invalida');
			$datos_ok = false;
		}
		
		if ((!is_null($fecha_inicio) && validar_fecha($fecha_inicio, $formato_fecha)) && (!is_null($fecha_fin) && validar_fecha($fecha_fin, $formato_fecha))) {
			// Si la "fecha inicio" es mayor a la "fecha fin"
			if (!validar_fecha_inicio_fin($fecha_inicio, $fecha_fin, $formato_fecha)) {
				$this->errores_campos['fecha_fin'] = kernel::traductor()->trans('moodle.fecha_inicio_mayor_fecha_fin');
				$datos_ok = false;
			}
		}
		
		if (!in_array($importar, ["0", "1"])) {
			$this->valores_campos['importar'] = 0;
			$this->errores_campos['importar'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}
		
		// Si "Importar Contenido de otro Curso" esta marcado valido "Copiar Contenido de"
		if ($importar == "1") {
			
			$cursos = $this->modelo()->get_cursos_moodle_de_docentes();
			$cursos_id = array_column($cursos, 'id');
		
			if (is_null($copiar_de)) {
				$this->errores_campos['copiar_de'] = kernel::traductor()->trans('moodle.campo_obligatorio');
				$datos_ok = false;
			} elseif (!in_array($copiar_de, $cursos_id)) {
				$this->errores_campos['copiar_de'] = kernel::traductor()->trans('moodle.dato_invalido');
				$datos_ok = false;
			}
			
		}
		
		if (!in_array($matricular_alumnos, ["0", "1"])) {
			$this->valores_campos['matricular_alumnos'] = 0;
			$this->errores_campos['matricular_alumnos'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}
		
		return $datos_ok;
	}
	
	function crear_curso_virtual($nombre, $nombre_abrev, $categoria, $descripcion, $visible, $importar, $copiar_de, $matricular_alumnos, $fecha_inicio = null, $fecha_fin = null)
	{
		
		$this->mensajes = [];
		
		$startdate = null;
		$enddate = null;
		
		// Si ingresÃ³ "Fecha Inicio"
		if (!is_null($fecha_inicio)) {
			
			$fecha_inicio_obj = \DateTime::createFromFormat('d/m/Y', $fecha_inicio);
			$startdate = $fecha_inicio_obj->getTimestamp();
			
			// Si ingresÃ³ "Fecha Inicio" y "Fecha Fin"
			if (!is_null($fecha_fin)) {
				$fecha_fin_obj = \DateTime::createFromFormat('d/m/Y', $fecha_fin);
				$enddate = $fecha_fin_obj->getTimestamp();
			}
			
		}

		list($crear_curso_ok, $messageMoodle) = $this->modelo()->crear_curso_moodle(
				$nombre, 
				$nombre_abrev, 
				$categoria, 
				nl2br($descripcion), 
				$visible, 
				$startdate, 
				$enddate
			);

		// Si el curso se creo bien
		if ($crear_curso_ok) {

			$this->mensajes[] = [
				'mensaje' => kernel::traductor()->trans('moodle.crear.creacion_exitosa_curso_moodle'),
				'tipo' => 'alert-success'
			];
			
			$this->modelo()->limpiar_datos_curso_virtual();
			$comision = $this->params_comision['comision'];
			$this->modelo()->cargar_contexto_comision($comision);

			// Si se debe importar contenido de otro curso
			if ($importar) {
				
				try{
					
					$this->modelo()->importar_contenido_curso($copiar_de);
				
				// Si fallÃ³ la importaciÃ³n se notifica
				} catch (\Exception $e) {
					
					$this->mensajes[] = [
						'mensaje' => kernel::traductor()->trans('moodle.crear.fallo_importacion_curso_moodle', ['%moodle_msj%' => $e->getMessage()]),
						'tipo' => 'alert-warning'
					];
					
				}
				
			}
		
			// Se matriculan los docentes
			list($actualizar_docentes_ok, $actualizar_docentes_msgs) = $this->modelo()->actualizar_docentes_moodle();
			if (!$actualizar_docentes_ok) {// Si fallÃ³ la actualizaciÃ³n de los docentes
				foreach ($actualizar_docentes_msgs as $actualizar_docentes_msg) {
					$this->mensajes[] = [
						'mensaje' => $actualizar_docentes_msg,
						'tipo' => 'alert-error'
					];
				}
			}
			$this->modelo()->limpiar_docentes();

			// Si se deben matricular los alumnos
			if ($matricular_alumnos) {
				// Se matriculan los alumnos
				list($actualizar_alumnos_ok, $actualizar_alumnos_msgs) = $this->modelo()->actualizar_alumnos_moodle();
				if (!$actualizar_alumnos_ok) {// Si fallÃ³ la actualizaciÃ³n de los alumnos
					foreach ($actualizar_alumnos_msgs as $actualizar_alumnos_msg) {
						$this->mensajes[] = [
							'mensaje' => $actualizar_alumnos_msg,
							'tipo' => 'alert-error'
						];
					}
				}
				$this->modelo()->limpiar_alumnos();
			}

		}
		
		return [$crear_curso_ok, $messageMoodle];
		
	}

	
	function accion__vincular()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$this->modelo()->cargar_contexto_comision($comision);
		
		// Limpio los mensajes, errores y valores
		$this->mensajes = [];
		$this->valores_campos = [];
		$this->errores_campos = [];
		
		// Si esta guardando
		if (kernel::request()->isPost()) {
			
			$curso				= $this->validate_param('curso', 'post', validador::TIPO_TEXTO, array('default' => null));
			$matricular_alumnos	= $this->validate_param('matricular_alumnos', 'post', validador::TIPO_TEXTO, array('default' => 0));
			
			kernel::log()->add_debug("=== curso ===", $curso);
			kernel::log()->add_debug("=== matricular_alumnos ===", $matricular_alumnos);
			
			$datos_ok = $this->validar_datos_vincular($curso, $matricular_alumnos);
			
			// Si fallÃ³ la validaciÃ³n de datos
			if (!$datos_ok) {
				
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.datos_incorrectos'),
					'tipo' => 'alert-error'
				];
				kernel::sesion()->set('mensajes_vincular', $this->mensajes);
				kernel::sesion()->set('valores_campos', $this->valores_campos);
				kernel::sesion()->set('errores_campos', $this->errores_campos);
				$this->vista()->set_pagelet('vincular');
				return false;
			}
			
			$vincular_curso_ok = $this->vincular_curso_virtual($curso, $matricular_alumnos);
			
			// Si el curso se vinculo bien
			if ($vincular_curso_ok) {
				
				kernel::sesion()->set('mensajes_con_curso', $this->mensajes);
				$operacion = kernel::ruteador()->get_id_operacion();
				$hash_comision = $this->get_hash_comision();
				$url_home = kernel::vinculador()->crear($operacion, 'home', $hash_comision);
				header("Location: $url_home");
				exit();
			
			} else {//sino
				
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.vincular.fallo_vinculacion_curso_moodle'),
					'tipo' => 'alert-error'
				];
				kernel::sesion()->set('mensajes_vincular', $this->mensajes);
				kernel::sesion()->set('valores_campos', $this->valores_campos);
				kernel::sesion()->set('errores_campos', $this->errores_campos);
				$this->vista()->set_pagelet('vincular');
				return false;
				
			}
			
				
		} else {// Si esta cargando el formulario
			
			$this->vista()->set_pagelet('vincular');
			return false;
			
		}
	}
	
	function validar_datos_vincular($curso, $matricular_alumnos)
	{
		$datos_ok = true;
		
		$this->valores_campos['curso'] = $curso;
		$this->valores_campos['matricular_alumnos'] = $matricular_alumnos;
		
		$cursos = $this->modelo()->get_cursos_moodle_de_docentes();
		$cursos_id = array_column($cursos, 'id');

		if (is_null($curso)) {
			$this->errores_campos['curso'] = kernel::traductor()->trans('moodle.campo_obligatorio');
			$datos_ok = false;
		} elseif (!in_array($curso, $cursos_id)) {
			$this->errores_campos['curso'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}

		if (!in_array($matricular_alumnos, ["0", "1"])) {
			$this->valores_campos['matricular_alumnos'] = 0;
			$this->errores_campos['matricular_alumnos'] = kernel::traductor()->trans('moodle.dato_invalido');
			$datos_ok = false;
		}
		
		return $datos_ok;
	}
	
	function vincular_curso_virtual($curso, $matricular_alumnos)
	{
		
		$this->mensajes = [];
		
		$vincular_curso_ok = $this->modelo()->vincular_curso_virtual($curso);
		
		// Si el curso se vinculo bien
		if ($vincular_curso_ok) {

			$this->mensajes[] = [
				'mensaje' => kernel::traductor()->trans('moodle.vincular.vinculacion_exitosa_curso_moodle'),
				'tipo' => 'alert-success'
			];
		
			$this->modelo()->limpiar_datos_curso_virtual();
			$this->modelo()->limpiar_docentes();
			$this->modelo()->limpiar_alumnos();
			$comision = $this->params_comision['comision'];
			$this->modelo()->cargar_contexto_comision($comision);
		
			// Se matriculan los docentes
			list($actualizar_docentes_ok, $actualizar_docentes_msgs) = $this->modelo()->actualizar_docentes_moodle();
			if (!$actualizar_docentes_ok) {// Si fallÃ³ la actualizaciÃ³n de los docentes
				foreach ($actualizar_docentes_msgs as $actualizar_docentes_msg) {
					$this->mensajes[] = [
						'mensaje' => $actualizar_docentes_msg,
						'tipo' => 'alert-error'
					];
				}
			}
			$this->modelo()->limpiar_docentes();

			// Si se deben matricular los alumnos
			if ($matricular_alumnos) {
				// Se matriculan los alumnos
				list($actualizar_alumnos_ok, $actualizar_alumnos_msgs) = $this->modelo()->actualizar_alumnos_moodle();
				if (!$actualizar_alumnos_ok) {// Si fallÃ³ la actualizaciÃ³n de los alumnos
					foreach ($actualizar_alumnos_msgs as $actualizar_alumnos_msg) {
						$this->mensajes[] = [
							'mensaje' => $actualizar_alumnos_msg,
							'tipo' => 'alert-error'
						];
					}
				}
				$this->modelo()->limpiar_alumnos();
			}

		}
		
		return $vincular_curso_ok;
	}
	
	function accion__desvincular()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$this->modelo()->cargar_contexto_comision($comision);
		
		// Limpio los mensajes
		$this->mensajes = [];
		
		kernel::db()->abrir_transaccion();
		
		try {

                        // Elimino grupo en caso de tener uno asociado
                        $eliminacion_grupo_ok = $this->modelo()->eliminar_grupo_curso_moodle();

                        // Si falla la eliminación del grupo hago que entre al "catch"
			if (!$eliminacion_grupo_ok) {
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.con_curso.curso_virtual_eliminacion_grupo_error'),
					'tipo' => 'alert-error'
				];
				throw new \Exception("Falló la eliminación del Grupo de la Comisión.");
			}

			// Desvinculo comisión del curso virtual 
			$desvinculacion_ok = \guarani::act('act_comisiones')->desvincular_curso_virtual_by_comision($comision);
			
			// Si falla la desvinculación hago que entre al "catch"
			if (!$desvinculacion_ok) {
				$this->mensajes[] = [
					'mensaje' => kernel::traductor()->trans('moodle.con_curso.curso_virtual_desvinculacion_error'),
					'tipo' => 'alert-error'
				];
				throw new \Exception("Falló la desvinculación del Curso Virtual de la Comisión.");
			}
			
			$this->modelo()->limpiar_docentes();
			$this->modelo()->limpiar_alumnos(true);
			
			// Se matriculan los docentes
			list($actualizar_docentes_ok, $actualizar_docentes_msgs) = $this->modelo()->actualizar_docentes_moodle(true);
			if (!$actualizar_docentes_ok) {// Si falló la actualización de los docentes
				
				foreach ($actualizar_docentes_msgs as $actualizar_docentes_msg) {
					$this->mensajes[] = [
						'mensaje' => $actualizar_docentes_msg,
						'tipo' => 'alert-error'
					];
				}
				
				throw new \Exception("Falló la actualización de docentes en Moodle.");
			}
			$this->modelo()->limpiar_docentes();
			
			// Se matriculan los alumnos
			list($actualizar_alumnos_ok, $actualizar_alumnos_msgs) = $this->modelo()->actualizar_alumnos_moodle(true);
			if (!$actualizar_alumnos_ok) {// Si falló la actualización de los alumnos
				foreach ($actualizar_alumnos_msgs as $actualizar_alumnos_msg) {
					$this->mensajes[] = [
						'mensaje' => $actualizar_alumnos_msg,
						'tipo' => 'alert-error'
					];
				}
				throw new \Exception("Falló la actualización de alumnos en Moodle.");
			}
			$this->modelo()->limpiar_alumnos(true);
			
			kernel::db()->cerrar_transaccion();
			
			// Limpio los datos del curso virtual para que vuelvan a ser cargados
			$this->modelo()->limpiar_datos_curso_virtual();
			
			$this->mensajes[] = [
				'mensaje' => kernel::traductor()->trans('moodle.con_curso.curso_virtual_desvinculacion_ok'),
				'tipo' => 'alert-success'
			];
			
			kernel::sesion()->set('mensajes_sin_curso', $this->mensajes);
			$operacion = kernel::ruteador()->get_id_operacion();
			$hash_comision = $this->get_hash_comision();
			$url_index = kernel::vinculador()->crear($operacion, 'index', $hash_comision);
			header("Location: $url_index");
			exit();
			
		} catch (\Exception $e) {
			
			kernel::db()->abortar_transaccion();
			
			kernel::log()->add_error($e);
			
			kernel::sesion()->set('mensajes_con_curso', $this->mensajes);
			$this->vista()->set_pagelet('con_curso');
			return false;
				
		}
	}
	
	function accion__actualizar_docentes()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$this->modelo()->cargar_contexto_comision($comision);
		
		$this->modelo()->limpiar_docentes();
		
		// Se matriculan los alumnos
		list($actualizar_docentes_ok, $actualizar_docentes_msgs) = $this->modelo()->actualizar_docentes_moodle();
		
		$tipo_msg = ($actualizar_docentes_ok)? 'alert-success' : 'alert-error';
		foreach ($actualizar_docentes_msgs as $actualizar_docentes_msg) {
			$this->mensajes[] = [
				'mensaje' => $actualizar_docentes_msg,
				'tipo' => $tipo_msg
			];
		}
		
		$this->modelo()->limpiar_docentes();
		
		kernel::sesion()->set('mensajes_con_curso', $this->mensajes);
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->get_hash_comision();
		$url_home = kernel::vinculador()->crear($operacion, 'home', $hash_comision);
		header("Location: $url_home");
		exit();
	}
	
	function accion__actualizar_alumnos()
	{
		$this->load_params();
		
		$comision = $this->params_comision['comision'];
		$this->modelo()->cargar_contexto_comision($comision);
		
		$this->modelo()->limpiar_alumnos();
		
		// Se matriculan los alumnos
		list($actualizar_alumnos_ok, $actualizar_alumnos_msgs) = $this->modelo()->actualizar_alumnos_moodle();
		
		$tipo_msg = ($actualizar_alumnos_ok)? 'alert-success' : 'alert-error';
		foreach ($actualizar_alumnos_msgs as $actualizar_alumnos_msg) {
			$this->mensajes[] = [
				'mensaje' => $actualizar_alumnos_msg,
				'tipo' => $tipo_msg
			];
		}
		
		$this->modelo()->limpiar_alumnos();
		
		kernel::sesion()->set('mensajes_con_curso', $this->mensajes);
		$operacion = kernel::ruteador()->get_id_operacion();
		$hash_comision = $this->get_hash_comision();
		$url_home = kernel::vinculador()->crear($operacion, 'home', $hash_comision);
		header("Location: $url_home");
		exit();
	}
	
	//--------------------------------------------------------------------------
	//---- UTIL ----------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	protected function load_params()
	{
		$this->hash_comision = $this->validate_param(0, 'get', validador::TIPO_TEXTO);
		$this->params_comision = $this->decodificar_hash($this->hash_comision, '*');
		// Si se trata de una subcomisión
		$this->es_subcomision = $this->params_comision['es_subcomision'];
	}
}
