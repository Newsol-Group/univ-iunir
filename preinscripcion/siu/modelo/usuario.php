<?php

namespace siu\modelo;

use SIU\Chulupi\kernel;
use siu\modelo\lib\abm;
use siu\errores\error_preinscripcion_login;
use siu\errores\error_preinscripcion_mail_no_validado;
use siu\errores\error_preinscripcion;
use siu\errores\error_preinscripcion_token;
use siu\errores\error_preinscripcion_clave;
use siu\preinscripcion;
use siu\modelo\util;

class usuario
{
	protected static $instancia;

	static function instancia()
	{
		if (!isset(self::$instancia)) {
			self::$instancia = kernel::localizador()->instanciar("modelo\\usuario");
		}
		return self::$instancia;
	}
	
	function nuevo($valores, $medio_se = null)
	{
		$datos = array();

		$clave = trim($valores['password']);
		$e_mail = a_minusculas($valores['e_mail']);
		$apellido = formatear($valores['apellido']);
		$apellido_elegido = formatear($valores['apellido_elegido']);
		$nombres = formatear($valores['nombres']);
		$nombres_elegido = formatear($valores['nombres_elegido']);

		//$datos['usuario'] = $e_mail;
		$datos['clave'] = $this->cifrar_clave($clave);
		$datos['e_mail'] = $e_mail;

		$datos['apellido'] = $apellido;
		$datos['nombres'] = $nombres;
		$datos['apellido_elegido'] = $apellido_elegido;
		$datos['nombres_elegido'] = $nombres_elegido;
		$datos['nacionalidad'] = $valores['nacionalidad'];
		$datos['pais_documento'] = $valores['pais_documento'];
		$datos['tipo_documento'] = $valores['tipo_documento'];
		$datos['nro_documento'] = $valores['nro_documento'];
		$datos['fecha_nacimiento'] = convertir_fecha_formato_base($valores['fecha_nacimiento']);
		$datos['pregunta_secreta'] = $valores['pregunta_secreta'];
		$datos['respuesta_secreta'] = $valores['respuesta_secreta'];
		$datos['duplicada'] = $valores['duplicada'];
		$datos['celular_numero'] = $valores['telefono'];
		$datos['fecha_registro'] = 'now()';
		$datos['fecha_ult_modif'] = 'now()';
		$datos['version_modificacion'] = '0';
		$datos['version_impresa'] = '0';
        
        if(isset($valores['validado_con_renaper']) && ($valores['validado_con_renaper'] == comunes::si)){
            $datos['sexo'] = $valores['sexo'];
            $datos['fecha_nacimiento'] = convertir_fecha_formato_base($valores['fecha_nacimiento']);
            $datos['calle_proc'] = $valores['calle_proc'];
            $datos['numero_proc'] = $valores['numero_proc'];
            $datos['piso_proc'] = $valores['piso_proc'];
            $datos['dpto_proc'] = $valores['dpto_proc'];
            $datos['cp_proc'] = $valores['cp_proc'];
            $datos['barrio_proc'] = $valores['barrio_proc'];
            $datos['validado_con_renaper'] = $valores['validado_con_renaper'];
        }

		if ($medio_se) {
			$datos[$medio_se] = $valores[$medio_se];
		}

		$datos['estado'] = preinscripcion::estado_pendiente;

		$datos['codigo_validacion'] = $this->generar_codigo_validacion();

		abm::alta('sga_preinscripcion', $datos);
		if ($datos['duplicada']== comunes::si){
			$this->marcar_duplicadas($datos);
		}
		return $datos['codigo_validacion'];
	}
	//marco como ducplicadas las preinscripciones que tengan mismo pais tipo y nro documento
	function marcar_duplicadas($datos)
	{
		$datos_duplicada = kernel::db()->quote($datos['duplicada']);
		$pais_documento = kernel::db()->quote($datos['pais_documento']);
		$tipo_documento = kernel::db()->quote($datos['tipo_documento']);
		$nro_documento = kernel::db()->quote($datos['nro_documento']);

		$sql = "UPDATE 
					sga_preinscripcion 
				SET
					duplicada = $datos_duplicada
				WHERE 
					pais_documento = $pais_documento"
				. " AND tipo_documento	= $tipo_documento"
				. " AND nro_documento 	= $nro_documento ";

		kernel::db()->ejecutar($sql);
		return $datos;
	}
	function validar_mail($codigo_validacion)
	{
		$datos = $this->controlar_codigo_validacion($codigo_validacion);
		$codigo_validacion = kernel::db()->quote($codigo_validacion);
		$estado_activado = kernel::db()->quote(preinscripcion::estado_activado);

		$sql = "UPDATE 
					sga_preinscripcion 
				SET
					codigo_validacion = NULL, 
					email_valido = now(), 
					estado = $estado_activado
				WHERE 
					codigo_validacion = $codigo_validacion";

		kernel::db()->ejecutar($sql);
		return $datos;
	}

