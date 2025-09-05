<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class CartService
{
    /**
     * Agregar un producto al carrito.
     *
     * @param array $productData
     * @return array
     */
    public function addToCart(array $productData)
    {
        $cart = Session::get('cart', []);
        
        // Verificar si el producto ya existe en el carrito
        $existingIndex = $this->findProductIndex($cart, $productData['product_id']);
        
        if ($existingIndex !== false) {
            // Actualizar cantidad si ya existe
            $cart[$existingIndex]['quantity'] += $productData['quantity'];
        } else {
            // Agregar nuevo producto
        $cart[] = $productData;
        }
        
        Session::put('cart', $cart);
        return $cart;
    }

    /**
     * Obtener el contenido del carrito.
     *
     * @return array
     */
    public function getCart()
    {
        return Session::get('cart', []);
    }

    /**
     * Actualizar cantidad de un producto.
     *
     * @param int $productId
     * @param int $quantity
     * @return array
     */
    public function updateQuantity($productId, $quantity)
    {
        $cart = Session::get('cart', []);
        $index = $this->findProductIndex($cart, $productId);
        
        if ($index !== false) {
            $cart[$index]['quantity'] = $quantity;
            Session::put('cart', $cart);
        }
        
        return $cart;
    }

    /**
     * Remover un producto del carrito.
     *
     * @param int $productId
     * @return array
     */
    public function removeFromCart($productId)
    {
        $cart = Session::get('cart', []);
        $index = $this->findProductIndex($cart, $productId);
        
        if ($index !== false) {
            unset($cart[$index]);
            $cart = array_values($cart); // Reindexar array
            Session::put('cart', $cart);
        }
        
        return $cart;
    }

    /**
     * Agregar notas al carrito.
     *
     * @param string $notes
     * @return array
     */
    public function addNotes($notes)
    {
        $cart = Session::get('cart', []);
        $cart['notes'] = $notes;
        Session::put('cart', $cart);
        return $cart;
    }

    /**
     * Limpiar el carrito.
     *
     * @return array
     */
    public function clearCart()
    {
        Session::forget('cart');
        return [];
    }

    /**
     * Encontrar el índice de un producto en el carrito.
     *
     * @param array $cart
     * @param int $productId
     * @return int|false
     */
    private function findProductIndex($cart, $productId)
    {
        foreach ($cart as $index => $item) {
            if (isset($item['product_id']) && $item['product_id'] == $productId) {
                return $index;
            }
        }
        return false;
    }
}
