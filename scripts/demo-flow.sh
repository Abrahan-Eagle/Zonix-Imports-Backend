#!/usr/bin/env bash
set -euo pipefail

BASE_URL="http://127.0.0.1:8000"

# Ensure server is running
if ! pgrep -f "php -S 127.0.0.1:8000 -t public" >/dev/null 2>&1; then
  (php -S 127.0.0.1:8000 -t public >/dev/null 2>&1 &)
  sleep 1
fi

# Generate or reuse Sanctum token for local demo (bypass Google OAuth only for testing)
TOKEN=$(php scripts/gen-token.php)

if [ -z "$TOKEN" ]; then
  echo "No se pudo generar token" >&2
  exit 1
fi

echo "Ping:" && curl -s -o /dev/null -w "%{http_code}\n" "$BASE_URL/api/ping"

echo "Asegurar producto del commerce del seller:" && PROD=$(php scripts/ensure-seller-product.php) && echo "Producto: $PROD"

echo "Agregar al carrito:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"product_id":'"$PROD"',"quantity":2}' -o /tmp/cart.json -w "%{http_code}\n" \
  "$BASE_URL/api/buyer/cart/add"

CID=$(php scripts/get-commerce-id.php)
echo "Checkout:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"commerce_id":'"$CID"',"products":[{"product_id":'"$PROD"',"quantity":2,"unit_price":10}],"delivery_type":"pickup","total":20}' \
  -o /tmp/checkout.json -w "%{http_code}\n" \
  "$BASE_URL/api/checkout"
ORDER=$(php -r '$r=json_decode(file_get_contents("/tmp/checkout.json"),true); echo $r["order"]["id"]??$r["id"]??"";')
echo "Order: ${ORDER:-"(no-id)"}"

echo "Pago comprobante:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"order_id":'"${ORDER:-0}"',"method":"zelle","reference":"Z'$(date +%s)'","amount":20}' \
  -o /tmp/pay.json -w "%{http_code}\n" \
  "$BASE_URL/api/payments/comprobante"

echo "Demo flow completo"

