"""
Crea o promueve a admin un usuario.

Uso:
    python create_admin.py admin@induretros.com Pass1234

Si el usuario existe: lo marca como is_admin=True.
Si no existe: lo crea con esa contraseña y is_admin=True.
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import SessionLocal, Base, engine
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.user_model import UserModel
from app.infrastructure.security.password_hasher import BcryptPasswordHasher

Base.metadata.create_all(bind=engine)

if len(sys.argv) < 3:
    print(__doc__)
    sys.exit(0)

email, password = sys.argv[1], sys.argv[2]

db = SessionLocal()
hasher = BcryptPasswordHasher()

user = db.query(UserModel).filter(UserModel.email == email).first()
if user:
    user.is_admin = True
    user.is_active = True
    user.hashed_password = hasher.hash(password)
    print(f"  [OK] Usuario {email} promovido a admin (contraseña actualizada).")
else:
    user = UserModel(
        email=email,
        name="Administrador",
        hashed_password=hasher.hash(password),
        is_admin=True,
        is_active=True,
    )
    db.add(user)
    print(f"  [OK] Admin creado: {email}")

db.commit()
db.close()
