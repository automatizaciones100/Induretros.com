from sqlalchemy import Column, Integer, String, Float, Text, Boolean, ForeignKey, DateTime
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from app.database import Base


class CategoryModel(Base):
    __tablename__ = "categories"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), nullable=False)
    slug = Column(String(255), unique=True, nullable=False, index=True)
    description = Column(Text, nullable=True)
    image_url = Column(String(500), nullable=True)
    parent_id = Column(Integer, ForeignKey("categories.id"), nullable=True)
    # Orden manual de visualización (menor número = aparece primero)
    display_order = Column(Integer, default=0, nullable=False, index=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    parent = relationship("CategoryModel", remote_side=[id], back_populates="children")
    children = relationship("CategoryModel", back_populates="parent")
    products = relationship("ProductModel", back_populates="category")


class ProductModel(Base):
    __tablename__ = "products"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(500), nullable=False)
    slug = Column(String(500), unique=True, nullable=False, index=True)
    description = Column(Text, nullable=True)
    short_description = Column(Text, nullable=True)
    price = Column(Float, nullable=True)
    regular_price = Column(Float, nullable=True)
    sale_price = Column(Float, nullable=True)
    sku = Column(String(255), nullable=True, index=True)
    stock = Column(Integer, default=0)
    in_stock = Column(Boolean, default=True)
    image_url = Column(String(500), nullable=True)
    category_id = Column(Integer, ForeignKey("categories.id"), nullable=True, index=True)
    featured = Column(Boolean, default=False)
    # SEO — si meta_title/meta_description están vacíos, se auto-generan desde name/short_description
    meta_title = Column(String(70), nullable=True)
    meta_description = Column(String(200), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    category = relationship("CategoryModel", back_populates="products")
    order_items = relationship("OrderItemModel", back_populates="product")
