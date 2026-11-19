<?php
namespace siu\operaciones\wsf;

use SIU\Chulupi\kernel;
use SIU\Chulupi\ErrorKernel;
use SIU\Chulupi\util\WsMensaje;
use siu\modelo\datos\catalogo;
use siu\extension_kernel\controlador_errores_control;
use siu\modelo\entidades\url_encuestas;

class controlador extends controlador_errores_control
{
	
	const id_fila_generica = '_toba_fila_';
	
    function accion__index()
    {
    }

	/*
	 * hacia guarani
	 */
    function accion__boton()
    {
			//$this->eco_test(array('hola' => 'chau'));
			//return; //prueba basica contra el propio servicio
		
			//$datos = array('pepe'=>'papa','pin'=>array('put','poke','delete'));

			$parametros = array('alcance'=>1);
			$datos = $this->get_datos_ws($parametros);
			$rta = $this->ejecutar_ws('habilitar', $datos);
			$this->procesar_respuesta_ws($rta, $parametros);
			$this->test_urls($datos['alcance']['item']);
			return;

	}
	private function ejecutar_ws($operacion, $parametros){
		$action = $operacion;
		$opciones = array( 
				"to" => "http://localhost/kolla/3.2/servicios.php/habilitaciones",
				"action" => $action,
				"useWSA" => true);
		$msg = new WsMensaje($parametros);
		try {
			$client = new \WSClient($opciones);
			$response = $client->request($msg->wsf());
			$msg_response = new WsMensaje($response);
			return $msg_response->get_array();
		} catch (\Exception $e) {
			if ($e instanceof \WSFault) {
				throw new ErrorKernel("Soap Fault: ". $e->Reason);
			} else {
				throw new ErrorKernel($e->getMessage());
		}
		}
		return array();
	}

	//Mapeo a la consulta que viene con fetch_num
	const encuesta = 0;
	const alc_titulo = 1;
	const alc_id = 2;
	const item_id = 3;
	const item_titulo = 4;
	
	private function get_datos_ws($parametros){
		
		$datos_db = catalogo::consultar('encuestas', 'definicion_alcance', $parametros);
		
		/*			a.encuesta, 
					a.titulo,
					a.alcance,
					i.item,
					i.titulo*/
		if(\guarani_arreglos::count($datos_db) <=0) {
				throw new ErrorKernel("El conjunto de datos es vacío");
			}
		
		$datos = array();
		$datos['anonima'] = 'S'; // gde_encuestas
		//$datos['debug'] = 'S';//no se guarda en kolla
		$datos['encuesta'] = $datos_db[0][self::encuesta]; //son todos iguales
		$datos['fecha_desde'] = '2010-05-11'; // gde_encues_alcance
		$datos['fecha_hasta'] = '2019-09-20';
		$datos['paginado'] = 'S';
		$datos['estilo'] = 1;
		//$datos->habilitacion = 1;
		//$datos['alcance'] = new alcance();
		$datos['alcance']['descripcion'] = $datos_db[0][self::alc_titulo].'soy làtínª';
		$datos['alcance']['id_externo'] = $datos_db[0][self::alc_id];
		$items = array();
		foreach($datos_db as $item){
			$items[] = array('id_externo' => $item[self::item_id],
							 'descripcion' =>   $item[self::item_titulo]);
		}
		$datos['alcance']['item'] = $items;
		var_dump($datos);
		var_dump($datos_db);

		return $datos;
		
		
		//esto de abajo funciona por si se rompe lo otro
		/*
		$datos = array();
		$datos['anonima'] = 'S';
		$datos['debug'] = 'n';//si es != 'N' se habilita igual por seguridad
		$datos['encuesta'] = '2';
		$datos['fecha_desde'] = '2010-05-11';
		$datos['fecha_hasta'] = '2019-09-20';
		$datos['paginado'] = 'S';
		$datos['estilo'] = 1;
		//$datos->habilitacion = 1;
		//$datos['alcance'] = new alcance();
		$datos['alcance']['descripcion'] = 'mi_alcance2';
		$datos['alcance']['id_externo'] = 'id_externo2';
		$items = array( 
				array('id_externo' => '9p7', 
					 'descripcion' => 'mi_item desc'),
				array('id_externo' => '087', 
					 'descripcion' => 'mi_item 2 desc'),
		);
		$datos['alcance']['item'] = $items;
		return $datos;*/
	}
	
