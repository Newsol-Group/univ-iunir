<?php
namespace siu\modelo\datos\db;
use SIU\Chulupi\kernel;
use SIU\Chulupi\util\db\db;
use siu\guarani;
use siu\modelo\datos\catalogo;
use siu\modelo\entidades\alumno_foto;
use siu\modelo\_comun\abm;
use siu\modelo\entidades\persona as entidad_persona;
use siu\modelo\entidades\parametro as parametros_sistema;

class persona
{
	/**
	 * parametros: usuario
	 * cache: no
	 * filas: 1
	 */
	function buscar_clave($parametros)
	{

		$sql = "SELECT	clave, persona
                        FROM	mdp_personas 
                        WHERE	usuario	= {$parametros['usuario']} ";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}

    /**
	 * parametros: usuario
	 * cache: no
	 * filas: 1
	 */  
    function buscar_persona($parametros)
    {
        $sql = "
                SELECT 
                    persona
                FROM	
                    mdp_personas
                WHERE
                    usuario = {$parametros['usuario']}";
        $datos = kernel::db()->consultar_fila($sql);
		return $datos;
    }
    
	/**
	 * parametros: usuario
	 * cache: no
	 * filas: 1
	 */
	function saml_buscar_por_usuario($parametros)
	{
		return $this->buscar_clave($parametros);
	}

	/**
	 * parametros: persona
	 * cache: no
	 * filas: 1
	 */
	function saml_buscar_por_persona($parametros)
	{

		$sql = "SELECT	persona
                        FROM	mdp_personas
                        WHERE	persona	= {$parametros['persona']} ";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos;
	}
        
	/**
	 * parametros: proveedor, id, mail
	 * cache: no
	 * 
	 * filas: 1
	 */
	function login_externo($parametros)
	{
		$sql = "SELECT persona, descripcion FROM acc_login_externos
				WHERE proveedor =  {$parametros['proveedor']}
				 AND id_externo = {$parametros['id']}";
		return $datos = kernel::db()->consultar_fila($sql);
	}	
	
	/**
	 * parametros: persona
	 * cache: no
	 * filas: n
	 */
	function get_login_externos($parametros)
	{

		$sql = "SELECT proveedor, id_externo, descripcion FROM acc_login_externos WHERE persona = {$parametros['persona']} ";
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}
	
	/**
	 * parametros: persona, id, mail, proveedor
	 * cache: no
	 * filas: 1
	 */
	function vincular_login_externo($parametros)
	{	
		$sql = "SELECT proveedor, persona FROM acc_login_externos
				WHERE proveedor =  {$parametros['proveedor']} AND persona = {$parametros['persona']} ";
		$datos = kernel::db()->consultar($sql);
		if(isset($datos[0])){
			$sql =	"UPDATE acc_login_externos
					   SET  id_externo= {$parametros['id']},
					        descripcion= {$parametros['mail']}
					 WHERE proveedor =   {$parametros['proveedor']}
					       AND persona = {$parametros['persona']} ;
					";
		}else{
			$sql =	"INSERT INTO acc_login_externos(
					id_externo, proveedor, persona, descripcion)
	                VALUES ({$parametros['id']}, {$parametros['proveedor']},{$parametros['persona']}, {$parametros['mail']})";

		}
		return $datos = kernel::db()->ejecutar($sql) > 0;
	}
	
	/**
	 * parametros: persona, proveedor
	 * cache: no
	 * filas: 1
	 */
	function desvincular_login_externo($parametros)
	{
		$sql = "DELETE FROM acc_login_externos
						WHERE persona   = {$parametros['persona']}
						  AND proveedor = {$parametros['proveedor']}
						";
		$datos = kernel::db()->ejecutar($sql);
		return $datos > 0;
	}
	
