#!/usr/bin/env bash
set -euo pipefail

BASE_URL="http://127.0.0.1:8000"

# Ensure server is running
if ! pgrep -f "php -S 127.0.0.1:8000 -t public" >/dev/null 2>&1; then
  (php -S 127.0.0.1:8000 -t public >/dev/null 2>&1 &)
  sleep 1
fi

# Generate or reuse Sanctum token for local demo (bypass Google OAuth only for testing)
TOKEN=$(php -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $kernel=$app->make(Illuminate\\Contracts\\Console\\Kernel::class); $kernel->bootstrap(); $u=App\\Models\\User::firstOrCreate(["email"=>"buyer@test.com"],["name"=>"Test Buyer"]); if(!$u->profile){App\\Models\\Profile::firstOrCreate(["user_id"=>$u->id],["firstName"=>"Test","lastName"=>"Buyer"]);} echo $u->createToken("cli")->plainTextToken;' )

if [ -z "$TOKEN" ]; then
  echo "No se pudo generar token" >&2
  exit 1
fi

echo "Ping:" && curl -s -o /dev/null -w "%{http_code}\n" "$BASE_URL/api/ping"

echo "Listar productos (buyer):" && curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/api/buyer/products" -o /tmp/prods.json -w "%{http_code}\n"
PROD=$(php -r '$r=json_decode(file_get_contents("/tmp/prods.json"),true); if(isset($r["data"][0]["id"])) echo $r["data"][0]["id"]; else if(isset($r[0]["id"])) echo $r[0]["id"];')
if [ -z "$PROD" ]; then echo "No hay productos" >&2; exit 1; fi
echo "Producto seleccionado: $PROD"

echo "Agregar al carrito:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"product_id":'"$PROD"',"quantity":2}' -o /tmp/cart.json -w "%{http_code}\n" \
  "$BASE_URL/api/buyer/cart/add"

echo "Checkout:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"commerce_id":1,"products":[{"product_id":'"$PROD"',"quantity":2,"unit_price":10}],"delivery_type":"pickup","total":20}' \
  -o /tmp/checkout.json -w "%{http_code}\n" \
  "$BASE_URL/api/checkout"
ORDER=$(php -r '$r=json_decode(file_get_contents("/tmp/checkout.json"),true); echo $r["order"]["id"]??$r["id"]??"";')
echo "Order: ${ORDER:-"(no-id)"}"

echo "Pago comprobante:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"order_id":'"${ORDER:-0}"',"method":"zelle","reference":"Z'$(date +%s)'","amount":20}' \
  -o /tmp/pay.json -w "%{http_code}\n" \
  "$BASE_URL/api/payments/comprobante"

echo "Demo flow completo"

