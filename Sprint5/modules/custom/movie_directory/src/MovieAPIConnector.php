<?php

namespace Drupal\movie_directory;

use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\RequestException;

/**
 * Service responsible for communicating with the external movie API.
 *
 * This connector:
 * - Reads base URL and API key from the module configuration (state).
 * - Builds a reusable Guzzle client with default query parameters.
 * - Exposes helper methods to discover and search movies.
 */
class MovieAPIConnector {

  /**
   * HTTP client configured for the movie API.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $client;

  /**
   * Default query parameters sent with every request (e.g. API key).
   *
   * @var array
   */
  private $query;

  /**
   * MovieAPIConnector constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $client
   *   The HTTP client factory from Drupal's service container.
   */
  public function __construct(ClientFactory $client) {
    // Load saved configuration (API base URL and key) from state.
    $movie_api_config = \Drupal::state()->get(\Drupal\movie_directory\Form\MovieAPI::MOVIE_API_CONFIG_PAGE);

    // Provide sensible defaults if configuration is missing.
    $api_url = ($movie_api_config["api_base_url"]) ?: 'https://api.themoviedb.org';
    $api_key = ($movie_api_config["api_key"]) ?: '';

    // Base query parameters that will be merged into each request.
    $query = ['api_key' => $api_key];
    $this->query = $query;

    // Build a Guzzle client with a base URI and default query.
    $this->client = $client->fromOptions(
      [
        'base_uri' => $api_url,
        'query' => $query,
      ]
    );
  }

  /**
   * Discover movies (e.g., popular or trending) for a given page.
   *
   * @param int $page
   *   The page number of the API results.
   *
   * @return array|object
   *   The decoded API response, or an empty array on error.
   */
  public function discoverMovies($page = 1) {
    $data = [];
    $endpoint = '/3/discover/movie';

    // Merge page number into the default query parameters.
    $options = [
      'query' => $this->query + [
        'page' => (int) $page,
      ],
    ];

    try {
      $request = $this->client->get($endpoint, $options);
      $result = $request->getBody()->getContents();
      $data = json_decode($result);
    }
    catch (RequestException $e) {
      // Log the error so site admins can troubleshoot issues.
      \Drupal::logger('movie_directory')->error($e->getMessage());
    }

    return $data;
  }

  /**
   * Search movies by title.
   *
   * @param string $query_string
   *   The search query.
   * @param int $page
   *   Page number for paginated results.
   *
   * @return array|object
   *   The decoded TMDB response.
   */
  public function searchMovies($query_string, $page = 1) {
    $data = [];
    $endpoint = '/3/search/movie';

    // Merge search query and page number into default query parameters.
    $options = [
      'query' => $this->query + [
        'query' => $query_string,
        'page' => (int) $page,
      ],
    ];

    try {
      $request = $this->client->get($endpoint, $options);
      $result = $request->getBody()->getContents();
      $data = json_decode($result);
    }
    catch (RequestException $e) {
      // Log the error instead of breaking the page.
      \Drupal::logger('movie_directory')->error($e->getMessage());
    }

    return $data;
  }

  /**
   * Builds a full image URL for a TMDB poster path.
   *
   * @param string $image_path
   *   The relative poster path returned by the API.
   *
   * @return string
   *   The absolute image URL for use in templates.
   */
  public function getImageUrl($image_path) {
    return 'https://image.tmdb.org/t/p/w500' . $image_path;
  }

}
