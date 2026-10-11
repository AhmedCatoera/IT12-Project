<?php

namespace App\Providers;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');
        Paginator::defaultSimpleView('partials.pagination');

        // ->paginateOrLast(15) pages a list like ->paginate(15), but a page number past the
        // end shows the last page instead of an empty list. Lists such as "Due today" shrink
        // as orders are handled, so page 2 can run out while someone is still on it.
        $paginateOrLast = function (int $perPage, string $pageName = 'page') {
            $page = Paginator::resolveCurrentPage($pageName);
            $result = (clone $this)->paginate($perPage, ['*'], $pageName, $page);

            if ($page > 1 && $result->isEmpty() && $result->total() > 0) {
                $result = (clone $this)->paginate($perPage, ['*'], $pageName, $result->lastPage());
            }

            return $result;
        };
        EloquentBuilder::macro('paginateOrLast', $paginateOrLast);
        QueryBuilder::macro('paginateOrLast', $paginateOrLast);

        // Low-stock count shown next to "Inventory" in the sidebar.
        View::composer('layouts.app', function ($view) {
            $view->with('navLowStock', auth()->check() ? Ingredient::active()->lowStock()->count() : 0);
        });

        // @owner ... @endowner shows a block only to the Owner.
        Blade::if('owner', fn () => auth()->check() && auth()->user()->isOwner());

        // @peso($amount) prints ₱1,234.50
        Blade::directive('peso', fn ($expression) => "<?php echo '₱' . number_format((float) ($expression), 2); ?>");

        // @qty($value) prints 1,000 or 2.5 (no trailing zeros)
        Blade::directive('qty', fn ($expression) => "<?php echo rtrim(rtrim(number_format((float) ($expression), 2), '0'), '.'); ?>");
    }
}
