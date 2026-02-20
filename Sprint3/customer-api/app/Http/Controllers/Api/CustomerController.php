<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        //Filtering
        if ($request->has('filter')) {
            foreach ($request->filter as $field => $value) {
                if (in_array($field, ['first_name', 'email'])) {
                    $query->where($field, 'like', "%{$value}%");
                }
            }
        }

        //Sorting
        if ($request->has('sort')) {
            foreach ($request->sort as $field => $direction) {
                if (in_array($field, ['first_name', 'email'])) {
                    $query->orderBy($field, $direction === 'desc' ? 'desc' : 'asc');
                }
            }
        }

        //Pagination
        $pageNumber = $request->input('page.number', 1);
        $pageSize = $request->input('page.size', 15);

        $customers = $query->paginate(
            $pageSize,
            ['*'],
            'page',
            $pageNumber
        );

        //JSON API Style Response
        $response = [
            'data' => $customers->items(),
            'links' => [
                'first' => $customers->url(1),
                'last' => $customers->url($customers->lastPage()),
                'prev' => $customers->previousPageUrl(),
                'next' => $customers->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $customers->currentPage(),
                'from' => $customers->firstItem(),
                'last_page' => $customers->lastPage(),
                'path' => $customers->path(),
                'per_page' => $customers->perPage(),
                'to' => $customers->lastItem(),
                'total' => $customers->total(),
            ]
        ];

        return response()
            ->json($response)
            ->header('x-api-version', 'v1');
    }
}