	private $password;
	private $id_habilitacion;
	
	private function procesar_respuesta_ws($rta, $parametros){
		// guardar en gde_encues_alcance: id_habilitacion / password
		$this->password = $rta['password'];
		$this->id_habilitacion = $rta['id_hab'];
		//guardo en atributos para usarlo en otros metodos de este circuito.
		$password = $rta['password'];
		$id_habilitacion = $rta['id_hab'];
		
		$sql = "UPDATE gde_encues_alcance 
				SET kolla_id_habilitacion = '$id_habilitacion',
				    kolla_password = '$password'
				WHERE alcance = {$parametros['alcance']}";
		$datos = kernel::db()->ejecutar($sql);
		return $datos;		
		
		
		
		//print_r($rta);
		/*$url_base = $rta['url']; //viene de configuracion.
		$id_hab = $this->id_habilitacion; //BD
		$password_hab = $this->password; //BD
		echo 'La habilitacion creada es '.$id_hab;
		echo '<br />';
		$asig_id = 0; //generar por cada alumno que se queira identificar
		foreach ($rta['log']['items'] as $item){
			$asig_id++;
			$token_seguro = static::get_token_seguridad($password_hab, $id_hab, $item['id'], $asig_id, false);
			$url_usuario = $url_base . $token_seguro;
			echo '<p>';
			echo "<a href='$url_usuario'>$url_usuario</a>";
			echo '</p>';
		}*/
	}
	
	private function test_urls($items){
		var_dump($items);
		$asig_id = 0; //obtener asignaciones para generar urls. Aca se habilita para todos los items
		foreach ($items as $item){
			$url = $this->generar_url($item['id_externo'], $asig_id++, false );
			echo '<p>';
			echo "<a href='$url'>$url</a>";
			echo '</p>';
			
		}
	}
	
	private function generar_url($item, $asignacion, $responder){
		$id_hab = $this->id_habilitacion; //BD
		$password_hab = $this->password; //BD
		//Esta url saldria de algun archivo de configuracion/configurable
		$url_base = 'http://localhost/kolla/3.2/aplicacion.php?ah=506b1dcbcdc01&ai=kolla||40000112&tcm=previsualizacion&h=6&t=';
		
		$token_seguro = static::get_token_seguridad($password_hab, $id_hab, $item, $asignacion, $responder);
		$url_usuario = $url_base . $token_seguro;
		return $url_usuario;
		
	}
	
	public function modelo() {
		return null;
	}
	
	
	
	
	private function eco_test($datos){
		$opciones = array( 
					"to" => "http://localhost/humanidades/servicios.php",
					"action" => "eco");
		$msg = new WsMensaje($datos);
		$client = new \WSClient($opciones);
		$response = $client->request($msg->wsf());
		$msg_response = new WsMensaje($response);

		print_r($msg_response->get_array());
	}
	
	
	///////////////////////////////////////////////////////////////////
	//////////////////FUNCIONES SIMETRICAS CON KOLLA ///////////
	///////////////////////////////////////////////////////////////////
	/**
	 *
	 * @param type $password el password provisto en la habilitacion
	 * @param type $id_hab el id de la habilitacion
	 * @param type $item_externo el item que se desea responder
	 * @param type $cod_externo id de asignacion???
	 * @param type $responder boolean
	 * @return type el token para concatener a la direccion base provista en la habilitacion.
	 */
	static function get_token_seguridad($password, $id_hab, $item_externo, $cod_externo, $responder)
	{
		$r = $responder ? 'S' : 'N';
		$s = "$id_hab|$item_externo|$cod_externo|$r";
		$t = url_encuestas::encriptar_kolla($s, $password);
		return urlencode($t);
	}
	
}
?>