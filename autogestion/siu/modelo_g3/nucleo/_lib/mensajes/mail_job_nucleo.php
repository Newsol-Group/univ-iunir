<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\OAuth;
// Alias the League Google OAuth2 provider class
use League\OAuth2\Client\Provider\Google;

class mail_job_nucleo extends PHPMailer
{
	// Tabla DJJob
	const jobs_tabla = 'jobs';
	// Colas
	const cola_email = 'emails';
	const cola_notificador = 'notificador';
	
	const AUTH_TYPE_SSL = 'SSL';
	const AUTH_TYPE_TLS = 'TLS';
	const AUTH_TYPE_SIN_AUTH = 'S/A';
	const AUTH_TYPE_XOAUTH2 = 'XOAUTH2';
	const CHARSET_UTF_8 = 'UTF-8';

	/** @var string La fecha a partir de la cual se debe enviar el email. */
	protected $run_at = null;
	/** @var string Email para configurar el ReplyTo. */
	protected $responder_a = null;
	
	// Propiedades para rearmar el objeto de la clase OAuth
	protected $oauth2_activo = false;
	protected $oauth2_type = null;
	protected $oauth2_email = null;
	protected $oauth2_client_id = null;
	protected $oauth2_client_secret = null;
	protected $oauth2_refresh_token = null;

	/**
	 * Constructor
	 *
	 * @param string $run_at La fecha a partir de la cual se debe enviar el email, formato 'Y-m-d'.
	 * @param string $responder_a Email para configurar el ReplyTo.
	 */
	function __construct($run_at = null, $responder_a = null)
	{
		parent::__construct();
		$this->run_at = $run_at;
		$this->responder_a = $responder_a;
		$this->configurarSMTP();
	}

	/**
	 * @param string $run_at La fecha a partir de la cual se debe enviar el email, formato 'Y-m-d'.
	 */
	public function setRunAt($run_at)
	{
		$this->run_at = $run_at;
	}

	public function agregarAdjunto($pathArchivo, $nombreArchivo = '')
	{
		try {
			//see: http://php.net/manual/es/function.finfo-file.php
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$type = finfo_file($finfo, $pathArchivo);
			finfo_close($finfo);
		} catch (Exception $e) {
			$type = 'application/octet-stream';
		}
		
		// $this->addAttachment($pathArchivo, $nombreArchivo, 'base64', $type);
		
		/*
		 * @see https://stackoverflow.com/questions/27405576/phpmailer-attach-remote-file-with-url
		 * Se adjunta dentro del mismo email, ver tamaño de la columna 'handler' de la tabla 'jobs'.
		 */
		$this->addStringAttachment(file_get_contents($pathArchivo), $nombreArchivo, 'base64', $type);
	}

	/**
	 * @return PDO Conexión PDO.
	 */
	protected function getConexionPDO()
	{
		return toba::db()->get_pdo();
	}
	
	public static function get_smtp_debug_nivel_gestion()
	{
		return guarani_instalacion_conf::get_parametro_instalacion('smtp_debug_nivel');
	}
	
	public static function get_smtp_debug_nivel_3w()
	{
		$parametros_smtp = \SIU\Chulupi\kernel::proyecto()->get_parametros_smtp();
		return (isset($parametros_smtp['smtp_debug']))? $parametros_smtp['smtp_debug'] : null;
	}
	
	/**
	 * @return int Nivel de debug del servidor de correo SMTP.
	 * Opciones:
	 * 0 = No genera información de debug de SMTP, opción por defecto.
	 * 1 = Genera información de debug del cliente de SMTP.
	 * 2 = Genera información de debug del cliente y servidor de SMTP.
	 * 3 = Genera información de debug del cliente, servidor y estados de conexión de SMTP.
	 * 4 = Genera toda la información de debug de SMTP.
     */
	public static function get_smtp_debug_nivel()
    {
        $valor = (\comunes::es_interfaz_autogestion())? static::get_smtp_debug_nivel_3w() : static::get_smtp_debug_nivel_gestion();
		
		$smtp_debug_nivel = SMTP::DEBUG_OFF;
		
		if (!is_null($valor)) {
			
			if (in_array($valor, [SMTP::DEBUG_OFF, SMTP::DEBUG_CLIENT, SMTP::DEBUG_SERVER, SMTP::DEBUG_CONNECTION, SMTP::DEBUG_LOWLEVEL])) {
				$smtp_debug_nivel = intval($valor);
			}
			
		}
		
        return $smtp_debug_nivel;
    }
	
