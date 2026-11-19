<?php
 return array (

	//--------------------------------------------------------------------------
	//---- PERFIL: Administración ----------------------------------------------
	//--------------------------------------------------------------------------

	'administracion'			=> 'Administración',
	
	'header.menu.formulario_preinscripcion'		=> 'Configuración de formulario',
	'header.menu.config_form'					=> 'Configuración de campos de formulario de Preinscripción',
	'header.menu.copiar_config_form'			=> 'Copiar configuración',
	'header.menu.importar_config_form'			=> 'Importar configuración de otra base',
	'header.menu.limpiar_cache'					=> 'Borrar caché APC',
	'header.menu.perfil_datos'					=> 'Perfil de datos',
	'header.menu.admin_turnos'					=> 'Turnos',
	'header.menu.tests'							=> 'Pruebas',
	'header.menu.test_mail'						=> 'Probar correo',
	'header.menu.test_cnx'						=> 'Probar conexión con G3',
	 
	'login_admin_título'		=> 'Identificación',
	'tit_cambio_clave_default'	=> 'Cambio de clave default',
	
	'admin_login_error_clave'	=> 'El usuario no existe o la clave es incorrecta',
	
	'admin_error_user_vacio'	=> 'Debe ingresar un nombre de usuario',
	'admin_error_user_repetido'	=> 'Ya existe un administrador con ese nombre de usuario',
	
	'cambiar_clave'				=> 'Cambiar clave',
	'desc_cambio_clave_default' => 'Para ingresar al sistema, es necesario cambiar la clave por defecto',
	'nueva_clave'				=> 'Nueva clave',
	'repetir_nueva_clave'		=> 'Repetir nueva clave',
	'cambio_clave_admin_ok'		=> 'Clave cambiada exitosamente',
	 
	'configuracion_campos'		=> 'Configuración de campos de formulario',
	
	'pantalla'					=> 'PANTALLA',
	'seccion'					=> 'SECCIÓN',
	 
	'campo'						=> 'Campo',
	'etiqueta'					=> 'Etiqueta',
	'visible'					=> 'Visible',
	'obligatorio'				=> 'Obligatorio',
	'se_imprime'				=> 'Se imprime',
	'genera_reporte'			=> 'Genera reporte',

	'tit_limpiar_cache'			=> 'Borrado de caché APC',
	'limpiar_cache'				=> 'Limpiar caché',
	'msj_limpieza_cache'		=> 'Presionar el botón para comenzar el proceso de borrado de caché.',
	'cache_borrado_ok'			=> 'La caché APC ha sido borrada exitosamente.',
	'cache_borrado_error'		=> 'Ocurrió un problema en el proceso de borrado. Una forma alternativa de borrar la caché APC es reiniciando el servidor Apache.',
	 
	//---- Copiar configuración ------------------------------------------------
	'tit_copiar_config'			=> 'Copiar configuración de campos de formulario',
	 
	'tipo_propuesta_origen'		=> 'Tipo de propuesta origen',
	'tipo_propuesta_destino'		=> 'Tipo de propuesta destino',
	'copiar'					=> 'Copiar',
	'error_tipos_iguales'		=> 'Los tipos seleccionados deben ser distintos',
	'copia_config_ok'			=> 'La configuración ha sido copiada exitosamente',
	'copia_config_error'		=> 'Ha ocurrido un error durante la copia de configuración',
	 
	//---- Importar configuración de otra base ---------------------------------
	'tit_import_config'		=> 'Importar configuración de otra base',
	 
	'import.db.dbname'		=> 'Base de datos origen',
	'import.db.host'		=> 'Host', 
	'import.db.port'		=> 'Puerto', 
	'import.db.pdo_user'	=> 'Usuario', 
	'import.db.pdo_passwd'	=> 'Password', 
	'importar'				=> 'Importar',
	'importar_msj'			=> 'Importar a la base <strong>%1%</strong>, corriendo en <strong>%2%:%3%</strong>, la configuración de campos de formulario definida en la siguiente base:',
	 
	'import_config_ok'		=> 'Configuración importada con éxito.',
	'import_error_bd'		=> 'Se ha producido un error de conexión. Por favor, verificar los parámetros ingresados.',
	'import_config_error'	=> 'Se han detectado errores en los datos ingresados.',
	
	//---- Configuración de perfil de datos ------------------------------------
	'perfil_datos.titulo' => 'Configuración de perfil de datos',
	'perfil_datos.info' => '<ul>'
	 . '<li>Con esta operación se puede restringir la oferta de propuestas según los filtros especificados. </li>'
	 . '<li>Si para algún grupo no se especifican restricciones, es equivalente a que estén todos habilitados.</li>'
	 . '<li>Después de guardar, podrá verse en el esquema de la derecha la oferta resultante.</li>'
	 . '<li>Tener en cuenta que si no hay propuestas o ubicaciones disponibles, el aspirante no tendrá opciones para preinscribirse en esa línea.</li>'
	 . '</ul>',
	'perfil_datos.ras' => 'Responsables académicas',
	'perfil_datos.tipos_propuesta' => 'Tipos de propuestas',
	'perfil_datos.propuestas' => 'Propuestas',
	'perfil_datos.tipos_ubicaciones' => 'Ubicaciones',
	'perfil_datos.sin_propuestas' => '-- Sin propuestas --',
	'perfil_datos.sin_ubicaciones' => '-- Sin ubicaciones --',
	'perfil_datos.todos' => 'Seleccionar todos',
	'perfil_datos.ninguno' => 'Deseleccionar todos',
	 
	//---- Configuración de turnos ---------------------------------------------
	'turnos.error_asignacion_turno' => 'No pudo asignarse el turno solicitado. Por favor, intentá nuevamente.',
	 
	'tit_admin_turnos'			=> 'Administración de turnos',
	
	'turnos.descripcion' => 'Se generarán turnos automáticamente siguiendo la configuración que se complete a continuación:',
	
	'turno.msg_config_error' => 'Se han encontrado errores en la configuración',
	'turno.cant_turnos' => 'Se han generado %1% turnos con las configuraciones ingresadas. Quedan %2% disponibles.',
	
	'turnos.sin_configuracion' => 'No se han configurado turnos.',
	 
	'turnos.periodos_configurados' => 'Períodos configurados',
	'turnos.th.fecha_desde' => 'Fecha desde',
	'turnos.th.fecha_hasta' => 'Fecha hasta',
	'turnos.th.ras' => 'Responsables Académicas',
	 
	'turnos.agregar_periodo' => 'Agregar período',
	 
	'turnos.fechas' => 'Fechas',
	'turnos.intervalo_atencion' => 'Intervalos de atención',
	'turnos.intervalo_atencion_descr' => 'Se debe ingresar al menos un intervalo de atención',
	'turnos.intervalo_1' => 'Intervalo #1',
	'turnos.intervalo_2' => 'Intervalo #2',
	'turnos.opciones' => 'Opciones',
	'turnos.dias_atencion' => 'Días de atención',
	'turno.ayuda_hora_hasta' => 'La hora hasta no se incluye en la generación de turnos. Opera como límite de rango en modo menor estricto.',
	'turnos.ras' => 'Responsables Académicas',
	'turnos.fecha_inicio' => 'Inicio', 
	'turnos.hora_inicio' => 'Hora desde',
	'turnos.fecha_fin' => 'Fin',
	'turnos.hora_fin' => 'Hora hasta',
	'turnos.minutos_intervalo' => 'Intervalo entre turnos',
	'turnos.cant_operadores' => 'Cantidad de operadores',
	'turno.atiende_feriados' => 'Generar turnos para días feriados y no laborables',
	'turno.atiende_feriados_ayuda' => 'Si se marca esta opción, se generarán turnos INCLUSO para las fechas cargadas en la tabla "sga_diasnohabiles"',
	'turno.atiende_lunes' => 'Lunes',
	'turno.atiende_martes' => 'Martes',
	'turno.atiende_miercoles' => 'Miércoles',
	'turno.atiende_jueves' => 'Jueves',
	'turno.atiende_viernes' => 'Viernes',
	'turno.atiende_sabado' => 'Sábado',
	'turno.atiende_domingo' => 'Domingo',
	 
	'turnos.generar_turnos' => 'Generar turnos',
	 
	'turnos.error_fecha_fin' => 'No puede ser anterior a la fecha desde',
	'turnos.error_hora_fin' => 'No puede ser anterior a la hora desde',
	'turnos.error_hora_intervalos' => 'El intervalo #2 debe comenzar luego de la finalización del intervalo #1',
	'turnos.error_intervalo' => 'El menor intervalo entre turnos permitido es un (1) minuto',
	'turnos.error_operadores' => 'La cantidad de operadores debe ser mayor o igual que uno (1)',
	'turnos.error_dias_atencion' => 'Debe seleccionarse al menos un día de atención',
	'turnos.error_solapamiento' => 'Las fechas ingresadas se solapan con otro período de turnos de alguna de las Resp. Acad. seleccionadas',
	'turnos.error_ras' => 'Debe seleccionarse al menos una Responsable Académica',
	
	//---- Testear configuración de correo -------------------------------------
	'tit_test_mail'				=> 'Probar correo',
	'test_mail'					=> 'Probar correo',
	'config_mail'				=> 'Configuración de correo',
	'mail_de_prueba'			=> 'E-mail de prueba',
	 
	'config_mail_default'		=> 'Este es el usuario de ejemplo. <br/>La configuración debe ser modificada en la sección \'smtp\' del archivo config.php',
	'desc_datos_mail'			=> 'Esta es la configuración del servidor de e-mail, según consta en la sección \'smtp\' del archivo config.php:',
	
	'mail_from'					=> 'From',
	'mail_from_name'			=> 'From name',
	'mail_host'					=> 'Host',
	'mail_seguridad'			=> 'Seguridad',
	'mail_auth'					=> 'Autenticación',
	'mail_port'					=> 'Puerto',
	'mail_usuario'				=> 'Usuario',
	'mail_clave'				=> 'Clave',
	'mail_reply_to'				=> 'Responder a',
	'mail_smtp_debug'			=> 'SMTP Debug',
	'mail_auth_type'			=> 'Tipo autenticación',
	'mail_oauth2_email'			=> 'Email para XOAUTH2',
	'mail_oauth2_client_id'		=> 'ID de cliente para XOAUTH2',
	'mail_oauth2_client_secret'	=> 'Secreto de cliente para XOAUTH2',
	'mail_oauth2_refresh_token'	=> 'Refresh Token para XOAUTH2',
	
	'prueba_mail'				=> 'Ingresar una dirección de correo para intentar enviar un e-mail de prueba',
	'enviar_mail'				=> 'Enviar e-mail',
	 
	'asunto_email_prueba'		=> 'SIU-Preinscripción :: Prueba de envío exitosa',
	'cuerpo_email_prueba'		=> 'Este es un e-mail enviado automáticamente por el sistema SIU-Preinscripción para verificar que la configuración del servidor de correo es correcta.',
	 
	'mensaje_prueba_enviado'	=> 'Se ha enviado un mensaje de prueba a la casilla ingresada. Por favor, verificar si ha sido recibido correctamente.',
	'mensaje_prueba_error'		=> 'El mensaje de prueba no ha podido ser enviado. Por favor, revisar los parámetros de configuración del servidor de correo.',
	 
	//---- Prueba de conexión con entidades G3 ---------------------------------
	
	'test_cnx.titulo' => 'Prueba de conexión con G3',
	
	'test_cnx.desc_datos_cnx' => 'Esta es la configuración de la conexión con la base de datos de Guaraní 3, según consta en el archivo config.php:',
	'test_cnx.vendor' => 'Vendor',
	'test_cnx.dbname' => 'Base',
	'test_cnx.schema' => 'Esquema',
	'test_cnx.host' => 'Host',
	'test_cnx.port' => 'Puerto',
	'test_cnx.pdo_user' => 'Usuario',
	'test_cnx.pdo_pass' => 'Password',
	
	//---- Módulo de turnos: e-mail de recordatorio ----------------------------
	'turnos.recordatorio.asunto'	=> 'SIU-Preinscripción :: Recordatorio de turnos',
	'turnos.recordatorio.cuerpo'	=> '%nombre%,<br/><br/>
										Te recordamos que se encuentra próximo tu turno para presentar la documentación de inscripción a propuestas:<br/>
										<ul>
											<li><strong>Fecha</strong>: %fecha% </li>
											<li><strong>Hora</strong>: %hora% </li>
											<li><strong>Ventanilla</strong>: %operador% </li>
										</ul>
										Propuestas en las que te preinscribiste:<br/>
										%propuestas%
										Recordá llevar impreso el comprobante y los requisitos solicitados.<br/><br/>
										Te esperamos!',
	 
	//--------------------------------------------------------------------------
	//---- PERFIL: Alumno ------------------------------------------------------
	//--------------------------------------------------------------------------
	 
	'nombre_sistema'						=> 'SIU-Preinscripción',
	'tit_acceso'							=> 'Acceso al sistema',
	'tit_alta'								=> 'Crear nuevo usuario',
	'header.menu.censo'						=> 'Datos personales',
	'header.menu.impresion'					=> 'Imprimir formulario',
	'header.bienvenido'						=> 'Bienvenido',
	'header.cerrar_sesion'					=> 'Cerrar sesión',
	'ingresa_tus_datos'						=> 'Ingresá tus datos',
	'email'									=> 'E-mail',
	'email_rep'								=> 'Repetir e-mail',
	'clave'									=> 'Clave',
	'repetir_clave'							=> 'Reingresá la clave',
	'ingresar'								=> 'Ingresar',
	 
	'usuario_nuevo'							=> '¿Usuario nuevo en el sistema?',
	'crear_nuevo_usuario'					=> 'Registrate',
	
	'olvidaste_clave'						=> '¿No recordás tu contraseña?',
	'recuperar_clave'						=> 'Recuperar acceso',

        'login.con_email'                                             => 'Ingresá con tu Email',
        'login.con_documento'                                         => 'Ingresá con tu Documento',
	'login.con_cuentas_ext'                                       => 'Ingresá con Cuentas Externas',
	'login.msg_alta_se'						=> 'El usuario creado en esta pantalla quedará asociado a la cuenta de %1% %2%',
	'login.se.aclaracion_clave'				=> 'La clave solicitada es para el ingreso tradicional al sistema. No necesariamente tiene que ser la misma que en %1%.',
	'login.sep_msg'							=> 'O ingresá con:',
	'login.msg_alta_se'						=> 'El usuario creado en esta pantalla quedará asociado a la cuenta de %1% %2%',
	'login.se.aclaracion_clave'				=> 'La clave solicitada es para el ingreso tradicional al sistema. No necesariamente tiene que ser la misma que en %1%.',
	 
	'login.error.incorporado_g3'			=> 'Ya sos alumno de la Institución. En adelante, utilizá la interfaz de <strong>autogestión</strong> para tus trámites.',
	'login.error.preinsc_copiadas'			=> 'Las preinscripciones que no hiciste efectivas podés gestionarlas con la operación <strong>Trámites » Preinscripción a Propuestas</strong>',
     
	'login.error.campo_obligatorio'			=> 'El campo <strong>%campo%</strong> es obligatorio.',
	'login.error.campo_invalido'			=> 'El campo <strong>%campo%</strong> es inválido.',
	'login.error.usuario_no_encontrado'             => 'No se encontró usuario con el número de documento <strong>%documento%</strong>.',

	'generar_usuario'						=> 'Generar usuario',
	'aceptar'								=> 'Aceptar',
	'cancelar'								=> 'Cancelar',
	'volver'								=> 'Volver',
	'limpiar'								=> 'Limpiar',
	'error'									=> 'Error',
	'buscar'								=> 'Buscar',
	'seleccionar'							=> 'Seleccionar',
	'ocultar'								=> 'Ocultar',
	'version'								=> 'Versión',
	 
	'pagina'								=> 'Página',
	'de'									=> 'de',
	 
	'form_sin_cambios'						=> 'No hay cambios para guardar',
	'busqueda_sin_resultados'				=> 'La búsqueda no arrojó resultados',
	 
	'titulo_datos_personales'				=> 'SIU-Preinscripción',
	'guardar_datos_personales'				=> 'Guardar',
	 
	'texto_portada'							=> 'Antes de comenzar el proceso de preinscripción asegurate de tener
												una dirección de correo electrónico válida y que funcione correctamente. <br/><br/>
												Si ya te preinscribiste, podés ingresar para completar tus datos escribiendo 
												el e-mail y la clave que ingresaste en el proceso de preinscripción.<br/><br/>
												Tu preinscripción no está completa hasta que presentes toda la documentación
												y el formulario obtenido por este sistema, firmado por vos, en Sección Alumnos de la Facultad.',
	
	'confirmar'								=> 'Confirmar',
	'operacion_invalida'					=> 'Operación inválida',
	 
	//---- Error configuración SMTP --------------------------------------------
	'asoc_se.titulo'						=> 'Autenticación externa',
	'asoc_se.nombre_operacion'				=> 'Asociación con servicios de autenticación externos',
	'asoc_se.descripcion'					=> 'La casilla de e-mail <strong>%1%</strong>, correspondiente a tu usuario de <strong>%2%</strong>, ya se encuentra registrada para otro usuario de Preinscripción. <br/><br/>
												Si sos el usuario asociado a ese e-mail, por favor ingresá tu clave de Preinscripción para poder acceder con %2%. <br/><br/>
												Si no recordás tu clave de Preinscripción podés recuperarla mediante el siguiente link:',
	'asoc_se.error_clave'					=> 'La clave ingresada no coincide con la registrada en el sistema',
	'asoc_se.cuenta_vinculada_ok'			=> 'Tu cuenta de Preinscripción ha sido vinculada con %1%. Ahora podrás ingresar por este medio o por el método de acceso tradicional.',
	
	 
	//---- Error configuración SMTP --------------------------------------------
	'smpt_no_configurado'					=> 'El servidor de correo no ha sido configurado todavía. Es un requisito indispensable para el funcionamiento del sistema.',
	 
	//---- Error versiones de sistema y BD no compatibles ----------------------
	'versiones_no_compatibles'				=> 'Las versiones del sistema y de la base de datos no son compatibles',
	 
	//---- Error de configuración de conexión a la BD --------------------------
	'error_cnx_bd'							=> 'Error de conexión con la base de datos.',
	 
	//---- Impresión de formulario ---------------------------------------------
	'imprimir'								=> 'Imprimir',
	'header_imprimir'						=> 'Imprimir formulario',
	//'faltan_completar_campos'				=> 'Te faltan completar los siguientes campos del formulario:',
	'impresion_campos_faltantes'			=> 'Para finalizar la preinscripción es necesario que completes todos los campos obligatorios.',
	'impresion_campos_faltantes_y_turno'	=> 'Para finalizar la preinscripción es necesario que completes todos los campos obligatorios y que selecciones un turno para concurrir a presentar la documentación.',
	'cambios_desde_impresion'				=> '<span class="subrayado">ATENCIÓN</span>: has realizado modificaciones desde la última impresión. Deberás generar un nuevo comprobante para que el mismo sea válido al momento de presentarlo.',
	 
	//---- Registro de nuevo usuario -------------------------------------------
	'msj_advertencia_alta'					=> '<strong>ATENCIÓN</strong>: Los datos que cargues en esta pantalla serán utilizados para validar tu identidad y acceder a tu cuenta. Por favor verificá que los mismos sean correctos antes de generar el Usuario.',
	'datos_usuario'							=> 'Datos del usuario',
	'mensaje_alta_iniciada'					=> 'Tus datos se grabaron correctamente. <br/>
												Para activar tu acceso a Preinscripción, ingresá a tu cuenta de correo y seguí los pasos indicados en el mail que te enviamos.<br/>
												Si no lo recibiste, puede estar en la carpeta de correo no deseado (SPAM). <br/>
												Si no, podés solicitar el reenvío del mail clickeando en este <a href="%1%">link</a>.',
	'mensaje_clave_reset'					=> 'Tu clave fue actualizada correctamente. <br/>
												Ya podés utilizarla para ingresar al sistema.',
	'mensaje_primer_acceso'					=> 'Tu cuenta ha sido confirmada exitosamente. Ahora podés elegir la propuesta. Luego, completá los datos solicitados, 
												y cuando termines imprimí el formulario para presentar en la Sección Alumnos de tu Facultad.',
	'no_existen_periodos'					=>	'No existen períodos de inscripción definidos',
	'alta.error.alumno_g3'					=> 'Ya sos alumno de la Institución. Si querés preinscribirte a una nueva Propuesta, utilizá la operación Trámites » Preinscripción a Propuestas de la interfaz de autogestión.',
	'alta.error.alumno_g3_ayuda'			=> '<br> Si tenés problemas con la preinscripción podés utilizar la <a target="_blank" href="%link%">%link% </a>para contactarte con la universidad.',
	'alta.error.email_duplicado'			=> 'Ya existe el email en la base de datos ',
	'alta.error.docente_g3'					=> 'Sos docente de la Institución. Si querés preinscribirte a una nueva Propuesta, utilizá la operación Trámites » Preinscripción a Propuestas del perfil Preinscripción de la interfaz de autogestión.',
	'alta.error.persona_g3'					=> 'Ya estás registrado en la Institución. Si querés preinscribirte a una nueva Propuesta, utilizá la operación Trámites » Preinscripción a Propuestas de la interfaz de autogestión.',
	'alta.error.perfil_valido_g3'			=> 'Ya estás registrado en la Institución. Si querés preinscribirte a una nueva Propuesta, utilizá la operación Trámites » Preinscripción a Propuestas de la interfaz de autogestión.',
	
	//---- Mail ----------------------------------------------------------------
	'remitente_email_crear_usuario'			=> 'SIU-Preinscripción',
	'asunto_email_crear_usuario'			=> 'Creación de usuario de SIU-Preinscripción',
	'cuerpo_email_crear_usuario'			=> 'Este e-mail fue enviado para iniciar el proceso de activación de la cuenta de Preinscripción asociada con la dirección de e-mail <strong>%mail%</strong>.
												Por favor, verificá que los datos ingresados sean correctos: <br/>
												<ul>
													<li><strong>Apellido</strong>: %apellido%</li>
													<li><strong>Nombres</strong>: %nombres%</li>
													<li><strong>Nacionalidad</strong>: %nacionalidad%</li>
													<li><strong>País emisor del documento</strong>: %pais_documento%</li>
													<li><strong>Tipo de Documento</strong>: %tipo_documento%</li>
													<li><strong>Nro. Documento</strong>: %nro_documento%</li>
												</ul>
												Si los datos son correctos, entonces seguí este link para completar el alta en Preinscripción: <a href="%link%">%link%</a> <br>
												Si clickear el link no funciona intentá copiarlo y pegarlo en la barra de direcciones de tu navegador.<br>
												Si no solicitaste este mail, simplemente ignoralo.
												<br><br><h2 style="background-color:MediumSeaGreen;"><b>Guardá este código para recuperar la cuenta en caso que lo necesites: %nro_preincripcion%</b></h2>',
	'asunto_email_recuperar_clave'			=> 'Asistencia de recuperación de clave - SIU-Preinscripción',
	'cuerpo_email_recuperar_clave'			=> 'Este mail fue enviado para iniciar el proceso de recuperación de la cuenta asociada
												con la dirección de mail %mail%. <br>
												Para reestablecer tu clave por favor seguí este link: <a href="%link%">%link%</a> <br>
												Si clickear el link no funciona intenta copiarlo y pegarlo en la barra de direcciones. <br>
												Si no solicitaste este mail simplemente ignoralo.',
	'se_envio_email_a'						=> 'Se ha enviado un e-mail a %1%', 
	'login_error_formato_mail'				=> 'El formato del e-mail es inválido',
	'login_error_clave_invalida'			=> 'El <strong>%campo%</strong> no existe o la clave es incorrecta.
												<br /> Si olvidaste tu clave podes <a href="%url_olvide_pass%" class="no-ajax">solicitar una nueva</a>',
	'email_no_validado'						=> 'Todavía no confirmaste cuenta, por favor chequeá tu correo. Si no recibiste el e-mail de confirmación, puede estar en la carpeta de correo no deseado. Si no, clickeando <a href="%1%">aquí</a> te lo reenviaremos.',
	'error_recaptcha'						=> 'El captcha no es válido',
	'error_csrf'							=> 'Se ha producido un error de seguridad, por favor intentá nuevamente.',
	'recuperar_clave'						=> 'olvide mi contraseña',
	'descripcion_recuperar_clave'			=> 'Ingresá tu cuenta de correo y recibirás un mail con instrucciones para reestablecer tu clave',
	'no_se_envio_email_recuperar_clave'		=> 'No se pudo enviar el mail para recuperar la clave',
	'descripcion_mail_no_existente_recuperar_clave' => 'Lamentablemente no tenemos registrado los datos que especificaste',
	
	'descripcion_mail_recuperar_clave'		=> 'Para ingresar a tu cuenta seguí las instrucciones que enviamos a tu e-mail (%1%).',
	'descripcion_nueva_clave'				=> 'Ingresá tu nueva clave y podrás ingresar al sistema.',
	'nueva_clave'							=> 'Nueva clave',
	'repetir_nueva_clave'					=> 'Repetir nueva clave',
	'no_se_puede_recuperar_clave'			=> 'No se puede reestablecer la clave',
	'desc_no_se_puede_recuperar_clave'		=> 'Intenta copiar y pegar la dirección desde tu e-mail, o repetir el proceso de recuperación de clave.',
    'acceso.token_expirado'                 => 'No se puede reestablecer la contraseña debido a que el token ha expirado',
    'acceso.desc_token_expirado'            => 'Volvé a reestablecer la contraseña nuevamente.',
	'crear_usuario'							=> 'Crear nuevo usuario',
	 
	'error_emails_no_coinciden'				=> 'Las casillas de e-mail ingresadas no coinciden',
	'error_clave_no_coinciden'				=> 'Las claves ingresadas no coinciden',
	'error_documento_no_coinciden'			=> 'Los números de documento ingresados no coinciden',
	'error_clave_igual_usuario'				=> 'La clave no puede ser igual al nombre de usuario',
	'error_clave_corta'						=> 'La clave debe contener al menos %1% caracteres',
	
	'error_alta'							=> 'Ha ocurrido un error dando de alta el usuario.',
	'error_email_repetido'					=> 'El e-mail seleccionado ya está registrado.',
	'error_token'							=> 'Este link no es válido. Puede que tu cuenta ya se encuentre activada o que lo hayas copiado mal del mail que te enviamos.',
	'error_token_expiro'							=> 'Token vencido. Generar una nueva preinscripción',
	'descripcion_mail_crear_usuario'		=> 'Se ha creado la cuenta. Para completar la registración seguí el link que se te 
												envió en el mail.',
	 
	'texto_alta'							=> 'Antes de comenzar la preinscripción asegurate de tener una dirección de e-mail válida y que funcione correctamente. Completá los campos de este formulario con atención ya que te acompañarán a lo largo de tu recorrido académico en esta institución.
												<br><br>
												Una vez completados todos los campos obligatorios deberás hacer clic en el botón Finalizar.<br>
												Tu preinscripción no estará completa hasta que la documentación presentada sea revisada y validada por la institución.
												<br><br>
												Podrás ingresar nuevamente a tu cuenta de preinscripción con el Usuario y la Clave que generarás durante este proceso. ',
	
	'confirm_doc_existente_1'				=> 'Detectamos un usuario registrado con ese número de documento, por favor verificá si son tus datos así no duplicás tu preinscripción (la información se muestra en forma parcial por seguridad): <br/><br/>
												<strong>Apellido y Nombre</strong>: %1% <br/>
												<strong>Inscripto el dia</strong>: %2% <br/>
												<strong>Se envió un mail a la cuenta</strong>: %3% <br/><br/>',
	'confirm_doc_existente_2_1'				=> '¿Olvidaste tu clave? Te enviamos una nueva clave al correo declarado: ',
	'confirm_doc_existente_2_2'				=> 'Si sos la misma persona, todavía te falta activar la cuenta. Por favor revisá tu casilla de correo, te enviamos allí las instrucciones (puede estar en correo no deseado). Si no lo recibiste hacé click en reenviar correo.
												<br><br><br>
												Si no sos la misma persona te dejamos las vías de contacto para que te comuniques con nosotros para resolver el problema.',
	'confirm_doc_existente_2_3'				=> 'Si no podés ingresar a esa cuenta de correo:',
	'confirm_doc_existente_3'				=> 'Si no sos la misma persona:',
	'reenviar_mail_alta'					=> 'Reenviar mail',
	'cambiar_correo_asociado'						=> 'Cambiar correo asociado',
	'continuar_otra_persona_alta'			=> 'Generar una nueva preinscripción',
	'mensaje_recuperacion'					=> 'Se ha enviado un nuevo mail de activación. <br/>
												Para activar tu cuenta, ingresá a tu casilla y seguí los pasos indicados.',
	 
	'guardar'								=> 'Guardar',
	 
	'mensaje_carga_datos'					=> 'Completá los campos de este formulario. Una vez completado podés 
												imprimirlo y presentarte en la sede, o podés guardarlo e imprimirlo posteriormente.<br/><br/>
												Los campos marcados con <strong>(*)</strong> son obligatorios.<br/><br/>
												<strong>No necesitás cargar todos los campos obligatorios en una sola sesión.</strong> ',
	 
	'carga_datos_censales'					=> 'Carga de datos censales',
	 
	//---- Mensajes de estado --------------------------------------------------
	'alta_error'							=> 'Se han encontrado errores. No se pudo completar el registro',
	'guardado_datos_error'					=> 'Se han encontrado errores. No se pudieron guardar los cambios',
	'guardado_datos_exitoso'				=> 'Se guardó correctamente',
	 
	//---- Validaciones --------------------------------------------------------
	'form_validacion_error.vacio_impr'		=> 'Es necesario completar este campo para finalizar',
	'form_validacion_error.vacio'			=> 'Este campo es obligatorio',
	'form_validacion_error.filtro_1'		=> 'El campo sólo acepta letras',
	'form_validacion_error.filtro_2'		=> 'El campo sólo acepta valores alfanuméricos',
	'form_validacion_error.filtro_3'		=> 'El campo sólo acepta dígitos',
	'form_validacion_error.filtro_4'		=> 'El formato de la fecha es incorrecto',
	'form_validacion_error.filtro_5'		=> 'El formato de la hora es incorrecto',
	'form_validacion_error.filtro_6'		=> 'El formato del valor introducido es incorrecto',
	'form_validacion_error.filtro_7'		=> 'El formato del mail es incorrecto',
	'form_validacion_error.filtro_8'		=> 'El formato del campo es incorrecto',
	'form_validacion_error.select'			=> 'El valor provisto es inválido',
	'form_validacion_error.largo'			=> 'El largo del valor provisto excede el máximo',
	'form_validacion_error.error_cuil'		=> 'El Número de CUIL no es válido',
	'faltan_datos_discapacidad' => 'Debe seleccionar al menos una opción de la lista de discapacidad',

	'error_pais_doc_0'						=> 'No coinciden país y tipo de documento',
	'error_pais_doc_1'						=> 'El país del documento debe ser Argentina',
	 
	'error_tipo_doc_1'						=> 'El tipo de documento no puede ser Pasaporte',
	'error_tipo_doc_2'						=> 'El tipo de documento no puede ser DNI Temporario',
	'error_tipo_doc_3'						=> 'El tipo de documento tiene que ser DNI, DNI temporario, Cédula o Pasaporte',
	'error_tipo_doc_4'						=> 'El tipo de documento no puede ser Cédula de Identidad',
	'error_tipo_doc_5'						=> 'El tipo de documento tiene que ser DNI o DNI Temporario',
	'error_tipo_doc_6'						=> 'El tipo de documento debe ser DNI o CI',	 
	 
	'error_nro_documento_1'					=> 'Para el tipo de documento seleccionado sólo se aceptan dígitos',
	'error_nro_documento_2'					=> 'Para el tipo de documento seleccionado sólo se aceptan letras y dígitos',
	 
	'error_fecha_nacimiento'				=> 'Tenés que ser mayor de 15 años',
	'error_fecha_antigua'					=> 'El año debe ser posterior a 1900',
	'error_fecha_posterior'					=> 'La fecha no puede ser posterior a la fecha actual',
	'error_fecha_egreso_posterior'			=> 'La fecha de egreso no puede ser anterior a la fecha de ingreso',
	 
	'error_fecha_vencimiento_otorg'			=> 'La fecha de vencimiento no puede ser menor o igual a la fecha de otorgamiento',
	'error_fecha_vencimiento_hoy'			=> 'La fecha de vencimiento no puede ser menor a la fecha actual',
	'error_fecha_vencimiento_req'			=> 'Se debe ingresar una fecha de vencimiento para este tipo de residencia',
	 
	'error_indefinido'						=> 'Ha ocurrido un error.',
	 
	//---- Nombres de pagelets -------------------------------------------------
	'propuestas'							=> 'Propuestas',
	'datos_adicionales'						=> 'Datos adicionales',
	'datos_principales'						=> 'Datos principales',
	'datos_personales'						=> 'Datos personales',
	'financiamiento'						=> 'Financiamiento de estudios',
	'datos_laborales'						=> 'Situación laboral',
	'datos_familiares'						=> 'Situación familiar',
	'datos_estudios'						=> 'Estudios',
	'tecnologia'							=> 'Tecnología',
	'deportes'								=> 'Deportes',
	'idiomas'								=> 'Idiomas',
	'datos_salud'							=> 'Datos de salud',
	'datos_discapacidad'					=> 'Discapacidad',
	'turno_presentacion'					=> 'Presentación de documentación',
	'actuacion_docente'						=> 'Actuación docente',
	'actuacion_profesional'					=> 'Actuación profesional',
	'formacion_acad'						=> 'Formación académica',
	 
	 //---- PANTALLA: propuestas -----------------------------------------------
	'seleccion_propuesta'		=> 'Selección de propuesta',
	'elegir_propuesta'			=> 'Elegir Propuesta',
	'inscribir_propuesta'		=> 'Aceptar',
	'mensaje_elegir_propuesta'	=> 'Como primer paso, elegí la propuesta a la que querés preinscribirte.',
	'sin_inscripciones'			=> 'Todavía no te preinscribiste a ninguna propuesta',
	'propuestas_elegidas'		=> 'Propuestas elegidas',
	'msj_confirmar_baja'		=> '¿Confirmás que querés eliminar la preinsripción a ',
	'msj_una_insc_posgrado_baja'=> 'Al eliminar esta inscripción a propuesta de posgrado se eliminarán también los registros de Actuación docente, Actuación profesional y Formación académica que hayas ingresado. ¿Estás seguro de continuar?',
	'tipo_de_propuesta'			=> 'Tipo de propuesta',
	'sin_propuestas' => '-- No se encontraron propuestas disponibles --',
	 
	//---- PANTALLA: Datos principales -----------------------------------------
	'datos_principales_pers'	=> 'Datos personales',
	'datos_principales_nacim'	=> 'Datos de nacimiento',
	'datos_principales_ci'		=> 'Cédula de Identidad Argentina',
	'datos_principales_pasap'	=> 'Pasaporte',
	'datos_principales_visa'	=> 'Visa',
	'datos_principales_res'		=> 'Residencia',
	 
	'pueblos_originarios'			=> 'Pueblos originarios',
	'pertenece_pueblo_originario'	=> '¿Pertenecés a algún pueblo originario?',
	'pueblo_originario'				=> '¿A cuál?',
	'pueblo_originario_otro'		=> 'Nombre',
	
	//---- PANTALLA: Datos personales ------------------------------------------
	'domicilio_per_clases'		=> 'Domicilio durante el período de clases',
	'celular_caracteristica'	=> 'Característica de celular (sin 0)',
	'celular_valor'				=> 'Número de celular (sin 15)',
	'domicilio_procedencia'		=> 'Domicilio de procedencia (donde vivís fuera del período de clases)',
	'persona_allegada'			=> 'Datos de una persona allegada',
	'obra_social'				=> 'Obra social',
	'copiar_domicilio'			=> 'Copiar domicilio del período de clases',
	'copiar_domicilio_proc'		=> 'Copiar domicilio de procedencia',
	
	//---- PANTALLA: Financiamiento de estudios --------------------------------
	'financ_fuente'				=> '¿Cómo costeás tus estudios?',
	'financ_beca_fuente'		=> 'Fuente de la beca',
	'financ_beca_tipo'			=> 'Tipo de beca',
	'financ_beca_tipo_econom'	=> 'Tipo de ayuda económica',
	 
	//---- PANTALLA: Situación laboral -----------------------------------------
	'datos_laborales_sit'		=> 'Situación laboral',
	'datos_laborales_adic'		=> 'Datos adicionales',
	 
	//---- PANTALLA: Situación familiar ----------------------------------------
	'situacion_familiar'		=> 'Situacion familiar',
	'datos_madre'				=> 'Datos de tu madre',
	'datos_padre'				=> 'Datos de tu padre',
	
	//---- PANTALLA: Estudios --------------------------------------------------
	'datos_estudios_sec'		=> 'Nivel secundario / Polimodal',
	'datos_estudios_sup'		=> 'Otros estudios superiores',
	
	//---- PANTALLA: Tecnología ------------------------------------------------
	'dispon_pc'					=> 'Disponés de PC',
	'accede_internet_en'		=> 'Accedés a Internet',
	 
	//---- PANTALLA: Deportes --------------------------------------------------
	'pract_deportes'			=> 'Deportes',
	'listado_deportes_lugares'	=> '¿Dónde practicás?',
	'listado_deportes'			=> '¿Qué practicás?',
	 
	//---- PANTALLA: Idiomas ---------------------------------------------------
	'conoc_idiomas'				=> 'Conocimiento de idiomas',
	 
	//---- PANTALLA: Datos de salud --------------------------------------------
	'salud' => 'Datos de salud',
	'es_celiaco' => '¿Sos celíaco?',
	'discapacidad.sin_datos' => 'No has ingresado datos de discapacidad.',
	'discapacidad.agregar_dato' => 'Agregar dato',
	
	'discapacidad.datos_discapacidad' => 'Datos de discapacidad',
	'discapacidad.carga_datos_discapacidad' => 'Carga de datos de discapacidad',
	'discapacidad.tipo' => 'Tipo', 
	'discapacidad.grado' => 'Grado', 
	'discapacidad.caracter' => 'Carácter', 
	'discapacidad.tiene_cobertura_salud' => '¿Tenés cobertura de salud?', 
	'discapacidad.certificado' => '¿Tenés certificado?', 
	'discapacidad.fecha_desde' => 'Desde', 
	'discapacidad.fecha_hasta' => 'Hasta', 
	'discapacidad.observaciones' => 'Observaciones', 
	'discapacidad.th_certificado' => 'Certificado', 
	 
	 //---- PANTALLA: Discapacidad ----------------------------------------
	'discapacidad'					=> 'Discapacidad',
	'cond_discapacidad'				=> 'Condición de Discapacidad',
	'listado_discapacidad_tipos'	=> 'Discapacidad',
	'condicion_discapacidad' => 'Condición de Discapacidad',
	'tiene_cud' => 'Tenés CUD',
	 
	'disc_auditiva' => 'Auditiva',
	'disc_visual' => 'Visual',
	'disc_motora' => 'Motora',
	'disc_cond_psicosocial' => 'Condición Psicosocial',
	'disc_otra' => 'Otra',
	 
	'auditiva' => 'Auditiva',
	'aud_tipo' => '¿Tenés dificultad para oír, incluso si usás un audífono o implante coclear?',
	'forma_habitual_comunicacion' => '¿Cuál es tu forma habitual de comunicación?',
	'aud_lenguaje_senias' => 'Lengua de Señas',
	'aud_lenguaje_labial' => 'Lectura labial',
	'aud_comunicacion_otra' => 'Otra forma',
	'apoyos_requeridos' => 'Apoyos que requerís',
	'aud_apoyo_vida_diaria' => '¿Qué apoyos utilizás en tu vida diaria?',
	'aud_req_interprete_lengua_senias' => 'Requerís Intérprete de Lengua de Señas Argentina',
	'aud_req_aro_magnetico' => 'Requerís Aro magnético',
	'aud_req_otros_apoyos' => '¿Requerís otros apoyos?',
	'aud_otros_apoyos' => 'Especificar cuáles',
	 
	'visual' => 'Visual',
	'vis_dificultad_para_ver' => '¿Tenés dificultad para ver, incluso si usás lentes?',
	'vis_apoyo_vida_diaria' => '¿Qué apoyos utilizás en tu vida diaria?',
	'vis_req_archivo_audio' => 'Requerís Archivos de audio',
	'vis_req_texto_digital' => 'Requerís Texto digital accesible',
	'vis_req_texto_braile' => 'Requerís Texto en Braille',
	'vis_req_otros_apoyos' => '¿Requerís otros apoyos?',
	'vis_otros_apoyos' => 'Especificar cuáles',
	 
	'motora' => 'Motora',
	'mot_apoyo_vida_diaria' => '¿Qué apoyos utilizás en tu vida diaria?',
	'mot_req_apoyo_aula' => '¿Requerís apoyos para la permanencia en el aula?',
	'mot_apoyo_aula' => '¿Cuáles?',
	'mot_req_otros_apoyos' => '¿Requerís otros apoyos?',
	'mot_otros_apoyos' => 'Especificar cuáles',
	 
	'psicosocial' => 'Condición Psicosocial',
	'psi_descripcion' => '¿Cuál?',
	'psi_apoyo_vida_diaria' => '¿Qué apoyos utilizás en tu vida diaria?',
	'psi_req_otros_apoyos' => '¿Requerís otros apoyos?',
	'psi_otros_apoyos' => 'Especificar cuáles',
	 
	'otras' => 'Otra situación de discapacidad',
	'otra_descripcion' => '¿Cuál/cuáles?',
	'otra_req_apoyo_vida_diaria' => '¿Requiere otros apoyos?',
	'otra_apoyo_vida_diaria' => 'Especificar cuáles',
	 
	'importante' => 'Alguna otra información que consideres importante comunicar',
	'informacion_importante' => 'Alguna otra información que consideres importante comunicar',
	 
	'sin_discapacidad_descr' => 'No presento ninguna condición que implique Discapacidad',
	'con_discapacidad_descr' => 'Declaro condición de discapacidad',
	 
	//---- PANTALLA: Documentación ---------------------------------------------
	'documentacion' => 'Documentación',
	'doc.descargar' => 'Descargar',
	'doc.modificar' => 'Modificar',
	'doc.eliminar' => 'Eliminar',
	'doc.msj_extensiones' => 'Las extensiones de archivo soportadas son: ',
	'doc.msj_tamanio_max' => 'El tamaño máximo permitido por archivo es: ',
	'doc.msj_error_rdi' => 'En estos momentos el servidor de archivos digitales no está disponible. <br/> Por favor, intentar nuevamente más tarde.',
	'doc.msj_error_tamanio' => 'El tamaño del archivo supera el máximo permitido',
	'doc.msj_no_hay_requisitos_ingreso' => 'No tenés requisitos de ingreso pendientes de presentación',
	
	//---- PANTALLA: Presentación de documentación (turno) ---------------------
	'sel_turno.descripcion' => 'Seleccioná el día para concurrir a presentar la documentación requerida haciendo click sobre el mismo. Luego seleccioná una franja horaria disponible y hacé click en "SOLICITAR TURNO".',
	'sel_turno.turno_existente_descr' => 'Tenés asignado el siguiente turno para presentar la documentación requerida:',
	
	'sel_turno.confirmar' => 'Solicitar turno',
	'sel_turno.modificar' => 'Modificar turno',
	 
	'sel_turno.fecha' => 'Fecha',
	'sel_turno.franja' => 'Franjas horarias disponibles',
	
	'sel_turno.hora' => 'Hora',
	'sel_turno.operador' => 'Ventanilla',
	 
	'sel_turno.error_sin_turnos' => 'Por favor, elegí un nuevo turno porque se han ocupado todos los disponibles para esa fecha y franja horaria.',
	 
	 
	//---- PANTALLA: datos extra de posgrado -----------------------------------
	'edicion_interna.alerta'	=> '<span class="subrayado">ATENCIÓN</span>: no olvides presionar el botón "Guardar" para salvar los cambios efectuados en este formulario.',
	 
	'sin_antecedentes_docentes'	=> 'No has ingresado antecedentes docentes.',
	'sin_antecedentes_prof'		=> 'No has ingresado antecedentes profesionales.',
	'sin_antecedentes_acad'		=> 'No has ingresado antecedentes académicos.',
	 
	'msj_confirmar_eliminar'	=> '¿Estás seguro que deseás eliminar el registro?',
	'agregar_antecedente'		=> 'Agregar antecedente',
	'carga_antecedente_doc'		=> 'Carga de antecedente docente',
	'carga_antecedente_prof'	=> 'Carga de antecedente profesional',
	'carga_antecedente_acad'	=> 'Carga de antecedente académico',
	'guardar_antecedente'		=> 'Guardar',
	 
	'antec_institucion'			=> 'Institución',
	'antec_cargo'				=> 'Cargo',
	'antec_materia'				=> 'Materia',
	'antec_hs_semanales'		=> 'Hs. Semanales',
	'antec_fecha_desde'			=> 'Fecha desde',
	'antec_fecha_hasta'			=> 'Fecha hasta',
	 
	'antec_empresa'				=> 'Empresa',
	'antec_area'				=> 'Área',
	 
	'antec_fa_titulo_sup_prein'	=> 'Título',
	'antec_fa_institucion_prein'=> 'Institución',
	'antec_fa_fecha_ingreso'	=> 'Fecha ingreso',
	'antec_fa_fecha_egreso'		=> 'Fecha egreso',
	'antec_fa_titulo_tesis'		=> 'Título tesis',
	'antec_fa_director_tesis'	=> 'Director tesis',
	'antec_fa_categoria_coneau'	=> 'Categoría CONEAU',
	'antec_fa_duracion_teorica_tit' => 'Duración teórica',
	'antec_fa_duracion_teorica'	=> 'Duración teórica (en años)',
	'antec_fa_nivel_estudio'	=> 'Nivel de estudio',
	'antec_fa_estado'			=> 'Estado',
	
	'editar'					=> 'Editar',
	'eliminar'					=> 'Eliminar',
	'mas_informacion'			=> 'Más información',
	 
	'error_fechas'				=> 'La fecha hasta no puede ser anterior a la fecha desde',
	
	//---- Selector de localidad -----------------------------------------------
	'busqueda_avanzada'			=> 'Búsqueda avanzada',
	'ayuda_lista_localidades'	=> 'Seleccioná el país y tipeá las primeras letras de la localidad buscada para ver una lista de opciones. También podés usar la búsqueda avanzada.',
	'seleccion_localidad'		=> 'Selección de localidad',
	'seleccionar_localidad'		=> 'Seleccionar localidad',
	'nota_indeterminado'		=> '<strong>Nota:</strong> Si no encontrás los datos de tu localidad 
									seleccioná "Indeterminado" en los lugares correspondientes.',
	'pais'						=> 'País',
	'provincia'					=> 'Provincia',
	'departamento'				=> 'Departamento',
	'localidad'					=> 'Localidad',
	'codigo_postal'				=> 'Código postal',
	 
	'busqueda_cp'				=> 'Buscar código postal',
	'seleccion_cp'				=> 'Seleccionar Código Postal',
	 
	'loc_sin_cp'				=> 'No se encontraron códigos postales para la localidad seleccionada.',
	 
	//---- Selector de colegio secundario --------------------------------------
	'seleccion_colegio'			=> 'Selección de colegio secundario',
	'seleccionar_colegio'		=> 'Seleccionar colegio',
	'colegio_nombre'			=> 'Nombre del colegio',
	'mensaje_filtrar_colegio'	=> 'Debés ingresar la localidad para buscar colegios',
	'mensaje_filtrar_caract'	=> 'Debés ingresar una opción de filtrado de al menos tres caracteres',
	'descr_filtro_colegio'		=> 'Primero ingresá la localidad de tu colegio. Luego, podés ingresar algunas palabras del nombre de la Institución (ejemplo: Manuel Belgrano) y presionar "Buscar"',
	'ayuda_filtro_colegio'		=> 'Ingresá algunas palabras (ejemplo: Manuel Belgrano) y presioná Buscar',
	 
	//---- Selector de título secundario ---------------------------------------
	'seleccion_titulo_sec'		=> 'Selección de título secundario',
	'seleccionar_titulo'		=> 'Seleccionar título',
	'mensaje_filtrar_titulo_sec'=> 'Debés ingresar algún nombre para obtener colegios',
	'ayuda_filtro_titulo_sec'	=> 'Ingresá algunas palabras (ejemplo: ciencias naturales) y presioná Buscar',
	
	//--------------------------------------------------------------------------
	//---- Campos de formulario ------------------------------------------------
	//--------------------------------------------------------------------------
	 
	'fecha_registro'			=> 'Fecha de registro en el sistema', 
	'fecha_ult_modif'			=> 'Fecha de la última modificación de datos', 
	'version_modificacion'		=> 'Versión de modificación', 
	'version_impresa'			=> 'Última versión impresa', 
	'estado'					=> 'Estado', 
	 
	//---- Propuesta -----------------------------------------------------------
	'responsable_academica'		=> 'Responsable Académica', 
	'ubicacion'					=> 'Ubicación', 
	'propuesta'					=> 'Propuesta', 
	'modalidad'					=> 'Modalidad',
	'fecha_fin_vigencia'		=> 'Vigente hasta',
	'observaciones'				=> 'Obs.',
	'pre_prop_observaciones'	=> 'Observaciones:',
	'turno_preferido'			=> 'Turno preferido', 
	'turno'						=> 'Turno', 
	'periodo_inscripcion'		=> 'Per. Insc.', 
	
	'inscripciones'				=> '',
	'otras_inscripciones'		=> 'Otras inscripciones',
	'insc_otra_inst'			=> '¿Te preinscribís simultáneamente a una segunda propuesta en otra Institución?',
	'insc_otra_inst_descr'		=> '¿En qué propuesta?',
	'como_conocio_institucion'	=> '¿Cómo conociste la Institución?',
	 
	'motivo_eleccion_inst'		=> 'Motivos por los que elegiste esta Institución educativa',
	'mot_inst_econom'			=> 'Por cuestiones económicas / bajo costo',
	'mot_inst_prestigio'		=> 'Por el prestigio',
	'mot_inst_difusion'			=> 'Por la difusión',
	'mot_inst_rec_estud'		=> 'Por recomendación de otros estudiantes / graduados',
	'mot_inst_rec_amigos'		=> 'Por recomendación de amigos / familiares',
	'mot_inst_sist_ing'			=> 'Por el sistema de ingreso',
	'mot_inst_ubicacion'		=> 'Ubicación geográfica',
	'mot_inst_otros'			=> 'Otros',

	'motivo_eleccion_prop'		=> '¿Cuál de los siguientes motivos tuvo mayor peso en tu elección de propuesta?',
	'mot_prop_vocacion'			=> 'Vocación',
	'mot_prop_sug_fam'			=> 'Sugerencia del núcleo familiar',
	'mot_prop_rec_social'		=> 'Reconocimiento social',
	'mot_prop_ins_lab'			=> 'Rápida inserción laboral',
	'mot_prop_perf_prof'		=> 'Perfil profesional',
	'mot_prop_mejora_ec'		=> 'Mejora de las condiciones económicas',
	'mot_prop_util_soc'			=> 'Deseo de ser útil a la sociedad',
	'mot_prop_otros'			=> 'Otros',

	'adeudas_materias'			=> 'Colegio secundario',
	'adeuda_materias'			=> '¿Adeudás materias?',	 
	
	//---- Propuesta Finalizada ---------------------------------------------------
	'prop_finalizada.msj'		=> 'Tu preinscripción está finalizada. Si querés realizar cambios o inscribirte a otra propuesta, debés reabrir la preinscripción.',
	'prop_finalizada.reabrir'	=> 'Reabrir Preinscripcion',
	'prop_finalizada.imprimir'	=> 'Imprimir Formulario',
	'prop_finalizada.error'		=> 'Ocurrió un error al finalizar la preinscripción',

	//---- Datos principales ---------------------------------------------------
	'apellido'				=> 'Apellido', 
	'nombres'				=> 'Nombres',
    'apellido_elegido'			=> 'Apellido elegido', 
	'nombres_elegido'			=> 'Nombre de Pila Elegido',
	'nombres_elegido_leyenda'	=> '<b> Ley de Identidad de Género 26.743</b>',
	'nombres_elegido_ayuda'		=> 'Este campo no debe utilizarse como un _nickname o nombre de usuario , sino que refiere al nombre por el que desea ser identificada la persona de acuerdo a su identidad de género, amparada en la Ley 26.743. Bajo este nombre será reconocida la persona en el sistema.',
	'pais_documento_desc'       => 'País emisor del documento', 
	'tipo_documento_desc'       => 'Tipo de documento',
	'nro_documento'				=> 'Número de documento',  
	'nro_documento_rep'			=> 'Repetir número de documento',  
	'nacionalidad_desc'			=> 'Nacionalidad',
	'e_mail'				=> 'E-mail', 
	'sexo'					=> 'Género', 
	'identidad_genero'			=>'Identidad de Género y Diversidad: ¿Cuál de estas opciones considerás que te describe mejor?',
	'identidad_genero_otro'			=> 'Especifique', 
	'mensaje_identidad_genero'		=> 'En esta parte del formulario te encontrás con un relevamiento de información que permite incorporar la perspectiva de géneros y diversidad en las políticas universitarias. Si tenés dudas, por favor apoyate en el material de ayuda',
	'ayuda_identidad_genero'		=> '<a href="%link%"  target="_blank" class="no-ajax" >Material de ayuda </a>.',
	'numero_cuil'				=> 'Número de CUIL (sin guiones)', 
	'pais_documento'			=> 'País emisor del documento', 
	'tipo_documento'			=> 'Tipo de Documento', 	
	'nacionalidad'				=> 'Nacionalidad',
    'nro_tramite_dni'			=> 'Número de Trámite', 
	
	'celular_numero'			=> 'Número de celular', 
	 
	'fecha_nacimiento'			=> 'Fecha de Nacimiento',
	'loc_nacimiento'			=> 'Localidad', 
	
	'ent_emisora_ci'			=> 'Entidad emisora', 
	'nro_ci'					=> 'Número de Cédula', 
	 
	'pais_emisor_pasap'			=> 'País emisor', 
	'nro_pasap'					=> 'Número de Pasaporte', 
	 
	'tipo_visa'					=> 'Tipo de visa', 
	'otorgamiento_visa'			=> 'Fecha de otorgamiento', 
	'vencimiento_visa'			=> 'Vencimiento', 
	 
	'tipo_residencia'			=> '¿La residencia es por cupo?', 
	'residencia'				=> 'Tipo de residencia', 
	'fecha_respre_otorg'		=> 'Fecha de otorgamiento de la residencia', 
	'fecha_respre_venc'			=> 'Fecha de vencimiento de la residencia', 
	 
	//---- Datos personales ----------------------------------------------------
	'tipo_res_per_lect'			=> 'Tipo de residencia', 
	'calle_per_lect'			=> 'Calle', 
	'numero_per_lect'			=> 'Número', 
	'piso_per_lect'				=> 'Piso', 
	'dpto_per_lect'				=> 'Departamento', 
	'unidad_per_lect'			=> 'Unidad', 
	'loc_per_lect'				=> 'Localidad', 
	'cp_per_lect'				=> 'Código Postal', 
	'te_per_lect'				=> 'Teléfono fijo', 
	'barrio_per_lec'			=> 'Barrio',
	 
	'calle_proc'				=> 'Calle', 
	'numero_proc'				=> 'Número', 
	'piso_proc'					=> 'Piso', 
	'dpto_proc'					=> 'Departamento', 
	'unidad_proc'				=> 'Unidad', 
	'loc_proc'					=> 'Localidad', 
	'cp_proc'					=> 'Código Postal', 
	'te_proc'					=> 'Teléfono fijo', 
	'barrio_proc'				=> 'Barrio', 
	
	'apellido_pers_alleg'		=> 'Apellido',
	'nombre_pers_alleg'			=> 'Nombre',
	'tipo_allegado'				=> 'Relación',
	'calle_pers_alleg'			=> 'Calle', 
	'nro_pers_alleg'			=> 'Número', 
	'piso_pers_alleg'			=> 'Piso', 
	'dpto_pers_alleg'			=> 'Departamento', 
	'unidad_pers_alleg'			=> 'Unidad', 
	'loc_pers_alleg'			=> 'Localidad', 
	'cp_pers_alleg'				=> 'Código Postal', 
	'te_pers_alleg'				=> 'Teléfono', 
	'barrio_alleg'				=> 'Barrio', 
	
	//---- Estudios ------------------------------------------------------------
	'orientacion_vocacional'	=> 'Orientacion Vocacional',
	'orientacion_recibida'		=> 'Orientacion vocacional recibida',
	 
	'colegio_secundario'		=> 'Colegio Secundario', 
	'titulo_secundario'			=> 'Título Secundario', 
	'anio_egreso_sec'			=> 'Año de egreso', 
	
	'alu_otestsup_uni'			=> 'Universidad o Institución', 
	'alu_otestsup_tipo'			=> 'Tipo', 
	'alu_otestsup_area'			=> 'Áreas', 
	'alu_otestsup_carr'			=> 'Propuesta', 
	'alu_otestsup_esta'			=> 'Estado', 
	'alu_otestsup_egre'			=> 'Año de egreso', 
	'alu_otestsup_niv'			=> 'Nivel', 
	'alu_otestsup_razon_abandono' => 'Razón de abandono', 
	'alu_otestsup_razon_abandono_otros' => 'Especificar razón de abandono',

     
	'es_mayor_25_sin_secundario'		=> '¿Es mayor a 25 años y no realizó el colegio secundario?',
	'error_es_mayor_25_sin_secundario'	=> 'Ud. no es mayor de 25 años',
	'error_fecha_nacimiento_vacia'		=> 'Debe completar la fecha de nacimiento en la solapa Datos principales',
     
	//---- Financiamiento ------------------------------------------------------
	'alu_cos_est_ap_fam'		=> 'Con el aporte de familiares', 
	'alu_cos_est_trab'			=> 'Con tu trabajo', 
	'alu_cos_est_beca'			=> 'Con beca', 
	'alu_cos_est_plsoc'			=> 'Con planes sociales', 
	'alu_cos_est_otra'			=> 'Otra fuente', 
	'alu_cos_est_espec'			=> 'Describí la otra fuente', 
	 
	'tiene_beca'				=> '¿Tenés beca?', 
	'tiene_beca_univ'			=> 'Universitaria', 
	'tiene_beca_nacio'			=> 'Nacional', 
	'tiene_beca_inter'			=> 'Internacional',
	
	'alu_beca_muni'				=> 'Municipal', 
	'alu_beca_otra'				=> 'Otra', 
	'alu_beca_prov'				=> 'Provincial', 
	'alu_beca_tipo_eco'			=> 'De ayuda económica', 
	'alu_beca_tipo_ser'			=> 'De contraprestación de servicios', 
	'alu_beca_tipo_inv'			=> 'De investigación', 
	'alu_beca_eco_tran'			=> 'Transporte', 
	'alu_beca_eco_come'			=> 'Comedor', 
	'alu_beca_eco_foto'			=> 'Fotocopias', 
	'alu_beca_eco_efec'			=> 'Efectivo', 
	'alu_beca_eco_habi'			=> 'Habitacional', 
	 
	//---- Situación laboral ---------------------------------------------------
	'existe_trab_alum'			=> 'Condición de actividad durante la semana pasada', 
	'alu_trab_hace'				=> 'En ese trabajo sos', 
	'alu_trab_desjub'			=> '¿Te hacen descuentos jubilatorios? (sólo para los asalariados)', 
	'alu_trab_fami'				=> '¿Trabajás en el negocio o empresa de un familiar?', 
	'alu_trab_ocup'				=> 'Esa ocupación es', 
	'alu_trab_tarea'			=> 'Describí la tarea que realizás', 
	'remuneracion'				=> '¿Recibís u obtienés pago por tu trabajo? (en dinero o especies)', 
	'hora_sem_trab_alum'		=> 'Horas semanales de trabajo', 
	'rel_trab_carrera'			=> 'Relación del trabajo con tu propuesta',  
	'rel_trab_propuesta'		=> 'Relación del trabajo con tu propuesta',
	 
	//---- Situación familiar --------------------------------------------------
	'apellido_padre'			=> 'Apellido', 
	'nombres_padre'				=> 'Nombres', 
	'padre_vive'				=> '¿Vive?',
	'ult_est_cur_padre'			=> 'Máximo nivel de estudios cursados', 
	'sit_laboral_padre'			=> 'Condición de actividad durante la semana pasada', 
	'act_econom_padre'			=> '', 
	'det_rama_act_padre'		=> '', 
	'categ_ocup_padre'			=> '', 
	'padre_trab_hace'			=> 'En ese trabajo es', 
	'padre_trab_ocup'			=> 'Esa ocupación es', 
	'padre_trab_otro'			=> 'Si no trabaja y no busca trabajo', 
	'padre_trab_tarea'			=> 'Describí la tarea que realiza',  
	
	'apellido_madre'			=> 'Apellido', 
	'nombres_madre'				=> 'Nombres', 
	'madre_vive'				=> '¿Vive?', 
	'sit_laboral_madre'			=> 'Condición de actividad durante la semana pasada', 
	'ult_est_cur_madre'			=> 'Máximo nivel de estudios cursados', 
	'act_econom_madre'			=> '', 
	'det_rama_act_madre'		=> '', 
	'categ_ocup_madre'			=> '', 
	'madre_trab_hace'			=> 'En ese trabajo es', 
	'madre_trab_ocup'			=> 'Esa ocupación es', 
	'madre_trab_otro'			=> 'Si no trabaja y no busca trabajo', 
	'madre_trab_tarea'			=> 'Describí la tarea que realiza',
	 
	//---- Tecnología ----------------------------------------------------------
	'alu_tec_pc_casa'			=> 'En tu casa', 
	'alu_tec_pc_trab'			=> 'En el trabajo', 
	'alu_tec_pc_univ'			=> 'En la universidad', 
	'alu_tec_pc_otro'			=> 'En otro lugar', 
	 
	'alu_tec_int_casa'			=> 'En tu casa', 
	'alu_tec_int_trab'			=> 'En el trabajo', 
	'alu_tec_int_univ'			=> 'En la universidad', 
	'alu_tec_int_movil'			=> 'En dispositivos móviles', 
	'alu_tec_int_otro'			=> 'En otro lugar', 
	 
	//---- Deportes ------------------------------------------------------------
	'practica_deportes'			=> '¿Practicás deportes?', 
	 
	'hace_dep_univ'				=> 'En la universidad', 
	'hace_dep_gim_priv'			=> 'En un gimnasio privado', 
	'hace_dep_partic'			=> 'Particular', 
	'hace_dep_otros'			=> 'Otros lugares', 
	 
	'prac_dep_futbol'			=> 'Fútbol', 
	'prac_dep_basquet'			=> 'Básquet', 
	'prac_dep_voley'			=> 'Vóley', 
	'prac_dep_gimnasia'			=> 'Gimnasia', 
	'prac_dep_tenis'			=> 'Tenis', 
	'prac_dep_natacion'			=> 'Natación', 
	'prac_dep_handball'			=> 'Handball', 
	'prac_dep_otros'			=> 'Otros', 
	 
	//---- Idiomas -------------------------------------------------------------
	'alu_idioma_ingl'			=> 'Inglés', 
	'alu_idioma_fran'			=> 'Francés', 
	'alu_idioma_port'			=> 'Portugués', 
	'alu_idioma_ital'			=> 'Italiano', 
	'alu_idioma_alem'			=> 'Alemán', 
	'alu_idioma_chino'			=> 'Chino', 
	'alu_idioma_otro'			=> 'Otro', 
	'alu_idioma_otro_nivel'		=> 'Nivel', 
	 
	//--------------------------------------------------------------------------
	 
	'alu_est_civil_uh'			=> '¿Convivís con alguien (unión de hecho)?', 
 
	'sec_egreso'				=> '', 
	'o_est_ter_estado'			=> '', 
	'o_est_uni_estado'			=> '', 
	'sit_actual_padre'			=> '', 
	'sit_actual_madre'			=> '', 
	'tit_obt_padre'				=> '', 
	'tit_obt_madre'				=> '', 
	'cant_fami_cargo'			=> 'Cantidad de familiares a cargo', 
	'cant_empl_cargo'			=> '', 
	'pais_nacionalidad'			=> '', 
	
	'cant_hijos_alum'			=> 'Cantidad de hijos', 
	'vive_actual_con'			=> '¿Con quién vivís durante este período?', 
	'obra_social_alu'			=> 'Cobertura de salud', 
	'obra_social_trab'			=> '', 
	'obra_social_fami'			=> '', 
	'obra_social_univ'			=> '', 
	'costea_estudios'			=> '', 

	'vive_con_conyuge'			=> '', 
	'vive_con_padre'			=> '', 
	'vive_con_madre'			=> '', 
	'vive_con_hijos'			=> '', 
	'vive_con_hermanos'			=> '', 
	 
	'estado_civil'				=> 'Estado civil', 
	 
	'otros_estud_super'			=> '', 
	'fliares_cargo_alum'		=> '', 
	'sit_laboral_alu'			=> '', 
	'categ_ocup_alum'			=> '', 
	'act_econom_alum'			=> '', 
	'det_rama_act_alum'			=> '', 	
	
	
	 
	'terciario_nombre_1'		=> '', 
	'terciario_titulo_1'		=> '', 
	'terciario_estado_1'		=> '', 
	'terciario_nombre_2'		=> '', 
	'terciario_titulo_2'		=> '', 
	'terciario_estado_2'		=> '', 
	'universit_nombre_1'		=> '', 
	'universit_titulo_1'		=> '', 
	'universit_estado_1'		=> '', 
	'universit_nombre_2'		=> '', 
	'universit_titulo_2'		=> '', 
	'universit_estado_2'		=> '', 
	'postgrado_nombre_1'		=> '', 
	'postgrado_titulo_1'		=> '', 
	'postgrado_estado_1'		=> '', 
	'postgrado_nombre_2'		=> '', 
	'postgrado_titulo_2'		=> '', 
	'postgrado_estado_2'		=> '', 
	'antecedente_1'				=> '', 
	'antecedente_2'				=> '', 
	'antecedente_3'				=> '', 
	'actividad_academica'		=> '', 
	'titulo_tentativo'			=> '', 
	'area_tematica'				=> '', 
	 
	'turno.msj_confirmar_baja' => '¿Confirmás que querés cancelar el turno de la Responsable Académica ',
	'turno.info' => 'En esta sección podrás solicitar un turno para presentar la documentación. Tené en cuenta que, si te has preinscripto en propuestas de distintas Responsables Académicas, deberás solicitar un turno por cada una.',
	'turno.ra' => 'Responsable Académica',
	'turno.fecha' => 'Fecha',
	'turno.hora' => 'Hora',
	'turno.operador' => 'Ventanilla',
	'turno.cancelar' => 'Cancelar turno',
	'turno.no_asignado' => '-- No asignado --',
	'turno.sin_turnos' => 'No se encontraron turnos para presentar la documentación.',
	 
	//--------------------------------------------------------------------------
	//---- Opciones de selección múltiple --------------------------------------
	//--------------------------------------------------------------------------

	'si'						=> 'Sí',
	'no'						=> 'No',
	'desconoce'					=> 'Desconoce',
	'no_desea_contestar'		=> 'No deseo contestar',
	 
	'descr_no_seleccion'		=> '-- Seleccioná --',
	 
	'genero_masculino'			=> 'Masculino',
	'genero_femenino'			=> 'Femenino',
	'genero_no_binario'			=> 'No Binario',
	 
	'turno_mañana'				=> 'Mañana',
	'turno_tarde'				=> 'Tarde',
	'turno_noche'				=> 'Noche',
	 
	'temporal'					=> 'Temporal',
    'permanente'				=> 'Permanente',
	 
	'residencia_1'				=> 'Sin residencia',
	'residencia_2'				=> 'Precaria',
	'residencia_3'				=> 'Temporal', 
	'residencia_4'				=> 'Definitiva',

	'existe_trab_alum_1'		=> 'Trabajaste al menos una hora (incluye a los que no trabajaron por licencia, vacaciones, enfermedad)',
	'existe_trab_alum_2'		=> 'No trabajaste y buscaste trabajo en algún momento de los últimos 30 días',
	'existe_trab_alum_3'		=> 'No trabajaste y no buscaste trabajo (no estás pensando en trabajar)',

	'alu_trab_hace_1'			=> 'Patrón (tenés empleados)',
	'alu_trab_hace_2'			=> 'Cuenta propia / independiente',
	'alu_trab_hace_3'			=> 'Obrero o empleado (asalariado)',
	'alu_trab_hace_4'			=> 'Pasante',

	'alu_trab_ocup_1'			=> 'Permanente (incluye fijo, estable, de planta)',
	'alu_trab_ocup_2'			=> 'Temporaria (incluye changa, trabajo transitorio, estacionales, contrato por tarea u obra, suplencia, etc.)',
	
	'hora_sem_trab_alum_1'		=> 'Hasta 10 horas',
	'hora_sem_trab_alum_2'		=> 'Más de 10 y hasta 20 horas',
	'hora_sem_trab_alum_3'		=> 'Más de 20 y menos de 35 horas',
	'hora_sem_trab_alum_4'		=> '35 o más horas',
	 
	'rel_trab_propuesta_1'		=> 'Total',
	'rel_trab_propuesta_2'		=> 'Parcial',
	'rel_trab_propuesta_3'		=> 'Sin Relación',
	 
	'nivel_estudio_1'			=> 'No realizó estudios',
	'nivel_estudio_2'			=> 'Estudios primarios incompletos',
	'nivel_estudio_3'			=> 'Estudios primarios completos',
	'nivel_estudio_4'			=> 'Estudios secundarios incompletos',
	'nivel_estudio_5'			=> 'Estudios secundarios completos',
	'nivel_estudio_8'			=> 'Estudios superiores incompletos',
	'nivel_estudio_9'			=> 'Estudios superiores completos',
	'nivel_estudio_10'			=> 'Estudios universitarios incompletos',
	'nivel_estudio_11'			=> 'Estudios universitarios completos',
	'nivel_estudio_12'			=> 'Estudios de postgrado',
	'nivel_estudio_13'			=> 'Desconoce',
	
	'sit_laboral_1'				=> 'Trabajó al menos una hora (incluye ausencia por licencia, vacaciones, enfermedad)',
	'sit_laboral_2'				=> 'No trabajó y buscó trabajo en algún momento de los últimos 30 días', 
	'sit_laboral_3'				=> 'No trabajó y no buscó trabajo (no esta pensando en trabajar)',
	'sit_laboral_4'				=> 'Desconoce',
	 
	'existe_trab_alum_2'		=> 'No trabajaste y buscaste trabajo en algún momento de los últimos 30 días',
	'existe_trab_alum_3'		=> 'No trabajaste y no buscaste trabajo (no estás pensando en trabajar)',
	 
	'padre_trab_hace_1'			=> 'Patrón (tiene empleados)',
	'padre_trab_hace_2'			=> 'Cuenta propia',
	'padre_trab_hace_3'			=> 'Obrero o empleado (asalariado)',
	'padre_trab_hace_4'			=> 'Desconoce',
	 
	'otra_ocup_1'				=> 'Es jubilado o pensionado',
	'otra_ocup_2'				=> 'Es rentista',
	'otra_ocup_3'				=> 'Es estudiante',
	'otra_ocup_4'				=> 'Realiza tareas del hogar',
	'otra_ocup_5'				=> 'Otro',
	'otra_ocup_6'				=> 'Desconoce',
	 
	'orient_voc_rec_1'			=> 'Ninguna',
	'orient_voc_rec_2'			=> 'Entidad privada', 
	'orient_voc_rec_3'			=> 'Entidad oficial',
	'orient_voc_rec_4'			=> 'Profesional especializado',
	'orient_voc_rec_5'			=> 'De la universidad',
	 
	'alu_otestsup_tipo_1'		=> 'Pública',
	'alu_otestsup_tipo_2'		=> 'Privada',
	 
	'alu_otestsup_esta_1'		=> 'Finalizado',
	'alu_otestsup_esta_2'		=> 'Abandonado',
	'alu_otestsup_esta_3'		=> 'En curso',
	 
	'alu_otestsup_area_1'		=> 'Educación',
	'alu_otestsup_area_2'		=> 'Ciencias Básicas',
	'alu_otestsup_area_3'		=> 'Ciencias Aplicadas',
	'alu_otestsup_area_4'		=> 'Ciencias Sociales',
	'alu_otestsup_area_5'		=> 'Ciencias Jurídicas',
	'alu_otestsup_area_6'		=> 'Ciencias Humanas',
	'alu_otestsup_area_7'		=> 'Ciencias de la Salud',
	'alu_otestsup_area_8'		=> 'Sin especificación',
	 
	'alu_otestsup_niv_1'		=> 'Universitario',
	'alu_otestsup_niv_2'		=> 'Superior no universitario',
	 
	'idiomas_nivel_1'			=> 'Muy bueno',
	'idiomas_nivel_2'			=> 'Bueno', 
	'idiomas_nivel_3'			=> 'Básico', 
	'idiomas_nivel_4'			=> 'Desconoce',
	 
	'vive_con_1'				=> 'Solo',
	'vive_con_2'				=> 'Con compañeros' ,
	'vive_con_3'				=> 'Con familia de origen (padres, hermanos, abuelos)',
	'vive_con_4'				=> 'Con tu pareja / hijos',
	'vive_con_5'				=> 'Otros',
	 
	'estado_civil_1'			=> 'Soltero',
	'estado_civil_2'			=> 'Casado',
	'estado_civil_3'			=> 'Separado',
	'estado_civil_4'			=> 'Divorciado',
	'estado_civil_6'			=> 'Viudo',
	 
	'no_tiene'					=> 'No tenés',
	'uno'						=> 'Uno',
	'dos'						=> 'Dos',
	'mas_de_dos'				=> 'Más de dos',
	 
	'categ_coneau_a'			=> 'A',
	'categ_coneau_b'			=> 'B',
	'categ_coneau_c'			=> 'C',
	'categ_coneau_t'			=> 'En trámite',
	'categ_coneau_n'			=> 'Ninguna',
	 
	'antec_fa_nivel_estudio_4'	=> 'Superior',
	'antec_fa_nivel_estudio_5'	=> 'Universitario',
	'antec_fa_nivel_estudio_6'	=> 'Posgrado',
	 
	'antec_fa_estado_F'			=> 'Finalizado',
	'antec_fa_estado_A'			=> 'Abandonado',
	'antec_fa_estado_C'			=> 'En curso',
	 
	'discapacidad.grado.transitoria'	=> 'Transitoria',
	'discapacidad.grado.permanente'		=> 'Permanente',
	 
	//--------------------------------------------------------------------------
	//---- Reporte -------------------------------------------------------------
	//--------------------------------------------------------------------------
	
	'rep_ficha_aspirante'		=> 'Ficha del aspirante: ',
	'rep_ficha_nro'				=> 'Ficha de inscripción nro.: ',
	'rep_fecha_ult_modif'		=> 'Fecha de última modificación: ',
	'rep_nro_lu'				=> 'Nro. de Libreta Universitaria: ',
	'rep_nro_lu_leyenda'		=> '(a completar por la Dir. de Alumnos y Graduados)',
	'rep_propuestas_a_insc'		=> 'PROPUESTA(S) A INSCRIBIRSE:',
	'ubicacion'					=> 'Ubicación',

	//---- Turno de presentación ----------------------------------------------
	'rep_turno_mensaje'			=> 'Se deberá presentar la documentación solicitada en el siguiente turno:',

	//---- CBC ----------------------------------------------------------------
	'rep_titulo_cbc'				=> 'SITUACIÓN ACADÉMICA CORRESPONDIENTE AL C.B.C.',
	'cbc_codigo'					=> 'COD.',
	'cbc_asignaturas'				=> 'ASIGNATURA',
	'cbc_calificacion'				=> 'CALIFICACIÓN',
	'cbc_fecha'					=> 'FECHA',
	'cbc_leyenda_cuadro'			=> 'Consigná si la materia fue realizada en CBC, UBA XXI, CNBA, ILSE o CPEL',
	//-------------------------------------------------------------------------

	'rep_firma'					=> 'FIRMA',
	'rep_titulo_credencial'		=> 'CREDENCIAL PROVISORIA',
	'rep_apellido_y_nombre'		=> 'Apellido y nombres',
	'rep_identificacion'			=> 'Identificación',
	'rep_telefono'					=> 'TE',
	'rep_propuestas'				=> 'Propuesta(s)',
     
     
	'nro_documento_rep'				=> 'Repetir el número de documento',  
	'mail_asunto_fin'				=> 'Recibimos tu solicitud de preinscripción - %1%',
	'mail_cuerpo_fin'				=> '¡Hola!<br><br>Recibimos tu solicitud de preinscripción a la %1% correctamente. Analizaremos la documentación que presentaste y, en caso de cumplir con todos los requisitos, recibirás la confirmación de inscripción.<br><br> Una vez aceptada tu preinscripción podrás modificar los datos declarados (o solicitar la modificación de aquellos que no puedas editar).',
	'notificacion_email_puede_continuar'	=> 'Hubo un problema al enviar el mail de confirmación, intentá loguearte más tarde',

	//-------------------------------------------------------------------------
	//---- Personalización de etiquetas de campos de formulario en reporte ----
	//-------------------------------------------------------------------------

	/**
	 * Si se desea que la etiqueta de un campo de formulario se vea distinto en 
	 * el PDF respecto a la etiqueta que se muestra en pantalla, agregar aquí 
	 * entradas respetando la siguiente convención:
	 * 
	 * 'pdf_<nombre_campo_original>' => '<Nueva etiqueta>',
	 * 
	 * Ej: Si se quiere que para la etiqueta 'nro_documento' se imprima 
	 * 'Nro. documento' en lugar de 'Número de documento' como está definida 
	 * para la salida en pantalla, agregar una entrada:
	 * 
	 * 'pdf_nro_documento'	=> 'Nro. documento',    
	 * 
	 */
	'pdf_nro_documento'			=> 'Nro. documento',

	//-------------------------------------------------------------------------
	//----- Personalización formulario en ayuda_email --------------------------
	//--------------------------------------------------------------------------
	//Operacion Ayuda_Menu
	'header.menu.ayuda_menu' => 'Ayuda',
	'ayuda_menu.titulo' => '¿Ténes problemas para ingresar?',
	'ayuda_menu.titulo_check' => 'Gracias por ponerte en contacto con nosotros.',
	'ayuda_menu.sub_titulo' => 'Completa el siguiente formulario para enviar tu consulta, te responderemos a la brevedad al correo electrónico que indiques a continuación.',
	'ayuda_menu.nombres' => 'Nombres y Apellidos',
	'ayuda_menu.documento' => 'D.N.I',
	'ayuda_menu.email' => 'Correo electrónico',
	'ayuda_menu.mensaje' => 'Mensaje',
	'ayuda_menu.siguiente' => 'Continuar',
	'ayuda_menu.enviar' => 'Enviar mensaje',
	'ayuda_menu.alta_error' => 'Se han encontrado errores. Por favor, verificá el formulario.',
	'ayuda_menu.error_emails' => 'El e-mail ingresado es incorrecto, verificá el formulario',
	'ayuda_menu.asunto_email' => '[SIU-Preinscripión] Formulario de contacto',
	'ayuda_menu.cuerpo_email' => 'Recibiste un mensaje de <b>%nombres%</b>,<br/><br/>
									<b>E-mail:</b> %email% <br/>
									<b>D.N.I:</b> %nro_documento% <br/>
									<b>Mensaje:</b> <pre>%mensaje%</pre><br/><br/>',
	'ayuda_menu.alta_error' => 'Se han encontrado errores. Por favor, verificá los datos ingresados.',
	'ayuda_menu.msj_info_enviado' => 'El mensaje fue enviado con éxito.',
	'ayuda_menu.ayuda_dni' => 'El documento no debe contener puntos ni caracteres extraños.',
	'ayuda_menu.ayuda_nombres' => 'El nombre y el apellido no debe superar los sesenta caracteres.',
	'ayuda_menu.ayuda_email' => 'Por este e-mail que ingresaste vamos a comunicarnos.',
	'ayuda_menu.error_email' => 'Falló al enviar el email, intente nuevamente.',
	'ayuda_menu.error_vacio' => 'No debe ser vacío.',
	'ayuda_menu.error_dni' => 'Ingrese un D.N.I correcto.',
	'ayuda_menu.error_email' => 'Ingrese un e-mail correcto.',
	 
	 
	//-------------------------------------------------------------------------
	//----- Número de documento --------------------------
	//--------------------------------------------------------------------------
	'nro_documento.dni.leyenda' => 'Ingrese el <strong>Documento Nacional de Identidad</strong> sin puntos, ej: 32753951',
	'nro_documento.dni.error' => 'El <strong>Documento Nacional de Identidad</strong> debe ser mayor a un millón y menor a cien millones.',
	'nro_documento.cuil.leyenda' => 'Ingrese el <strong>CUIL/CUIT</strong> sin guiones, ej: 20327539517',
	'nro_documento.cuil.error' => 'El CUIL/CUIT es inválido',
     'nro_documento.dni.rep' => 'Los <strong>Documentos de Identidad</strong> no coinciden.',

	//-------------------------------------------------------------------------
	//----- Número de trámite del dni --------------------------
	//--------------------------------------------------------------------------     
    'nro_tramite.dni.leyenda' => 'Ingrese el <strong>Número de trámite</strong> con un máximo de once (11) caracteres, ej: 00234587632',
	'nro_tramite.dni.error' => 'El <strong>Número de trámite</strong> es incorrecto, ingrese nuevamente.',
    'nro_tramite.dni.error_ws_renaper' => 'RENAPER no encontró la persona con los datos ingresados.',
    'ws_renaper.validar'				=> 'Validar con renaper',
    'ayuda_numero_tramite'				=> 'La validación con renaper autocompletará los siguientes datos de la persona: <strong>apellido</strong>, <strong>nombres</strong>, <strong>domicilio</strong> y <strong>fecha de nacimiento</strong>',
    'info_dni_en_tramite'				=> 'Si no contás con la última copia de tu <strong>DNI</strong> para poder registrarte debes comunicarte con la institución para que puedas realizar la preinscripción.',
    'ayuda_documento'				=> 'El tipo de documento Documento/Cédula de Identidad refiere al "Documento de identidad principal del país emisor"',
    
	'seleccione_una_opcion'				=> 'Seleccione al menos una opción',
	//pregunta secreta//
	'pregunta_mascota'					=>'¿Cuál es el nombre de tu mascota?',
	'pregunta_escuela'					=>'¿Cuál es el nombre de tu escuela primaria?',
	'pregunta_vacaciones'				=>'¿Cuál es tu ciudad favorita para ir de vacaciones?',
	'no_puedo_arcceder_email'			=>'<a  class="no-ajax" href="%link%">No puedo acceder a mi email</a>',
	'para_recuperar_tu_acceso_contacta'	=>'Para recuperar tu acceso comunicate con la Universidad:',
	'datos_de_contacto'	                =>'TEL:%1%<br>Email:%2%',
	'ingresa_nuevo_email'	            =>'Ingresá nuevo e-mail',
	'descripcion_ingresa_nuevo_email'	=>'Ingresá un nuevo e-mail para recibir el instructivo de recupero de tu cuenta de preinscripción. Será utilizado para identificarte en la Universidad y allí te llegarán las notificaciones. La nueva dirección que ingreses pasará a ser la principal.',
	'nuevo_email'						=>'Nuevo e-mail',
	'no_puedo_acceder_a_mi_mail'		=>'No puedo acceder a mi e-mail',
	'pregunta_secreta'					=>'Pregunta secreta',
	'propuestas_preinscriptas'			=>'Propuestas preinscriptas',
	'sin_puntos_ni_guiones'				=>'sin puntos ni guiones',
	'respuesta_secreta'					=>'Respuesta secreta',
	'email_principal'					=>'Email principal',
	'descripcion_recuperar_codigo'		=>'Para recuperar tu acceso ingresá el código que te enviamos por e-mail cuando creaste tu usuario.',
	'codigo'							=>'código',
	'leyenda_ayuda_datos_usuario'		=>'Ingresa la información necesaria para validar tu cuenta en caso de recuperación',
	'selecciona_metodo_recuperacion'	=>'Selecciona un método de recuperación',
	  
);
