<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 */
class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'cover_image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted()
    {
        static::deleting(function (Category $cat) {
            if (! $cat->isForceDeleting()) {
                return;
            }

            if ($cat->cover_image) {
                // delete from public disk if exists
                try {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($cat->cover_image);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });
    }
}
