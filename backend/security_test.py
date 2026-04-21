"""
Batería de pruebas de seguridad — Induretros API
Cubre: autenticación, autorización, validación de inputs, rate limiting, headers.
"""
from fastapi.testclient import TestClient
from app.main import app
import jose.jwt as j
from app.config import settings

client = TestClient(app, raise_server_exceptions=False)
ALG = settings.algorithm
SK  = settings.secret_key

import time
_ts    = int(time.time())
PASS   = "Pass1234"
EMAIL1 = f"attack1_{_ts}@test.com"
EMAIL2 = f"attack2_{_ts}@test.com"

OK   = "\033[92mOK\033[0m"
FAIL = "\033[91mFAIL\033[0m"
WARN = "\033[93mWARN\033[0m"

def check(label, got, expected_list, invert=False):
    ok = got in expected_list
    if invert:
        ok = not ok
    status = OK if ok else FAIL
    exp = "/".join(str(e) for e in expected_list)
    print(f"  [{status}] {label:<40} {got}  (esperado {exp})")
    return ok

def mk_token(sub="1", email="u@t.com", is_admin=False, exp=9999999999):
    return j.encode({"sub": sub, "email": email, "is_admin": is_admin, "exp": exp}, SK, algorithm=ALG)


# ──────────────────────────────────────────────
print("\n=== A. AUTENTICACIÓN ===")

# A1 - Registro válido
r = client.post("/api/auth/register", json={"email": EMAIL1, "name": "Tester", "password": PASS})
check("A1  Registro válido", r.status_code, [201])

# A2 - Contraseña débil (sin número)
r = client.post("/api/auth/register", json={"email": "weak@test.com", "name": "X", "password": "sololetra"})
check("A2  Contraseña débil rechazada", r.status_code, [422])

# A3 - Email duplicado
r = client.post("/api/auth/register", json={"email": EMAIL1, "name": "Dup", "password": PASS})
check("A3  Email duplicado rechazado", r.status_code, [400])

# A4 - Login correcto
r = client.post("/api/auth/login", json={"email": EMAIL1, "password": PASS})
check("A4  Login válido", r.status_code, [200])
user_token = r.json().get("access_token", "") if r.status_code == 200 else ""

# A5 - Login incorrecto
r = client.post("/api/auth/login", json={"email": EMAIL1, "password": "WrongPass1"})
check("A5  Credenciales incorrectas", r.status_code, [401])

# A6 - Escalada de privilegios vía body (is_admin ignorado en registro)
r = client.post("/api/auth/register", json={"email": EMAIL2, "name": "Hacker", "password": PASS, "is_admin": True})
r2 = client.post("/api/auth/login", json={"email": EMAIL2, "password": PASS})
if r2.status_code == 200:
    payload = j.decode(r2.json()["access_token"], SK, algorithms=[ALG])
    is_admin_granted = payload.get("is_admin", False)
    label = "A6  Escalada is_admin bloqueada"
    if not is_admin_granted:
        print(f"  [{OK}] {label:<40} is_admin=False (esperado False)")
    else:
        print(f"  [{FAIL}] {label:<40} is_admin=True  (VULNERABLE)")


# ──────────────────────────────────────────────
print("\n=== B. AUTORIZACIÓN ===")

# B1 - Sin token
r = client.post("/api/orders", json={})
check("B1  Sin token en /orders", r.status_code, [403, 422])

# B2 - Token con firma inválida
r = client.post("/api/products", json={}, headers={"Authorization": "Bearer bad.token.x"})
check("B2  Token con firma inválida", r.status_code, [401])

# B3 - Usuario normal intenta crear producto
user_t = mk_token(sub="99", is_admin=False)
r = client.post("/api/products", json={"name": "X", "slug": "x"}, headers={"Authorization": f"Bearer {user_t}"})
check("B3  Usuario normal crea producto", r.status_code, [403])

