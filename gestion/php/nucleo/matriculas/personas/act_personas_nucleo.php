<?php
/**
 * Ejecución de comandos SQL que producen cambios en la base de negocio.
 * 
 * @package 	Matrículas
 * @subpackage 	Personas
 * 
 */
class act_personas_nucleo
{
	//-----------------------------------------------------------------------------------
	//---- mdp_personas
	//-----------------------------------------------------------------------------------

	/**
	 * Inserta una persona.
	 *
	 * @return id interno de la persona insertada
	 */
	function insertar_persona($apellido, $nombres, $sexo, $fecha_nacimiento, $localidad_nacimiento, $nacionalidad, $pais_origen)
	{
		$apellido = toba::db()->quote($apellido);
		$nombres = toba::db()->quote($nombres);
		$sexo = toba::db()->quote($sexo);
		$fecha_nacimiento = is_null($fecha_nacimiento) ? '1990-01-01' : $fecha_nacimiento;
		$fecha_nacimiento = toba::db()->quote($fecha_nacimiento);
		$localidad_nacimiento = (is_null($localidad_nacimiento)) ? 'null' : toba::db()->quote($localidad_nacimiento);
		$nacionalidad = is_null($nacionalidad) ? persona::nac_argentino : toba::db()->quote($nacionalidad);
		$pais_origen = (is_null($pais_origen)) ? 'null' : toba::db()->quote($pais_origen);
		
		$sql = "INSERT INTO mdp_personas
				(
					apellido,
					nombres,
					sexo,
					fecha_nacimiento,
					localidad_nacimiento,
					nacionalidad,
					pais_origen
				) 
				VALUES 
				(
					$apellido,
					$nombres,
					$sexo,
					$fecha_nacimiento,
					$localidad_nacimiento,
					$nacionalidad,
					$pais_origen
				)";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó persona con éxito. ************');
		return toba::db()->recuperar_secuencia('mdp_personas_seq');
	}

	function actualizar_apellido_nombres_elegido($persona, $apellido_elegido, $nombres_elegido)
	{
		$persona = toba::db()->quote($persona);
		$apellido_elegido = is_null($apellido_elegido) ? 'null' : toba::db()->quote($apellido_elegido);
		$nombres_elegido = is_null($nombres_elegido) ? 'null' : toba::db()->quote($nombres_elegido);

		$sql = "UPDATE	mdp_personas
				SET		apellido_elegido = $apellido_elegido,
						nombres_elegido = $nombres_elegido
				WHERE	persona = $persona
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron los datos de apellido y nombre elegidos con éxito. ************');
	}

	function actualizar_identidad_genero($persona, $identidad_genero, $identidad_genero_otro)
	{
		$persona = toba::db()->quote($persona);
		$identidad_genero = (is_null($identidad_genero)) ? 'null' : toba::db()->quote($identidad_genero);
		$identidad_genero_otro = (is_null($identidad_genero_otro)) ? 'null' : toba::db()->quote($identidad_genero_otro);

		$sql = "UPDATE	mdp_personas
				SET		identidad_genero = $identidad_genero,
						identidad_genero_otro = $identidad_genero_otro
				WHERE	persona = $persona
		";

		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron datos de identidad de género con éxito. ************');
	}
	
	function actualizar_pueblos_originarios($persona, $pertenece_pueblo_originario, $pueblo_originario, $pueblo_originario_otro)
	{
		$persona = toba::db()->quote($persona);
		$pertenece_pueblo_originario = (is_null($pertenece_pueblo_originario)) ? 'null' : toba::db()->quote($pertenece_pueblo_originario);
		$pueblo_originario = (is_null($pueblo_originario)) ? 'null' : toba::db()->quote($pueblo_originario);
		$pueblo_originario_otro = (is_null($pueblo_originario_otro)) ? 'null' : toba::db()->quote($pueblo_originario_otro);
		
		$sql = "UPDATE	mdp_personas
				SET		pertenece_pueblo_originario = $pertenece_pueblo_originario,
						pueblo_originario = $pueblo_originario,
						pueblo_originario_otro = $pueblo_originario_otro
				WHERE	persona = $persona
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizaron datos de pueblos originarios con éxito. ************');
	}

