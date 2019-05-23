<?php

namespace Drupal\mbta_routes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class RoutesController extends ControllerBase {

  public function index() {

  	// Get routes from api.
  	$routes = \Drupal::service('mbta_routes.mbta_routes')->getRoutes();

    if (empty($routes['data'])) {
      return [
        '#markup' => t('Sorry, we are having trouble getting the routes.'),
      ];
    }

    // Loop through routes results and group by route type.
    $routes_sorted = [];
    foreach ($routes['data'] as $route) {
      $routes_sorted[$route['attributes']['fare_class']][$route['attributes']['sort_order']] = [
      	'id' => $route['id'],
      	'long_name' => $route['attributes']['long_name'],
      	'color' => $route['attributes']['color'],
      	'text_color' => $route['attributes']['text_color'],
      	'sort_order' => $route['attributes']['sort_order'],
      ];
    }
    // Make sure each type is sorted by the sort order.
    foreach ($routes_sorted as &$routes_type) {
      ksort($routes_type);
    }

    // Render output.
    $output = [];
    foreach ($routes_sorted as $route_type => $routes) {
      $route_items = [];
      foreach ($routes as $route) {
        $url = Url::fromRoute('mbta_routes.schedules', ['route_id' => $route['id']]);
        $link_options = array(
          'attributes' => array(
            'style' => 'display: inline-block; padding: 2px 5px; width: 360px; color: #' . $route['text_color'] . '; background-color: #' . $route['color'] . ';',
          ),
        );
        $url->setOptions($link_options);
  		  $link = Link::fromTextAndUrl($route['long_name'], $url);
      	$route_items[] = $link;
      }
      $output[] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $route_type,
      ];
      $output[] = [
  	    '#theme' => 'item_list',
  	    '#list_type' => 'ul',
  	    '#items' => $route_items,
  	    '#wrapper_attributes' => ['class' => 'container'],
  	  ];
    }
    return $output;
  }

}