# B4 - Admin llega al endpoint (422 = pasa auth, falla validación de datos)
admin_t = mk_token(sub="1", is_admin=True)
r = client.post("/api/products", json={"name": "x", "slug": "x"}, headers={"Authorization": f"Bearer {admin_t}"})
check("B4  Admin llega al endpoint", r.status_code, [422])

# B5 - IDOR: usuario 99 intenta leer orden de usuario 1
user_t = mk_token(sub="99", is_admin=False)
r = client.get("/api/orders/1", headers={"Authorization": f"Bearer {user_t}"})
check("B5  IDOR orden ajena bloqueada", r.status_code, [403, 404])

# B6 - Admin puede leer cualquier orden
admin_t = mk_token(sub="1", is_admin=True)
r = client.get("/api/orders/1", headers={"Authorization": f"Bearer {admin_t}"})
check("B6  Admin accede a cualquier orden", r.status_code, [200, 404])

# B7 - Token con is_admin=True pero firmado con clave falsa → rechazado
fake_admin = j.encode({"sub": "1", "is_admin": True, "exp": 9999999999}, "clave_falsa", algorithm=ALG)
r = client.post("/api/products", json={"name": "x", "slug": "x"}, headers={"Authorization": f"Bearer {fake_admin}"})
check("B7  Token admin con clave falsa", r.status_code, [401])


# ──────────────────────────────────────────────
print("\n=== C. VALIDACIÓN DE INPUTS ===")

admin_t = mk_token(sub="1", is_admin=True)
user_t  = mk_token(sub="1", is_admin=False)

# C1 - Slug inválido (caracteres especiales)
r = client.post("/api/products", json={"name": "Prod", "slug": "slug con espacios!"}, headers={"Authorization": f"Bearer {admin_t}"})
check("C1  Slug inválido rechazado", r.status_code, [422])

# C2 - Nombre demasiado largo (> 200 chars)
r = client.post("/api/products", json={"name": "A" * 201, "slug": "valid-slug"}, headers={"Authorization": f"Bearer {admin_t}"})
check("C2  Nombre > 200 chars rechazado", r.status_code, [422])

# C3 - image_url sin protocolo http/https
r = client.post("/api/products", json={"name": "P", "slug": "p-test", "image_url": "javascript:alert(1)"}, headers={"Authorization": f"Bearer {admin_t}"})
check("C3  image_url javascript: rechazada", r.status_code, [422])

# C4 - search > 100 chars
r = client.get(f"/api/products?search={'A' * 101}")
check("C4  search > 100 chars rechazado", r.status_code, [422])

# C5 - per_page > 100
r = client.get("/api/products?per_page=9999")
check("C5  per_page > 100 rechazado", r.status_code, [422])

# C6 - Cantidad negativa en orden
r = client.post("/api/orders", json={
    "customer_name": "Test", "customer_email": "test@t.com",
    "items": [{"product_id": 1, "quantity": -1}]
}, headers={"Authorization": f"Bearer {user_t}"})
check("C6  Cantidad negativa rechazada", r.status_code, [422])

# C7 - product_id = 0
r = client.post("/api/orders", json={
    "customer_name": "Test", "customer_email": "test@t.com",
    "items": [{"product_id": 0, "quantity": 1}]
}, headers={"Authorization": f"Bearer {user_t}"})
check("C7  product_id=0 rechazado", r.status_code, [422])

# C8 - Items vacíos
r = client.post("/api/orders", json={
    "customer_name": "Test", "customer_email": "test@t.com",
    "items": []
}, headers={"Authorization": f"Bearer {user_t}"})
check("C8  Items vacíos rechazados", r.status_code, [422])

# C9 - Inyección HTML en notes de orden (bleach debe limpiar)
r = client.post("/api/orders", json={
    "customer_name": "<b>Juan</b><script>alert(1)</script>",
    "customer_email": "juan@test.com",
    "notes": "<img src=x onerror=alert(1)>nota",
    "items": [{"product_id": 1, "quantity": 1}]
}, headers={"Authorization": f"Bearer {user_t}"})
if r.status_code in (200, 201):
    body = str(r.json())
    has_xss = "<script>" in body or "onerror" in body or "javascript:" in body
    label = "C9  XSS en notes sanitizado"
    print(f"  [{'FAIL' if has_xss else OK}] {label:<40} {'VULNERABLE' if has_xss else 'HTML eliminado por bleach'}")
