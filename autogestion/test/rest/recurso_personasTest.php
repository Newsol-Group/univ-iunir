<?php
namespace test\rest;

use SIU\Chulupi\util\rest\RestTestCase;
use SIUToba\rest\seguridad\rest_usuario;

/**
 * Para correr estas clases (parte del suit rest) hay que ejecutar en el directorio de test
 *  phpunit -c php-unit.xml rest
 */
class recurso_personasTest extends RestTestCase {


	protected function setUp()
	{
		parent::setUp();
		$app = $this->setupRest();

		//todo subir a una clase del proyecto
		$user = new rest_usuario();
		$user->set_usuario('user');
		$this->mock_autenticador($user, $app);
	}


	public function test()
	{
		$p = $this->ejecutar('GET', '/personas', array(), array());
		$this->assertEquals(200, $p->get_status());
	}
}

 