	function actualizar_clave($usuario, $nueva_clave)
	{
		$sql = "UPDATE mdp_personas SET clave = '$nueva_clave' WHERE usuario = '$usuario'";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó la clave del usuario con éxito. ************');
	}

	function actualizar_perfil_funcional_defecto($usuario, $usuario_grupo_acc)
	{
        try {
			toba::db()->abrir_transaccion();
			$sql = "UPDATE	mdp_personas_grupo_acc
					SET		grupo_acceso_default = '".comunes::no."'
					FROM	mdp_personas
					WHERE	mdp_personas_grupo_acc.persona = mdp_personas.persona AND
							mdp_personas.usuario = '$usuario'";
			guarani_db::ejecutar($sql);
			$usuario_grupo_acc = toba::db()->quote($usuario_grupo_acc);
			$sql = "UPDATE	mdp_personas_grupo_acc
					SET		grupo_acceso_default = '".comunes::si."'
					FROM	mdp_personas
					WHERE	mdp_personas_grupo_acc.persona = mdp_personas.persona AND
							mdp_personas.usuario = '$usuario' AND
							mdp_personas_grupo_acc.usuario_grupo_acc = $usuario_grupo_acc ";
			guarani_db::ejecutar($sql);
            toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se actualizó el perfil funcional por defecto del usuario con éxito. ************');
        } catch (toba_error_db $e) {
            toba::db()->abortar_transaccion();
			$mensaje = $e->getMessage();
            toba::logger()->debug($mensaje);
            toba::logger()->info('************ Error al actualizar el perfil funcional del usuario. ************');
			throw guarani::error($mensaje);
		}
	}

	//-----------------------------------------------------------------------------------
	//---- mdp_personas_documentos
	//-----------------------------------------------------------------------------------

	function insertar_documento($persona, $pais_documento, $tipo_documento, $nro_documento, $validado_con_renaper = persona::no_validado_con_renaper)
	{
		$persona = toba::db()->quote($persona);
		$pais_documento = is_null($pais_documento) ? persona::pais_argentina : toba::db()->quote($pais_documento);
		$tipo_documento = toba::db()->quote($tipo_documento);
		$nro_documento = toba::db()->quote($nro_documento);
		$validado_con_renaper = toba::db()->quote($validado_con_renaper);
		$sql = "INSERT INTO mdp_personas_documentos
				(
					persona,
					pais_documento,
					tipo_documento,
					nro_documento,
					validado_con_renaper
				) 
				VALUES 
				(
					$persona,
					$pais_documento,
					$tipo_documento,
					$nro_documento,
					$validado_con_renaper
				)";		
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó documento personal con éxito. ************');
	}

	//-----------------------------------------------------------------------------------
	//---- mdp_personas_extranjeros
	//-----------------------------------------------------------------------------------

	function insertar_extranjero($persona, $tipo_visa, $otorgamiento_visa, $vencimiento_visa, $tipo_residencia, $residencia_cupo, $otorgamiento_residencia, $vencimiento_residencia)
	{
		$persona = toba::db()->quote($persona);
		$tipo_visa = (is_null($tipo_visa)) ? 'null' : toba::db()->quote($tipo_visa);
		$otorgamiento_visa = (is_null($otorgamiento_visa)) ? 'null' : toba::db()->quote($otorgamiento_visa);
		$vencimiento_visa = (is_null($vencimiento_visa)) ? 'null' : toba::db()->quote($vencimiento_visa);
		$tipo_residencia = (is_null($tipo_residencia)) ? 'null' : toba::db()->quote($tipo_residencia);
		$residencia_cupo = (is_null($residencia_cupo)) ? 'null' : toba::db()->quote($residencia_cupo);
		$otorgamiento_residencia = (is_null($otorgamiento_residencia)) ? 'null' : toba::db()->quote($otorgamiento_residencia);
		$vencimiento_residencia = (is_null($vencimiento_residencia)) ? 'null' : toba::db()->quote($vencimiento_residencia);
		
		$sql = "INSERT INTO mdp_personas_extranjeros
				(
					persona,
					tipo_visa,
					otorgamiento_visa,
					vencimiento_visa,
					tipo_residencia,
					residencia_cupo,
					otorgamiento_residencia,
					vencimiento_residencia
				) 
				VALUES 
				(
					$persona,
					$tipo_visa,
					$otorgamiento_visa,
					$vencimiento_visa,
					$tipo_residencia,
					$residencia_cupo,
					$otorgamiento_residencia,
					$vencimiento_residencia
				)";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó registro en mdp_personas_extranjeros con éxito. ************');
	}

