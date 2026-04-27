from dataclasses import dataclass
from datetime import datetime
from typing import Optional


@dataclass
class User:
    id: Optional[int]
    email: str
    name: str
    hashed_password: str
    is_active: bool = True
    is_admin: bool = False
    phone: Optional[str] = None
    address: Optional[str] = None
    created_at: Optional[datetime] = None
