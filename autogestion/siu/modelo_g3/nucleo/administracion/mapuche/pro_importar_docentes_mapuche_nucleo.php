<?php
/**
 *
 * 
 * @package 	Administración
 * @subpackage 	Mapuche
 */
class pro_importar_docentes_mapuche_nucleo extends guarani_procesos_bk
{
	protected $archivo;

	const reporte_docentes_con_errores = 1;
	const reporte_resultados = 2;

	const nombre = 'pro_importar_docentes_mapuche';

	function set_datos($datos=array())
	{
		$this->archivo = $datos['archivo'];
	}

	function procesar()
	{
		$handle = fopen($this->archivo, 'r');
		// Conteo de filas.
		if ($handle) {
			$cant_filas = 0;
			while (!feof($handle)) {
				$fila = fgets($handle);
				if ($fila && trim($fila) != '') {
					$cant_filas++;
				}
			}
		}
		fclose($handle);

		$cant_docentes_sin_errores = 0;
		$cant_docentes_con_errores = 0;
		$paso = 100/$cant_filas;
		$handle = fopen($this->archivo, 'r');
		if ($handle) {
			$i_fila = 0;
			while (!feof($handle)) {
				$estado = $this->get_estado();
				if ((isset($estado['abortar'])) && ($estado['abortar'])) {
					$this->abortar = true;
					break;
				}
				$fila = fgets($handle);
				$i_fila++;
				if (!$fila || trim($fila) == '') {
					continue;
				}
				$this->log_estado->incrementar_progreso($paso);
				$columnas = $this->get_columnas(trim($fila));
				try {
					toba::db()->abrir_transaccion();
					$this->validar($columnas);
					$persona = $this->procesar_persona($columnas);
					guarani::act('act_docentes')->insertar_docente($persona, $columnas['legajo']);
					$cant_docentes_sin_errores++;
					toba::db()->cerrar_transaccion();
				} catch (guarani_error $e) {
					$datos_persona['fila'] = $i_fila;
					$datos_persona['legajo'] = $columnas['legajo'];
					$datos_persona['apellido_y_nombres'] = "{$columnas['apellido']}, {$columnas['nombres']}";
					$datos_persona['observaciones'] = $e->get_mensaje();
					$this->log->set($datos_persona, array(self::reporte_docentes_con_errores));
					$this->log->guardar();
					$cant_docentes_con_errores++;
					toba::db()->abortar_transaccion();
				}
			}
		}
		fclose($handle);
		$mensaje_resultado['docentes_sin_errores'] = $cant_docentes_sin_errores;
		$mensaje_resultado['docentes_con_errores'] = $cant_docentes_con_errores;
		$this->log->set($mensaje_resultado, array(self::reporte_resultados));
		$this->log->guardar();
	}