	function login($e_mail, $clave)
	{
		$e_mail = kernel::db()->quote($e_mail);
		$clave = kernel::db()->quote($this->cifrar_clave(trim($clave)));

		$sql = "SELECT 
					id_preinscripcion, 
					e_mail,
					email_valido,
					codigo_validacion,
					estado
				FROM 
					sga_preinscripcion
				WHERE 
					e_mail = $e_mail 
					AND clave = $clave";

		$datos = kernel::db()->consultar_fila($sql);

		if (!$datos) {
			throw new error_preinscripcion_login('login_error_clave_invalida');
		}

		// Si el mail no esta validado no se puede loguear
		if (is_null($datos['email_valido'])) {
			kernel::sesion()->set('__datos_reenvio_mail__', $datos);
			throw new error_preinscripcion_mail_no_validado('email_no_validado');
		}
		
		$this->controlar_estado($datos['id_preinscripcion'], $datos['estado']);

		return $datos['id_preinscripcion'];
	}

	function login_facebook($id)
	{
		$id = kernel::db()->quote($id);

		$sql = "SELECT 	id_preinscripcion,
						e_mail,
						clave,
						codigo_validacion,
						email_valido, 
						estado
				FROM 	sga_preinscripcion 
				WHERE	facebook is not null 
						AND facebook = $id";

		$datos = kernel::db()->consultar_fila($sql);

		// Si el mail no esta validado (independientemente de la vinculación con
		// Facebook) no se puede loguear.
		if ((!empty($datos)) && (is_null($datos['email_valido']))) {
			kernel::sesion()->set('__datos_reenvio_mail__', $datos);
			throw new error_preinscripcion_mail_no_validado('email_no_validado');
		}
		
		$this->controlar_estado($datos['id_preinscripcion'], $datos['estado']);

		if (empty($datos['id_preinscripcion'])) {
			$salida = null;
		} else {
			$salida = $datos['id_preinscripcion'];
		}

		return $salida;
	}

	function login_google($id)
	{
		$id = kernel::db()->quote($id);

		$sql = "SELECT 	id_preinscripcion,
						e_mail,
						clave,
						codigo_validacion,
						email_valido,
						estado
				FROM 	sga_preinscripcion 
				WHERE	google is not null 
						AND google = $id";

		$datos = kernel::db()->consultar_fila($sql);

		// Si el mail no esta validado (independientemente de la vinculación con
		// un Servicio Externo) no se puede loguear.
		if ((!empty($datos)) && (is_null($datos['email_valido']))) {
			kernel::sesion()->set('__datos_reenvio_mail__', $datos);
			throw new error_preinscripcion_mail_no_validado('email_no_validado');
		}

		$this->controlar_estado($datos['id_preinscripcion'], $datos['estado']);
		
		if (empty($datos['id_preinscripcion'])) {
			$salida = null;
		} else {
			$salida = $datos['id_preinscripcion'];
		}

		return $salida;
	}

	function vincular_usuario_se($e_mail, $id_se, $medio)
	{
		$e_mail = kernel::db()->quote($e_mail);
		$id_se = kernel::db()->quote($id_se);

		$sql = "UPDATE	sga_preinscripcion 
				SET		$medio = $id_se
				WHERE	e_mail = $e_mail;";

		kernel::db()->ejecutar($sql);
	}

	//--------------------------------------------------------------------------
	//---- RECUPERAR CLAVE -----------------------------------------------------
	//--------------------------------------------------------------------------

