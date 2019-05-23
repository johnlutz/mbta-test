<?php

namespace Drupal\mbta_routes;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;


/**
 * Defines a class for working with the MBTA Routes API.
 */
class MBTARoutes implements MBTARoutesInterface {

  /**
   * The MBTA V3 API Url.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $api_url = 'https://api-v3.mbta.com/';

  /**
   * A guzzle http client instance.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * Constructs a new class instance.
   *
   * @param \GuzzleHttp\Client $http_client
   *   A guzzle http client instance.
   */
  public function __construct(Client $http_client) {
    $this->http_client = $http_client;
  }

  /**
   * Queries the MBTA REST API to get a list of all routes.
   *
   * @return array
   *   Routes from the API.
   */
  function getRoutes() {

    $results = [];

    try {
      if ($cache = \Drupal::cache()->get('mbta_all_routes')) {
        $results = $cache->data;
      }
      else {
        $url = $this->createUrl('routes');
        $results = $this->request($url);
        // Set results to cach for 1hr.
        \Drupal::cache()->set('mbta_all_routes', $results, (time() + (60 * 60)));
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('mbta_routes')->error($e->getMessage());
      return [];
    }

    return $results;

  }

  /**
   * Queries the MBTA REST API to get a schedule for a particular route.
   *
   * @param string $route_id
   *   A route id.
   *
   * @return array
   *   Schedule from the API.
   */
  function getSchedule($route_id) {

    $results = [];
    $params = [
      'include' => 'trip,stop,route',
      'filter[route]' => $route_id,
    ];

    try {
      if ($cache = \Drupal::cache()->get('mbta_schedule_' . $route_id)) {
        $results = $cache->data;
      }
      else {
        $url = $this->createUrl('schedules', $params);
        $results = $this->request($url);
        // Set results to cach for 1hr.
        \Drupal::cache()->set('mbta_schedule_' . $route_id, $results, (time() + (60 * 60)));
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('mbta_routes')->error($e->getMessage());
      return [];
    }

    return $results;
    
  }

  /**
   * Build api url.
   *
   * @param string $path
   *   Endpoint path to service.
   * @param null|array $params
   *   Extra query string parameters to pass along.
   *
   * @return string
   *   Full url to service.
   */
  protected function createUrl($path, $params = NULL) {

    $url = $this->api_url . "/{$path}";

    if (!empty($params) && is_array($params)) {
      $url .= "?" . http_build_query($params);
    }

    return $url;

  }

  /**
   * Send the request to the api.
   *
   * @param string $url
   *   Full url that we will send to.
   *
   * @return $this|false
   *   False if there was problem, the class object if ok.
   */
  protected function request($url) {

    try {
      $response  = $this->http_client->get($url);

      return Json::decode($response->getBody(), TRUE);

    } catch (RequestException $e) {

      \Drupal::logger('mbta_routes')->error('Error connecting to MBTA API. <br/>Request: <pre>@request</pre>', [
        '@request' => print_r($e->getRequest(), TRUE),
      ]);

      if ($e->hasResponse()) {
       \Drupal::logger('mbta_routes')->error('Error connecting to MBTA API. <br/>Response: <pre>@response</pre>', [
          '@response' => print_r($e->getResponse(), TRUE),
        ]);
      }

    }

    return NULL;
  }

}
