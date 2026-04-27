from dataclasses import dataclass, field
from datetime import datetime
from typing import Optional


@dataclass
class Category:
    id: Optional[int]
    name: str
    slug: str
    description: Optional[str] = None
    image_url: Optional[str] = None
    parent_id: Optional[int] = None
    created_at: Optional[datetime] = None
    children: list["Category"] = field(default_factory=list)


@dataclass
class Product:
    id: Optional[int]
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
    meta_title: Optional[str] = None
    meta_description: Optional[str] = None
    created_at: Optional[datetime] = None
    updated_at: Optional[datetime] = None
    category: Optional[Category] = None