	function solicitud_resetear_clave($e_mail)
	{
		// el mail tiene que ser correcto (existente, validado)
		$e_mail = kernel::db()->quote($e_mail);
        
        $codigo = $this->generar_codigo_validacion();

		$sql = "UPDATE 
					sga_preinscripcion 
				SET
					codigo_validacion = '$codigo',
					codigo_validacion_alta = CURRENT_TIMESTAMP
				WHERE 
					e_mail = $e_mail";

		$filas_afectadas = kernel::db()->ejecutar($sql);
        
        // Si el UPDATE no impactó en ninguna fila es pq no existe el usuario en sga_preinscripcion
        if ($filas_afectadas == 0) {
			throw new error_preinscripcion_login('EMAIL inexistente');
		}
        
		return $codigo;
	}
	function solicitud_resetear_clave_documento($e_mail)
	{
		// el mail tiene que ser correcto (existente, validado)
		$e_mail = kernel::db()->quote($e_mail);
        
        $codigo = $this->generar_codigo_validacion();

		$sql = "UPDATE 
					sga_preinscripcion 
				SET
					codigo_validacion = '$codigo',
					codigo_validacion_alta = CURRENT_TIMESTAMP
				WHERE 
					e_mail = $e_mail";

		$filas_afectadas = kernel::db()->ejecutar($sql);
        
        // Si el UPDATE no impactó en ninguna fila es pq no existe el usuario en sga_preinscripcion
        if ($filas_afectadas == 0) {
			throw new error_preinscripcion_login('EMAIL inexistente');
		}
        
		return $codigo;
	}
	function cambiar_email($viejo, $nuevo)
	{
		$viejo = kernel::db()->quote($viejo);
		$nuevo = kernel::db()->quote($nuevo);

		$sql = "UPDATE 
					sga_preinscripcion
				SET 
					e_mail = $nuevo
				WHERE 
					e_mail = $viejo";

		kernel::db()->ejecutar($sql);
		return $nuevo;
	}
	function validar_resetear_clave($codigo_validacion)
	{
		return $this->controlar_codigo_validacion($codigo_validacion);
	}

	function resetear_clave($clave, $codigo_validacion)
	{
		// Validación de datos
		$datos = $this->controlar_codigo_validacion($codigo_validacion);
		$clave = kernel::db()->quote($this->cifrar_clave(trim($clave)));
		$codigo_validacion = kernel::db()->quote($codigo_validacion);

		$sql = "UPDATE 
					sga_preinscripcion
				SET 
					clave = $clave,
					codigo_validacion = NULL,
					codigo_validacion_alta = NULL,
					email_valido = now()
				WHERE 
					codigo_validacion = $codigo_validacion";

		kernel::db()->ejecutar($sql);
		return $datos;
	}

	function get_usuario_asociado_token($token)
	{
		$token = kernel::db()->quote($token);

		$sql = "SELECT 
					e_mail 
				FROM 
					sga_preinscripcion
				WHERE 
					codigo_validacion = $token";

		$datos = kernel::db()->consultar_fila($sql);
		return (isset($datos['e_mail'])) ? ($datos['e_mail']) : null;
	}
	function get_usuario_codigo($e_mail, $codigo)
	{
		$e_mail = kernel::db()->quote($e_mail);
		$codigo = kernel::db()->quote($codigo);

		$sql = "SELECT 
					e_mail 
				FROM 
					sga_preinscripcion
				WHERE 
					e_mail = $e_mail
					AND id_preinscripcion = $codigo";

		$datos = kernel::db()->consultar_fila($sql);
		return (isset($datos['e_mail'])) ? ($datos['e_mail']) : null;
	}

	//--------------------------------------------------------------------------
	//---- CAMBIAR MAIL --------------------------------------------------------
	//--------------------------------------------------------------------------

	function solicitud_cambiar_mail($cod_usuario_frontend, $email_nuevo)
	{
		$codigo = $this->generar_codigo_validacion();
		return $codigo;
	}

	function validar_nuevo_mail($codigo_validacion)
	{
		$datos_usuario = $this->controlar_codigo_validacion($codigo_validacion, false);
	}

	//--------------------------------------------------------------------------

	protected function cifrar_clave($clave)
	{
		$salt = kernel::proyecto()->get('salt');
		return sha1($salt . $clave);
	}

	protected function generar_codigo_validacion()
	{
		return sha1(uniqid(rand(0, 100), true));
	}

	protected function controlar_codigo_validacion($codigo_validacion)
	{
		$codigo_validacion = kernel::db()->quote($codigo_validacion);
        $interval = kernel::db()->quote(kernel::proyecto()->get_ttl_codigo_validacion());

		$sql = "SELECT 
					id_preinscripcion, 
					e_mail, 
					email_valido,
                    fecha_registro,
                    codigo_validacion_alta, 
                    codigo_validacion_alta + interval $interval AS codigo_validacion_expiracion,
                    COALESCE((codigo_validacion_alta + interval $interval) < CURRENT_TIMESTAMP, true) AS codigo_validacion_expirado
				FROM 
					sga_preinscripcion
				WHERE 
					codigo_validacion = $codigo_validacion";

		$datos = kernel::db()->consultar_fila($sql);
		if (empty($datos)) {
			throw new error_preinscripcion('TOKEN inexistente');
		}
		return $datos;
	}

