import requests
import pytest

BASE_URL = "http://localhost/Interface_operator_cinema/"

def test_check_connection():
    resp = requests.get(BASE_URL, params={"action": "check_connection"})
    assert resp.status_code == 200
    data = resp.json()
    assert data["success"] is True
    assert "version" in data

def test_get_current_db():
    resp = requests.get(BASE_URL, params={"action": "get_current_db"})
    assert resp.status_code == 200
    data = resp.json()
    assert data["db"] == "project_Kozlov"

def test_admin_login_api():
    resp = requests.post(
        BASE_URL + "auth.php",
        data={"action": "login", "password": "admin123"},
        headers={"Accept": "application/json"}
    )
    assert resp.status_code == 200
    assert resp.json()["success"] is True
    # Проверяем, что сессия установлена
    assert "PHPSESSID" in resp.cookies

def test_invalid_login():
    resp = requests.post(BASE_URL + "auth.php", data={"action": "login", "password": "wrong"})
    assert resp.status_code == 401
    assert resp.json()["success"] is False