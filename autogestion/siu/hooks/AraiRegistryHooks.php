<?php

namespace siu\hooks;

use SIU\AraiCli\AraiCli;
use SIU\AraiCli\Services\Registry\HooksInterface;
use SIU\AraiJsonParser\Feature\Consumption;
use SIU\AraiJsonParser\Feature\Provision;
use SIU\AraiJsonParser\Version\VersionParser;
use Symfony\Component\Yaml\Yaml;

class AraiRegistryHooks implements HooksInterface
{
    protected $pathInstalacion;
    protected $WebConf;
    protected $samlSettings;
    protected $loginConf;
    protected $config;

    public function __construct()
    {
        $this->pathInstalacion = realpath(__DIR__.'/../../../instalacion');
        $this->WebConf = include "$this->pathInstalacion/servicios_web_config.php";
        $this->samlSettings = include "$this->pathInstalacion/saml/settings.php";
        $this->loginConf = include "$this->pathInstalacion/login.php";
        $this->config = include "$this->pathInstalacion/config.php";
    }

    /**
     * Retorna la URL base bien formada. Excluye puertos estandar.
     *
     * Ej.
     *  - http://url.huarpe.com
     *  - http://url.huarpe.com:7000
     *
     *
     * @return string la url base completa
     */
    protected function getUrl()
    {
        $parametros = $this->readParameters();
        $url = trim($parametros['url_base'], '/');

        return $url;
    }

    /**
     * Se ejecuta antes de instanciar el cliente de guzzle, se puede usar para inyectar las credenciales para registry
     * y no forzar a setear las variables de entorno.
    */
    public function preConf(\Pimple\Container $c)
    {
        $parametros = $this->readParameters();
        $c['arai-registry-user'] = $parametros['arai_registry_user'];
        $c['arai-registry-pass'] = $parametros['arai_registry_pass'];
    }

    /**
     * Se ejecuta antes de enviar una feature al registry. En esta ventana se deben configurar las features para incluir
     * los datos dependientes de la instalación.
     *
     * @param Provision $feature una feature sin información de instalación, por ej: url
     */
    public function preProvide(Provision $feature)
    {
        if ($feature->getType() == 'app') {
            $feature->setEndpoint($this->getUrl());
        }

        $vParser = new VersionParser();
        $version = $vParser->parseConstraint($this->getProyectoVersion());
        $feature->setVersion($version);
    }

    /**
     * Se ejecuta después de que se registra una provisión. Todos los consumidores están en $f->getConsumers().
     *
     * @param Provision $feature
     */
    public function postProvide(Provision $feature)
    {
    }

    /**
     * Se ejecuta antes de enviar una feature para consumo al registry.
     *
     * @param Consumption $f
     */
    public function preConsume(Consumption $feature)
    {
        if ($feature->getName() == 'service:siu/sso-saml-idp') {
            $this->preConsumeSamlIdp($feature);

            return;
        }
        if ($feature->getType() == 'api') {
            $this->preConsumeApi($feature);
        }
    }

    /**
     * Se ejecuta cuando vuelve una feature del registry con los datos para su consumo. Este hook es utilizado para
     * configurar el sistema a partir de la información en registry.
     *
     * @param Consumption $feature
     */
    public function postConsume(Consumption $feature)
    {
        if ($feature->getName() == 'service:siu/sso-saml-idp') {
            $this->postConsumeSamlIdp($feature);
        }
        if ($feature->getType() == 'api') {
            $this->postConsumeApi($feature);
        }
    }

    //---------------------------------------------------------------
	//---- PRE - CONSUME
	//---------------------------------------------------------------

    protected function preConsumeSamlIdp(Consumption $feature)
    {
        $portalUrl = $this->getUrl();
        $sp = "$portalUrl/acceso";
        $acs = "$portalUrl/acceso?auth=saml";
        $sls = "$portalUrl/acceso/logout";

        $parametros = $this->readParameters();

        $parametros['sp.entity_id'] = $sp;
        $parametros['sp.url_acs'] = $acs;
        $parametros['sp.url_sls'] = $sls;

        $this->saveParameters($parametros);

        $options['assertionConsumerService'] = $acs;
        $options['singleLogoutService'] = $sls;
        $options['appUniqueId'] = self::getAppUniqueId();

        $feature->setEndpoint($sp);
        $feature->setOptions($options);
    }


