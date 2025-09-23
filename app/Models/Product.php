<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'commerce_id',
        'category_id',
        'name',
        'sku',
        'description',
        'modality',
        'base_price',
        'stock',
        'min_wholesale_quantity',
        'wholesale_price',
        'preorder_eta',
        'origin_product_id',
        'image',
        'video_url',
        'weight',
        'dimensions',
        'available'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'preorder_eta' => 'date',
        'available' => 'boolean'
    ];

    /**
     * Relación con el comercio
     */
    public function commerce()
    {
        return $this->belongsTo(Commerce::class);
    }

    /**
     * Relación con la categoría
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con las imágenes del producto
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Relación con los items de orden
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relación con las órdenes a través de order_items
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items');
    }

    /**
     * Relación con el carrito
     */
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Relación con referidos
     */
    public function referrals()
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * Relación con movimientos de inventario
     */
    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Relación con producto origen (dropshipping)
     */
    public function originProduct()
    {
        return $this->belongsTo(Product::class, 'origin_product_id');
    }

    /**
     * Relación con productos que usan este como origen
     */
    public function dropshippingProducts()
    {
        return $this->hasMany(Product::class, 'origin_product_id');
    }
}
