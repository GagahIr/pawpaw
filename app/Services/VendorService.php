<?php

namespace App\Services;

use App\Models\CategoryService;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VendorService
{
    public function getVendors(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $search = trim($filters['search'] ?? '');
        $categoryId = $filters['category_id'] ?? null;
        $verified = $filters['verified'] ?? false;
        $activeSubscription = $filters['active_subscription'] ?? false;

        // pecah per kata, abaikan spasi berlebih
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        return Vendor::query()
            ->with([
                'vendorServices' => fn ($q) => $q
                    ->where('status_service', true)
                    ->with('categoryService:id,name'),
            ])
            ->whereNotNull('join_at')
            ->when($terms, function (Builder $query) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%'.$this->escapeLike($term).'%';

                    $query->where(fn (Builder $q) => $q
                        ->where('name', 'like', $like)
                        ->orWhere('address', 'like', $like));
                }
            })
            ->when($categoryId, function (Builder $query, int $id) {
                $query->whereHas('vendorServices', fn ($q) => $q
                    ->where('status_service', true)
                    ->where('category_service_id', $id));
            })
            ->when($verified, fn (Builder $query) => $query->where('status_verification', 'approved'))
            ->when($activeSubscription, fn (Builder $query) => $query
                ->where('status_subscription', 'active')
                ->where('subscription_expired_at', '>', now()))
            ->whereHas('vendorServices', fn ($q) => $q->where('status_service', true))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function getCategories(): Collection
    {
        return CategoryService::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getVendorDetail(string $slug): ?Vendor
    {
        return Vendor::query()
            ->with([
                'vendorServices' => fn ($q) => $q
                    ->where('status_service', true)
                    ->with(['categoryService:id,name', 'vendorServiceItems']),
                'vendorOperatingHours' => fn ($q) => $q->orderBy('day_of_week'),
            ])
            ->where('slug', $slug)
            ->whereNotNull('join_at')
            ->where('status_verification', 'approved')
            ->where('status_subscription', 'active')
            ->where('subscription_expired_at', '>', now())
            ->first();
    }

    public function getActiveVendorCount(): int
    {
        return Vendor::whereNotNull('join_at')->count();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