	/**
	 * parametros:  persona,registrar, ip, login_por, status
	 * no_quote: registrar
	 * param_null: status
	 * cache: no
	 * filas: 1
	 */
	function insertar_registro_logueo($parametros)
	{
		$datos = array();
		if  ($parametros['registrar']) {
			$sql = "INSERT INTO
						aud_login_autogestion (
							persona,
						    ip,
						    login_por
						)
					VALUES (
							{$parametros['persona']},
							{$parametros['ip']},
							{$parametros['login_por']}
							)";
			$datos = kernel::db()->ejecutar($sql);
			
			if($datos==1 and $parametros['status']==0)
			{
				$sql = "SELECT	max(id_login) as log_ingreso 
						FROM	aud_login_autogestion
						WHERE	persona	=	{$parametros['persona']}";
				$dato_aud = kernel::db()->consultar_fila($sql);
				kernel::sesion()->set('log_ingreso', $dato_aud['log_ingreso']);
			}
			
		}
		return $datos;
	}
	
	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: 1
	 */
	function get_propuesta_inicial($parametros)
	{
                // Filtro por calidad
		$filtro_calidad_alumno = '';
		$param_solo_alumnos_activos = parametros_sistema::get_valor('g3w_ingreso_solo_alumnos_activos');
                // Si está configurado como 'Se visualizan solo propuestas con calidad "Activo"'
		if ($param_solo_alumnos_activos == \comunes::si) {
			// Solo alumnos con calidad Activo.
			$filtro_calidad_alumno = " AND sga_alumnos.calidad = '".\alumno::calidad_activo."'";
		}
                
		$sql = "SELECT	propuesta_inicial_autogestion
				FROM	mdp_configuracion
		        WHERE	persona = {$parametros['persona']}
                        AND propuesta_inicial_autogestion IN (SELECT propuesta FROM sga_alumnos WHERE persona = mdp_configuracion.persona{$filtro_calidad_alumno})";
		$datos = kernel::db()->consultar_fila($sql);
		return $datos['propuesta_inicial_autogestion'] ?? null;
	}

