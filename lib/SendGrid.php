<?php

class SendGrid {
  const VERSION = "2.1.1";

  protected $namespace  = "SendGrid",
            $headers    = array('Content-Type' => 'application/json'),
            $options,
            $web;
  public    $api_user,
            $api_key,
            $url,
            $version = self::VERSION;

  /**
   * @param string $api_user SendGrid username
   * @param string $api_key SendGrid password
   * @param array $options Config for the client
   */
  public function __construct($api_user, $api_key, $options=array()) {
    $this->api_user = $api_user;
    $this->api_key = $api_key;

    if( !isset($options["turn_off_ssl_verification"]) ){
      $options["turn_off_ssl_verification"] = false;
    }

    $protocol = isset($options['protocol']) ? $options['protocol'] : 'https';
    $host = isset($options['host']) ? $options['host'] : 'api.sendgrid.com';
    $port = isset($options['port']) ? $options['port'] : '';
    $endpoint = isset($options['endpoint']) ? $options['endpoint'] : '/api/mail.send.json';

    $this->url = isset($options['url']) ? $options['url'] : $protocol . "://" . $host . ($port ? ":" . $port : "") . $endpoint;

    $this->options  = $options;
  }

  // Should this just be public?
  /**
   * @return array The protected options array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Makes a post request to SendGrid to send an email
   * @param SendGrid\Email $email Email object built
   * @throws SendGrid\Exception if the response code is not 200
   * @return stdClass Parsed json of response
   */
  public function send(SendGrid\Email $email) {
    $form             = $email->toWebFormat();
    $form['api_user'] = $this->api_user; 
    $form['api_key']  = $this->api_key; 

    // option to ignore verification of ssl certificate
    if (isset($this->options['turn_off_ssl_verification']) && $this->options['turn_off_ssl_verification'] == true) {
      \Unirest::verifyPeer(false);
    }

    $response = $this->makeRequest($form);

    if ($response->code != 200) {
      throw new SendGrid\Exception($response->raw_body);
    }

    return $response->body;
  }

  /**
   * Makes the actual HTTP request to SendGrid
   * @param $form Array web ready version of SendGrid\Email
   * @return Unirest\HttpResponse
   */
  public function makeRequest($form) {
    return \Unirest::post($this->url, array('User-Agent' => 'sendgrid/' . $this->version . ';php'), $form);
  }
  
  public static function register_autoloader() {
    spl_autoload_register(array('SendGrid', 'autoloader'));
  }

  public static function autoloader($class) {
    // Check that the class starts with "SendGrid"
    if ($class == 'SendGrid' || stripos($class, 'SendGrid\\') === 0) {
      $file = str_replace('\\', '/', $class);

      if (file_exists(dirname(__FILE__) . '/' . $file . '.php')) {
        require_once(dirname(__FILE__) . '/' . $file . '.php');
      }
    }
  }
}
