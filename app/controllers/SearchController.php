<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Services\SearchEngine;

/**
 * Reusable Global Search Engine Controller
 * Manages routing actions for autocomplete queries and central search index rendering.
 */
class SearchController extends Controller {
    private SearchEngine $searchEngine;

    public function __construct() {
        $this->searchEngine = new SearchEngine();
    }

    /**
     * Renders the Central Search Workspace Dashboard
     */
    public function index(): void {
        $user = Session::get('user');
        
        $query = $_GET['q'] ?? '';
        $target = $_GET['target'] ?? '';

        $filters = ['target' => $target];
        $results = [];
        
        if (!empty($query)) {
            $results = $this->searchEngine->search($query, $filters);
        }

        $history = $this->searchEngine->getHistory();

        $this->view('user/search', [
            'title'        => 'Global SIEM Search Console',
            'user'         => $user,
            'query'        => $query,
            'target'       => $target,
            'results'      => $results,
            'history'      => $history,
            'searchEngine' => $this->searchEngine // Expose engine for highlighting utility
        ]);
    }

    /**
     * Returns JSON Autocomplete live search list
     */
    public function liveSearch(): void {
        $query = $_GET['q'] ?? '';
        if (empty($query)) {
            $this->json(['status' => 'success', 'results' => []]);
        }

        // Search with target all
        $results = $this->searchEngine->search($query);

        // Map live autocomplete matches
        $autocomplete = [];
        
        foreach ($results['users'] as $u) {
            $autocomplete[] = [
                'label' => "Operator Profile: " . $u['username'],
                'url'   => APP_URL . '/admin/users?search=' . urlencode($u['username'])
            ];
        }

        foreach ($results['logs'] as $l) {
            $autocomplete[] = [
                'label' => "Audit Event: " . $l['action'] . " (" . $l['ip_address'] . ")",
                'url'   => APP_URL . '/admin/audit?search=' . urlencode($l['action'])
            ];
        }

        foreach ($results['messages'] as $m) {
            $autocomplete[] = [
                'label' => "Envelopes Carrier Target: " . $m['recipient'],
                'url'   => APP_URL . '/decrypt-payload'
            ];
        }

        // Return up to 10 latest autocomplete suggestions
        $this->json([
            'status' => 'success',
            'results' => array_slice($autocomplete, 0, 10)
        ]);
    }
}
