<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Commerce;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Referral;
use App\Models\InventoryMovement;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test product creation with commerce relationship.
     */
    public function test_product_can_be_created_with_commerce(): void
    {
        $commerce = Commerce::factory()->create();
        $product = Product::factory()->create(['commerce_id' => $commerce->id]);

        $this->assertDatabaseHas('products', [
            'commerce_id' => $commerce->id,
            'id' => $product->id,
        ]);

        $this->assertInstanceOf(Commerce::class, $product->commerce);
        $this->assertEquals($commerce->id, $product->commerce->id);
    }

    /**
     * Test product modalities.
     */
    public function test_product_modalities(): void
    {
        $modalities = ['retail', 'wholesale', 'preorder', 'referral', 'dropshipping'];
        
        foreach ($modalities as $modality) {
            $product = Product::factory()->create(['modality' => $modality]);
            $this->assertEquals($modality, $product->modality);
        }
    }

    /**
     * Test product category relationship.
     */
    public function test_product_has_category_relationship(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    /**
     * Test product images relationship.
     */
    public function test_product_has_images_relationship(): void
    {
        $product = Product::factory()->create();
        $images = ProductImage::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertCount(3, $product->images);
        $this->assertTrue($product->images->contains($images->first()));
    }

    /**
     * Test product order items relationship.
     */
    public function test_product_has_order_items_relationship(): void
    {
        $product = Product::factory()->create();
        $orderItems = OrderItem::factory()->count(2)->create(['product_id' => $product->id]);

        $this->assertCount(2, $product->orderItems);
        $this->assertTrue($product->orderItems->contains($orderItems->first()));
    }

    /**
     * Test product cart items relationship.
     */
    public function test_product_has_cart_items_relationship(): void
    {
        $product = Product::factory()->create();
        $cartItems = CartItem::factory()->count(2)->create(['product_id' => $product->id]);

        $this->assertCount(2, $product->cartItems);
        $this->assertTrue($product->cartItems->contains($cartItems->first()));
    }

    /**
     * Test product referrals relationship.
     */
    public function test_product_has_referrals_relationship(): void
    {
        $product = Product::factory()->create();
        $referrals = Referral::factory()->count(2)->create(['product_id' => $product->id]);

        $this->assertCount(2, $product->referrals);
        $this->assertTrue($product->referrals->contains($referrals->first()));
    }

    /**
     * Test product inventory movements relationship.
     */
    public function test_product_has_inventory_movements_relationship(): void
    {
        $product = Product::factory()->create();
        $movements = InventoryMovement::factory()->count(3)->create(['product_id' => $product->id]);

        $this->assertCount(3, $product->inventoryMovements);
        $this->assertTrue($product->inventoryMovements->contains($movements->first()));
    }

    /**
     * Test product dropshipping relationships.
     */
    public function test_product_dropshipping_relationships(): void
    {
        $originProduct = Product::factory()->create();
        $dropshippingProduct = Product::factory()->create([
            'origin_product_id' => $originProduct->id,
            'modality' => 'dropshipping'
        ]);

        $this->assertInstanceOf(Product::class, $dropshippingProduct->originProduct);
        $this->assertEquals($originProduct->id, $dropshippingProduct->originProduct->id);

        $this->assertTrue($originProduct->dropshippingProducts->contains($dropshippingProduct));
    }

    /**
     * Test product pricing for wholesale modality.
     */
    public function test_product_wholesale_pricing(): void
    {
        $product = Product::factory()->create([
            'modality' => 'wholesale',
            'base_price' => 100.00,
            'wholesale_price' => 80.00,
            'min_wholesale_quantity' => 10,
        ]);

        $this->assertEquals(100.00, $product->base_price);
        $this->assertEquals(80.00, $product->wholesale_price);
        $this->assertEquals(10, $product->min_wholesale_quantity);
    }

    /**
     * Test product preorder attributes.
     */
    public function test_product_preorder_attributes(): void
    {
        $eta = now()->addWeeks(2);
        $product = Product::factory()->create([
            'modality' => 'preorder',
            'preorder_eta' => $eta,
        ]);

        $this->assertEquals('preorder', $product->modality);
        $this->assertEquals($eta->format('Y-m-d'), $product->preorder_eta->format('Y-m-d'));
    }

    /**
     * Test product dimensions casting.
     */
    public function test_product_dimensions_casting(): void
    {
        $dimensions = [
            'length' => 10.5,
            'width' => 5.2,
            'height' => 3.1,
        ];

        $product = Product::factory()->create(['dimensions' => $dimensions]);

        $this->assertIsArray($product->dimensions);
        $this->assertEquals($dimensions, $product->dimensions);
    }

    /**
     * Test product availability.
     */
    public function test_product_availability(): void
    {
        $availableProduct = Product::factory()->create(['available' => true]);
        $unavailableProduct = Product::factory()->create(['available' => false]);

        $this->assertTrue($availableProduct->available);
        $this->assertFalse($unavailableProduct->available);
    }

    /**
     * Test product fillable attributes.
     */
    public function test_product_fillable_attributes(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST123',
            'description' => 'Test description',
            'modality' => 'retail',
            'base_price' => 50.00,
            'stock' => 100,
            'weight' => 1.5,
        ]);

        $this->assertEquals('Test Product', $product->name);
        $this->assertEquals('TEST123', $product->sku);
        $this->assertEquals('Test description', $product->description);
        $this->assertEquals('retail', $product->modality);
        $this->assertEquals(50.00, $product->base_price);
        $this->assertEquals(100, $product->stock);
        $this->assertEquals(1.5, $product->weight);
    }
}
