<?php
class guarani_facebook_nucleo
{
    /**
     * Publica un mensaje en el muro de la página institucional de Facebook.
     *
     * @param string $mensaje Texto a publicar.
     */
    static function publicar($mensaje)
    {
        if (!extension_loaded('curl')) {
            throw guarani::error('La extensión CURL de php no se encuentra cargada.');
        }

        $app_id = guarani::parametros()->get_valor('facebook_app_id');
        $app_secret = guarani::parametros()->get_valor('facebook_app_secret');
        $pagina_id = guarani::parametros()->get_valor('facebook_pagina_id');
        $access_token = guarani::parametros()->get_valor('facebook_access_token');

        if (empty($app_id) || empty($app_secret) || empty($pagina_id) || empty($access_token)) {
            throw guarani::error('No están definidos todos los parámetros de autenticación correspondientes.');
        }

        require_once __DIR__ . '/../../3ros/Facebook/autoload.php';

        $fb = new Facebook\Facebook([
            'app_id' => $app_id,
            'app_secret' => $app_secret,
            'default_graph_version' => 'v2.8',
        ]);

        $mensaje = strip_tags($mensaje);
        $mensaje = html_entity_decode($mensaje);
        $datos = ['message' => utf8_encode($mensaje)];

        try {
            $response = $fb->post("/$pagina_id/feed", $datos, $access_token);
        } catch (Facebook\Exceptions\FacebookResponseException $e) {
            throw guarani::error($e->getMessage());
        } catch (Facebook\Exceptions\FacebookSDKException $e) {
            throw guarani::error($e->getMessage());
        }
    }

}
?>