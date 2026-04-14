# Induretros.com — Sitio WordPress de Referencia

> **Rama:** `feature/web_wordpress_complete`  
> **Propósito:** Código fuente completo del sitio WordPress original de [induretros.com](https://induretros.com), usado como referencia para construir el nuevo aplicativo desde cero con FastAPI + Next.js.

---

## Descripción del Sitio

**Induretros** es una tienda de comercio electrónico especializada en la venta de repuestos para excavadoras hidráulicas. El sitio está construido sobre WordPress + WooCommerce y fue alojado en Cloudways.

| Propiedad | Valor |
|---|---|
| Dominio | induretros.com |
| Plataforma | WordPress 5.8+ |
| E-commerce | WooCommerce |
| Tema | Kadence |
| Hosting original | Cloudways |
| Imágenes de productos | ~6.038 archivos |

---

## Estructura del Repositorio

```
feature/web_wordpress_complete/
│
├── wp-admin/                  # Panel de administración de WordPress (core)
├── wp-includes/               # Núcleo de WordPress (core)
│
├── wp-content/
│   ├── plugins/               # Plugins instalados (ver lista abajo)
│   ├── themes/
│   │   └── kadence/           # Tema principal con personalizaciones
│   └── languages/             # Traducciones
│
├── wp-config-sample.php       # Plantilla de configuración (sin credenciales)
├── .htaccess                  # Reglas de Apache
├── robots.txt                 # Directivas para motores de búsqueda
├── llms.txt                   # Directivas para crawlers de IA
├── .gitignore                 # Exclusiones del repositorio
└── README.md                  # Este archivo
```

> **Nota:** `wp-config.php` está excluido del repositorio por contener credenciales de base de datos. Usar `wp-config-sample.php` como plantilla.

---

## Archivos Excluidos (no están en el repo)

| Ruta | Razón |
|---|---|
| `wp-config.php` | Contiene credenciales de base de datos |
| `wp-content/uploads/` | 633 MB de imágenes de productos |
| `wp-content/updraft/` | 3.6 GB de backups de UpdraftPlus |
| `induretros.zip` | Copia comprimida completa del sitio (79 MB) |
| `Cw Bup/` | Backup manual del hosting |

---

## Plugins Instalados (34)

### E-commerce
| Plugin | Función |
|---|---|
| `woocommerce` | Motor principal de la tienda |
| `woocommerce-paypal-payments` | Pagos con PayPal |
| `woocommerce-google-analytics-integration` | Analytics integrado |
| `yith-woocommerce-ajax-navigation` | Filtros de productos por Ajax |
| `yith-woocommerce-wishlist` | Lista de deseos |
| `kadence-woo-extras` | Extras WooCommerce para el tema |

### SEO y Marketing
| Plugin | Función |
|---|---|
| `seo-by-rank-math` | SEO principal + Sitemap |
| `seo-by-rank-math-pro` | Funciones avanzadas de SEO |
| `google-listings-and-ads` | Google Shopping / Ads |
| `metricool` | Analítica y redes sociales |
| `redirection` | Gestión de redirecciones 301 |

### Rendimiento y Caché
| Plugin | Función |
|---|---|
| `wp-rocket` | Caché y optimización de velocidad |
| `breeze` | Caché alternativo (Cloudways) |
| `redis-cache` | Caché de objetos con Redis |
| `wp-asset-clean-up` | Optimización de CSS/JS por página |

### Seguridad
| Plugin | Función |
|---|---|
| `wordfence` | Firewall y antivirus |
| `protect-uploads` | Protección del directorio uploads |
| `advanced-google-recaptcha` | Protección anti-bots en formularios |
| `complianz-gdpr-premium` | Cumplimiento GDPR / cookies |

### Constructor y Diseño
| Plugin | Función |
|---|---|
| `kadence-blocks` | Bloques de Gutenberg avanzados |
| `kadence-blocks-pro` | Bloques premium |
| `kadence-pro` | Funciones avanzadas del tema |
| `custom-css-js` | CSS y JS personalizado |
| `classic-widgets` | Widgets clásicos de WordPress |
| `wpb-accordion-menu-or-category` | Menú acordeón de categorías |

### Formularios y Comunicación
| Plugin | Función |
|---|---|
| `ninja-forms` | Formularios de contacto |
| `wp-mail-smtp` | Configuración de correo SMTP |
| `creame-whatsapp-me` | Botón flotante de WhatsApp |
| `call-now-button` | Botón de llamada rápida |

### Administración y Utilidades
| Plugin | Función |
|---|---|
| `updraftplus` | Backups automáticos |
| `simple-history` | Registro de actividad del admin |
| `duplicate-page` | Duplicar páginas/entradas |
| `code-snippets` | Fragmentos de código sin editar functions.php |
| `seoplugins` | Herramientas SEO adicionales |

---

## Tema: Kadence

El tema principal es **Kadence v1.4.3**, un tema de WordPress moderno, ligero y altamente personalizable. Toda la personalización visual del sitio (colores, tipografía, layout) está gestionada desde el Customizer de WordPress y los Kadence Blocks.

```
wp-content/themes/kadence/
├── header.php / footer.php    # Estructura base de páginas
├── functions.php              # Funciones y hooks del tema
├── inc/                       # Clases y utilidades del tema
├── assets/                    # CSS, JS e imágenes del tema
├── template-parts/            # Plantillas reutilizables
└── theme.json                 # Configuración de bloques Gutenberg
```

---

## Configuración SEO

El sitio tiene SEO avanzado configurado con **Rank Math Pro**:

- Sitemap XML: `https://induretros.com/sitemap.xml`
- Páginas paginadas: `noindex, follow`
- Búsquedas internas: `noindex, follow`
- Filtros de productos (marca, precio): `noindex, follow`

**robots.txt** bloquea: `/carrito/`, `/checkout/`, `/mi-cuenta/`, `/wp-admin/`

**llms.txt** permite a crawlers de IA indexar: `/repuestos/`, `/excavadoras-hidraulicas/`, `/blog/`, `/contacto/`

---

## Contexto: Migración en Progreso

Este repositorio es el punto de partida de una migración completa del sitio. El objetivo es **reconstruir induretros.com desde cero** con un stack moderno:

| Capa | Tecnología actual | Tecnología nueva |
|---|---|---|
| Backend | PHP / WordPress | Python / FastAPI |
| Frontend | PHP / Kadence Theme | JavaScript / Next.js |
| Base de datos | MySQL / WordPress DB | PostgreSQL |
| E-commerce | WooCommerce | Desarrollo a medida |

> El código de este WordPress sirve como referencia visual y funcional para el nuevo desarrollo.

---

## Ramas del Repositorio

| Rama | Propósito |
|---|---|
| `main` | Producción |
| `staging` | Pruebas pre-producción |
| `develop` | Integración de desarrollo |
| `feature/web_wordpress_complete` | **Esta rama** — Sitio WordPress original como referencia |
| `content/marketing` | Contenido y marketing |