	/**
	 * parametros: persona, propuesta 
	 * cache: no
	 * filas: 1
	 */
	function registrar_propuesta_inicial($parametros)
	{
		$sql = "UPDATE mdp_configuracion 
				SET propuesta_inicial_autogestion = {$parametros['propuesta']}
				WHERE persona = {$parametros['persona']}";
		return kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros:  log_ingreso
	 * cache: no
	 * filas: 1
	 */
	function registrar_logout($parametros)
	{
		// Seteo la fecha y hora de logout.	
		$sql = "UPDATE aud_login_autogestion
				SET egreso		= current_timestamp
				WHERE id_login	= {$parametros['log_ingreso']}";
		$datos = kernel::db()->ejecutar($sql);
		return $datos;
	}	
	
	/**
	 * parametros: persona
	 * cache: sesion
	 * filas: 1
	 */
	function datos_basicos($parametros)
	{

		// Toma el primer perfil del usuario, si tiene uno seleccionado por el usuario o sino el orden por defecto de los tipos de usuarios
		$formato_fecha_visual = kernel::db()->quote(\comunes::formato_fecha_visual);
		
		$sql = "SELECT	p.persona,
                td.tipo_documento,
                COALESCE(mdp_tipo_documento_pais.descripcion, td.descripcion) as tipo_documento_desc ,
				COALESCE(mdp_tipo_documento_pais.desc_abreviada, td.desc_abreviada) as tipo_documento_desc_abreviada,
				doc.nro_documento, 
                p.apellido, 
                p.nombres,
                COALESCE (p.nombres_elegido, p.nombres) AS nombre_mostrar,
                COALESCE (p.apellido_elegido, p.apellido) AS apellido_mostrar,
                UPPER(LEFT(TRIM(p.apellido), 1)) AS inicial_apellido,
				p.fecha_nacimiento,
				to_char(p.fecha_nacimiento, $formato_fecha_visual) AS fecha_nacimiento_visual,
				p.id_imagen as id_imagen,
                CASE
                   WHEN p.tipo_usuario_inicial IS NOT NULL AND p.tipo_usuario_inicial <> 'Gestion'  THEN p.tipo_usuario_inicial
                   ELSE (SELECT t.tipo_usuario
                           FROM mdp_personas_tipo_usuario as t, acc_tipos_usuarios as tp
                          WHERE t.persona = p.persona
                            AND tp.tipo_usuario = t.tipo_usuario
							AND tp.tipo_usuario <> 'Gestion'
                          ORDER BY tp.orden_acceso_default 
                          LIMIT 1
                         )
                END as perfil_inicial,
                p.usuario as identificacion,
                p.arai_identificador_sso as arai_identificador_sso,
                p.arai_uuid as arai_uuid,
                d.legajo as legajo_docente,
                d.docente as docente,
                CASE 
                   WHEN p.tipo_usuario_inicial IS NULL THEN acc_tipos_usuarios.orden_acceso_default
                   ELSE 
                        (CASE 
                           WHEN p.tipo_usuario_inicial = mdp_personas_tipo_usuario.tipo_usuario THEN 0
                           ELSE acc_tipos_usuarios.orden_acceso_default
                         END)  
                END as orden  -- Orden de acceso del tipo de usuario..
			FROM mdp_personas as p
            JOIN mdp_personas_tipo_usuario ON mdp_personas_tipo_usuario.persona = p.persona
            JOIN acc_tipos_usuarios ON acc_tipos_usuarios.tipo_usuario = mdp_personas_tipo_usuario.tipo_usuario
            JOIN mdp_personas_documentos as doc ON (doc.persona = p.persona AND doc.documento = p.documento_principal)
            JOIN mdp_tipo_documento as td ON td.tipo_documento = doc.tipo_documento
            JOIN mdp_tipo_documento_pais ON (td.tipo_documento = mdp_tipo_documento_pais.tipo_documento AND doc.pais_documento = mdp_tipo_documento_pais.pais )
            LEFT JOIN sga_docentes as d ON (d.persona = p.persona AND d.estado = 'A')
                
			WHERE	p.persona = {$parametros['persona']}
			 AND	mdp_personas_tipo_usuario.tipo_usuario <> 'Gestion' 
            ORDER BY orden
            LIMIT 1
            ";

		$datos = kernel::db()->consultar_fila($sql);
		return $datos;		
	}
	
	/**
	 * parametros: persona
	 * no_quote: persona
	 * cache: sesion
	 * filas: n
	 */
	function perfiles($parametros)
	{
	
		// Perfiles de acceso de autogestion parametro = persona
		$sql = "SELECT mptu.tipo_usuario 
		          FROM mdp_personas_tipo_usuario as mptu
				  JOIN acc_tipos_usuarios as atu ON mptu.tipo_usuario = atu.tipo_usuario
		         WHERE mptu.persona = {$parametros['persona']} 
				  ORDER BY atu.orden_acceso_default
		       ";
		$datos = kernel::db()->consultar($sql, db::FETCH_ASSOC);
		
		$perfiles = [];
		foreach($datos as $dato) {
			$perfiles[$dato['tipo_usuario']] = $dato['tipo_usuario'];
		}
		
		// Si tiene el perfil alumno 'Alumno' y no tiene propuestas para mostar en el combo, se saca el perfil,
		// Esto es ante situaciones que haya quedado asignado mal el perfil 'Alumno'
		if (isset($perfiles[entidad_persona::perfil_alumno])) {
			$tiene_propuestas = \guarani_arreglos::count(catalogo::consultar('persona', 'carreras', ['persona' => $parametros['persona']])) > 0;
			if (!$tiene_propuestas) {
				unset($perfiles[entidad_persona::perfil_alumno]);
			}
		}
		
		return entidad_persona::perfiles_validos($perfiles,$parametros['persona']);
	}
			
	/**
	 * parametros: persona, perfil_inicial
	 * cache: no
	 * filas: 1
	 */
	function set_perfil_defecto($parametros)
	{
		$sql = "UPDATE	mdp_personas
		        SET		tipo_usuario_inicial = {$parametros['perfil_inicial']} 
		        WHERE	persona = {$parametros['persona']}";
		return kernel::db()->ejecutar($sql);
	}
	
	
	/**
	 * parametros: persona
	 * cache: memoria
	 * filas: 1
	 */
	function get_tipo_usuario_inicial($parametros)
	{
		// Perfiles de acceso de autogestion
		$sql = "SELECT	tipo_usuario_inicial
				FROM	mdp_personas
		        WHERE	persona = {$parametros['persona']}";
		$datos = kernel::db()->consultar_fila($sql);
        return $datos['tipo_usuario_inicial'];
	}
	
	/**
	 * parametros: persona
	 * cache: sesion
	 * filas: n
	 */
	function carreras($parametros)
	{
		/*
		   Recupera las carreras en las que se encuentra la persona como calidad solo activo o todas segun parametro g3w_ingreso_solo_alumnos_activos.
		   Solo alumnos con inscripciones aceptadas o pendientes en la propuesta.
		   Alumnos regulares y no regulares (Regular = SI / NO)
		   Lista propuestas de tipos de propuestas que estan marcadas como que estan disponibles en autogestion (sga_propuestas_tipos.disponible_en_autogestion = S)
		*/   
		
		// Filtro de propuestas por responsable academica.
		// Filtramos por ra que este en punto de acceso kernel::proyecto()->get_puntoacceso_x_ra();
		$filtro_responsable_academica = '';
		$ras = '';
		
		$ra_bloque_acceso = kernel::proyecto()->get_puntoacceso_x_ra();
		if (is_array($ra_bloque_acceso)) {
			$ra_bloque_acceso = array_filter($ra_bloque_acceso, 'strlen');
			$ras = implode(',', $ra_bloque_acceso);
			if (is_numeric($ras)) {
				$filtro_responsable_academica = " AND sga_alumnos.propuesta IN (SELECT pra.propuesta FROM sga_propuestas_ra as pra WHERE pra.responsable_academica IN (" . $ras . "))";
			}
		} else {
			if (is_numeric($ra_bloque_acceso)) {
				$ras = kernel::db()->quote($ra_bloque_acceso);
				$filtro_responsable_academica = " AND sga_alumnos.propuesta IN (SELECT pra.propuesta FROM sga_propuestas_ra as pra WHERE pra.responsable_academica IN (" . $ras . "))";
			}
		}
                
		// Filtro por calidad 
		$filtro_calidad_alumno = '';
		$param_solo_alumnos_activos = parametros_sistema::get_valor('g3w_ingreso_solo_alumnos_activos');
                // Si está configurado como 'Se visualizan solo propuestas con calidad "Activo"'
		if ($param_solo_alumnos_activos == \comunes::si) {
			// Solo alumnos con calidad Activo.
			$filtro_calidad_alumno = " AND sga_alumnos.calidad = '".\alumno::calidad_activo."'";
		}	
		//si arp es true agrego rechazadas a las carreras
                (isset($parametros['arp']) && $parametros['arp']) ? $arp = ", '".\propuesta::inscripcion_rechazada."'" : $arp = '';
		// Propuestas del alumno
		$sql = "SELECT  
						sga_alumnos.propuesta,
						sga_alumnos.alumno,
						sga_alumnos.legajo as legajo,
						sga_planes_versiones.plan,
						sga_alumnos.plan_version as plan_version,
						sga_alumnos.regular,
						CASE sga_alumnos.regular
						  WHEN '".\comunes::si."' THEN 'Regular'
						  WHEN '".\comunes::no."' THEN 'No Regular'
						END as regular_nombre,
						sga_alumnos.calidad,
						sga_alumnos.ubicacion,
						sga_alumnos.modalidad,
						sga_alumnos.division,
						sga_alumnos.coeficiente as coeficiente,
						sga_planes.version_actual as version,
						sga_alumnos_calidad.nombre as calidad_nombre,
						sga_propuestas.codigo as propuesta_codigo,
						sga_propuestas.nombre as propuesta_nombre,
						sga_propuestas.nombre_abreviado as propuesta_nombre_abreviado,
						sga_propuestas.propuesta_tipo as propuesta_tipo,
						sga_propuestas_tipos.descripcion as propuesta_tipo_nombre,
						sga_planes.codigo as plan_codigo,
						sga_planes.nombre as plan_nombre,
						sga_planes_versiones.version as planes_versiones_version,
						sga_planes_versiones.nombre as planes_versiones_nombre,
						sga_situacion_aspirante.resultado_asp as estado_inscripcion,
						CASE sga_situacion_aspirante.resultado_asp
						  WHEN '".\propuesta::inscripcion_aceptada."' THEN '".\propuesta::inscripcion_aceptada_descr."'
						  WHEN '".\propuesta::inscripcion_pendiente."' THEN '".\propuesta::inscripcion_pendiente_descr."'
						  ELSE ''
						END as desc_estado_inscripcion
						
					FROM sga_alumnos
					JOIN sga_alumnos_calidad		ON sga_alumnos_calidad.calidad = sga_alumnos.calidad
					JOIN sga_propuestas				ON sga_propuestas.propuesta = sga_alumnos.propuesta
					JOIN sga_propuestas_aspira		ON sga_propuestas_aspira.persona = sga_alumnos.persona AND sga_propuestas_aspira.propuesta = sga_alumnos.propuesta
					JOIN sga_situacion_aspirante	ON sga_situacion_aspirante.situacion_asp = sga_propuestas_aspira.situacion_asp  
					JOIN sga_propuestas_tipos		ON sga_propuestas_tipos.propuesta_tipo = sga_propuestas.propuesta_tipo
					JOIN sga_planes_versiones		ON sga_planes_versiones.plan_version = sga_alumnos.plan_version
					JOIN sga_planes					ON sga_planes.plan = sga_planes_versiones.plan
					WHERE sga_alumnos.persona = {$parametros['persona']}
					  AND sga_propuestas_tipos.disponible_en_autogestion = '".\comunes::si."'
                                          AND sga_situacion_aspirante.resultado_asp IN ('".\propuesta::inscripcion_aceptada."', '".\propuesta::inscripcion_pendiente."'{$arp})
					  $filtro_calidad_alumno
					  $filtro_responsable_academica 
					ORDER BY sga_propuestas.nombre  
					";
					
		$datos = kernel::db()->consultar($sql);
		return $datos;
	}

	/**
	 * parametros: persona
	 * cache: memoria
	 * cache_expiracion: 600 
	 * filas: n
	 */
	function configuracion($parametros)
	{
		// Recupera el mail de la configuracion (mdp_configuracion) o de los datos personales el mail principal
		$sql = "SELECT email, recibe_mail_mensajes, email_temporal FROM f_configuracion_email_persona ({$parametros['persona']})";
		$datos = kernel::db()->consultar_fila($sql, db::FETCH_ASSOC);
		return $datos;
	}
    
	/**
	 * parametros: persona, recibe_mail_mensajes, email
	 * param_null: email
	 * cache: no
	 * filas: n
	 */
	function actualizar_configuracion($parametros)
	{
	$email = ($parametros['email'])? $parametros['email'] : "''";
		$sql =  "SELECT * FROM f_configuracion_email_actualizar (
									{$parametros['persona']},
									$email,
									'MP',
									{$parametros['recibe_mail_mensajes']}::smallint
									)";
	$datos = kernel::db()->ejecutar($sql);
		return $datos;
	}