	//-----------------------------------------------------------------------------------
	//---- mdp_personas_contactos
	//-----------------------------------------------------------------------------------
	
	function insertar_contacto($persona, $contacto_tipo, $parametros)
	{
		$persona = toba::db()->quote($persona);
		$contacto_tipo = toba::db()->quote($contacto_tipo);
		
		$insert = '';
		$values = '';
		
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_personas_contactos
				(
					persona,
					contacto_tipo
					$insert
				) 
				VALUES 
				(
					$persona,
					$contacto_tipo
					$values
				)";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó contacto con éxito. ************');
	}
	
	function actualizar_contacto($persona, $contacto_tipo, $parametros)
	{
		$persona = toba::db()->quote($persona);
		$contacto_tipo = toba::db()->quote($contacto_tipo);
		
		$set = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				if (!empty($set)) {
					$set .= ', ';
				}
				$set .= $parametro['campo'].' = '.toba::db()->quote($parametro['valor']);
			}
		}
		
		if (!empty($set)) {
			$sql = "UPDATE	mdp_personas_contactos
					SET 	$set
					WHERE	persona = $persona
							AND contacto_tipo =$contacto_tipo
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizó contacto con éxito. ************');
		}
	}

	function actualizar_user_pass($persona, $user, $pass)
	{
		if (usuario::validar_id_usuario_permitido($user)) {
			$persona = toba::db()->quote($persona);
			$user = toba::db()->quote($user);

			$sql = "UPDATE	mdp_personas
							SET 	usuario = $user, clave = $pass 
							WHERE	persona = $persona;
							";

			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizó usuario y contraseña con éxito. ************');                    
		}
	}
        
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_estudios
	//-----------------------------------------------------------------------------------
	
	function insertar_datos_estudios($persona, $nivel_estudio, $parametros)
	{
		$persona = toba::db()->quote($persona);
		$nivel_estudio = toba::db()->quote($nivel_estudio);
		
		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_datos_estudios
				(
					persona,
					nivel_estudio
					$insert
				) 
				VALUES 
				(
					$persona,
					$nivel_estudio
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó dato de estudio con éxito. ************');
	}
	
	function actualizar_datos_estudios($persona, $parametros, $nivel_estudio, $institucion=null, $titulo=null)
	{
		$persona = toba::db()->quote($persona);
		$nivel_estudio = toba::db()->quote($nivel_estudio);
		$where = '';
		if (!is_null($institucion)) {
			$institucion = toba::db()->quote($institucion);
			$where .= 'AND '.guarani_sql::armar_condicion_compara_cadenas('institucion_otra', $institucion);
		}
		if (!is_null($titulo)) {
			$titulo = toba::db()->quote($titulo);
			$where .= 'AND '.guarani_sql::armar_condicion_compara_cadenas('titulo_otro', $titulo);
		}
		
		$set = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				if (!empty($set)) {
					$set .= ', ';
				}
				$set .= $parametro['campo'].' = '.toba::db()->quote($parametro['valor']);
			}
		}
		
		if (!empty($set)) {
			$sql = "UPDATE 	mdp_datos_estudios
					SET		$set
					WHERE 	persona = $persona AND
							nivel_estudio = $nivel_estudio
							$where
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizó dato de estudio con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_personas_allegados
	//-----------------------------------------------------------------------------------
	
	function insertar_persona_allegada($dato_censal, $tipo_allegado, $parametros)
	{		
		$dato_censal = toba::db()->quote($dato_censal);
		$tipo_allegado = toba::db()->quote($tipo_allegado);
		
		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_personas_allegados
				(
					dato_censal,
					tipo_allegado
					$insert
				) 
				VALUES 
				(
					$dato_censal,
					$tipo_allegado
					$values
				);
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó dato de allegado con éxito. ************');
	}
	
	function actualizar_persona_allegada($dato_censal, $tipo_allegado, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);
		$tipo_allegado = toba::db()->quote($tipo_allegado);
		
		$set = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				if (!empty($set)) {
					$set .= ', ';
				}
				$set .= $parametro['campo'].' = '.toba::db()->quote($parametro['valor']);
			}
		}
		
		if (!empty($set)) {
			$sql = "UPDATE 	mdp_personas_allegados
					SET		$set
					WHERE 	dato_censal = $dato_censal
							AND tipo_allegado = $tipo_allegado
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizó dato de allegado con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_censales
	//-----------------------------------------------------------------------------------
	
	function insertar_dato_censal($persona)
	{
		$persona = toba::db()->quote($persona);
		$fecha_hoy = toba::db()->quote(guarani_fecha::get_hoy());
		
		$sql = "INSERT INTO mdp_datos_censales
				(
					persona,
					fecha_relevamiento,
					fecha_actualizacion
				) 
				VALUES 
				(
					$persona,					
					$fecha_hoy,
					$fecha_hoy
				)";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó dato censal con éxito. ************');
		return toba::db()->recuperar_secuencia('mdp_datos_censales_seq');
	}
	
	function actualizar_dato_censal($dato_censal)
	{
		$dato_censal = toba::db()->quote($dato_censal);
		$fecha_hoy = toba::db()->quote(guarani_fecha::get_hoy());
		
		$sql = "UPDATE	mdp_datos_censales
				SET		fecha_actualizacion = $fecha_hoy
				WHERE 	dato_censal = $dato_censal
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se actualizó dato censal con éxito. ************');
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_economicos
	//-----------------------------------------------------------------------------------
	
	function insertar_datos_economicos($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_datos_economicos
				(
					dato_censal
					$insert
				) 
				VALUES 
				(
					$dato_censal
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron datos censales económicos con éxito. ************');
	}
	
	function actualizar_datos_economicos($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$set = '';
		foreach ($parametros as $parametro) {
			if (!empty($set)) {
				$set .= ', ';
			}
			if (empty($parametro['valor'])) {
				$valor = 'null';
			} else {
				$valor = toba::db()->quote($parametro['valor']);
			}	
			$set .= $parametro['campo'] . ' = ' . $valor;
		}
		
		if (!empty($set)) {
			$sql = "UPDATE	mdp_datos_economicos
					SET		$set
					WHERE	dato_censal = $dato_censal
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizaron datos censales económicos con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_economicos
	//-----------------------------------------------------------------------------------
	
	function insertar_datos_actividades($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_datos_actividades
				(
					dato_censal
					$insert
				) 
				VALUES 
				(
					$dato_censal
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron datos censales de actividades con éxito. ************');
	}
	
	function actualizar_datos_actividades($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$set = '';
		foreach ($parametros as $parametro) {
			if (!empty($set)) {
				$set .= ', ';
			}
			if (empty($parametro['valor'])) {
				$valor = 'null';
			} else {
				$valor = toba::db()->quote($parametro['valor']);
			}	
			$set .= $parametro['campo'] . ' = ' . $valor;
		}
		
		if (!empty($set)) {
			$sql = "UPDATE	mdp_datos_actividades
					SET		$set
					WHERE	dato_censal = $dato_censal
					";
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizaron datos censales de actividades con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_personales
	//-----------------------------------------------------------------------------------
	
	function insertar_datos_personales($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_datos_personales
				(
					dato_censal
					$insert
				) 
				VALUES 
				(
					$dato_censal
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron datos censales personales con éxito. ************');
	}
	
	function actualizar_datos_personales($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$set = '';
		foreach ($parametros as $parametro) {
			if (!empty($set)) {
				$set .= ', ';
			}
			if (empty($parametro['valor'])) {
				$valor = 'null';
			} else {
				$valor = toba::db()->quote($parametro['valor']);
			}	
			$set .= $parametro['campo'] . ' = ' . $valor;
		}
		
		if (!empty($set)) {
			$sql = "UPDATE	mdp_datos_personales
					SET		$set
					WHERE	dato_censal = $dato_censal
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizaron datos censales personales con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_eleccion_propuesta
	//-----------------------------------------------------------------------------------
	
	function insertar_eleccion_propuesta($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_eleccion_propuesta
				(
					dato_censal
					$insert
				) 
				VALUES 
				(
					$dato_censal
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron datos censales personales con éxito. ************');
	}
	
	function actualizar_eleccion_propuesta($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$set = '';
		foreach ($parametros as $parametro) {
                    if (!is_null($parametro['valor'])) {
                        if (!empty($set)) {
				$set .= ', ';
			}
			if (empty($parametro['valor'])) {
				$valor = 'null';
			} else {
				$valor = toba::db()->quote($parametro['valor']);
			}	
			$set .= $parametro['campo'] . ' = ' . $valor;
                    }
                }
		
		if (!empty($set)) {
			$sql = "UPDATE	mdp_eleccion_propuesta
					SET		$set
					WHERE	dato_censal = $dato_censal
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizaron datos censales personales con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_eleccion_propuesta
	//-----------------------------------------------------------------------------------
	
	function insertar_eleccion_institucion($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_eleccion_institucion
				(
					dato_censal
					$insert
				) 
				VALUES 
				(
					$dato_censal
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron datos censales personales con éxito. ************');
	}
	
	function actualizar_eleccion_institucion($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$set = '';
		foreach ($parametros as $parametro) {
                    if (!is_null($parametro['valor'])) {
                        if (!empty($set)) {
                            $set .= ', ';
                        }
                        if (empty($parametro['valor'])) {
                            $valor = 'null';
                        } else {
                            $valor = toba::db()->quote($parametro['valor']);
                        }
                        $set .= $parametro['campo'] . ' = ' . $valor;
                    }
                }

        if (!empty($set)) {
			$sql = "UPDATE	mdp_eleccion_institucion
					SET		$set
					WHERE	dato_censal = $dato_censal
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizaron datos censales personales con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_discapacidad
	//-----------------------------------------------------------------------------------
	
	function insertar_discapacidad($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_datos_discapacidad
				(
					dato_censal
					$insert
				) 
				VALUES 
				(
					$dato_censal
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron datos censales personales con éxito. ************');
	}
	
	function actualizar_discapacidad($dato_censal, $parametros)
	{
		$dato_censal = toba::db()->quote($dato_censal);

		$set = '';
		foreach ($parametros as $parametro) {
                    if (!is_null($parametro['valor'])) {
                        if (!empty($set)) {
                            $set .= ', ';
                        }
                        if (empty($parametro['valor'])) {
                            $valor = 'null';
                        } else {
                            $valor = toba::db()->quote($parametro['valor']);
                        }
                        $set .= $parametro['campo'] . ' = ' . $valor;
                    }
                }

        if (!empty($set)) {
			$sql = "UPDATE	mdp_datos_discapacidad
					SET		$set
					WHERE	dato_censal = $dato_censal
					";
			
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizaron datos censales personales con éxito. ************');
		}
	}
	
	//-----------------------------------------------------------------------------------
	//---- sga_requisitos_digitales_draft
	//-----------------------------------------------------------------------------------
	
	function insertar_draft_requisito_digital($persona, $parametros)
	{
		$persona = toba::db()->quote($persona);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO sga_requisitos_digitales_draft
				(
					persona
					$insert
				) 
				VALUES 
				(
					$persona
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertó draft de requisito digital con éxito. ************');
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_antecedentes_docentes
	//-----------------------------------------------------------------------------------
	
	function insertar_antecedentes_docentes($persona, $parametros)
	{
		$persona = toba::db()->quote($persona);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_antecedentes_docentes
				(
					persona
					$insert
				) 
				VALUES 
				(
					$persona
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron antecedentes docentes con éxito. ************');
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_antecedentes_profesionales
	//-----------------------------------------------------------------------------------
	
	function insertar_antecedentes_profesionales($persona, $parametros)
	{
		$persona = toba::db()->quote($persona);

		$insert = '';
		$values = '';
		foreach ($parametros as $parametro) {
			if (!is_null($parametro['valor'])) {
				$insert .= ', '.$parametro['campo'];
				$values .= ', '.toba::db()->quote($parametro['valor']);
			}
		}
		
		$sql = "INSERT INTO mdp_antecedentes_profesionales
				(
					persona
					$insert
				) 
				VALUES 
				(
					$persona
					$values
				)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se insertaron antecedentes profesionales con éxito. ************');
	}
	
	//-----------------------------------------------------------------------------------
	//---- mdp_datos_censales_config
	//-----------------------------------------------------------------------------------
	
	function actualizar_datos_censales_config($datos)
	{
		$sql = array();
		
		foreach ($datos as $dato) {
			$visible = toba::db()->quote($dato['visible']);
			$obligatorio = toba::db()->quote($dato['obligatorio']);
			$tabla = toba::db()->quote($dato['tabla']);
			$campo = toba::db()->quote($dato['campo']);
			
			$sql[] = "	UPDATE 	mdp_datos_censales_config  
						SET 	visible = $visible,
								obligatorio = $obligatorio
						WHERE 	tabla = $tabla
								AND campo = $campo
					; ";
		}
		
		guarani_db::ejecutar($sql);
	}
	
	//-----------------------------------------------------------------------------------
	//---- Actualizaciones al eliminar una persona
	//-----------------------------------------------------------------------------------
	
	/**
	 * Elimina los datos censales personales de la persona dada como parámetro.
	 */
	function eliminar_datos_personales($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	mdp_datos_personales
				WHERE	dato_censal IN (SELECT	dato_censal 
										FROM	mdp_datos_censales 
										WHERE	persona = $persona)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros correspondientes a datos personales con éxito. ************');
	}
	
	/**
	 * Elimina los datos censales económicos de la persona dada como parámetro.
	 */
	function eliminar_datos_economicos($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	mdp_datos_economicos
				WHERE	dato_censal IN (SELECT	dato_censal 
										FROM	mdp_datos_censales 
										WHERE	persona = $persona)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros correspondientes a datos económicos con éxito. ************');
	}
	
	/**
	 * Elimina los datos censales de actividades de la persona dada como parámetro.
	 */
	function eliminar_datos_actividades($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	mdp_datos_actividades
				WHERE	dato_censal IN (SELECT	dato_censal 
										FROM	mdp_datos_censales 
										WHERE	persona = $persona)
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros correspondientes a datos de actividades con éxito. ************');
	}
	
	/**
	 * Elimina los datos censales de la persona dada como parámetro.
	 */
	function eliminar_datos_censales($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	mdp_datos_censales
				WHERE	persona = $persona
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros correspondientes a datos censales con éxito. ************');
	}
	
	/**
	 * Elimina los recordatorios de la persona dada como parámetro.
	 */
	function eliminar_recordatorios_usuario($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE
				FROM	sga_usuarios_recordatorios
				WHERE	persona = $persona
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los recordatorios de la persona con éxito. ************');
	}
	
	/**
	 * Elimina los registros de login en Autogestión
	 */
	function eliminar_aud_login_autogestion($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	aud_login_autogestion 
				WHERE	persona = $persona
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los registros de login en Autogestión de la persona con éxito. ************');
	}
	
	/**
	 * Elimina las configuraciones personales
	 */
	function eliminar_configuracion($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	mdp_configuracion 
				WHERE	persona = $persona
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron las configuraciones personales con éxito. ************');
	}
	
	/**
	 * Elimina los requisitos digitales presentados
	 */
	function eliminar_requisitos_digitales($persona)
	{
		$persona = toba::db()->quote($persona);
		
		$sql = "DELETE 
				FROM	sga_requisitos_digitales 
				WHERE	persona = $persona
				";
		
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los requisitos digitales de la persona con éxito. ************');
	}

	/**
	 * Elimina los mensajes de los cuales la persona es destinataria.
	 */
	function eliminar_mensaje_destinatarios($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "DELETE FROM men_destinatarios  WHERE persona = $persona;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron los mensajes de la persona con éxito. ************');
	}

	/**
	 * Elimina las encuestas de la persona.
	 */
	function eliminar_encuestas_pendientes($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "DELETE FROM	gde_encuestas_pendientes WHERE persona = $persona;
		        DELETE FROM	gde_encuesta_pendiente_propuestas WHERE persona = $persona; ";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminaron las encuestas de la persona con éxito. ************');
	}

	/**
	 * Elimina el log de inscripciones a propuestas
	 */
	function eliminar_inscripcion_propuesta_log($persona)
	{
		$persona = toba::db()->quote($persona);
		$sql = "DELETE FROM	sga_propuestas_aspira_log WHERE persona = $persona;";
		guarani_db::ejecutar($sql);
		toba::logger()->info('************ Se eliminó el log de inscripciones a propuestas ************');
	}

    function limpiar_token($persona)
    {
        $persona = toba::db()->quote($persona);

        $sql = "UPDATE mdp_personas SET token = NULL, token_alta = NULL WHERE persona = $persona";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se limpió el token y token_alta de la persona con éxito. ************');
    }
    
    function actualizar_token($persona, $token)
    {
        $persona = toba::db()->quote($persona);
        $token = toba::db()->quote($token);

        $sql = "UPDATE mdp_personas SET token = $token, token_alta = CURRENT_TIMESTAMP WHERE persona = $persona";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se actualizó el token y token_alta de la persona con éxito. ************');
    }
	
    function actualizar_arai_uuid($persona, $arai_uuid)
    {
        $persona = toba::db()->quote($persona);
        $arai_uuid = is_null($arai_uuid)? 'NULL' : toba::db()->quote($arai_uuid);

        $sql = "UPDATE mdp_personas SET arai_uuid = $arai_uuid WHERE persona = $persona";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se actualizó el arai_uuid de la persona con éxito. ************');
    }
	
    function actualizar_arai_uuid_x_arai_identificador_sso($arai_identificador_sso, $arai_uuid)
    {
        $arai_identificador_sso = toba::db()->quote($arai_identificador_sso);
        $arai_uuid = is_null($arai_uuid)? 'NULL' : toba::db()->quote($arai_uuid);

        $sql = "UPDATE mdp_personas SET arai_uuid = $arai_uuid WHERE arai_identificador_sso = $arai_identificador_sso";
        guarani_db::ejecutar($sql);
        toba::logger()->info('************ Se actualizó el arai_uuid de la persona con éxito. ************');
    }
	
	/**
	 * El siguiente metodo se usa en el comando vincular_usuarios_arai 
	 * @param arai_uuid type String 
	 * @param arai_identificador_sso type String
	 * @return bool
	 */
	function actualizar_arai_uuid_y_arai_identificador_sso($arai_uuid, $arai_identificador_sso, $cuenta)
    {
		try {
			$arai_identificador_sso = toba::db()->quote($arai_identificador_sso);
			$arai_uuid = toba::db()->quote($arai_uuid);
			$cuenta = toba::db()->quote($cuenta);

			$sql = "UPDATE mdp_personas SET arai_uuid = $arai_uuid,  arai_identificador_sso = $arai_identificador_sso WHERE usuario = $cuenta";
			guarani_db::ejecutar($sql);
			toba::logger()->info('************ Se actualizó el arai_uuid de la persona con éxito. ************');
		}catch(toba_error_db $e){
			toba::db()->abortar_transaccion();
			$mensaje = $e->getMessage();
            toba::logger()->debug($mensaje);
            toba::logger()->info('************ Error al actualizar usuario en la operación insertar_arai_identificador_sso.. ************');
			throw guarani::error($mensaje);
		}
    }

	/**
	 * El siguiente metodo se usa en el comando vincular_usuarios_arai
	 * @param cuenta type String
	 * @param identificador_usuario type String
	 * @param tipo type Int 
	 * @return bool
	 */
	function actualizar_arai_identificador_sso($cuenta, $identificador_usuario)
	{
        try {
			toba::db()->abrir_transaccion();
			$cuenta = toba::db()->quote($cuenta);
			$identificador_usuario = toba::db()->quote($identificador_usuario);
			$sql = "UPDATE mdp_personas SET arai_identificador_sso = $identificador_usuario WHERE usuario = $cuenta";			
			guarani_db::ejecutar($sql);			
            toba::db()->cerrar_transaccion();
			toba::logger()->info('************ Se realizó con exito la operación insertar_arai_identificador_sso. ************');
        } catch (toba_error_db $e) {
            toba::db()->abortar_transaccion();
			$mensaje = $e->getMessage();
            toba::logger()->debug($mensaje);
            toba::logger()->info('************ Error al actualizar usuario en la operación insertar_arai_identificador_sso.. ************');
			throw guarani::error($mensaje);
		}
	}
}