	function clave_permitida($clave, $usuario)
	{
		$clave = trim($clave);

		// La clave debe ser distinta al nombre de usuario
		if ($clave == $usuario) {
			$mensaje = kernel::traductor()->trans('error_clave_igual_usuario');
			throw new error_preinscripcion_clave($mensaje);
		}

		// La clave debe tener una longitud determinada.
		$longitud_minima = kernel::proyecto()->get_clave_long_minima();
		if (strlen($clave) < $longitud_minima) {
			$mensaje = kernel::traductor()->trans('error_clave_corta', array('%1%' => $longitud_minima));
			throw new error_preinscripcion_clave($mensaje);
		}
	}

	function get_datos_usuario($e_mail)
	{
		$e_mail = kernel::db()->quote($e_mail);

		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion, 
					sga_preinscripcion.e_mail, 
					sga_preinscripcion.clave, 
					sga_preinscripcion.codigo_validacion, 
					sga_preinscripcion.email_valido, 
					sga_preinscripcion.facebook, 
					sga_preinscripcion.google, 
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,	
					COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,	
					sga_preinscripcion.tipo_documento, 
					sga_preinscripcion.nro_documento,
					sga_preinscripcion.pregunta_secreta
				FROM 
					sga_preinscripcion
				WHERE
					sga_preinscripcion.e_mail = $e_mail
				";

		return kernel::db()->consultar_fila($sql);
	}

        function get_usuario_by_documento($pais_documento, $tipo_documento, $nro_documento, $fecha_nacimiento)
	{
		$nro_documento = kernel::db()->quote($nro_documento);
		$fecha_nacimiento = kernel::db()->quote($fecha_nacimiento);

		$sql = "SELECT e_mail
                        FROM sga_preinscripcion
                        WHERE pais_documento = {$pais_documento}
                        AND tipo_documento = {$tipo_documento}
                        AND nro_documento = {$nro_documento}
                        AND fecha_nacimiento = {$fecha_nacimiento}
                ";

		$fila = kernel::db()->consultar_fila($sql);
                return $fila['e_mail'] ?? null;
	}

	//--------------------------------------------------------------------------
	//---- Validación de documento existente
	//--------------------------------------------------------------------------

	function documento_existente($pais_documento, $tipo_documento, $nro_documento)
	{
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);

		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion, 
					sga_preinscripcion.e_mail, 
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,	
					COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,	
					sga_preinscripcion.fecha_registro,
					sga_preinscripcion.codigo_validacion,
					sga_preinscripcion.email_valido
				FROM 
					sga_preinscripcion
				WHERE
					sga_preinscripcion.pais_documento = $pais_documento
					AND sga_preinscripcion.tipo_documento = $tipo_documento
					AND sga_preinscripcion.nro_documento = $nro_documento
				";

		return kernel::db()->consultar_fila($sql);
	}
	//existen el documento, y no tiene preinscripciones procesesadas I 
	function documento_no_procesado($pais_documento, $tipo_documento, $nro_documento)
	{
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);

		$sql = "SELECT sga_preinscripcion_propuestas.estado,
					sga_preinscripcion.id_preinscripcion, 
					sga_preinscripcion.e_mail, 
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,	
					COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,	
					sga_preinscripcion.fecha_registro,
					sga_preinscripcion.codigo_validacion,
					sga_preinscripcion.email_valido
				FROM 
					sga_preinscripcion
				left Join sga_preinscripcion_propuestas on sga_preinscripcion_propuestas.id_preinscripcion= sga_preinscripcion.id_preinscripcion

				WHERE
					sga_preinscripcion.pais_documento = $pais_documento
					AND sga_preinscripcion.tipo_documento = $tipo_documento
					AND sga_preinscripcion.nro_documento = $nro_documento
					and ( sga_preinscripcion_propuestas.estado <> 'I' or   sga_preinscripcion_propuestas.estado is null)
				";

		return kernel::db()->consultar_fila($sql);
	}

	function documento_email_existente($pais_documento, $tipo_documento, $nro_documento, $e_mail)
	{
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);
		$e_mail = kernel::db()->quote($e_mail);

		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion, 
					sga_preinscripcion.e_mail, 
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,	
					COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,	
					sga_preinscripcion.fecha_registro,
					sga_preinscripcion.codigo_validacion,
					sga_preinscripcion.email_valido
				FROM 
					sga_preinscripcion
				WHERE					
					sga_preinscripcion.pais_documento = $pais_documento
					AND sga_preinscripcion.tipo_documento = $tipo_documento
					AND sga_preinscripcion.nro_documento = $nro_documento
					AND sga_preinscripcion.e_mail =  $e_mail
				";

		return kernel::db()->consultar_fila($sql);
	}