	function get_columnas($fila)
	{
		$columnas = array();
		// 0 - Número de legajo
		$columnas['legajo'] = trim(substr($fila, 0, 10));
		// 1 - Apellido
		$columnas['apellido'] = trim(substr($fila, 10, 30));
		// 2 - Nombre
		$columnas['nombres'] = trim(substr($fila, 40, 30));
		// 3 - Tipo documento
		$filtro = "upper(mdp_tipo_documento.desc_abreviada) = '".trim(strtoupper(substr($fila, 70, 4)))."'";
		$tipo_documento = current(toba::consulta_php('co_personas')->get_descr_tipo_documento($filtro));
		$columnas['tipo_documento'] = empty($tipo_documento) ? persona::doc_dni : $tipo_documento['tipo_documento'];
		// 4 - Número documento
		$columnas['nro_documento'] = trim(substr($fila, 74, 9));
		// 5 - Sexo
		$columnas['sexo'] = substr($fila, 83, 1);
		// 6 - Fecha de nacimiento
		list($dd, $mm, $aaaa) = explode('/', trim(substr($fila, 84, 10)));
		$columnas['fecha_nacimiento'] = checkdate($mm, $dd, $aaaa) ? "$aaaa-$mm-$dd" : '';
		// 7 - Código de nacionalidad
		$columna = trim(strtoupper(substr($fila, 94, 4)));
		$columnas['nacionalidad'] = ($columna == 'ARG' || $columna = '') ? persona::nac_argentino : persona::nac_extranjero;
		// 8 - Código de país
		$filtro = "upper(mug_paises.codigo_iso) = '".trim(strtoupper(substr($fila, 98, 2)))."'";
		$pais = current(toba::consulta_php('co_mug')->get_paises(null, $filtro));
		$columnas['pais_documento'] = empty($pais) ? persona::pais_argentina : $pais['valor'];
		/*// 9 - Estado extranjero
		$columnas[] = substr($fila, 100, 30);
		// 10 - Código postal extranjero
		$columnas[] = substr($fila, 130, 20);
		// 11 - Ciudad extranjero
		$columnas[] = substr($fila, 150, 30);
		// 12 - Código de provincia argentina
		$columnas[] = substr($fila, 180, 1);*/
		// 13 - Código postal argentino
		$columnas['periodo_lectivo_codigo_postal'] = trim(substr($fila, 181, 4));
		// 14 - Código Casa Manzana
		$columnas['periodo_lectivo_unidad'] = trim(substr($fila, 185, 3));
		/*// 15 - Descripción localidad
		$columnas[] = substr($fila, 188, 60);*/
		// 16 - Zona-Paraje-Barrio
		$columnas['periodo_lectivo_barrio'] = trim(substr($fila, 248, 60));
		// 17 - Calle
		$columnas['periodo_lectivo_calle'] = trim(substr($fila, 308, 30));
		// 18 - Número
		$columnas['periodo_lectivo_numero'] = trim(substr($fila, 338, 10));
		// 19 - Piso
		$columnas['periodo_lectivo_piso'] = trim(substr($fila, 348, 3));
		// 20 - Depto-Oficina
		$columnas['periodo_lectivo_departamento'] = trim(substr($fila, 351, 5)); // // Se sacan los primeros 5 caracteres, que es la longitud que manejamos.
		// 21 - Teléfono
		$columnas['telefono'] = trim(substr($fila, 357, 20)); // Se sacan los primeros 20 caracteres, que es la longitud que manejamos.
		// 22 - Teléfono-fax
		$columnas['fax'] = trim(substr($fila, 387, 20)); // Se sacan los primeros 20 caracteres, que es la longitud que manejamos.
		// 23 - Teléfono-celular
		$columnas['celular'] = trim(substr($fila, 417, 20)); // Se sacan los primeros 20 caracteres, que es la longitud que manejamos.
		// 24 - Correo electrónico
		$columnas['email'] = trim(substr($fila, 447, 60));
		/*// 25 - Código de unidad académica
		$columnas[] = substr($fila, 507, 4);*/
		return $columnas;
	}

