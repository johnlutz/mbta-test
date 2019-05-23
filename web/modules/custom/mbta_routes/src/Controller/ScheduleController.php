<?php

namespace Drupal\mbta_routes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class ScheduleController extends ControllerBase {

  public function index($route_id) {
    
    // Get routes from api.
  	$schedule = \Drupal::service('mbta_routes.mbta_routes')->getSchedule($route_id);

    if (empty($schedule['data'])) {
      return [
        '#markup' => t('Sorry, we are having trouble getting the schedule.'),
      ];
    }

    // Loop through routes results and group by route type.
    $schedule_sorted = [];
    $trips = [];
    $stops = [];
    $routes = [];
    foreach ($schedule['data'] as $item) {
      $schedule_sorted[$item['relationships']['trip']['data']['id']][$item['attributes']['stop_sequence']] = [
      	'id' => $item['id'],
      	'arrival' => $item['attributes']['arrival_time'],
      	'departure' => $item['attributes']['departure_time'],
      	'stop_id' => $item['relationships']['stop']['data']['id'],
      	'route_id' => $item['relationships']['route']['data']['id'],
      	'direction_id' => $item['attributes']['direction_id'],
      ];
    }
    // Make sure each type is sorted by the sort order.
    foreach ($schedule_sorted as &$schedule_trip) {
      ksort($schedule_trip);
    }
    // Loop through included data to get trip, stop, and route info.
    foreach ($schedule['included'] as $item) {
      switch ($item['type']) {
    	case 'trip':
          $trips[$item['id']] = $item['attributes']['headsign'];
          break;
    	case 'stop':
          $stops[$item['id']] = $item['attributes']['name'];
          break;
    	case 'route':
          $routes[$item['id']] = [
            'name' => $item['attributes']['long_name'],
            'direction_destinations' => $item['attributes']['direction_destinations'],
            'direction_names' => $item['attributes']['direction_names'],
            'color' => $item['attributes']['color'],
            'text_color' => $item['attributes']['text_color'],
          ];
          break;
      }
    }

    // Render output.
    $output[] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $routes[$route_id]['name'],
      '#attributes' => [
        'style' => [
          'background-color:#' . $routes[$route_id]['color'] . ';',
          'color:#' . $routes[$route_id]['text_color'] . ';',
          'padding: 2px 5px;',
        ],
      ],
    ];
    foreach ($schedule_sorted as $trip_id => $schedule_items) {
      $schedule_render_items = [];
      $first_arrival = '';
      foreach ($schedule_items as $schedule_item) {
      	if (empty($first_arrival)) {
      	  $first_arrival = $schedule_item['arrival'];
      	}
  		  $schedule_render_items[] = date('g:i a', strtotime($schedule_item['arrival'])) . ' - ' . $stops[$schedule_item['stop_id']];
      }
      // Render schedule items as list.
      $description = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $schedule_render_items,
        '#wrapper_attributes' => ['class' => 'container'],
      ];
      // Render trip as collapsible details element.
      $output[strtotime($first_arrival)] = [
        '#type' => 'details',
        '#title' => $routes[$schedule_item['route_id']]['name'] . ' - ' . $trips[$trip_id] . ' ' . $routes[$schedule_item['route_id']]['direction_names'][$schedule_item['direction_id']] . ' - Departs ' . date('g:i a', strtotime($first_arrival)) . ' (Dest: ' . $routes[$schedule_item['route_id']]['direction_destinations'][$schedule_item['direction_id']]. ')',
        '#description' => $description,
        '#open' => FALSE,
      ];
    }
    // Sort the trips by the departure date.
    ksort($output);
    return $output;
  }

}