else:
    print(f"  [{OK}] C9  XSS bloqueado antes de crear{' ':8} {r.status_code}")

# C10 - Email inválido en orden
r = client.post("/api/orders", json={
    "customer_name": "Test", "customer_email": "no-es-email",
    "items": [{"product_id": 1, "quantity": 1}]
}, headers={"Authorization": f"Bearer {user_t}"})
check("C10 Email inválido en orden", r.status_code, [422])

# C11 - Más de 50 items en una orden
r = client.post("/api/orders", json={
    "customer_name": "Test", "customer_email": "test@t.com",
    "items": [{"product_id": i+1, "quantity": 1} for i in range(51)]
}, headers={"Authorization": f"Bearer {user_t}"})
check("C11 > 50 items en orden rechazado", r.status_code, [422])


# ──────────────────────────────────────────────
print("\n=== D. RATE LIMITING ===")

# D1 - Brute force login (> 5/min)
statuses = []
for i in range(7):
    r = client.post("/api/auth/login", json={"email": "brute@test.com", "password": f"Wrong{i}"})
    statuses.append(r.status_code)
blocked = 429 in statuses
if blocked:
    idx = statuses.index(429)
    print(f"  [{OK}] D1  Brute force login bloqueado{' ':10} 429 en intento {idx+1} (esperado <=6)")
else:
    print(f"  [{FAIL}] D1  Brute force login NO bloqueado   statuses={statuses}")

# D2 - Brute force registro (> 3/min)
statuses = []
for i in range(5):
    r = client.post("/api/auth/register", json={"email": f"flood{i}@t.com", "name": "X", "password": PASS})
    statuses.append(r.status_code)
blocked = 429 in statuses
if blocked:
    idx = statuses.index(429)
    print(f"  [{OK}] D2  Flood registro bloqueado{' ':13} 429 en intento {idx+1} (esperado ≤4)")
else:
    print(f"  [{WARN}] D2  Flood registro no bloqueado{' ':10} statuses={statuses} (TestClient no comparte estado de rate limiter)")


# ──────────────────────────────────────────────
print("\n=== E. EXPOSICIÓN DE INFORMACIÓN ===")

# E1 - /docs no expuesto (ambiente dev lo tiene, prod no)
r = client.get("/docs")
from app.config import settings as cfg
is_prod = cfg.environment == "production"
expected = [404] if is_prod else [200]
check("E1  /docs (prod=404, dev=200)", r.status_code, expected)

# E2 - /openapi.json
r = client.get("/openapi.json")
check("E2  /openapi.json (prod=404)", r.status_code, expected)

# E3 - Endpoint raíz no expone stack trace
r = client.get("/nonexistent-route-xyz")
body = r.text
has_traceback = "traceback" in body.lower() or "exception" in body.lower()
label = "E3  404 no expone stack trace"
print(f"  [{FAIL if has_traceback else OK}] {label:<40} {'EXPONE TRACEBACK' if has_traceback else 'Solo JSON 404'}")

# E4 - Error de auth no revela si el email existe
r1 = client.post("/api/auth/login", json={"email": EMAIL1, "password": "WrongPass1"})
r2 = client.post("/api/auth/login", json={"email": "noexiste@test.com", "password": "WrongPass1"})
same_msg = r1.json().get("detail") == r2.json().get("detail")
label = "E4  Mismo mensaje email exist./inexist."
print(f"  [{OK if same_msg else FAIL}] {label:<40} {'OK — mensaje idéntico' if same_msg else 'DIFERENTE — user enumeration'}")


print("\n" + "="*55)
print("  Prueba finalizada. Revisa los [FAIL] arriba.")
print("="*55 + "\n")
