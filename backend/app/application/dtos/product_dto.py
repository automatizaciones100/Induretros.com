from pydantic import BaseModel, Field, field_validator
from typing import Optional
from datetime import datetime
import bleach

# Tags y atributos HTML permitidos en descripciones de productos
_ALLOWED_TAGS = [
    "p", "br", "strong", "em", "ul", "ol", "li",
    "h2", "h3", "h4", "blockquote", "a", "span",
]
_ALLOWED_ATTRS = {"a": ["href", "title"], "span": ["class"]}


class CategoryDTO(BaseModel):
    id: int
    name: str
    slug: str
    description: Optional[str] = None
    image_url: Optional[str] = None
    parent_id: Optional[int] = None
    created_at: datetime
    children: list["CategoryDTO"] = []

    model_config = {"from_attributes": True}


class ProductDTO(BaseModel):
    id: int
    name: str
    slug: str
    description: Optional[str] = None
    short_description: Optional[str] = None
    price: Optional[float] = None
    regular_price: Optional[float] = None
    sale_price: Optional[float] = None
    sku: Optional[str] = None
    stock: int = 0
    in_stock: bool = True
    image_url: Optional[str] = None
    category_id: Optional[int] = None
    featured: bool = False
    created_at: datetime
    category: Optional[CategoryDTO] = None

    model_config = {"from_attributes": True}


class ProductSummaryDTO(BaseModel):
    """DTO ligero para listas — excluye description (hasta 50k chars) para reducir payload."""
    id: int
    name: str
    slug: str
    short_description: Optional[str] = None
    price: Optional[float] = None
    regular_price: Optional[float] = None
    sale_price: Optional[float] = None
    sku: Optional[str] = None
    stock: int = 0
    in_stock: bool = True
    image_url: Optional[str] = None
    category_id: Optional[int] = None
    featured: bool = False
    created_at: datetime
    category: Optional[CategoryDTO] = None

    model_config = {"from_attributes": True}


class ProductListDTO(BaseModel):
    items: list[ProductSummaryDTO]
    total: int
    page: int
    pages: int


class CreateProductCommand(BaseModel):
    name: str = Field(..., min_length=2, max_length=200)
    slug: str = Field(..., min_length=2, max_length=200, pattern=r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
    description: Optional[str] = Field(None, max_length=50_000)
    short_description: Optional[str] = Field(None, max_length=500)
    price: Optional[float] = Field(None, ge=0)
    regular_price: Optional[float] = Field(None, ge=0)
    sale_price: Optional[float] = Field(None, ge=0)
    sku: Optional[str] = Field(None, max_length=100)
    stock: int = Field(0, ge=0)
    in_stock: bool = True
    image_url: Optional[str] = Field(None, max_length=500)
    category_id: Optional[int] = None
    featured: bool = False

    @field_validator("description")
    @classmethod
    def sanitize_description(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        return bleach.clean(v, tags=_ALLOWED_TAGS, attributes=_ALLOWED_ATTRS, strip=True)

    @field_validator("image_url")
    @classmethod
    def validate_image_url(cls, v: Optional[str]) -> Optional[str]:
        if v is None or v == "":
            return v
        if not (v.startswith("https://") or v.startswith("http://") or v.startswith("/")):
            raise ValueError("image_url debe ser http/https o ruta relativa (/static/...)")
        return v


class UpdateProductCommand(BaseModel):
    """Todos los campos opcionales — solo se actualizan los que vengan."""
    name: Optional[str] = Field(None, min_length=2, max_length=200)
    slug: Optional[str] = Field(None, min_length=2, max_length=200, pattern=r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
    description: Optional[str] = Field(None, max_length=50_000)
    short_description: Optional[str] = Field(None, max_length=500)
    price: Optional[float] = Field(None, ge=0)
    regular_price: Optional[float] = Field(None, ge=0)
    sale_price: Optional[float] = Field(None, ge=0)
    sku: Optional[str] = Field(None, max_length=100)
    stock: Optional[int] = Field(None, ge=0)
    in_stock: Optional[bool] = None
    image_url: Optional[str] = Field(None, max_length=500)
    category_id: Optional[int] = None
    featured: Optional[bool] = None

    @field_validator("description")
    @classmethod
    def sanitize_description(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        return bleach.clean(v, tags=_ALLOWED_TAGS, attributes=_ALLOWED_ATTRS, strip=True)

    @field_validator("image_url")
    @classmethod
    def validate_image_url(cls, v: Optional[str]) -> Optional[str]:
        if v is None or v == "":
            return v
        if not (v.startswith("https://") or v.startswith("http://") or v.startswith("/")):
            raise ValueError("image_url debe ser http/https o ruta relativa")
        return v


class CreateCategoryCommand(BaseModel):
    name: str = Field(..., min_length=2, max_length=100)
    slug: str = Field(..., min_length=2, max_length=100, pattern=r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
    description: Optional[str] = Field(None, max_length=1000)
    image_url: Optional[str] = Field(None, max_length=500)
    parent_id: Optional[int] = None

    @field_validator("image_url")
    @classmethod
    def validate_image_url(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        if not (v.startswith("https://") or v.startswith("http://")):
            raise ValueError("image_url debe ser una URL válida (http/https)")
        return v


class GetProductsQuery(BaseModel):
    page: int = 1
    per_page: int = 12
    category: Optional[str] = None
    search: Optional[str] = None
    featured: Optional[bool] = None