	function validar($columnas)
	{
		// No nulos: 1 - Apellido, 2 - Nombre, 3 - Tipo documento, 4 - Número documento, 5 - Sexo, 6 - Fecha de nacimiento, 7 - Código de nacionalidad, 8 - Código de país.
		if ($columnas['apellido'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_apellido_vacio'));
		}
		if ($columnas['nombres'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_nombre_vacio'));
		}
		if ($columnas['tipo_documento'] === '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_tipo_doc_incorrecto'));
		}
		if ($columnas['nro_documento'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_nro_doc_vacio'));
		}
		if ($columnas['sexo'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_sexo_vacio'));
		}
		if ($columnas['fecha_nacimiento'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_fecha_nac_incorrecta'));
		}
		if ($columnas['nacionalidad'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_nac_vacia'));
		}
		if ($columnas['pais_documento'] == '') {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_pais_doc_incorrecto'));
		}

		// FKs: 5 - Sexo (mdp_personas_sexo), 8 - Código de País.
		$filtro = "upper(mdp_personas_sexo.sexo) = '".trim(strtoupper($columnas['sexo']))."'";
		$sexos = toba::consulta_php('co_personas')->get_sexos($filtro);
		if (guarani_arreglos::count($sexos) == 0) {
			throw guarani::error(guarani_mensajes::get('800ADM_mapuche_sexo_incorrecto'));
		}

		// La persona existe (por país + tipo + nro. documento) y es docente activo con el mismo u otro legajo?
		$persona = toba::consulta_php('co_personas')->validar_documento($columnas['tipo_documento'], $columnas['nro_documento'], $columnas['pais_documento']);
		if (!empty($persona)) {
			$docente = toba::consulta_php('co_docentes')->get_docente_activo_por_persona($persona);
			if (!empty($docente)) {
				$mensaje = ($docente['legajo'] == $columnas['legajo']) ? '800ADM_mapuche_persona_mismo_legajo' : '800ADM_mapuche_persona_otro_legajo';
				throw guarani::error(guarani_mensajes::get($mensaje));
			}
		}

		// El legajo está asignado a otro docente?
		$docente = toba::consulta_php('co_docentes')->get_docente_por_legajo($columnas['legajo']);
		if (!empty($docente)) {
			 throw guarani::error(guarani_mensajes::get('800ADM_mapuche_legajo_duplicado'));
		}
	}

	function procesar_persona($datos)
	{
		$persona = toba::consulta_php('co_personas')->validar_documento($datos['tipo_documento'], $datos['nro_documento'], $datos['pais_documento']);
		if (empty($persona)) {
			$persona = guarani::act('act_personas')->insertar_persona($datos['apellido'], $datos['nombres'], $datos['sexo'], $datos['fecha_nacimiento'], null, $datos['nacionalidad'], null);
			guarani::act('act_personas')->insertar_documento($persona, $datos['pais_documento'], $datos['tipo_documento'], $datos['nro_documento']);
			// Contactos: teléfono fijo, fax, celular, email.
			$this->insertar_contactos($datos, $persona);
			// Datos censales: domicilio durante el período lectivo.
			$this->insertar_datos_censales($datos, $persona);
			
			// Usuario y clave de la persona.
			$this->insertar_usuario_clave($datos, $persona);
		}
		return $persona;
	}

	function insertar_contactos($datos, $persona)
	{
		// Teléfono fijo.
		if (!empty($datos['telefono'])) {
			guarani::act('act_personas')->insertar_contacto($persona, persona::tel_fijo, array(array('campo' => 'telefono_numero', 'valor' => $datos['telefono'])));
		}
		// Fax.
		if (!empty($datos['fax'])) {
			guarani::act('act_personas')->insertar_contacto($persona, persona::fax, array(array('campo' => 'telefono_numero', 'valor' => $datos['fax'])));
		}
		// Teléfono celular.
		if (!empty($datos['celular'])) {
			guarani::act('act_personas')->insertar_contacto($persona, persona::tel_celular, array(array('campo' => 'telefono_numero', 'valor' => $datos['celular'])));
		}
		// Email.
		if (!empty($datos['email'])) {
			guarani::act('act_personas')->insertar_contacto($persona, persona::mail_primario, array(array('campo' => 'email', 'valor' => $datos['email'])));
		}
	}

	function insertar_datos_censales($datos, $persona)
	{
		$dato_censal = guarani::act('act_personas')->insertar_dato_censal($persona);
		$parametros = array(
			array('campo' => 'periodo_lectivo_calle', 'valor' => $datos['periodo_lectivo_calle']),
			array('campo' => 'periodo_lectivo_numero', 'valor' => $datos['periodo_lectivo_numero']),
			array('campo' => 'periodo_lectivo_piso', 'valor' => $datos['periodo_lectivo_piso']),
			array('campo' => 'periodo_lectivo_departamento', 'valor' => $datos['periodo_lectivo_departamento']),
			array('campo' => 'periodo_lectivo_unidad', 'valor' => $datos['periodo_lectivo_unidad']),
			array('campo' => 'periodo_lectivo_barrio', 'valor' => $datos['periodo_lectivo_barrio']),
			array('campo' => 'periodo_lectivo_codigo_postal', 'valor' => $datos['periodo_lectivo_codigo_postal'])
		);
		guarani::act('act_personas')->insertar_datos_personales($dato_censal, $parametros);
	}
	
	function insertar_usuario_clave($datos, $persona)
	{
		$user = usuario::get_usuario_default($datos['pais_documento'], $datos['tipo_documento'], $datos['nro_documento']);
		$pass = 'null';
		guarani::act('act_personas')->actualizar_user_pass($persona, $user, $pass);
	}

}