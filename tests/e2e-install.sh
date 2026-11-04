#!/usr/bin/env bash
# End-to-end test of the web installer + deploy hook against a real MySQL.
# Used by .github/workflows/test.yml. Needs: vendor/ installed, MySQL reachable.
set -euo pipefail

BASE="http://127.0.0.1:8000"
DB_JSON='{"host":"127.0.0.1","port":"3306","database":"brownfoxit","username":"root","password":"root"}'
ADMIN_JSON='{"name":"Test Admin","email":"admin@example.com","password":"Secret@12345"}'
SITE_JSON="{\"name\":\"Brown Fox IT\",\"url\":\"$BASE\",\"image_source\":\"https://www.brownfoxit.com/wp-content/\"}"
PAYLOAD="{\"db\":$DB_JSON,\"site\":$SITE_JSON,\"admin\":$ADMIN_JSON}"

rm -f .env storage/installed.json

php artisan serve --host=127.0.0.1 --port=8000 --no-reload > /tmp/serve.log 2>&1 &
SERVER_PID=$!
trap 'kill $SERVER_PID 2>/dev/null || true; echo "--- server log"; tail -n 40 /tmp/serve.log; echo "--- laravel log"; tail -n 60 storage/logs/laravel.log 2>/dev/null || true' EXIT
for i in $(seq 1 30); do curl -s -o /dev/null "$BASE" && break; sleep 1; done

step() { # step <action> <json> -> prints response, fails on ok:false
  local out
  out=$(curl -sS -X POST "$BASE/?action=$1" -H 'Content-Type: application/json' -d "$2")
  echo "[$1] ${out:0:400}"
  grep -qF '"ok":true' <<< "$out" || { echo "::error::installer step $1 failed"; exit 1; }
}

echo "== 1. installer page is shown before install"
PAGE=$(curl -sS "$BASE/"); grep -qF "Installer" <<< "$PAGE" || { echo "::error::installer page not shown"; exit 1; }

echo "== 2. installer steps"
step requirements '{}'
step test-db "$DB_JSON"
step write-env "$PAYLOAD"
step migrate "$PAYLOAD"
step seed "$PAYLOAD"
step admin "$PAYLOAD"
IMG=$(curl -sS -X POST "$BASE/?action=images" -H 'Content-Type: application/json' -d '{"skip":0}')
echo "[images] ${IMG:0:400}"   # informational only (depends on the old site being reachable)
step finish '{}'
test -f storage/installed.json || { echo "::error::installed.json missing"; exit 1; }

echo "== 3. home page renders"
HTML=$(curl -sS -w '\n%{http_code}' "$BASE/")
CODE=${HTML##*$'\n'}
[ "$CODE" = "200" ] || { echo "::error::home page returned $CODE"; echo "$HTML" | head -c 3000; exit 1; }
for text in "Brown Fox IT" "Full Stack Digital Visualizer" "Elizabeth K." "Why Every Business Needs" "Project Planning" "hello@pabitra.net"; do
  grep -qF "$text" <<< "$HTML" || { echo "::error::home page is missing: $text"; exit 1; }
done
echo "home page OK (${#HTML} bytes)"

echo "== 4. admin user"
php artisan tinker --execute='$u = App\Models\User::where("email","admin@example.com")->first(); echo $u && $u->isAdmin() && Hash::check("Secret@12345", $u->password) ? "admin OK" : "ADMIN BAD";' | tee /tmp/admin.txt
grep -qF "admin OK" /tmp/admin.txt || { echo "::error::admin user not created correctly"; exit 1; }

echo "== 5. installer is locked after install"
PAGE=$(curl -sS "$BASE/"); if grep -qF "Brown Fox IT – Installer" <<< "$PAGE"; then echo "::error::installer still visible"; exit 1; fi

echo "== 6. deploy hook"
TOKEN=$(grep '^DEPLOY_TOKEN=' .env | cut -d= -f2)
BAD=$(curl -s -o /dev/null -w '%{http_code}' -X POST "$BASE/_deploy" -H "X-Deploy-Token: wrong")
[ "$BAD" = "404" ] || { echo "::error::deploy hook accepted a wrong token ($BAD)"; exit 1; }
OUT=$(curl -sS -X POST "$BASE/_deploy" -H "X-Deploy-Token: $TOKEN" -H 'Accept: application/json')
echo "$OUT" | head -c 600; echo
grep -qF '"ok":true' <<< "$OUT" || { echo "::error::deploy hook failed"; exit 1; }

echo "== 7. site still works with cached config/routes/views"
[ "$(curl -sS -o /dev/null -w '%{http_code}' "$BASE/")" = "200" ] || { echo "::error::home broken after optimize"; exit 1; }

echo "ALL CHECKS PASSED"