    /**
     * parametros: persona
     * cache: no
     * filas: 1
     */
    function existe_configuracion_email($parametros)
    {
        $sql = "SELECT EXISTS(
                    SELECT 1
                    FROM mdp_personas_contactos
                    WHERE persona = {$parametros['persona']}
                    AND contacto_tipo = 'MP'
                ) AS existe";

        $fila = kernel::db()->consultar_fila($sql);
        return $fila['existe'];
    }

    /**
     * parametros: persona, email
     * cache: no
     * filas: n
     */
    function insertar_configuracion_email($parametros)
    {
        $sql = "INSERT INTO mdp_personas_contactos (email, persona, contacto_tipo) VALUES ({$parametros['email']}, {$parametros['persona']}, 'MP')";

        $datos = kernel::db()->ejecutar($sql);
        return $datos;
    }

    /**
     * parametros: persona, email
     * cache: no
     * filas: n
     */
    function actualizar_configuracion_email($parametros)
    {
        $sql = "UPDATE mdp_personas_contactos
                SET email = {$parametros['email']}
                WHERE persona = {$parametros['persona']} AND contacto_tipo = 'MP'";

        $datos = kernel::db()->ejecutar($sql);
        return $datos;
    }

    /**
     * parametros: persona, recibe_mail_mensajes
     * cache: no
     * filas: n
     */
    function actualizar_configuracion_checks($parametros)
    {
        $sql = "UPDATE mdp_configuracion
                SET recibe_mail_mensajes = {$parametros['recibe_mail_mensajes']}::smallint
                WHERE persona = {$parametros['persona']}";
        $datos = kernel::db()->ejecutar($sql);
        return $datos;
    }
	
	/**
	 * parametros: usuario, actual_pass, nuevo_pass
	 * no_quote: actual_pass, nuevo_pass
	 * cache: no
	 * filas: 1
	 */
	function actualizar_password($parametros)
	{
		/*
		@TODO = Cambiar este UPDATE por una funcion con codigos de retorno y validaciones...
						Falta setear el campo fecha_vencimiento_clave
		*/
		$sql = "SELECT clave FROM mdp_personas WHERE usuario = {$parametros["usuario"]}";
		$respuesta = kernel::db()->consultar_fila($sql);
		$resp = array();		
		
		if (! guarani::chequear_clave($parametros['actual_pass'], $respuesta['clave'])) {
			$resp[0] = '-3';
			$resp[1] = kernel::traductor()->trans('configuracion.error_clave_actual');
			return $resp;
		}
		
		if (guarani::chequear_clave($parametros['nuevo_pass'], $respuesta['clave'])) {
			$resp[0] = '-3';
			$resp[1] = kernel::traductor()->trans('configuracion.error_clave_repetida');
			return $resp;
		}
		
		$nueva_clave = kernel::db()->quote(guarani::encriptar($parametros["nuevo_pass"]));
		$sql = "UPDATE	mdp_personas 
				SET		clave	= $nueva_clave
				WHERE	usuario = {$parametros["usuario"]}
				";
		$respuesta = kernel::db()->consultar($sql);
		$resp[0] = '1';
		$resp[1] = kernel::traductor()->trans('configuracion.pass_ok');
		return $resp;
	}

	/**
	 * parametros: pais, tipo_doc, doc
	 * cache: no
	 * filas: 1
	 */
	function data_recuperar_pass($parametros)
	{
		// Recupera el mail de la persona. Busca la persona por su nro de documento.
		$sql = "SELECT	persona 
				FROM	mdp_personas_documentos
				WHERE	pais_documento	= {$parametros["pais"]}
				AND		tipo_documento	= {$parametros["tipo_doc"]}
				AND		nro_documento	= {$parametros["doc"]}
				";
		$persona = kernel::db()->consultar_fila($sql,db::FETCH_ASSOC);
		$respuesta = array();
		if (empty($persona)) {
			// Error. No existe la persona segun el documento.
			$respuesta[0] = '-1';
			$respuesta[1] = 'No existe la persona segun el nro de documento';
			$respuesta[2] = '';
			$respuesta[3] = '';
			$respuesta[4] = '';
		 } else {
			// Recupero el mail y datos de la persona
			$email_persona = $this->configuracion($persona);
                        $datos_persona = $this->datos_basicos($persona);
			if (isset($email_persona['email'])) {
				$respuesta[0] = '1';
				$respuesta[1] = 'Existe la persona';
				$respuesta[2] = $email_persona['email'];
				$respuesta[3] = $persona['persona'];
                                $respuesta[4] = $datos_persona['identificacion'];
			} else {
				$respuesta[0] = '-1';
				$respuesta[1] = 'La persona no tiene cuenta de email registrado en el sistema';
				$respuesta[2] = '';
				$respuesta[3] = $persona['persona'];
				$respuesta[4] = '';
			}
		 }
		
		return $respuesta;
	}
        
        /**
	 * parametros: persona, email
	 * cache: no
	 * filas: 1
	 */
	function update_email_temporal($parametros)
	{
		$sql = "UPDATE mdp_personas SET email_temporal = {$parametros["email"]} WHERE persona = {$parametros["persona"]}";
		kernel::db()->ejecutar($sql,db::FETCH_NUM);
	}
	
	/**
	 * parametros: persona
	 * cache: no
	 * filas: 1
	 */
	function blanquear_email_temporal($parametros)
	{
		$sql = "UPDATE mdp_personas SET email_temporal = NULL WHERE persona = {$parametros["persona"]}";
		
		kernel::db()->ejecutar($sql,db::FETCH_NUM);
	}
	
    /**
     * parametros: persona, token
     * no_quote: persona, token
     * cache: no
     */
    function update_token($parametros)
    {
        \guarani::act('act_personas')->actualizar_token($parametros['persona'], $parametros['token']);
    }

    /**
     * parametros: persona
     * no_quote: persona
     * cache: no
     */
    function limpiar_token($parametros)
    {
        \guarani::act('act_personas')->limpiar_token($parametros['persona']);
    }

    /**
     * parametros: token
     * no_quote: token
     * cache: no
     * filas: 1
     */
    function buscar_por_token($parametros)
    {
        return \toba::consulta_php('co_personas')->get_persona_x_token($parametros['token']);
    }

    /**
	 * parametros: token
	 * cache: no
	 * filas: 1
	 */
	function buscar_email_temporal_por_token($parametros)
	{
		$sql = "SELECT email_temporal FROM mdp_personas WHERE token = {$parametros["token"]}";
		$respuesta = kernel::db()->consultar_fila($sql,db::FETCH_NUM);
		
		return $respuesta[0];
	}
	
	/**
	 * parametros: persona, password
	 * cache: no
	 * filas: 1
	 */
	function reset_password($parametros)
	{
		/*
		@TODO = Falta actualiar el campo fecha_vencimiento_clave
		*/
		$sql = "UPDATE mdp_personas SET	clave = {$parametros["password"]} WHERE	persona  = {$parametros["persona"]}";
		kernel::db()->ejecutar($sql);
	}

	/**
	 * parametros: persona, id_imagen
	 * cache: no
	 * filas: 1
	 */
	function asociar_foto($parametros)
	{
		try {
			kernel::db()->abrir_transaccion();
			$sql_sinc = "	UPDATE	mdp_personas_foto
							SET		sincronizada = 1
							WHERE	persona = {$parametros['persona']}
			";
			$sql_img = "	UPDATE	mdp_personas
							SET		id_imagen = {$parametros['id_imagen']}
							WHERE	persona = {$parametros['persona']}
			";
			kernel::db()->ejecutar($sql_sinc);
			kernel::db()->ejecutar($sql_img);
			kernel::db()->cerrar_transaccion();
		} catch (ErrorKernelDb $e) {
			$msj = "Error asociando la foto a la persona '{$parametros['persona']}'.";
			kernel::db()->abortar_transaccion($msj);
		}
	}
	
	/**
	 * parametros: persona
	 * cache: no
	 * filas: 1
	 */
    function datos_persona_header($parametros)
    {
        $sql = "SELECT      
					(mdp_personas.apellido::text || ' ' ::text) || mdp_personas.nombres::text AS apellido_nombres,
					date_part('year',age( mdp_personas.fecha_nacimiento )) AS edad,
					(mdp_tipo_documento.desc_abreviada::text || ' ' ::text) || mdp_personas_documentos.nro_documento::text AS documento_principal,
					mdp_personas.id_imagen as id_imagen
				FROM mdp_personas
				JOIN mdp_personas_documentos ON mdp_personas_documentos.documento = mdp_personas.documento_principal
				JOIN mdp_tipo_documento ON mdp_tipo_documento.tipo_documento = mdp_personas_documentos.tipo_documento
				WHERE       
					mdp_personas.persona = {$parametros['persona']}";
                
		$datos = kernel::db()->consultar_fila($sql);

		if (!empty($datos['id_imagen'])) {
			$datos['url_imagen'] = alumno_foto::url_imagen($datos['id_imagen']);
			$datos['url_imagen_mediana'] = alumno_foto::url_imagen($datos['id_imagen'], alumno_foto::TAMANIO_MEDIANA);
			$datos['url_imagen_grande'] = alumno_foto::url_imagen($datos['id_imagen'], alumno_foto::TAMANIO_GRANDE);
		} else {
			$datos['url_imagen'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm.png');
			$datos['url_imagen_mediana'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png');
			$datos['url_imagen_grande'] = kernel::vinculador()->vinculo_recurso('img/iconos/mm_grande.png');
		}
		
		$datos['apellido_nombre_edad'] = $datos['apellido_nombres'] . ' (' . $datos['edad'] . ' años)';
		return $datos;
	}
	
	//--------------------------------------------------------------------------
	//---- Alta de persona para inscripción a cursos ---------------------------
	//--------------------------------------------------------------------------
	
	/**
	 * Inserta los datos principales de la persona en mdp_personas
	 * 
	 * parametros: apellido, nombres, sexo, fecha_nacimiento, localidad_nacimiento, nacionalidad, pais_origen, usuario, clave
	 * no_quote: apellido, nombres, apellido_elegido, nombres_elegido, sexo, fecha_nacimiento, localidad_nacimiento, nacionalidad, pais_origen, usuario, clave, identidad_genero, identidad_genero_otro
	 * cache: no
	 * filas: 1
	 * 
	 * @return int id de la persona insertada
	 */
	function insertar_cabecera_persona($parametros)
	{
		abm::alta('mdp_personas', $parametros);
		
		// Se obtiene el ID de la persona insertada
		$sql = "SELECT currval('mdp_personas_seq') as secuencia";
		$secuencia = kernel::db()->consultar_fila($sql);
		return $secuencia['secuencia'];
	}
	
	/**
	 * Inserta el documento principal en mdp_personas_documentos y asocia el documento
	 * insertado con mdp_personas.documento_principal
	 * 
	 * parametros: persona, pais_documento, tipo_documento, nro_documento
	 * no_quote: persona, pais_documento, tipo_documento, nro_documento,validado_con_renaper
	 * cache: no
	 * filas: 1
	 */
	function insertar_documento_principal($parametros)
	{
		abm::alta('mdp_personas_documentos', $parametros);
		
		// Se obtiene el ID del documento insertado
		$sql = "SELECT currval('mdp_personas_documentos_seq') as secuencia";
		$secuencia = kernel::db()->consultar_fila($sql);
		$documento = $secuencia['secuencia'];
		
		// Se vincula el documento insertado con la persona
		$datos_update = array('documento_principal' => $documento);
		$clave = array('persona' => $parametros['persona']);
		abm::modificacion('mdp_personas', $datos_update, $clave);
	}
    
    /**
	 * Inserta otro documento, se utiliza para agregar el cuil en renaper
	 * 
	 * parametros: persona, pais_documento, tipo_documento, nro_documento, validado_con_renaper
	 * no_quote: persona, pais_documento, tipo_documento, nro_documento, validado_con_renaper
	 * cache: no
	 * filas: 1
	 */
	function insertar_otro_documento($parametros)
	{
		abm::alta('mdp_personas_documentos', $parametros);
	}
	
	/**
	 * Inserta el mail principal en mdp_personas_contactos
	 * 
	 * parametros: persona, email
	 * no_quote: persona, email
	 * cache: no
	 * filas: 1
	 */
	function insertar_mail_principal($parametros)
	{
		$parametros['contacto_tipo'] = \persona::mail_primario;
		abm::alta('mdp_personas_contactos', $parametros);
	}
	
	/**
	 * Inserta documentos digitales presentados por una persona
	 * 
	 * parametros: persona, requisito, id_documento
	 * no_quote: persona, requisito, id_documento
	 * cache: no
	 * filas: 1
	 */
	function insertar_documento_digital($parametros)
	{
		abm::alta('sga_requisitos_digitales', $parametros);
	}
	
	/**
	 * parametros: persona, tipo_documento
	 * param_null: tipo_documento
	 * cache: memoria
	 * filas: n
	 */
	function get_documentos_persona($parametros)
	{
		$where = "";
		if(!is_null($parametros['tipo_documento'])){
			$where = "AND tipo_documento = {$parametros['tipo_documento']}";
		}
		
		$sql = "
			SELECT pais_documento, tipo_documento, nro_documento
			FROM mdp_personas_documentos
			WHERE persona = {$parametros['persona']}
			$where
		";
		
        return kernel::db()->consultar($sql);
	}
            		/**
     * Devuelve segun el parametro nombre_persona_doc_oficiales como se obtiene el nombre elegido para los documentos oficiales
     *
     * @return string 
     */
    function param_nombre_persona_doc_oficiales() {
        $param_nombre_persona_doc_oficiales = parametros_sistema::get_valor('nombre_persona_doc_oficiales');
        $apellido_y_nombre = 'vw_personas.apellido_nombres';
        if ($param_nombre_persona_doc_oficiales == persona::nombre_persona_doc_oficiales_n) {
            $apellido_y_nombre = 'vw_personas.apellido_nombres';
        } elseif ($param_nombre_persona_doc_oficiales == persona::nombre_persona_doc_oficiales_s) {
            $apellido_y_nombre = 'vw_personas.apellido_nombres_elegido';
        } else {
            //Las personas se visualizan con las iniciales del nombre,el apellido completo, día y año de nacimiento, número de documento y nombre elegido
            $apellido_y_nombre = "
                        CONCAT(
                            SUBSTRING(vw_personas.nombres FROM 1 FOR 1), -- Obtener la inicial del nombre
                            vw_personas.apellido,                       -- Concatenar el apellido
                            TO_CHAR(vw_personas.fecha_nacimiento, 'DDYYYY'),   -- Concatenar día y año de nacimiento
                            vw_personas.documento,                      -- Concatenar el número de documento
                            vw_personas.nombres_elegido                   -- Concatenar el campo elegido
                        ) ";
        }

        return $apellido_y_nombre;
    }
}