function documento_nacimiento_existente($pais_documento, $tipo_documento, $nro_documento, $fecha_nacimiento)
	{
		$pais_documento = kernel::db()->quote($pais_documento);
		$tipo_documento = kernel::db()->quote($tipo_documento);
		$nro_documento = kernel::db()->quote($nro_documento);
		$fecha_nacimiento = kernel::db()->quote(convertir_fecha_formato_base($fecha_nacimiento));

		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion, 
					sga_preinscripcion.e_mail, 
					COALESCE(sga_preinscripcion.apellido_elegido, sga_preinscripcion.apellido) as apellido,	
					COALESCE(sga_preinscripcion.nombres_elegido, sga_preinscripcion.nombres) as nombres,	
					sga_preinscripcion.fecha_registro,
					sga_preinscripcion.codigo_validacion,
					sga_preinscripcion.email_valido
				FROM 
					sga_preinscripcion
				WHERE					
					sga_preinscripcion.pais_documento = $pais_documento
					AND sga_preinscripcion.tipo_documento = $tipo_documento
					AND sga_preinscripcion.nro_documento = $nro_documento
					AND sga_preinscripcion.fecha_nacimiento =  $fecha_nacimiento
				";

		return kernel::db()->consultar_fila($sql);
	}
function email_pregunta_secreta_fecha_nacimento_existente($e_mail, $respuesta_secreta, $fecha_nacimento)
	{
		$e_mail = kernel::db()->quote($e_mail);
		$respuesta_secreta = kernel::db()->quote($respuesta_secreta);
		$fecha_nacimento = kernel::db()->quote(convertir_fecha_formato_base($fecha_nacimento));

		$sql = "SELECT 
					sga_preinscripcion.id_preinscripcion
				FROM 
					sga_preinscripcion
				WHERE					
					sga_preinscripcion.e_mail = $e_mail
					AND sga_preinscripcion.respuesta_secreta = $respuesta_secreta
					AND sga_preinscripcion.fecha_nacimiento = $fecha_nacimento
				";

		return kernel::db()->consultar_fila($sql);
	}
	function usuario_existente($e_mail)
	{
		$e_mail = kernel::db()->quote($e_mail);

		$sql = "SELECT 
					1 as existe
				FROM 
					sga_preinscripcion
				WHERE
					sga_preinscripcion.e_mail = $e_mail
				";

		$res = kernel::db()->consultar_fila($sql);
			return $res['existe'];
		}

	protected function controlar_estado($id_preinscripcion, $estado)
	{	
		if ($estado == preinscripcion::estado_inscripto) {
			$mensaje = kernel::traductor()->trans('login.error.incorporado_g3');
			if ((!empty($id_preinscripcion)) && ($this->hay_propuestas_copiadas_g3($id_preinscripcion))) {
				$mensaje .= '<br/><br/>';
				$mensaje .= kernel::traductor()->trans('login.error.preinsc_copiadas');
			}
			throw new error_preinscripcion_login($mensaje);
		}
	}
	
	protected function hay_propuestas_copiadas_g3($id_preinscripcion)
	{
		$propuestas_preinsc = util::consultas()->get_propuestas_insc($id_preinscripcion);
		foreach ($propuestas_preinsc as $preinsc) {
			if ($preinsc['estado'] == preinscripcion::estado_preinsc_copiada_g3) {
				return true;
			}
		}
		return false;
	}
        function validar_expiracion_token($codigo_validacion) {
            /* Validar que la recha de registro no sea mayor a fecha de actual mas parametro
            cantitidad de horas */
            $datos_usuario = $this->controlar_codigo_validacion($codigo_validacion, false);
            $cantidad_horas = kernel::proyecto()->get_parametro('tiempo_confirmacion_alta_preinscripcion');
            $fecha_limite = get_fecha_hora_desplazada($datos_usuario['fecha_registro'], $cantidad_horas, 'hora');
            $hoy = date("Y-m-d H");
            $codigo_validacion = kernel::db()->quote($codigo_validacion);
            if ($fecha_limite < $hoy) {
               //borro registro de la base de datos 
                $sql = "DELETE FROM sga_preinscripcion 
			WHERE codigo_validacion = $codigo_validacion";

		kernel::db()->ejecutar($sql);
               throw new error_preinscripcion_token('TOKEN_expiro');
            }
        }

}