<?php

require_once __DIR__ . '/../../php/nucleo/_lib/comunes_nucleo.php';
require_once __DIR__ . '/../../personalizacion/php/nucleo/_lib/comunes.php';
require_once __DIR__ . '/../../php/nucleo/matriculas/personas/persona_nucleo.php';
require_once __DIR__ . '/../../personalizacion/php/nucleo/matriculas/personas/persona.php';

class guarani_fuente_datos extends toba_fuente_datos
{
	function crear_usuario_para_auditoria($db)
	{
		if ($this->definicion['tiene_auditoria'] == '1') {
			
			$usuario = toba::usuario()->get_id();
			
			// Si el usuario NO esta autenticado
			if ($usuario == toba_usuario_no_autenticado::NO_AUTENTICADO) {
				
				// Si la interfaz es consola (cli)
				// https://stackoverflow.com/questions/933367/php-how-to-best-determine-if-the-current-invocation-is-from-cli-or-web-server
				if(php_sapi_name() == comunes::interfaz_consola){
					$usuario = persona::get_usuario_consola();
				}
				else{ //Si la interfaz es WEB
					$usuario = persona::perfil_anonimo;
				}
			}
			
			$id_solicitud = $db->quote(toba::instancia()->get_id_solicitud());
			$usuario = $db->quote($usuario);
			$sql = 'CREATE TEMP TABLE tt_usuario ( usuario VARCHAR(60), id_solicitud BIGINT);';
			if (isset($this->definicion['permisos_por_tabla']) && $this->definicion['permisos_por_tabla'] == '1') {
				$id_operacion = toba::memoria()->get_item_solicitado();
				$rol_runtime =  toba_modelo_proyecto::get_rol_prueba_db_basico($this->definicion['fuente_datos']);									//Obtengo el rol basico de prueba
				$rol_operacion = toba_modelo_proyecto::get_rol_prueba_db($this->definicion['fuente_datos'], $id_operacion['1']);			//Obtengo el particular para la operacion
				if ($db->existe_rol($rol_operacion)) {							//Si existe el rol para la operacion entonces lo seteo como el apropiado
					$rol_runtime = $rol_operacion;
				}

				$sql .= "GRANT SELECT, INSERT ON tt_usuario TO $rol_runtime ;";
			}			
			$sql .= "INSERT INTO tt_usuario (usuario, id_solicitud) VALUES ($usuario, $id_solicitud);";
			$db->ejecutar($sql);
		}
	}
}