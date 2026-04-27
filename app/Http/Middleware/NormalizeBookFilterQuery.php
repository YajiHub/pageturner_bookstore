<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeBookFilterQuery
{
    /**
     * @var array<int, string>
     */
    private array $allowedSorts = [
        'price_asc',
        'price_desc',
        'rating',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $query = $request->query();

        if (isset($query['search']) && is_string($query['search'])) {
            $query['search'] = mb_substr(trim(preg_replace('/\s+/', ' ', $query['search']) ?? $query['search']), 0, 120);
            if ($query['search'] === '') {
                unset($query['search']);
            }
        }

        if (isset($query['sort']) && ! in_array($query['sort'], $this->allowedSorts, true)) {
            unset($query['sort']);
        }

        foreach (['min_price', 'max_price'] as $priceKey) {
            if (! array_key_exists($priceKey, $query)) {
                continue;
            }

            if ($query[$priceKey] === '' || $query[$priceKey] === null) {
                unset($query[$priceKey]);
                continue;
            }

            if (! is_numeric($query[$priceKey])) {
                unset($query[$priceKey]);
                continue;
            }

            $query[$priceKey] = max(0, (float) $query[$priceKey]);
        }

        if (isset($query['min_price'], $query['max_price']) && $query['min_price'] > $query['max_price']) {
            [$query['min_price'], $query['max_price']] = [$query['max_price'], $query['min_price']];
        }

        if (isset($query['page'])) {
            $page = filter_var($query['page'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($page === false) {
                unset($query['page']);
            } else {
                $query['page'] = $page;
            }
        }

        $request->query->replace($query);

        return $next($request);
    }
}
