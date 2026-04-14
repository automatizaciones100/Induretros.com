from pydantic import BaseModel
from typing import Optional
from datetime import datetime


class CategoryBase(BaseModel):
    name: str
    slug: str
    description: Optional[str] = None
    image_url: Optional[str] = None
    parent_id: Optional[int] = None


class CategoryCreate(CategoryBase):
    pass


class CategoryOut(CategoryBase):
    id: int
    created_at: datetime
    children: list["CategoryOut"] = []

    class Config:
        from_attributes = True


class ProductBase(BaseModel):
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


class ProductCreate(ProductBase):
    pass


class ProductOut(ProductBase):
    id: int
    category: Optional[CategoryOut] = None
    created_at: datetime

    class Config:
        from_attributes = True


class ProductListOut(BaseModel):
    items: list[ProductOut]
    total: int
    page: int
    pages: int
