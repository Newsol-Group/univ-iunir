<?php
class guarani_twitter_nucleo
{
	static function publicar($mensaje)
	{
		$consumerKey = guarani::parametros()->get_valor('twitter_consumer_key');
		$consumerSecret = guarani::parametros()->get_valor('twitter_consumer_secret');
		$accessToken = guarani::parametros()->get_valor('twitter_access_token');
		$accessTokenSecret = guarani::parametros()->get_valor('twitter_access_token_secret');
		
		if (empty($consumerKey) || empty($consumerSecret) || empty($accessToken) || empty($accessTokenSecret)) {
			throw guarani::error('No están definidos todos los parámetros de autenticación correspondientes.');
		}

		$twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
		$status = $twitter->send(utf8_encode($mensaje));
		return $status;
	}
}
?>