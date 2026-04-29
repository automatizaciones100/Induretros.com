# Induretros — E-commerce de Repuestos para Maquinaria Pesada

> **Rama activa:** `feature/new-web-fastapi-nextjs`
> **Stack:** FastAPI (Python 3.13) + Next.js 16 (TypeScript) + SQLite/PostgreSQL
> **Estado:** Fase 1 funcional · Pago vía WhatsApp · Panel admin operativo

E-commerce completo desde cero para [induretros.com](https://induretros.com), reemplazando el WordPress + WooCommerce original por un stack moderno con Clean Architecture, panel administrativo autónomo para marketing y cumplimiento OWASP/ISO 27001.

---

## Tabla de contenidos

- [Características](#características)
- [Stack técnico](#stack-técnico)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Cómo ejecutar localmente](#cómo-ejecutar-localmente)
- [Panel de administración](#panel-de-administración)
- [Carga de productos e imágenes](#carga-de-productos-e-imágenes)
- [Seguridad](#seguridad)
- [API REST](#api-rest)
- [Páginas del frontend](#páginas-del-frontend)
- [Roadmap](#roadmap)

---

## Características

### Para el cliente final
- Catálogo de productos con búsqueda, filtros por categoría y paginación
- Detalle de producto con SSG + ISR (regeneración cada hora)
- **Carrito persistente** (localStorage, sobrevive a recargas)
- Checkout con creación de pedido + apertura automática de WhatsApp con detalle pre-llenado
- Confirmación de pedido `/orden/[id]` con copia imprimible
- SEO: sitemap dinámico, robots.txt, JSON-LD Schema.org, Open Graph editable
- CAPTCHA invisible (Cloudflare Turnstile) en formularios

### Para el equipo de marketing (panel admin)
- **Dashboard** con KPIs comparativos (período actual vs anterior) y gráficos de tendencia
- **Analítica in-house** privacy-first (visitantes únicos, pageviews, productos más vistos, tasa de conversión)
- **CRUD de productos** con SEO editable (meta title/description) y preview Google SERP
- **CRUD de categorías** con orden manual y subcategorías
- **Gestión de pedidos** con cambio de estado (pending → processing → completed → cancelled)
- **Carga masiva de imágenes** con matching difuso por SKU/slug
- **Configuración del sitio** editable (Hito 5):
  - Hero del home (título, subtítulo, CTAs, imagen)
  - Contacto (teléfono, email, dirección, horario, WhatsApp)
  - Redes sociales (Facebook, Instagram, YouTube, TikTok, LinkedIn)
  - SEO global (title template, descripción, keywords, OG image)
- **Importador CSV** de productos (CLI) — `python import_products.py productos.csv`

### Para el equipo de sistemas
- Clean Architecture en backend (domain/application/infrastructure/presentation)
- Clean Architecture en frontend (domain/application/infrastructure)
- JWT auth (HS256, 30 min) con bcrypt + rate limiting + login throttle por email
- Migraciones idempotentes scripted (no requieren Alembic en dev)
- Logs estructurados JSON con request-id y enmascaramiento de PII (ISO 27001 A.8.11)
- Tests automatizados de seguridad (`security_test.py`)
- Importador CSV con dry-run y validación por fila

---

## Stack técnico

### Backend
| Tecnología | Versión | Uso |
|---|---|---|
| FastAPI | 0.115.5 | Framework HTTP |
| SQLAlchemy | 2.0.36 | ORM |
| python-jose | 3.3.0 | JWT |
| passlib + bcrypt | 1.7.4 / 4.2.1 | Hash de contraseñas |
| slowapi | 0.1.9 | Rate limiting |
| bleach | 6.3.0 | Sanitización HTML |
| httpx | 0.28.1 | Cliente HTTP (Turnstile) |
| Pydantic | 2.x | Validación de DTOs |

### Frontend
| Tecnología | Versión | Uso |
|---|---|---|
| Next.js | 16.2.4 (Turbopack) | Framework React |
| React | 19.0 | UI |
| TypeScript | 5.x | Tipado estático |
| Tailwind CSS | 3.4 | Estilos |
| Zustand | 5.x | Estado global (carrito + auth) |
| recharts | 3.x | Gráficos del dashboard |
| lucide-react | 0.468 | Iconografía |

### Base de datos
- **Desarrollo:** SQLite (`backend/induretros.db`)
- **Producción:** PostgreSQL (managed: AWS RDS / Cloud SQL / Supabase)

---

## Estructura del proyecto

```
InduretrosApp/
├── backend/
│   ├── app/
│   │   ├── domain/                    # Entidades + interfaces (sin dependencias externas)
│   │   ├── application/               # Use cases + DTOs
│   │   ├── infrastructure/            # SQLAlchemy + JWT + bcrypt + Turnstile + analytics
│   │   └── presentation/              # FastAPI routers + middleware + DI
│   ├── seed.py                        # Datos demo (categorías + productos)
│   ├── security_test.py               # 27 pruebas de seguridad
│   ├── import_products.py             # Importador CSV de productos
│   ├── bulk_images.py                 # Importador CLI de imágenes
│   ├── create_admin.py                # CLI para crear/promover admins
│   ├── migrate_seo.py                 # Migración: campos meta_title / meta_description
│   ├── migrate_categories.py          # Migración: 13 categorías de Induretros
│   ├── migrate_contact_settings.py    # Migración: contact + redes sociales
│   ├── migrate_hero_settings.py       # Migración: hero del home editable
│   └── requirements.txt
│
├── frontend/
│   └── src/
│       ├── app/
│       │   ├── layout.tsx             # Layout raíz (SSR de settings + chrome condicional)
│       │   ├── page.tsx               # Home (hero dinámico + categorías + featured)
│       │   ├── repuestos/             # Catálogo con Streaming + Suspense
│       │   ├── producto/[slug]/       # Detalle de producto (SSG + ISR + JSON-LD)
│       │   ├── carrito/               # Carrito editable
│       │   ├── checkout/              # Form + creación de pedido
│       │   ├── orden/[id]/            # Confirmación post-checkout
│       │   ├── contacto/              # Formulario de contacto + Turnstile
│       │   ├── privacidad/            # Política de privacidad (Ley 1581 + GDPR)
│       │   ├── login/                 # Login admin
│       │   └── admin/                 # Panel administrativo
│       │       ├── page.tsx           # Dashboard con KPIs + gráficos
│       │       ├── productos/         # CRUD productos
│       │       ├── pedidos/           # Gestión de pedidos
│       │       ├── categorias/        # CRUD categorías
│       │       ├── imagenes/          # Carga masiva
│       │       └── configuracion/     # Site settings (Hero, Contacto, Redes, SEO)
│       ├── domain/                    # Tipos TS (Product, Order, IRepository)
│       ├── application/               # Use cases (frontend)
│       ├── infrastructure/            # HTTP repositories + cache decorator
│       ├── components/
│       │   ├── layout/                # Header / Footer / TopBar / Chrome wrapper
│       │   ├── products/              # ProductCard
│       │   ├── cart/                  # AddToCartButton, CartIcon
│       │   ├── analytics/             # PageViewTracker, ProductViewTracker
│       │   └── admin/                 # Sidebar, ComparisonCard, TimeSeriesChart, GoogleSerpPreview
│       ├── lib/
│       │   ├── siteSettings.ts        # Helper SSR para leer site_settings (cache 60s)
│       │   ├── analytics.ts           # Cliente analytics in-house
│       │   ├── whatsapp.ts            # Builder de URL wa.me con detalle pedido
│       │   ├── imageUrl.ts            # Resuelve /static → backend
│       │   ├── authFetch.ts           # Wrapper fetch con JWT
│       │   ├── cache.ts               # React.cache wrappers
│       │   └── container.ts           # DI container
│       └── stores/                    # Zustand (cartStore, authStore)
│
└── static/
    └── images/                        # Imágenes subidas vía /api/images/upload
```

---

## Cómo ejecutar localmente

### Requisitos previos
- Python 3.13+
- Node.js 20+ (instalado en `C:\Program Files\nodejs\`)
- (Opcional para producción) PostgreSQL 15+

### 1. Backend

```bash
cd backend

python -m venv venv
source venv/Scripts/activate              # Windows Git Bash
# venv\Scripts\activate                   # Windows CMD/PowerShell

pip install -r requirements.txt

cp .env.example .env                      # editar SECRET_KEY mínimo
python seed.py                            # poblar BD demo
python migrate_seo.py                     # añadir campos SEO
python migrate_categories.py              # 13 categorías Induretros
python migrate_contact_settings.py        # contacto + redes
python migrate_hero_settings.py           # hero editable
python create_admin.py admin@induretros.com Admin1234

uvicorn app.main:app --reload --port 8000
```

API en `http://localhost:8000` · Docs Swagger en `/docs` (solo si `SHOW_DOCS=true`)

### 2. Frontend

```bash
cd frontend

npm install
cp .env.example .env.local                # editar URLs si fuera necesario

npm run dev
```

Sitio en `http://localhost:3000` · Admin en `/admin` (login: `admin@induretros.com` / `Admin1234`)

---

## Panel de administración

Accesible en `/admin` (requiere usuario con `is_admin=true`):

| Sección | Funcionalidades |
|---|---|
| **Dashboard** | KPIs comparativos vs período anterior · gráficos diarios de tráfico, pedidos e ingresos · top productos vendidos / vistos · salud SEO del catálogo |
| **Productos** | Tabla con búsqueda y paginación · CRUD completo · meta SEO editable con preview Google · gestión de stock · imágenes |
| **Pedidos** | Filtro por estado · buscador por nombre/email · cambio de status con audit log · vista de detalle con datos del cliente |
| **Categorías** | Lista plana + jerarquía · orden manual · CRUD · eliminación bloqueada si tiene productos |
| **Imágenes** | Drag & drop hasta 50 archivos · matching automático por SKU/slug · galería de imágenes ya subidas |
| **Configuración** | Hero del home · contacto público · redes sociales · SEO global · datos de organización para Schema.org |

---

## Carga de productos e imágenes

### Productos por CSV
```bash
cd backend

# Validar sin escribir
python import_products.py productos.csv --dry-run

# Importar (UPDATE si SKU existe, CREATE si no)
python import_products.py productos.csv
```

Plantilla disponible en `backend/products_template.csv`.

### Imágenes por carpeta local
```bash
python bulk_images.py /ruta/a/imagenes/
```

Las imágenes se enlazan automáticamente:
1. Por SKU: `FLT-001.jpg` → producto con SKU `FLT-001`
2. Por slug producto: `filtro-aceite-pc200.jpg` → producto correspondiente
3. Por slug categoría con matching difuso: `valvulas.jpg` → categoría `valvulas-solenoides-y-electrovalvulas`

### Imágenes por UI
`/admin/imagenes` permite drag & drop de hasta 50 archivos a la vez con reporte por archivo.

---

## Seguridad

### Cumplimiento

| Estándar | Estado | Detalle |
|---|---|---|
| **OWASP Top 10 2021** | 9 PASS · 1 PARTIAL | A10 SSRF requiere infra externa |
| **ISO/IEC 27001:2022** | 11 controles técnicos | A.5.28, A.5.34, A.8.5, A.8.9-11, A.8.15-16, A.8.26-29 |

### Controles activos

- **Auth:** JWT HS256 + bcrypt · rate limiting (slowapi por IP + login throttle por email tras 10 fallos)
- **CAPTCHA:** Cloudflare Turnstile invisible en register/login
- **Headers:** CSP nonce-based · HSTS · X-Frame-Options · X-Content-Type-Options · X-XSS-Protection · Permissions-Policy
- **Validación:** Pydantic en backend · sanitización con bleach en campos de texto libre
- **Subida de archivos:** validación de **magic bytes** (no solo extensión) · path traversal hardening · max 5 MB
- **Logs:** JSON estructurado · `request_id` UUID por petición · email enmascarado (`us***@dominio.com`) · eventos: login_success/failed, access_denied, admin_action, data_accessed, user_deleted, password_changed
- **PII:** confirmación de pedido muestra email enmascarado · borrado de cuenta GDPR (DELETE /api/users/me) con confirmación
- **Páginas privadas:** `/admin/*` redirige a `/login?next=...` si no hay sesión · 403 si no es admin

### Tests
```bash
python security_test.py
```
Cubre 27 escenarios: auth, IDOR, validación, rate limiting, exposición de información.

---

## API REST

### Públicos (sin auth)
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/health` | Sonda de salud (incluye check de BD) |
| `GET` | `/api/products` | Catálogo con filtros |
| `GET` | `/api/products/{slug}` | Detalle de producto |
| `GET` | `/api/products/categories` | Lista de categorías |
| `GET` | `/api/products/categories/{slug}` | Detalle de categoría |
| `POST` | `/api/orders` | Crear pedido (auth opcional) |
| `POST` | `/api/auth/register` | Registro · rate 3/min |
| `POST` | `/api/auth/login` | Login · rate 5/min · throttle por email |
| `POST` | `/api/analytics/event` | Tracking anónimo (pageview, click, etc.) |
| `GET` | `/api/admin/site-settings/public` | Settings públicos (sin datos sensibles) |

### Auth requerida (Bearer JWT)
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/orders/{id}` | Detalle de pedido (solo dueño o admin) |
| `POST` | `/api/users/me/change-password` | Cambiar contraseña |
| `DELETE` | `/api/users/me` | Borrado de cuenta (GDPR) |

### Admin (Bearer JWT con `is_admin=true`)
| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/admin/stats` | KPIs agregados |
| `GET` | `/api/admin/marketing` | Marketing + SEO + tráfico |
| `GET` | `/api/admin/analytics/timeseries?days=N` | Serie diaria |
| `GET` | `/api/admin/analytics/comparison?period_days=N` | Comparativo vs período anterior |
| `GET` | `/api/admin/orders` | Lista todas las órdenes con filtros |
| `PATCH` | `/api/admin/orders/{id}/status` | Cambiar status |
| `GET/PUT` | `/api/admin/site-settings` | Configuración del sitio |
| `POST/PUT/DELETE` | `/api/products` y `/api/products/categories` | CRUD admin |
| `POST` | `/api/images/upload` | Subida masiva (max 50 archivos, 5 MB c/u) |
| `GET` | `/api/images/list` | Listado de imágenes subidas |

---

## Páginas del frontend

| Ruta | Tipo | Descripción |
|---|---|---|
| `/` | SSR | Home con hero editable, stats, categorías y destacados |
| `/repuestos` | Streaming + Suspense | Catálogo con sidebar de categorías |
| `/producto/[slug]` | SSG + ISR (1h) | Detalle con JSON-LD para Google |
| `/carrito` | Client | Carrito editable con persistencia localStorage |
| `/checkout` | Client | Form de checkout + POST /api/orders |
| `/orden/[id]` | Client | Confirmación con CTA de WhatsApp |
| `/contacto` | SSR + Client form | Datos de contacto dinámicos · form con Turnstile |
| `/privacidad` | SSR | Política Ley 1581 / GDPR |
| `/login` | Client | Login admin |
| `/admin/*` | Client (auth-protected) | Panel administrativo |

---

## Roadmap

### Fase 1 — Sitio funcional ✅ Completada
- [x] Catálogo + carrito + checkout
- [x] Pago vía WhatsApp (con detalle pre-llenado)
- [x] Panel admin completo (productos, pedidos, categorías, imágenes)
- [x] SEO editable + analytics + dashboard con histórico
- [x] Auditoría de seguridad (OWASP + ISO 27001)
- [x] Importador CSV de productos
- [x] Configuración editable: contacto + redes (Hito 5.1)
- [x] Hero del home editable (Hito 5.2)

### Fase 2 — Autonomía total para marketing 🔄 En progreso
- [ ] **5.3** Estadísticas del home editables (+9 años, +1200 ref, etc.)
- [ ] **5.4** Banner promocional / barra de anuncios
- [ ] **5.5** CRUD de testimonios
- [ ] **5.6** CRUD de FAQ con sección colapsable
- [ ] **5.7** "Por qué elegirnos" editable
- [ ] **5.8** Política de garantía (rich-text editable)
- [ ] **5.9** Vista previa antes de guardar
- [ ] **5.10** Restaurar / undo (audit log con rollback)

### Fase 3 — Despliegue ⏳ Planeada
- [ ] **6.1** Imágenes en S3 / GCS / Cloudflare R2 (CDN)
- [ ] **6.2** Dockerfile multi-stage + docker-compose
- [ ] **6.3** PostgreSQL managed (RDS / Cloud SQL / Supabase)
- [ ] **6.4** Documentación de despliegue para AWS o GCP

### Fase 4 — E-commerce completo ⏳ Planeada
- [ ] Integración Wompi (pagos online Colombia)
- [ ] Email transaccional (Resend / SendGrid) — confirmación pedido, registro, recovery
- [ ] Recuperación de contraseña
- [ ] Formulario de contacto funcional (envía email)
- [ ] Cuentas de usuario (`/mi-cuenta`)
- [ ] Reseñas / ratings de productos

---

## Ramas del repositorio

| Rama | Propósito |
|---|---|
| `main` | Producción |
| `staging` | Pre-producción |
| `develop` | Integración de desarrollo |
| `feature/web_wordpress_complete` | Sitio WordPress original (referencia visual) |
| **`feature/new-web-fastapi-nextjs`** | **Esta aplicación** |
