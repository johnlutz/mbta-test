<?php

namespace Drupal\mbta_routes;


/**
 * Interface for MBTA Routes API.
 */
interface MBTARoutesInterface {

  /**
   * Queries the MBTA REST API to get a list of all routes.
   *
   * @return array
   *   Routes from the API.
   */
  function getRoutes();

  /**
   * Queries the MBTA REST API to get a schedule for a particular route.
   *
   * @param string $route_id
   *   A route id.
   *
   * @return array
   *   Schedule from the API.
   */
  function getSchedule($route_id);

}
