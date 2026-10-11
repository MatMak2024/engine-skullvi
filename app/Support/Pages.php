<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Pages
{
    public const PerPage = 10;

    /**
     * @param  Collection<int, mixed>  $items
     */
    public static function of(Collection $items, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        return new LengthAwarePaginator(
            $items->forPage($page, self::PerPage)->values(),
            $items->count(),
            self::PerPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
                'query' => request()->query(),
            ],
        );
    }
}
