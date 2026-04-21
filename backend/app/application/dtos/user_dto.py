from pydantic import BaseModel, EmailStr, Field, field_validator
from typing import Optional
from datetime import datetime


class RegisterUserCommand(BaseModel):
    email: EmailStr
    name: str = Field(..., min_length=2, max_length=100)
    password: str = Field(..., min_length=8, max_length=128)
    phone: Optional[str] = Field(None, max_length=30)
    cf_turnstile_response: Optional[str] = None

    @field_validator("password")
    @classmethod
    def password_strength(cls, v: str) -> str:
        if len(v) < 8:
            raise ValueError("La contraseña debe tener al menos 8 caracteres")
        if not any(c.isdigit() for c in v):
            raise ValueError("La contraseña debe contener al menos un número")
        if not any(c.isalpha() for c in v):
            raise ValueError("La contraseña debe contener al menos una letra")
        return v

    @field_validator("name")
    @classmethod
    def name_not_empty(cls, v: str) -> str:
        if not v.strip():
            raise ValueError("El nombre no puede estar vacío")
        return v.strip()


class LoginCommand(BaseModel):
    email: EmailStr
    password: str
    cf_turnstile_response: Optional[str] = None


class UserDTO(BaseModel):
    id: int
    email: str
    name: str
    phone: Optional[str] = None
    is_admin: bool
    created_at: datetime

    model_config = {"from_attributes": True}


class TokenDTO(BaseModel):
    access_token: str
    token_type: str
