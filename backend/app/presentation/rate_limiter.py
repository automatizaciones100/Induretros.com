"""
Instancia única del rate limiter compartida por todos los routers.
Se registra en app.state en main.py para que slowapi procese los 429.
"""
from slowapi import Limiter
from slowapi.util import get_remote_address

limiter = Limiter(key_func=get_remote_address)
