<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (is_array($product->images) && count($product->images) > 0) {
                $product->image = $product->images[0];
            }
        });
    }

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'price',
        'image',
        'images',
        'is_active',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:3',
            'is_active' => 'boolean',
            'stock' => 'integer',
            'images' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAvailable(int $quantity = 1): bool
    {
        return $this->is_active && $this->stock >= $quantity;
    }

    public function getLocalizedName(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }

        return $this->name;
    }

    public function getLocalizedDescription(): ?string
    {
        if (app()->getLocale() === 'ar' && $this->description_ar) {
            return $this->description_ar;
        }

        return $this->description;
    }

    /**
     * @return list<string>
     */
    public function getGalleryImages(): array
    {
        if (is_array($this->images) && count($this->images) > 0) {
            return $this->images;
        }

        if ($this->image) {
            return [$this->image];
        }

        return [];
    }

    public function getCoverImage(): ?string
    {
        $gallery = $this->getGalleryImages();

        return $gallery[0] ?? null;
    }
}