	/**
	 * Obtiene la configuración del servidor SMTP desde los parámetros del sistema.
	 *
	 * @return array.
	 */
	public static function get_configuracion_smtp()
	{
		$datos = [];
		$datos['from'] = guarani::parametros()->get_valor('email_cuenta', null, false);
		$datos['host'] = guarani::parametros()->get_valor('email_servidor', null, false);
		$datos['usuario'] = guarani::parametros()->get_valor('email_usuario', null, false);
		$datos['clave'] = guarani::parametros()->get_valor('email_clave', null, false);
		$datos['responder_a'] = guarani::parametros()->get_valor('email_responder_a', null, false);
		$datos['nombre_remitente'] = guarani::parametros()->get_valor('email_nombre_remitente', null, false);
		$datos['auth'] = (int) guarani::parametros()->get_valor('email_autentificacion', null, false);
		$datos['seguridad'] = guarani::parametros()->get_valor('email_autentificacion_tipo', null, false);
		$datos['puerto'] = (int) guarani::parametros()->get_valor('email_autentificacion_puerto', null, false);
		$datos['oauth2_activo'] = guarani::parametros()->get_valor('email_xoauth2_activo', null, false);
		$datos['oauth2_email'] = guarani::parametros()->get_valor('email_xoauth2_email', null, false);
		$datos['oauth2_client_id'] = guarani::parametros()->get_valor('email_xoauth2_client_id', null, false);
		$datos['oauth2_client_secret'] = guarani::parametros()->get_valor('email_xoauth2_client_secret', null, false);
		$datos['oauth2_refresh_token'] = guarani::parametros()->get_valor('email_xoauth2_refresh_token', null, false);
		
		return $datos;
	}
	
	/**
	 * Configura un objeto de la clase PHPMailer.
	 *
	 * @param PHPMailer|mail_job $mail Objeto de la clase PHPMailer.
	 */
	public static function configurar_smtp(&$mail)
	{
		//Se obtiene la configuración del SMTP
		$datos = static::get_configuracion_smtp();
		
		//Tell PHPMailer to use SMTP
	   	$mail->IsSMTP();
		
		// Set encoding
		$mail->CharSet = static::CHARSET_UTF_8;
		
		// Set email format to HTML
		$mail->isHTML(true);
		
		//Enable SMTP debugging
		$mail->SMTPDebug = static::get_smtp_debug_nivel();
		
		//Set the hostname of the mail server
		$mail->Host = trim($datos['host']);
		
		if (isset($datos['puerto']) && trim($datos['puerto']) != '') {
			//Set the SMTP port number
			$mail->Port = $datos['puerto'];
		}
		
		if (isset($datos['seguridad']) && trim($datos['seguridad']) != '') {
			if ($datos['seguridad'] == static::AUTH_TYPE_SSL) {
				if (! extension_loaded('openssl')) {
					throw new Exception('Para usar un SMTP con encriptación SSL es necesario activar la extensión "openssl" en php.ini');
				}
			}
			//Set the encryption mechanism to use
			$mail->SMTPSecure = trim(strtolower($datos['seguridad']));
		}
		
		if (isset($datos['auth']) && $datos['auth']) {
			
			//Whether to use SMTP authentication
			$mail->SMTPAuth = true;
			
			/**
			 * Si se autentica con OAUTH2
			 * Se toma como ejemplo el archivo vendor/phpmailer/phpmailer/examples/gmail_xoauth.phps
			 * @see https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2
			 */
			if ($datos['oauth2_activo'] == comunes::si) {
				
				//Set AuthType to use XOAUTH2
				$mail->AuthType = static::AUTH_TYPE_XOAUTH2;

				//Fill in authentication details here
				//Either the gmail account owner, or the user that gave consent
				$email = $datos['oauth2_email'];
				$clientId = $datos['oauth2_client_id'];
				$clientSecret = $datos['oauth2_client_secret'];

				//Obtained by configuring and running get_oauth_token.php
				//after setting up an app in Google Developer Console.
				$refreshToken = $datos['oauth2_refresh_token'];

				//Create a new OAuth2 provider instance
				$provider = new Google(
					[
						'clientId' => $clientId,
						'clientSecret' => $clientSecret,
					]
				);

				//Pass the OAuth provider instance to PHPMailer
				$mail->setOAuth(
					new OAuth(
						[
							'provider' => $provider,
							'clientId' => $clientId,
							'clientSecret' => $clientSecret,
							'refreshToken' => $refreshToken,
							'userName' => $email,
						]
					)
				);

			} else {// Si se autentica con usuario y password
				
				$mail->Username = trim($datos['usuario']);
				$mail->Password = trim($datos['clave']);
				
			}
			
		}

        //Habilita la encripcion por defecto de la capa de transporte si el server provee el mecanismo.
        $mail->SMTPAutoTLS = (isset($datos['auto_tls']) && $datos['auto_tls'] == 1);
		
		//Set who the message is to be sent from
		//For gmail, this generally needs to be the same as the user you logged in as
		$mail->setFrom($datos['from'], $datos['nombre_remitente']);
		
	}
	
