from typing import Optional
from sqlalchemy.orm import Session
from app.domain.entities.user import User
from app.domain.repositories.user_repository import IUserRepository
from app.infrastructure.database.models.user_model import UserModel


def _user_model_to_entity(model: UserModel) -> User:
    return User(
        id=model.id,
        email=model.email,
        name=model.name,
        hashed_password=model.hashed_password,
        is_active=model.is_active,
        is_admin=model.is_admin,
        phone=model.phone,
        address=model.address,
        created_at=model.created_at,
    )


class SQLAlchemyUserRepository(IUserRepository):
    def __init__(self, db: Session):
        self._db = db

    def get_by_email(self, email: str) -> Optional[User]:
        model = self._db.query(UserModel).filter(UserModel.email == email).first()
        return _user_model_to_entity(model) if model else None

    def get_by_id(self, user_id: int) -> Optional[User]:
        model = self._db.query(UserModel).filter(UserModel.id == user_id).first()
        return _user_model_to_entity(model) if model else None

    def create(self, user: User) -> User:
        model = UserModel(
            email=user.email,
            name=user.name,
            hashed_password=user.hashed_password,
            phone=user.phone,
            is_active=user.is_active,
            is_admin=user.is_admin,
        )
        self._db.add(model)
        self._db.commit()
        self._db.refresh(model)
        return _user_model_to_entity(model)

    def update_password(self, user_id: int, hashed_password: str) -> None:
        self._db.query(UserModel).filter(UserModel.id == user_id).update(
            {"hashed_password": hashed_password}
        )
        self._db.commit()

    def delete(self, user_id: int) -> None:
        """
        Elimina la cuenta del usuario.
        Las órdenes vinculadas quedan con user_id=NULL (FK nullable) para
        preservar el historial contable pero desvinculadas del titular.
        """
        self._db.query(UserModel).filter(UserModel.id == user_id).delete()
        self._db.commit()
