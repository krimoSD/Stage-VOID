<?php

namespace Drupal\movie_directory\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns the movie listing page.
 *
 * This controller:
 * - Reads query parameters for page number and search term.
 * - Retrieves movie data from the movie API service.
 * - Prepares render arrays for the listing and pagination.
 * - Attaches the custom library and cache metadata.
 */
class MovieListing extends ControllerBase {

  /**
   * Builds the movie listing page.
   *
   * @return array
   *   A render array for the movie listing theme.
   */
  public function view() {
    // Read the current page from the URL query; default to page 1.
    $request = \Drupal::request();
    $current_page = (int) $request->query->get('page', 1);
    if ($current_page < 1) {
      $current_page = 1;
    }

    // Read and trim the search query (movie title).
    $search_query = trim((string) $request->query->get('q', ''));

    // Fetch raw movie data from the API service.
    $movie_data = $this->listMovie($current_page, $search_query);

    // Prepare content for the Twig template.
    $content = [];
    $content['movies'] = $this->createMovieCard($movie_data);
    $content['search_query'] = $search_query;

    // Compute pagination information from the API response.
    $total_pages = !empty($movie_data) && isset($movie_data->total_pages) ? (int) $movie_data->total_pages : 1;
    if ($total_pages < 1) {
      $total_pages = 1;
    }

    $content['pagination'] = [
      'current' => $current_page,
      'total_pages' => $total_pages,
      'has_previous' => $current_page > 1,
      'has_next' => $current_page < $total_pages,
    ];

    // Return render array using the custom "movie-listing" theme hook.
    return [
      '#theme' => 'movie-listing',
      '#content' => $content,
      '#attached' => [
        // Attach module CSS and front-end assets.
        'library' => [
          'movie_directory/movie-directory-styling',
        ],
      ],
      '#cache' => [
        // Ensure cached output varies by query string (page and q).
        'contexts' => ['url.query_args:page', 'url.query_args:q'],
        // Disable caching of this page (always show fresh results).
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Returns a list of movies for a page and search query.
   *
   * Uses the movie API connector service to either:
   * - Search for movies when a query is provided.
   * - Discover popular movies when no query is provided.
   *
   * @param int $page
   *   The current page number.
   * @param string $search_query
   *   The search string (movie title).
   *
   * @return array|object
   *   The decoded API response or an empty array on failure.
   */
  public function listMovie($page = 1, $search_query = '') {
    /** @var \Drupal\movie_directory\MovieAPIConnector $movie_api_connector_service */
    $movie_api_connector_service = \Drupal::service('movie_directory.api_connector');

    if (!empty($search_query)) {
      // When a search term is present, call the search endpoint.
      $movie_list = $movie_api_connector_service->searchMovies($search_query, $page);
    }
    else {
      // Otherwise, fall back to the discover endpoint.
      $movie_list = $movie_api_connector_service->discoverMovies($page);
    }

    return $movie_list ?: [];
  }

  /**
   * Builds render arrays for movie cards from API data.
   *
   * @param array|object $movie_data
   *   The decoded API response containing a "results" list.
   *
   * @return array
   *   An array of render arrays, one per movie, using the "movie-card" theme.
   */
  public function createMovieCard($movie_data = []) {
    /** @var \Drupal\movie_directory\MovieAPIConnector $movie_api_connector_service */
    $movie_api_connector_service = \Drupal::service('movie_directory.api_connector');

    $movieCards = [];

    // Normalize movies list from the API response.
    $movies = [];
    if (!empty($movie_data) && isset($movie_data->results)) {
      $movies = $movie_data->results;
    }

    // Create a card for each movie result.
    if (!empty($movies)) {
      foreach ($movies as $movie) {
        $content = [
          'title' => $movie->title,
          'description' => $movie->overview,
          'movie_id' => $movie->id,
          'image' => $movie_api_connector_service->getImageUrl($movie->poster_path),
        ];

        $movieCards[] = [
          '#theme' => 'movie-card',
          '#content' => $content,
        ];
      }
    }

    return $movieCards;
  }

}