    public function preConsumeApi(Consumption $feature)
    {
        $parametros = $this->readParameters();
        $opcionesFijas = $feature->getOptions();
        if (!isset($opcionesFijas['symfony-parameter'])) {
            echo "No se pudo configurar la feature '".$feature->getName()."', falta setearle el nombre de la api en el campo 'symfony-parameter'\n";

            return;
        }

        $apiSistema = $opcionesFijas['symfony-parameter'].'.api';

        $providers = $feature->getProviders();
        if (empty($providers)) {
            return; //Nada para configurar
        }

        $provider = current($providers);  //Asume primer PROVIDER que encuentra! OJO

        $certServer = $provider->getOptions()['auth']['credentials']['cert'] ?: null;

        if (! $certServer) {
            echo "No se pudo configurar la feature '".$feature->getName()."': el provider '".$provider->getName()."', no posee el certificado público\n";

            return;
        }

        $parametros[$apiSistema] = $this->getAuthFor3w($parametros);
        ;

        $this->saveParameters($parametros);


        $registryCredentials = $this->getAuthForRegistry($parametros, $certServer);

        $feature->addAuth(
            $registryCredentials['type'],
            $registryCredentials['credentials']
        );
        
        $url = $parametros['url_siu'];
		$apiId = str_replace("-", "_", explode("/", $feature->getName())[1]);
        $this->WebConf['consumidos'][$apiId] = [
            'tipo' => 'rest',
            'parametros' => [
                'base_uri' => $provider->getEndpoint(),
                'auth' => ['guarani', 'guarani', 'basic']
            ]
        ];

        file_put_contents("$this->pathInstalacion/servicios_web_config.php", "<?php\nreturn ".var_export($this->WebConf, true).";");
        
    }

    //---------------------------------------------------------------
	//---- POST - CONSUME
	//---------------------------------------------------------------

    protected function postConsumeApi(Consumption $feature)
    {
        $opcionesFijas = $feature->getOptions();
        $apiSistema = $opcionesFijas['symfony-parameter'].'.api';

        $cant = count($feature->getProviders());
        echo "Detectando proveedores de api '{$feature->getName()}': {$cant}\n";

        $providers = $feature->getProviders();
        if (empty($providers)) {
            return; //Nada para configurar
        }

        $provider = current($providers);  //Asume primer PROVIDER que encuentra! OJO

        echo "Procesando proveedor '{$provider->getName()}': ";

        $endpoint = $provider->getEndpoint();
        if ($endpoint == '') {
            echo "no posee configurado 'endpoint'\n";
            return;
        }

        $type = $provider->getOptions()['auth']['type'];

        $parametros = $this->readParameters();

        $parametros[$apiSistema] = $this->getAuthFor3w($parametros);
        
        // registro url del provider
        $parametros[$apiSistema]['base_uri'] = $endpoint;
        // forzamos el tipo de auth a la que soporta el server
        $parametros[$apiSistema]['auth'][2] = $type;

        $this->loginConf['saml']['activo'] = true;
        $this->loginConf['saml']['parametros']['settings_file'] = "$this->pathInstalacion/saml/settings.php";
        $this->loginConf['saml']['parametros']['saml_uid'] = 'userAccounts';
        $this->loginConf['saml']['parametros']['local_uid'] = 'usuario';
        unset($this->loginConf['saml']['parametros']['accesos']);
        file_put_contents("$this->pathInstalacion/login.php", "<?php\nreturn ".var_export($this->loginConf, true).";");

        echo "configurado con '{$provider->getEndpoint()}'\n";

        $this->saveParameters($parametros);
    }