	/**
	 * @see https://github.com/PHPMailer/PHPMailer Documentación de PHPMailer.
	 */
	protected function configurarSMTP()
	{
		
		// Se obtiene la configuración del SMTP
		$datos = static::get_configuracion_smtp();
		
		if ($datos['oauth2_activo'] == comunes::si) {
			$this->oauth2_activo = true;
			//Set AuthType to use XOAUTH2
			$this->oauth2_type = static::AUTH_TYPE_XOAUTH2;

			//Fill in authentication details here
			//Either the gmail account owner, or the user that gave consent
			$this->oauth2_email = $datos['oauth2_email'];
			$this->oauth2_client_id = $datos['oauth2_client_id'];
			$this->oauth2_client_secret = $datos['oauth2_client_secret'];

			//Obtained by configuring and running get_oauth_token.php
			//after setting up an app in Google Developer Console.
			$this->oauth2_refresh_token = $datos['oauth2_refresh_token'];
		}
		
		// Se configura el objeto de la clase PHPMailer.
		static::configurar_smtp($this);
		
		// Se quita el objeto OAuth para evitar el error "Serialization of 'Closure' is not allowed"
		$this->oauth = null;

		// Si esta seteada la propiedad $responder_a la tomo de ahí
		if(!is_null($this->responder_a)){
			$this->addReplyTo($this->responder_a);
		}// Si esta seteado el parámetro 'email_responder_a' lo tomo de ahí
		else if ($datos['responder_a']) {
			$this->addReplyTo($datos['responder_a']);
		}
		
	}
    
	/*
	 * Al hacerse el unserialize() del objeto PHPMailer vuelvo a armar 
	 * la propiedad $this->oauth con un objeto de la clase OAuth.
	 * @see: https://www.php.net/manual/es/language.oop5.magic.php#object.wakeup
	 */
    public function __wakeup()
    {
        /**
		 * Si se autentica con OAUTH2
		 * Se toma como ejemplo el archivo vendor/phpmailer/phpmailer/examples/gmail_xoauth.phps
		 * @see https://github.com/PHPMailer/PHPMailer/wiki/Using-Gmail-with-XOAUTH2
		 */
		if ($this->oauth2_activo) {

			//Set AuthType to use XOAUTH2
			$this->AuthType = $this->oauth2_type;

			//Fill in authentication details here
			//Either the gmail account owner, or the user that gave consent
			$email = $this->oauth2_email;
			$clientId = $this->oauth2_client_id;
			$clientSecret = $this->oauth2_client_secret;

			//Obtained by configuring and running get_oauth_token.php
			//after setting up an app in Google Developer Console.
			$refreshToken = $this->oauth2_refresh_token;

			//Create a new OAuth2 provider instance
			$provider = new Google(
				[
					'clientId' => $clientId,
					'clientSecret' => $clientSecret,
				]
			);

			//Pass the OAuth provider instance to PHPMailer
			$this->setOAuth(
				new OAuth(
					[
						'provider' => $provider,
						'clientId' => $clientId,
						'clientSecret' => $clientSecret,
						'refreshToken' => $refreshToken,
						'userName' => $email,
					]
				)
			);

		}
    }

	public function sendLater()
	{
		$pdo = $this->getConexionPDO();
		DJJobSIU::setConnection($pdo);
		DJJobSIU::setJobsTable(static::jobs_tabla);
		DJJobSIU::enqueue($this, static::cola_notificador, $this->run_at);
	}

	public function perform()
	{
		$this->Send();
	}

}