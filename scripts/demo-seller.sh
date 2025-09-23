#!/usr/bin/env bash
set -euo pipefail

BASE_URL="http://127.0.0.1:8000"

# Ensure server is running
if ! pgrep -f "php -S 127.0.0.1:8000 -t public" >/dev/null 2>&1; then
  (php -S 127.0.0.1:8000 -t public >/dev/null 2>&1 &)
  sleep 1
fi

# Generate seller token (set users.role = seller)
TOKEN=$(php scripts/gen-seller-token.php)

if [ -z "$TOKEN" ]; then
  echo "No se pudo generar token seller" >&2
  exit 1
fi

echo "Listar pedidos del comercio:" && curl -s -H "Authorization: Bearer $TOKEN" "$BASE_URL/api/commerce/orders" -o /tmp/sorders.json -w "%{http_code}\n"
OID=$(php -r '$r=json_decode(file_get_contents("/tmp/sorders.json"),true); if(isset($r["data"][0]["id"])) echo $r["data"][0]["id"]; else if(isset($r[0]["id"])) echo $r[0]["id"];')
echo "Pedido: ${OID:-"(ninguno)"}"

if [ -n "${OID:-}" ]; then
  echo "Actualizar estado a preparing:" && curl -s -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -X PUT -d '{"status":"preparing"}' -o /tmp/upd.json -w "%{http_code}\n" \
    "$BASE_URL/api/commerce/orders/$OID/status"
fi

echo "Demo seller completo"