    protected function postConsumeSamlIdp(Consumption $feature)
    {
        $providers = $feature->getProviders();
        if (empty($providers)) {
            return;
        }    //Nada para configurar
        /* @var Provision */
        $provider = current($providers);  //Asume primer IDP que encuentra!
        $options = $provider->getOptions();

        $endpoint = $provider->getEndpoint();
        if ($endpoint == '') {
            return;
        } //Nada para configurar

        $parametros = $this->readParameters();

        $parametros['idp.entity_id'] = $endpoint;
        $parametros['idp.url_sso'] = $options['singleSignOnService'];
        $parametros['idp.url_sls'] = $options['singleLogoutService'];
        $parametros['idp.cert_data'] = $options['certificate'];
        $parametros['idp.uid_attribute'] = $options['attributes']['uid'];

        $fp = str_split(strtoupper(openssl_x509_fingerprint($options['certificate'])),2);
        $fp = implode(':', $fp);
        $url = $this->getUrl();
        $url_siu = $parametros['url_siu'];
        $file = file_get_contents("$this->pathInstalacion/saml/settings.php");
        $file = str_replace("http://localhost/g3w3_sso", $url, $file);
        $file = str_replace("http://localhost/simplesaml", "$url_siu/idp", $file);
        $file = str_replace("AF:E7:1C:28:EF:74:0B:C8:74:25:BE:13:A2:26:3D:37:97:1D:A1:F9", $fp, $file);
        file_put_contents("$this->pathInstalacion/saml/settings.php", $file);
        
        $this->config['global']['huarpe_url'] = "$url_siu/huarpe";
        $this->config['global']['huarpe_url_bandeja'] = "$url_siu/huarpe/documentos";
        $this->config['global']['url_sq_pagos'] = "$url_siu/sq_pagos";

        file_put_contents("$this->pathInstalacion/config.php", "<?php\nreturn ".var_export($this->config, true).";");


        $this->saveParameters($parametros);
    }

    /**
     * Se ejecuta luego de hacer un add.
     */
    public function postAdd()
    {
    }

    /**
     * Se ejecuta luego de hacer un sync.
     */
    public function postSync()
    {
    }

    private function readParameters()
    {
        // ultra mega hack, mientras usemos arai-registry
        $parametersFile = $this->pathInstalacion.'/registry.yml';
        $parametros = Yaml::parse(file_get_contents($parametersFile));

        return $parametros['parameters'];
    }

    private function saveParameters($data)
    {
        $parameters = ['parameters' => $data];

        $parametersFile = $this->pathInstalacion.'/registry.yml';
        if (false === file_put_contents($parametersFile, Yaml::dump($parameters))) {
            throw new \Exception("No se pudo escribir el archivo $parametersFile. ¿Problemas de permisos?");
        }

    }

    private function getAuthFor3w($parametros)
    {
        $type = $parametros['api_client_auth'];
        $user = $parametros['api_client_user'];
        $password = $parametros['api_client_password'];
        
        $localCredentials = [
            'auth' => [$user, $password, $type],
        ];
        
        return $localCredentials;
    }

    /**
     * Encripta la clave del Cliente Huarpe y arma el array para enviar a Registry
     *
     * @param string $theirPublic
     * @return array los datos de autenticación con la password encriptada
     *
     * @throws \Exception cuando la clave de sincronización no existe
     */
    private function getAuthForRegistry($parametros, $theirPublic)
    {
        $type = $parametros['api_client_auth'];
        $user = $parametros['api_client_user'];
        $password = $parametros['api_client_password'];
        
        $keyPath = $parametros['api_client_arai_sync_key'];
        
        if (empty($keyPath) || !is_readable($keyPath)) {
            throw new \Exception("La clave de sincronización de Araí no es correcta");
        }
        
        $ourPrivate = AraiCli::getCryptoService()->getPrivateFromSyncKey($keyPath);
        $ourPublic = AraiCli::getCryptoService()->getPublicFromSyncKey($keyPath);
        
        $encrypted = AraiCli::getCryptoService()->encrypt($password, $ourPrivate, $theirPublic);
        
        return [
            'type' => $type,
            'credentials' =>[
                'user' => $user,
                'password' => $encrypted,
                'cert' => $ourPublic,
                ]
            ];
    }

    private function getProyectoVersion()
    {
        $parametros = $this->readParameters();
        $version = $parametros['version'];
        
        $definition = trim(preg_replace('/\s\s+/', ' ', $version));
        
        //Si es una version y comienza con v, se saca
        if ($definition[0] == 'v') {
            $definition = substr($definition, 1, strlen($definition)-1);
        }
        
        if (strpos($definition, '-dev')) {
            $definition = substr($definition, 0, strpos($definition, '-'));
        }
        
        return $definition ?? 'develop';
    }

    public static function getAppUniqueId()
	{
		 try {
			$registry = AraiCli::getRegistryService();
			$providers = $registry->getPackage()->getProvideList();
			if (empty($providers)) return null;

			$enc = false;
			$i = 0;
			$cantidad = count($providers);
			While ($i < $cantidad && !$enc) {
				if ($providers[$i]->getType() == 'app') {
					$appName = $providers[$i]->getName();
					$enc = true;
				}
				$i++;
			}
			if ($enc) {
				return $registry->generateAppUniqueId($appName);
			} else {
				return null;
			}
		} catch (\Exception $e) {
			return null;
		}
	}
}
