class DomainException(Exception):
    pass


class EntityNotFoundError(DomainException):
    def __init__(self, entity: str, identifier: str):
        super().__init__(f"{entity} '{identifier}' no encontrado")
        self.entity = entity
        self.identifier = identifier


class OutOfStockError(DomainException):
    def __init__(self, product_name: str):
        super().__init__(f"Producto '{product_name}' sin stock")
        self.product_name = product_name


class DuplicateEmailError(DomainException):
    def __init__(self, email: str):
        super().__init__(f"El correo '{email}' ya está registrado")
        self.email = email


class InvalidCredentialsError(DomainException):
    def __init__(self):
        super().__init__("Correo o contraseña incorrectos")
