# Induretros — Nueva Aplicación Web

> **Rama:** `feature/new-web-fastapi-nextjs`
> **Stack:** FastAPI (Python) + Next.js 15 (TypeScript) + PostgreSQL
> **Referencia visual:** [induretros.com](https://induretros.com) (ver rama `feature/web_wordpress_complete`)

Reconstrucción completa del sitio **induretros.com** desde cero, reemplazando WordPress + WooCommerce por un stack moderno de alto rendimiento.

---

## Diseño Visual

El frontend replica fielmente el diseño original del sitio WordPress:

| Elemento | Valor |
|---|---|
| Color primario | `#f08215` (naranja) |
| Color secundario | `#fecc00` (amarillo) |
| Texto principal | `#1A202C` |
| Fuente cuerpo | **Poppins** (300, 400, 500, 600, 700) |
| Fuente títulos | **Oswald** (300, 400, 500, 600) |
| Fondo general | `#F7FAFC` |

---

## Estructura del Proyecto

```
/
├── backend/                   # API REST con FastAPI (Python)
│   ├── app/
│   │   ├── main.py            # Punto de entrada + CORS + rutas
│   │   ├── config.py          # Variables de entorno (Pydantic Settings)
│   │   ├── database.py        # Conexión SQLAlchemy + sesión
│   │   ├── models/
│   │   │   ├── product.py     # Modelos Product y Category
│   │   │   ├── user.py        # Modelo User
│   │   │   └── order.py       # Modelos Order y OrderItem
│   │   ├── schemas/
│   │   │   ├── product.py     # Schemas Pydantic de productos
│   │   │   ├── user.py        # Schemas de usuario y autenticación
│   │   │   └── order.py       # Schemas de pedidos
│   │   └── routers/
│   │       ├── products.py    # CRUD productos y categorías
│   │       ├── auth.py        # Registro, login, JWT
│   │       └── orders.py      # Creación y consulta de pedidos
│   ├── requirements.txt
│   └── .env.example
│
└── frontend/                  # Next.js 15 + TypeScript + Tailwind
    ├── src/
    │   ├── app/
    │   │   ├── layout.tsx     # Layout principal (Header + Footer)
    │   │   ├── globals.css    # Estilos globales + variables de color
    │   │   ├── page.tsx       # Homepage (Hero + Stats + Categorías + Destacados)
    │   │   ├── repuestos/     # Catálogo con filtros por categoría
    │   │   ├── producto/[slug]/ # Detalle de producto
    │   │   └── contacto/      # Formulario de contacto
    │   ├── components/
    │   │   ├── layout/
    │   │   │   ├── Header.tsx    # Logo + búsqueda + carrito
    │   │   │   ├── TopBar.tsx    # Barra superior (teléfono + redes)
    │   │   │   ├── Navbar.tsx    # Menú con mega-dropdown de categorías
    │   │   │   └── Footer.tsx    # Footer con columnas + contacto
    │   │   ├── products/
    │   │   │   └── ProductCard.tsx # Tarjeta de producto con hover actions
    │   │   └── ui/
    │   │       └── WhatsAppButton.tsx # Botón flotante de WhatsApp
    │   └── lib/
    │       └── api.ts          # Cliente HTTP hacia el backend FastAPI
    ├── package.json
    ├── tailwind.config.ts      # Paleta de colores del sitio original
    ├── next.config.ts
    └── .env.example
```

---

## Endpoints de la API

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/api/products` | Listar productos (paginación, filtros) |
| `GET` | `/api/products/{slug}` | Detalle de un producto |
| `POST` | `/api/products` | Crear producto |
| `GET` | `/api/products/categories` | Listar categorías |
| `GET` | `/api/products/categories/{slug}` | Detalle de categoría |
| `POST` | `/api/auth/register` | Registro de usuario |
| `POST` | `/api/auth/login` | Login (retorna JWT) |
| `POST` | `/api/orders` | Crear pedido |
| `GET` | `/api/orders/{id}` | Consultar pedido |
| `GET` | `/health` | Estado del servidor |

---

## Páginas del Frontend

| Ruta | Componente | Descripción |
|---|---|---|
| `/` | `page.tsx` | Homepage: hero, estadísticas, categorías, productos destacados |
| `/repuestos` | `repuestos/page.tsx` | Catálogo con filtro lateral por categoría y paginación |
| `/producto/[slug]` | `producto/[slug]/page.tsx` | Detalle del producto, precio, stock, agregar al carrito |
| `/contacto` | `contacto/page.tsx` | Formulario de contacto + información de la empresa |

---

## Requisitos Previos

- **Python 3.13+** ✅ (instalado)
- **Node.js 20+** ⚠️ (requiere instalación — ver abajo)
- **PostgreSQL 15+**

---

## Instalación y Ejecución

### 1. Instalar Node.js

Descargar desde [nodejs.org](https://nodejs.org) (versión LTS recomendada).

### 2. Backend (FastAPI)

```bash
cd backend

# Crear entorno virtual
python -m venv venv
venv\Scripts\activate      # Windows

# Instalar dependencias
pip install -r requirements.txt

# Configurar variables de entorno
copy .env.example .env
# Editar .env con los datos de tu base de datos PostgreSQL

# Ejecutar
uvicorn app.main:app --reload --port 8000
```

API disponible en: `http://localhost:8000`
Documentación automática en: `http://localhost:8000/docs`

### 3. Frontend (Next.js)

```bash
cd frontend

# Instalar dependencias
npm install

# Configurar variables de entorno
copy .env.example .env.local

# Ejecutar en desarrollo
npm run dev
```

Sitio disponible en: `http://localhost:3000`

---

## Ramas del Repositorio

| Rama | Propósito |
|---|---|
| `main` | Producción |
| `staging` | Pruebas pre-producción |
| `develop` | Integración de desarrollo |
| `feature/web_wordpress_complete` | Sitio WordPress original como referencia visual |
| `feature/new-web-fastapi-nextjs` | **Esta rama** — Nueva aplicación Python + JavaScript |
